<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CashfreePayoutService
{
    protected string $payoutClientId;
    protected string $payoutClientSecret;
    protected string $payoutApiVersion;
    protected string $payoutBaseUrl;
    protected string $payoutWebhookSecret;

    protected string $verificationClientId;
    protected string $verificationClientSecret;
    protected string $verificationBaseUrl;

    public function __construct()
    {
        $this->payoutClientId = config('cashfree.payouts.client_id', '');
        $this->payoutClientSecret = config('cashfree.payouts.client_secret', '');
        $this->payoutApiVersion = config('cashfree.payouts.api_version', '2024-01-01');
        $this->payoutBaseUrl = config('cashfree.payouts.base_url', 'https://sandbox.cashfree.com/payout');
        $this->payoutWebhookSecret = config('cashfree.payouts.webhook_secret', '');

        $this->verificationClientId = config('cashfree.verification.client_id', '');
        $this->verificationClientSecret = config('cashfree.verification.client_secret', '');
        $this->verificationBaseUrl = config('cashfree.verification.base_url', 'https://sandbox.cashfree.com');
    }

    /**
     * Common headers for Payouts V2 API.
     *
     * @return array<string, string>
     */
    protected function payoutHeaders(): array
    {
        return [
            'x-client-id' => $this->payoutClientId,
            'x-client-secret' => $this->payoutClientSecret,
            'x-api-version' => $this->payoutApiVersion,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Common headers for Bank Account Verification API.
     *
     * @return array<string, string>
     */
    protected function verificationHeaders(): array
    {
        return [
            'x-client-id' => $this->verificationClientId,
            'x-client-secret' => $this->verificationClientSecret,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Optimized HTTP client for Payouts V2.
     */
    protected function payoutClient(int $timeout = 10, int $connectTimeout = 4)
    {
        return Http::withOptions([
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_TCP_NODELAY => 1,
            ],
        ])
        ->timeout($timeout)
        ->connectTimeout($connectTimeout)
        ->withHeaders($this->payoutHeaders());
    }

    /**
     * Optimized HTTP client for Bank Account Verification API.
     */
    protected function verificationClient(int $timeout = 10, int $connectTimeout = 4)
    {
        return Http::withOptions([
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_TCP_NODELAY => 1,
            ],
        ])
        ->timeout($timeout)
        ->connectTimeout($connectTimeout)
        ->withHeaders($this->verificationHeaders());
    }

    /**
     * Cashfree Verification Suite: Bank Account Verification Sync V2.
     * POST /verification/bank-account/sync
     *
     * @param  string  $name
     * @param  string  $accountNumber
     * @param  string  $ifsc
     * @return array<string, mixed>
     */
    public function verifyBankAccount(string $name, string $accountNumber, string $ifsc): array
    {
        $payload = [
            'name' => trim($name),
            'bank_account' => trim($accountNumber),
            'ifsc' => strtoupper(trim($ifsc)),
        ];

        $response = $this->verificationClient(10, 4)
            ->post($this->verificationBaseUrl . '/verification/bank-account/sync', $payload);

        if (!$response->successful()) {
            Log::error('Cashfree Bank Verification failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            $message = $response->json('message') ?? 'Bank account verification request failed.';
            throw new RuntimeException($message, $response->status());
        }

        return $response->json() ?? [];
    }

    /**
     * Register or confirm beneficiary in Cashfree Payouts.
     * POST /beneficiaries
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @param  \App\Models\User  $user
     * @return string Beneficiary ID
     */
    public function ensureBeneficiary(BankAccount $bankAccount, User $user): string
    {
        if (!empty($bankAccount->cashfree_beneficiary_id)) {
            return $bankAccount->cashfree_beneficiary_id;
        }

        $beneficiaryId = 'bene_' . $user->id . '_' . $bankAccount->id;
        $customerPhone = $user->phone ?: '9999999999';

        $payload = [
            'beneficiary_id' => $beneficiaryId,
            'beneficiary_name' => $bankAccount->account_holder_name,
            'beneficiary_instrument_details' => [
                'bank_account_number' => (string) $bankAccount->account_number,
                'bank_ifsc' => $bankAccount->ifsc_code,
            ],
            'beneficiary_contact_details' => [
                'beneficiary_email' => $user->email,
                'beneficiary_phone' => $customerPhone,
            ],
        ];

        $response = $this->payoutClient(10, 4)
            ->post($this->payoutBaseUrl . '/beneficiary', $payload);

        // 200/201 = success; 409 = beneficiary already exists
        if (!$response->successful() && $response->status() !== 409) {
            $errorMsg = $response->json('message') ?? '';
            // If message indicates already exists, treat as success
            if (!str_contains(strtolower($errorMsg), 'already exist')) {
                Log::error('Cashfree ensureBeneficiary failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                throw new RuntimeException($errorMsg ?: 'Failed to register beneficiary on Cashfree.', $response->status());
            }
        }

        $bankAccount->update(['cashfree_beneficiary_id' => $beneficiaryId]);

        return $beneficiaryId;
    }

    /**
     * Create bank transfer in Cashfree Payouts V2.
     * POST /transfers
     *
     * @param  string  $transferId
     * @param  int  $amountPaise
     * @param  string  $beneficiaryId
     * @param  string|null  $remarks
     * @return array<string, mixed>
     */
    public function createTransfer(string $transferId, int $amountPaise, string $beneficiaryId, ?string $remarks = null): array
    {
        $amountRupees = (float) Wallet::paiseToRupees($amountPaise);
        $transferMode = config('cashfree.payouts.transfer_mode', 'banktransfer');

        $payload = [
            'transfer_id' => $transferId,
            'transfer_amount' => $amountRupees,
            'transfer_currency' => 'INR',
            'transfer_mode' => $transferMode,
            'beneficiary_details' => [
                'beneficiary_id' => $beneficiaryId,
            ],
            'transfer_remarks' => $remarks ?: 'Wallet Withdrawal',
        ];

        $response = $this->payoutClient(10, 4)
            ->post($this->payoutBaseUrl . '/transfers', $payload);

        if (!$response->successful()) {
            Log::error('Cashfree createTransfer failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'payload' => $payload,
            ]);

            $message = $response->json('message') ?? 'Cashfree transfer initiation failed.';
            throw new RuntimeException($message, $response->status());
        }

        return $response->json() ?? [];
    }

    /**
     * Get transfer status from Cashfree Payouts V2.
     * GET /transfers?transfer_id={transfer_id}
     *
     * @param  string  $transferId
     * @return array<string, mixed>
     */
    public function getTransferStatus(string $transferId): array
    {
        $response = $this->payoutClient(8, 3)
            ->get($this->payoutBaseUrl . '/transfers', [
                'transfer_id' => $transferId,
            ]);

        if (!$response->successful()) {
            // Also try direct endpoint GET /transfers/{transferId} if query param didn't return 200
            $fallbackResponse = $this->payoutClient(8, 3)
                ->get($this->payoutBaseUrl . '/transfers/' . urlencode($transferId));

            if (!$fallbackResponse->successful()) {
                Log::error('Cashfree getTransferStatus failed', [
                    'transfer_id' => $transferId,
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                $message = $response->json('message') ?? 'Failed to retrieve transfer status from Cashfree.';
                throw new RuntimeException($message, $response->status());
            }

            return $fallbackResponse->json() ?? [];
        }

        return $response->json() ?? [];
    }

    /**
     * Enterprise Account Verification & Activation Strategy:
     * 1. If mode is 'auto' or 'verification_suite', attempt penny-drop/sync verification via Verification Suite.
     * 2. If valid, update bank account metadata and register as a payout beneficiary.
     * 3. If Verification Suite is not enabled on the merchant account (and mode allows auto-fallback),
     *    fallback gracefully to direct Payout Beneficiary registration with Cashfree.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @param  \App\Models\User  $user
     * @return array<string, mixed>
     */
    public function verifyAndActivateAccount(BankAccount $bankAccount, User $user): array
    {
        $mode = config('cashfree.verification.mode', 'auto');

        if ($mode === 'beneficiary_only') {
            return $this->activateViaBeneficiaryRegistration($bankAccount, $user);
        }

        try {
            $verification = $this->verifyBankAccount(
                $bankAccount->account_holder_name,
                (string) $bankAccount->account_number,
                $bankAccount->ifsc_code
            );

            $accountStatus = strtoupper($verification['account_status'] ?? '');

            if ($accountStatus === 'VALID') {
                $bankAccount->update([
                    'is_verified' => true,
                    'verification_status' => 'valid',
                    'name_at_bank' => $verification['registered_name'] ?? null,
                    'bank_name' => $verification['bank_name'] ?? $bankAccount->bank_name,
                    'branch_name' => $verification['branch'] ?? null,
                    'verification_reference_id' => (string) ($verification['reference_id'] ?? ''),
                    'verified_at' => now(),
                ]);

                // Ensure beneficiary registration in Payouts
                try {
                    $this->ensureBeneficiary($bankAccount, $user);
                } catch (\Throwable $e) {
                    Log::warning('Beneficiary registration delayed for account ' . $bankAccount->id, ['error' => $e->getMessage()]);
                }

                return [
                    'success' => true,
                    'status' => 'valid',
                    'message' => 'Bank account verified successfully! It is now active for withdrawals.',
                ];
            }

            $bankAccount->update([
                'verification_status' => 'invalid',
                'name_at_bank' => $verification['registered_name'] ?? null,
            ]);

            return [
                'success' => false,
                'status' => 'invalid',
                'message' => 'Cashfree reported the bank account details as INVALID. Please check and retry.',
            ];

        } catch (\Throwable $e) {
            Log::warning('Verification Suite call exception', ['error' => $e->getMessage()]);

            // If account has not subscribed to Verification Suite and mode is 'auto', fallback to Payout Beneficiary
            if ($mode === 'auto' && (str_contains(strtolower($e->getMessage()), 'not enabled') || str_contains(strtolower($e->getMessage()), 'unauthorized'))) {
                return $this->activateViaBeneficiaryRegistration($bankAccount, $user);
            }

            $bankAccount->update(['verification_status' => 'failed']);

            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Bank verification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Fallback activation via Cashfree Payouts Beneficiary Registration.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @param  \App\Models\User  $user
     * @return array<string, mixed>
     */
    protected function activateViaBeneficiaryRegistration(BankAccount $bankAccount, User $user): array
    {
        try {
            $this->ensureBeneficiary($bankAccount, $user);

            $bankAccount->update([
                'is_verified' => true,
                'verification_status' => 'valid',
                'verified_at' => now(),
            ]);

            return [
                'success' => true,
                'status' => 'valid',
                'message' => 'Bank account registered and verified as a Payout beneficiary! It is now active for withdrawals.',
            ];
        } catch (\Throwable $e) {
            Log::error('Payout Beneficiary registration failed', ['error' => $e->getMessage()]);

            $bankAccount->update(['verification_status' => 'failed']);

            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Payout beneficiary registration failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Cashfree Payouts webhook signature using HMAC-SHA256.
     */
    public function verifyWebhookSignature(string $rawBody, string $timestamp, string $signature): bool
    {
        if (empty($this->payoutWebhookSecret) || empty($timestamp) || empty($signature)) {
            return false;
        }

        $payload = $timestamp . $rawBody;
        $expectedBase64 = base64_encode(hash_hmac('sha256', $payload, $this->payoutWebhookSecret, true));
        $expectedHex = hash_hmac('sha256', $payload, $this->payoutWebhookSecret);

        return hash_equals($expectedBase64, $signature) || hash_equals($expectedHex, $signature);
    }
}
