<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\StoreWithdrawalRequest;
use App\Models\BankAccount;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class WalletWithdrawalController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Withdraw funds page.
     */
    public function create(): View
    {
        $user = auth()->user();
        $wallet = $user->wallet()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'reserved_balance' => 0]
        );

        $verifiedAccounts = $user->bankAccounts()
            ->where('is_verified', true)
            ->latest()
            ->get();

        $recentWithdrawals = $user->withdrawals()
            ->with('bankAccount')
            ->latest()
            ->take(10)
            ->get();

        return view('wallet.withdraw', [
            'wallet' => $wallet,
            'verifiedAccounts' => $verifiedAccounts,
            'recentWithdrawals' => $recentWithdrawals,
            'newIdempotencyKey' => (string) Str::uuid(),
        ]);
    }

    /**
     * Reserve funds and initiate withdrawal to verified bank account.
     */
    public function store(StoreWithdrawalRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $bankAccount = BankAccount::where('id', $request->input('bank_account_id'))
            ->where('user_id', $user->id)
            ->firstOrFail();

        $amountPaise = Wallet::rupeesToPaise($request->input('amount'));
        $idempotencyKey = $request->input('idempotency_key');

        try {
            $withdrawal = $this->walletService->reserveAndInitiateWithdrawal(
                $user,
                $bankAccount,
                $amountPaise,
                $idempotencyKey
            );

            if ($withdrawal->status === 'completed') {
                $utrMsg = $withdrawal->utr ? " (UTR: {$withdrawal->utr})" : '';
                return redirect()->route('wallet.withdraw.create')
                    ->with('success', "Withdrawal of {$withdrawal->formatted_amount} processed successfully!{$utrMsg}");
            }

            if ($withdrawal->status === 'failed') {
                return redirect()->route('wallet.withdraw.create')
                    ->with('error', "Withdrawal failed: {$withdrawal->failure_reason}. The reserved amount has been restored to your available balance.");
            }

            return redirect()->route('wallet.withdraw.create')
                ->with('info', "Withdrawal of {$withdrawal->formatted_amount} has been initiated and is currently processing. Your funds are securely reserved.");
        } catch (Throwable $e) {
            Log::error('Withdrawal request failed', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Synchronize withdrawal status against Cashfree Payouts API.
     */
    public function sync(Withdrawal $withdrawal): RedirectResponse
    {
        Gate::authorize('sync', $withdrawal);

        try {
            $updated = $this->walletService->syncWithdrawalStatus($withdrawal);

            return back()->with('info', "Transfer status updated: {$updated->status}" . ($updated->utr ? " (UTR: {$updated->utr})" : ''));
        } catch (Throwable $e) {
            return back()->with('error', 'Status sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Cashfree Payouts Webhook handler.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->json('data') ?? $request->all();
        $transfer = $payload['transfer'] ?? $payload;
        $transferId = $transfer['transfer_id'] ?? $request->json('transfer_id');

        if (!$transferId) {
            return response()->json(['status' => 'ignored', 'message' => 'No transfer_id provided in payout webhook.'], 200);
        }

        $withdrawal = Withdrawal::where('transfer_id', $transferId)->first();

        if (!$withdrawal) {
            return response()->json(['status' => 'ignored', 'message' => 'Transfer record not found.'], 200);
        }

        $status = strtoupper($transfer['status'] ?? $payload['event'] ?? '');
        $utr = $transfer['utr'] ?? null;
        $refId = (string) ($transfer['reference_id'] ?? '');

        try {
            if ($status === 'SUCCESS' || str_contains($status, 'TRANSFER_SUCCESS')) {
                $this->walletService->finalizeWithdrawalSuccess($withdrawal, $utr, $refId);
            } elseif (in_array($status, ['FAILED', 'REJECTED']) || str_contains($status, 'TRANSFER_FAILED') || str_contains($status, 'TRANSFER_REJECTED')) {
                $reason = $transfer['status_description'] ?? $transfer['failure_reason'] ?? 'Payout rejected';
                $this->walletService->handleWithdrawalFailure($withdrawal, $reason);
            } elseif ($status === 'REVERSED' || str_contains($status, 'TRANSFER_REVERSED')) {
                $reason = $transfer['status_description'] ?? 'Payout reversed';
                $this->walletService->handleWithdrawalReversal($withdrawal, $reason);
            }

            return response()->json(['status' => 'OK']);
        } catch (Throwable $e) {
            Log::error("Payout webhook handling failed for transfer {$transferId}", [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
