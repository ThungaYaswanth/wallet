<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cashfree Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | API documentation: https://docs.cashfree.com/docs/pg-api
    | Uses separate credentials with prefix 'TEST' or 'PROD' for PG.
    |
    */
    'payment_gateway' => [
        'client_id' => trim((string) env('CASHFREE_CLIENT_ID', '')),
        'client_secret' => trim((string) env('CASHFREE_CLIENT_SECRET', '')),
        'env' => trim((string) env('CASHFREE_ENV', 'sandbox')),
        'api_version' => trim((string) env('CASHFREE_API_VERSION', '2025-01-01')),
        'webhook_secret' => trim((string) env('CASHFREE_WEBHOOK_SECRET', '')),
        'base_url' => env('CASHFREE_ENV') === 'production'
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cashfree Payouts Configuration
    |--------------------------------------------------------------------------
    |
    | Cashfree Payouts V2 uses separate client credentials (typically 'CF...').
    | Never fall back to payment_gateway keys.
    |
    */
    'payouts' => [
        'client_id' => trim((string) env('CASHFREE_PAYOUT_CLIENT_ID', '')),
        'client_secret' => trim((string) env('CASHFREE_PAYOUT_CLIENT_SECRET', '')),
        'env' => trim((string) env('CASHFREE_ENV', 'sandbox')),
        'api_version' => trim((string) env('CASHFREE_PAYOUT_API_VERSION', '2024-01-01')),
        'webhook_secret' => trim((string) env('CASHFREE_PAYOUT_WEBHOOK_SECRET', '')),
        'transfer_mode' => trim((string) env('CASHFREE_PAYOUT_TRANSFER_MODE', 'banktransfer')),
        'base_url' => env('CASHFREE_ENV') === 'production'
            ? 'https://api.cashfree.com/payout'
            : 'https://sandbox.cashfree.com/payout',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cashfree Verification Suite Configuration
    |--------------------------------------------------------------------------
    |
    | Verification credentials fall back to Payouts credentials when unset.
    |
    */
    'verification' => [
        'client_id' => trim((string) (env('CASHFREE_VERIFICATION_CLIENT_ID') ?: env('CASHFREE_PAYOUT_CLIENT_ID', ''))),
        'client_secret' => trim((string) (env('CASHFREE_VERIFICATION_CLIENT_SECRET') ?: env('CASHFREE_PAYOUT_CLIENT_SECRET', ''))),
        'base_url' => env('CASHFREE_ENV') === 'production'
            ? 'https://api.cashfree.com'
            : 'https://sandbox.cashfree.com',
        // 'auto' (tries Verification Suite, falls back to Payout Beneficiary if not enabled on account),
        // 'verification_suite' (strict penny-drop verification only),
        // 'beneficiary_only' (skips verification suite, registers directly as payout beneficiary)
        'mode' => env('CASHFREE_VERIFICATION_MODE', 'auto'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Limits & Guardrails
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'min_deposit_rupees' => 1.00,
        'max_deposit_rupees' => 500000.00,
        'min_withdrawal_rupees' => 1.00,
        'max_withdrawal_rupees' => 100000.00,
    ],
];
