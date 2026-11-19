<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class WalletService
{
    public function __construct(
        protected CashfreePayoutService $payoutService,
        protected CashfreePaymentService $paymentService
    ) {}

    /**
     * Compile dashboard summary statistics for user.
     *
     * @param  \App\Models\User  $user
     * @return array<string, mixed>
     */
    public function getDashboardSummary(User $user): array
    {
        $wallet = $user->wallet()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'reserved_balance' => 0]
        );

        $totalAddedPaise = (int) $user->walletTransactions()
            ->where('type', 'credit')
            ->where('status', 'completed')
            ->sum('amount');

        $totalWithdrawnPaise = (int) $user->withdrawals()
            ->where('status', 'completed')
            ->sum('amount');

        $verifiedAccountsCount = $user->bankAccounts()
            ->where('is_verified', true)
            ->count();

        $recentTransactions = $user->walletTransactions()
            ->latest()
            ->take(10)
            ->get();

        $recentWithdrawals = $user->withdrawals()
            ->with('bankAccount')
            ->latest()
            ->take(5)
            ->get();

        return [
            'wallet' => $wallet,
            'total_added_paise' => $totalAddedPaise,
            'total_added_formatted' => Wallet::formatPaise($totalAddedPaise),
            'total_withdrawn_paise' => $totalWithdrawnPaise,
            'total_withdrawn_formatted' => Wallet::formatPaise($totalWithdrawnPaise),
            'verified_accounts_count' => $verifiedAccountsCount,
            'recent_transactions' => $recentTransactions,
            'recent_withdrawals' => $recentWithdrawals,
        ];
    }

    /**
     * Record a pending deposit transaction.
     */
    public function createPendingDeposit(
        User $user,
        int $amountPaise,
        string $orderId,
        ?string $sessionId = null
    ): WalletTransaction {
        $wallet = $user->wallet()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'reserved_balance' => 0]
        );

        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => $amountPaise,
            'reference_id' => $orderId,
            'payment_session_id' => $sessionId,
            'transaction_type' => 'deposit',
            'status' => 'pending',
            'description' => 'Deposit via Cashfree Payment Gateway (' . Wallet::formatPaise($amountPaise) . ')',
            'metadata' => [
                'order_id' => $orderId,
                'payment_session_id' => $sessionId,
            ],
        ]);
    }

    /**
     * Idempotently credit a deposit to user's wallet.
     * Guaranteed strictly once inside DB transaction with row locking.
     */
    public function creditDeposit(
        string $orderId,
        int $amountPaise,
        string $gatewayPaymentId,
        array $metadata = []
    ): WalletTransaction {
        return DB::transaction(function () use ($orderId, $amountPaise, $gatewayPaymentId, $metadata) {
            $transaction = WalletTransaction::where('reference_id', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                throw new RuntimeException("Transaction not found for order ID: {$orderId}");
            }

            // Strictly idempotent: If already completed, do NOT credit again
            if ($transaction->status === 'completed') {
                Log::info("Idempotent credit skipped: Order {$orderId} is already completed.");
                return $transaction;
            }

            // Verify amount matching to prevent tampering
            if ($transaction->amount !== $amountPaise) {
                Log::warning("Deposit amount mismatch for order {$orderId}", [
                    'expected' => $transaction->amount,
                    'received' => $amountPaise,
                ]);
            }

            $wallet = Wallet::where('id', $transaction->wallet_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Credit wallet available balance
            $wallet->balance += $transaction->amount;
            $wallet->save();

            // Mark transaction as completed
            $currentMeta = $transaction->metadata ?? [];
            $transaction->status = 'completed';
            $transaction->gateway_reference_id = $gatewayPaymentId;
            $transaction->metadata = array_merge($currentMeta, $metadata, [
                'credited_at' => now()->toIso8601String(),
                'gateway_payment_id' => $gatewayPaymentId,
            ]);
            $transaction->save();

            Log::info("Successfully credited deposit for order {$orderId}", [
                'user_id' => $transaction->user_id,
                'amount_paise' => $transaction->amount,
                'new_balance' => $wallet->balance,
            ]);

            return $transaction;
        });
    }

    /**
     * Atomically reserve funds and initiate payout transfer with Cashfree.
     */
    public function reserveAndInitiateWithdrawal(
        User $user,
        BankAccount $bankAccount,
        int $amountPaise,
        string $idempotencyKey
    ): Withdrawal {
        if (!$bankAccount->is_verified) {
            throw new RuntimeException('Cannot withdraw to an unverified bank account.');
        }

        if ($bankAccount->user_id !== $user->id) {
            throw new RuntimeException('Unauthorized bank account selection.');
        }

        // 1. Database Atomic Step: Lock wallet, verify balance, reserve funds, create pending withdrawal
        $withdrawal = DB::transaction(function () use ($user, $bankAccount, $amountPaise, $idempotencyKey) {
            // Check for existing withdrawal with this idempotency key
            $existing = Withdrawal::where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $wallet = Wallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->balance < $amountPaise) {
                throw new RuntimeException('Insufficient available wallet balance for withdrawal.');
            }

            // Atomic balance transfer: move from balance to reserved_balance
            $wallet->balance -= $amountPaise;
            $wallet->reserved_balance += $amountPaise;
            $wallet->save();

            $transferId = 'tr_' . Str::lower(Str::random(16));

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $amountPaise,
                'reference_id' => $transferId,
                'transaction_type' => 'withdrawal',
                'status' => 'pending',
                'description' => 'Withdrawal to ' . $bankAccount->masked_account_number . ' (' . $bankAccount->ifsc_code . ')',
                'metadata' => [
                    'transfer_id' => $transferId,
                    'bank_account_id' => $bankAccount->id,
                    'idempotency_key' => $idempotencyKey,
                ],
            ]);

            return Withdrawal::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'bank_account_id' => $bankAccount->id,
                'wallet_transaction_id' => $transaction->id,
                'amount' => $amountPaise,
                'transfer_id' => $transferId,
                'idempotency_key' => $idempotencyKey,
                'status' => 'pending',
            ]);
        });

        // If this was an existing withdrawal returned from idempotency check, return it directly
        if ($withdrawal->status !== 'pending') {
            return $withdrawal;
        }

        // 2. Gateway Step: Register Beneficiary & Initiate Cashfree Transfer
        try {
            $beneficiaryId = $this->payoutService->ensureBeneficiary($bankAccount, $user);

            $response = $this->payoutService->createTransfer(
                $withdrawal->transfer_id,
                $amountPaise,
                $beneficiaryId,
                'Wallet withdrawal for ' . $user->name
            );

            $cfStatus = strtoupper($response['status'] ?? 'PENDING');
            $utr = $response['utr'] ?? null;
            $refId = (string) ($response['reference_id'] ?? $response['transfer_id'] ?? '');

            if ($cfStatus === 'SUCCESS') {
                return $this->finalizeWithdrawalSuccess($withdrawal, $utr, $refId);
            } elseif (in_array($cfStatus, ['FAILED', 'REJECTED'])) {
                $reason = $response['status_description'] ?? $response['failure_reason'] ?? 'Transfer rejected by Cashfree';
                return $this->handleWithdrawalFailure($withdrawal, $reason);
            } else {
                // 'RECEIVED', 'PENDING', or intermediate status
                $withdrawal->update([
                    'status' => 'processing',
                    'cashfree_reference_id' => $refId,
                ]);
                return $withdrawal->fresh();
            }
        } catch (Throwable $e) {
            Log::error('Payout initiation error for transfer ' . $withdrawal->transfer_id, [
                'error' => $e->getMessage(),
            ]);

            // Keep in processing state so funds remain reserved while awaiting webhook or manual sync
            $withdrawal->update(['status' => 'processing']);
            return $withdrawal->fresh();
        }
    }

    /**
     * Finalize successful withdrawal: release from reserved balance and mark completed.
     */
    public function finalizeWithdrawalSuccess(
        Withdrawal $withdrawal,
        ?string $utr = null,
        ?string $refId = null
    ): Withdrawal {
        return DB::transaction(function () use ($withdrawal, $utr, $refId) {
            $w = Withdrawal::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($w->status === 'completed') {
                return $w;
            }

            $wallet = Wallet::where('id', $w->wallet_id)->lockForUpdate()->firstOrFail();

            // Clear reserved balance
            $wallet->reserved_balance = max(0, $wallet->reserved_balance - $w->amount);
            $wallet->save();

            $w->status = 'completed';
            $w->utr = $utr ?: $w->utr;
            $w->cashfree_reference_id = $refId ?: $w->cashfree_reference_id;
            $w->processed_at = now();
            $w->save();

            if ($w->wallet_transaction_id) {
                WalletTransaction::where('id', $w->wallet_transaction_id)->update([
                    'status' => 'completed',
                    'gateway_reference_id' => $utr ?: $refId,
                ]);
            }

            Log::info("Withdrawal completed: transfer {$w->transfer_id}", [
                'utr' => $utr,
                'amount' => $w->amount,
            ]);

            return $w;
        });
    }

    /**
     * Handle failed/rejected withdrawal: refund reserved funds back to available balance.
     */
    public function handleWithdrawalFailure(Withdrawal $withdrawal, string $reason): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $reason) {
            $w = Withdrawal::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();

            // If already finalized or failed, do not repeat
            if (in_array($w->status, ['failed', 'completed'])) {
                return $w;
            }

            $wallet = Wallet::where('id', $w->wallet_id)->lockForUpdate()->firstOrFail();

            // Release reserved funds back to available balance
            $wallet->reserved_balance = max(0, $wallet->reserved_balance - $w->amount);
            $wallet->balance += $w->amount;
            $wallet->save();

            $w->status = 'failed';
            $w->failure_reason = $reason;
            $w->processed_at = now();
            $w->save();

            if ($w->wallet_transaction_id) {
                WalletTransaction::where('id', $w->wallet_transaction_id)->update([
                    'status' => 'failed',
                    'description' => 'Withdrawal failed: ' . $reason,
                ]);
            }

            Log::warning("Withdrawal failed and funds returned to balance: transfer {$w->transfer_id}", [
                'reason' => $reason,
                'refunded_paise' => $w->amount,
            ]);

            return $w;
        });
    }

    /**
     * Handle post-completion transfer reversal: credit funds back to available balance.
     */
    public function handleWithdrawalReversal(Withdrawal $withdrawal, string $reason): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $reason) {
            $w = Withdrawal::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($w->status === 'reversed') {
                return $w;
            }

            $wallet = Wallet::where('id', $w->wallet_id)->lockForUpdate()->firstOrFail();

            // Credit amount back to available balance
            $wallet->balance += $w->amount;
            $wallet->save();

            $w->status = 'reversed';
            $w->failure_reason = $reason;
            $w->save();

            // Create ledger entry for the reversal
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $w->user_id,
                'type' => 'credit',
                'amount' => $w->amount,
                'reference_id' => 'rev_' . Str::lower(Str::random(16)),
                'gateway_reference_id' => $w->transfer_id,
                'transaction_type' => 'withdrawal_reversal',
                'status' => 'completed',
                'description' => 'Reversal of withdrawal ' . $w->transfer_id . ' (' . $reason . ')',
                'metadata' => [
                    'original_transfer_id' => $w->transfer_id,
                    'reversal_reason' => $reason,
                ],
            ]);

            Log::info("Withdrawal reversed and credited to balance: transfer {$w->transfer_id}", [
                'reason' => $reason,
                'credited_paise' => $w->amount,
            ]);

            return $w;
        });
    }

    /**
     * Synchronize withdrawal status against Cashfree Payouts API.
     */
    public function syncWithdrawalStatus(Withdrawal $withdrawal): Withdrawal
    {
        try {
            $data = $this->payoutService->getTransferStatus($withdrawal->transfer_id);

            // Response can be a single transfer object or wrapper with 'data'
            $transfer = $data['data'] ?? $data;
            $cfStatus = strtoupper($transfer['status'] ?? '');
            $utr = $transfer['utr'] ?? null;
            $refId = (string) ($transfer['reference_id'] ?? $transfer['transfer_id'] ?? '');

            return match ($cfStatus) {
                'SUCCESS' => $this->finalizeWithdrawalSuccess($withdrawal, $utr, $refId),
                'FAILED', 'REJECTED' => $this->handleWithdrawalFailure(
                    $withdrawal,
                    $transfer['status_description'] ?? $transfer['failure_reason'] ?? 'Transfer failed'
                ),
                'REVERSED' => $this->handleWithdrawalReversal(
                    $withdrawal,
                    $transfer['status_description'] ?? 'Transfer reversed by bank'
                ),
                'RECEIVED', 'PENDING' => tap($withdrawal, fn ($w) => $w->update(['status' => 'processing'])),
                default => $withdrawal,
            };
        } catch (Throwable $e) {
            Log::error('Failed to sync withdrawal ' . $withdrawal->transfer_id, [
                'error' => $e->getMessage(),
            ]);
            return $withdrawal;
        }
    }
}
