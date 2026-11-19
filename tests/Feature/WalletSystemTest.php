<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WalletSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cashfree.payment_gateway.client_id', 'TEST_PG_CLIENT_ID');
        Config::set('cashfree.payment_gateway.client_secret', 'TEST_PG_CLIENT_SECRET');
        Config::set('cashfree.payment_gateway.webhook_secret', 'test_pg_webhook_secret_key_123');
        Config::set('cashfree.payment_gateway.base_url', 'https://sandbox.cashfree.com/pg');

        Config::set('cashfree.payouts.client_id', 'CF_PAYOUT_CLIENT_ID');
        Config::set('cashfree.payouts.client_secret', 'CF_PAYOUT_CLIENT_SECRET');
        Config::set('cashfree.payouts.webhook_secret', 'test_payout_webhook_secret_key_456');
        Config::set('cashfree.payouts.base_url', 'https://sandbox.cashfree.com/payout');

        Config::set('cashfree.verification.client_id', 'CF_PAYOUT_CLIENT_ID');
        Config::set('cashfree.verification.client_secret', 'CF_PAYOUT_CLIENT_SECRET');
        Config::set('cashfree.verification.base_url', 'https://sandbox.cashfree.com');
    }

    /**
     * Test guest redirection and authentication protection.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/wallet');
        $response->assertRedirect('/login');

        $response = $this->get('/wallet/add-money');
        $response->assertRedirect('/login');

        $response = $this->get('/wallet/withdraw');
        $response->assertRedirect('/login');

        $response = $this->get('/wallet/bank-accounts');
        $response->assertRedirect('/login');

        $response = $this->get('/wallet/transactions');
        $response->assertRedirect('/login');
    }

    /**
     * Test user registration automatically creates a zero-balance wallet.
     */
    public function test_registration_automatically_creates_zero_balance_wallet(): void
    {
        $response = $this->post('/register', [
            'name' => 'Amit Verma',
            'email' => 'amit@example.com',
            'phone' => '9876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/wallet');
        $this->assertAuthenticated();

        $user = User::where('email', 'amit@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('9876543210', $user->phone);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 0,
            'reserved_balance' => 0,
        ]);

        $this->assertSame(0, $user->wallet->balance);
        $this->assertSame(0, $user->wallet->reserved_balance);
    }

    /**
     * Test user login and logout.
     */
    public function test_user_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'loginuser@example.com',
            'password' => bcrypt('secret1234'),
        ]);

        $response = $this->post('/login', [
            'email' => 'loginuser@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertRedirect('/wallet');
        $this->assertAuthenticatedAs($user);

        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * Test JSON balance endpoint returns accurate formatted resource.
     */
    public function test_balance_json_endpoint(): void
    {
        $user = User::factory()->create();
        $user->wallet()->update(['balance' => 25050, 'reserved_balance' => 5000]);

        $response = $this->actingAs($user)->getJson('/wallet/balance');

        $response->assertOk()
            ->assertJson([
                'available_balance_paise' => 25050,
                'available_balance_rupees' => 250.50,
                'formatted_available_balance' => '₹250.50',
                'reserved_balance_paise' => 5000,
                'reserved_balance_rupees' => 50.00,
                'formatted_reserved_balance' => '₹50.00',
                'currency' => 'INR',
            ]);
    }

    /**
     * Test successful deposit via order creation, signed webhook, and server verification.
     */
    public function test_successful_deposit_flow(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://sandbox.cashfree.com/pg/orders/*/payments' => Http::response([
                [
                    'payment_id' => 'pay_test_999',
                    'cf_payment_id' => 'pay_test_999',
                    'payment_status' => 'SUCCESS',
                    'payment_amount' => 500.00,
                ],
            ], 200),
            'https://sandbox.cashfree.com/pg/orders/*' => function ($request) {
                $segments = explode('/', $request->url());
                $orderId = end($segments);
                return Http::response([
                    'order_id' => $orderId,
                    'order_amount' => 500.00,
                    'order_status' => 'PAID',
                ], 200);
            },
            'https://sandbox.cashfree.com/pg/orders' => Http::response([
                'order_id' => 'ord_test_123',
                'order_amount' => 500.00,
                'order_currency' => 'INR',
                'payment_session_id' => 'session_test_abc_123',
                'order_status' => 'ACTIVE',
            ], 200),
        ]);

        // 1. Create order
        $orderResponse = $this->actingAs($user)->postJson('/wallet/add-money', [
            'amount' => 500.00,
        ]);

        $orderResponse->assertOk()
            ->assertJson([
                'success' => true,
                'payment_session_id' => 'session_test_abc_123',
            ]);

        $orderId = $orderResponse->json('order_id');

        $this->assertDatabaseHas('wallet_transactions', [
            'reference_id' => $orderId,
            'amount' => 50000,
            'type' => 'credit',
            'status' => 'pending',
        ]);

        // 2. Incoming signed Cashfree PG webhook
        $timestamp = (string) (time() * 1000);
        $rawPayload = json_encode([
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'data' => [
                'order' => [
                    'order_id' => $orderId,
                    'order_amount' => 500.00,
                    'order_status' => 'PAID',
                ],
                'payment' => [
                    'cf_payment_id' => 'pay_test_999',
                    'payment_status' => 'SUCCESS',
                ],
            ],
        ]);

        $secret = config('cashfree.payment_gateway.webhook_secret');
        $signature = base64_encode(hash_hmac('sha256', $timestamp . $rawPayload, $secret, true));

        $webhookResponse = $this->call(
            'POST',
            '/webhooks/cashfree/payment',
            [],
            [],
            [],
            [
                'HTTP_x-webhook-timestamp' => $timestamp,
                'HTTP_x-webhook-signature' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawPayload
        );

        $webhookResponse->assertOk();

        // Wallet balance credited to 50000 paise (₹500.00)
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 50000,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'reference_id' => $orderId,
            'status' => 'completed',
            'gateway_reference_id' => 'pay_test_999',
        ]);
    }

    /**
     * Test duplicate payment webhook is strictly credited once (Idempotency).
     */
    public function test_duplicate_webhook_credits_strictly_once(): void
    {
        $user = User::factory()->create();
        $orderId = 'ord_idempotent_test';

        // Pre-create pending transaction
        $walletService = app(WalletService::class);
        $walletService->createPendingDeposit($user, 10000, $orderId, 'sess_123');

        Http::fake([
            'https://sandbox.cashfree.com/pg/orders/' . $orderId => Http::response([
                'order_id' => $orderId,
                'order_amount' => 100.00,
                'order_status' => 'PAID',
            ], 200),
            'https://sandbox.cashfree.com/pg/orders/' . $orderId . '/payments' => Http::response([
                [
                    'payment_id' => 'pay_idem_1',
                    'cf_payment_id' => 'pay_idem_1',
                    'payment_status' => 'SUCCESS',
                ],
            ], 200),
        ]);

        $timestamp = (string) (time() * 1000);
        $rawPayload = json_encode(['data' => ['order' => ['order_id' => $orderId]]]);
        $secret = config('cashfree.payment_gateway.webhook_secret');
        $signature = base64_encode(hash_hmac('sha256', $timestamp . $rawPayload, $secret, true));

        $headers = [
            'HTTP_x-webhook-timestamp' => $timestamp,
            'HTTP_x-webhook-signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ];

        // First call credits wallet
        $this->call('POST', '/webhooks/cashfree/payment', [], [], [], $headers, $rawPayload)->assertOk();
        $this->assertSame(10000, $user->wallet->fresh()->balance);

        // Second duplicate call must NOT credit again
        $this->call('POST', '/webhooks/cashfree/payment', [], [], [], $headers, $rawPayload)->assertOk();
        $this->assertSame(10000, $user->wallet->fresh()->balance);
    }

    /**
     * Test invalid webhook signature returns 401 Unauthorized.
     */
    public function test_invalid_webhook_signature_returns_401(): void
    {
        $timestamp = (string) (time() * 1000);
        $rawPayload = json_encode(['test' => 'data']);

        $response = $this->call(
            'POST',
            '/webhooks/cashfree/payment',
            [],
            [],
            [],
            [
                'HTTP_x-webhook-timestamp' => $timestamp,
                'HTTP_x-webhook-signature' => 'invalid_signature_hash',
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawPayload
        );

        $response->assertStatus(401);
    }

    /**
     * Test failed deposit does not credit wallet.
     */
    public function test_failed_deposit_does_not_credit_wallet(): void
    {
        $user = User::factory()->create();
        $orderId = 'ord_failed_test';

        $walletService = app(WalletService::class);
        $walletService->createPendingDeposit($user, 20000, $orderId, 'sess_fail');

        Http::fake([
            'https://sandbox.cashfree.com/pg/orders/' . $orderId => Http::response([
                'order_id' => $orderId,
                'order_amount' => 200.00,
                'order_status' => 'FAILED',
            ], 200),
            'https://sandbox.cashfree.com/pg/orders/' . $orderId . '/payments' => Http::response([
                [
                    'payment_status' => 'FAILED',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->post('/wallet/orders/' . $orderId . '/verify');
        $response->assertSessionHas('warning');

        // Wallet balance remains 0
        $this->assertSame(0, $user->wallet->fresh()->balance);
    }

    /**
     * Test bank account validation, DB encryption, Cashfree verification, and cross-user 403 protection.
     */
    public function test_bank_account_verification_and_cross_user_protection(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Http::fake([
            'https://sandbox.cashfree.com/verification/bank-account/sync' => Http::response([
                'status' => 'SUCCESS',
                'account_status' => 'VALID',
                'account_status_code' => 'ACCOUNT_IS_VALID',
                'registered_name' => 'Rahul Sharma',
                'bank_name' => 'HDFC Bank',
                'branch' => 'Bandra West',
                'reference_id' => 987654,
            ], 200),
            'https://sandbox.cashfree.com/payout/beneficiaries' => Http::response([
                'beneficiary_id' => 'bene_' . $user1->id . '_1',
                'status' => 'SUCCESS',
            ], 200),
        ]);

        // 1. Invalid IFSC validation failure
        $invalidIfscResponse = $this->actingAs($user1)->post('/wallet/bank-accounts', [
            'account_holder_name' => 'Rahul Sharma',
            'account_number' => '123456789012',
            'account_number_confirmation' => '123456789012',
            'ifsc_code' => 'INVALID_IFSC', // Not 11 chars or regex match
        ]);
        $invalidIfscResponse->assertSessionHasErrors(['ifsc_code']);

        // 2. Valid bank account addition + verification
        $validResponse = $this->actingAs($user1)->post('/wallet/bank-accounts', [
            'account_holder_name' => 'Rahul Sharma',
            'account_number' => '123456789012',
            'account_number_confirmation' => '123456789012',
            'ifsc_code' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
        ]);

        $validResponse->assertRedirect('/wallet/bank-accounts');
        $validResponse->assertSessionHas('success');

        $bankAccount = BankAccount::where('user_id', $user1->id)->first();
        $this->assertNotNull($bankAccount);
        $this->assertTrue($bankAccount->is_verified);
        $this->assertSame('valid', $bankAccount->verification_status);
        $this->assertSame('XXXXXX9012', $bankAccount->masked_account_number);

        // Verify DB encryption: raw DB value must not equal raw account number
        $rawDbValue = \Illuminate\Support\Facades\DB::table('bank_accounts')
            ->where('id', $bankAccount->id)
            ->value('account_number');
        $this->assertNotSame('123456789012', $rawDbValue);
        // But model decrypts automatically
        $this->assertSame('123456789012', $bankAccount->account_number);

        // 3. Cross-user protection: User2 cannot delete User1's bank account (403)
        $unauthorizedResponse = $this->actingAs($user2)->delete('/wallet/bank-accounts/' . $bankAccount->id);
        $unauthorizedResponse->assertForbidden();
    }

    /**
     * Test successful payout reservation, webhook finalization, and UTR recording.
     */
    public function test_successful_payout_reservation_and_finalization(): void
    {
        $user = User::factory()->create();
        $user->wallet()->update(['balance' => 100000]); // ₹1,000.00

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'is_verified' => true,
        ]);

        Http::fake([
            'https://sandbox.cashfree.com/payout/beneficiaries' => Http::response(['status' => 'SUCCESS'], 200),
            'https://sandbox.cashfree.com/payout/transfers' => Http::response([
                'status' => 'RECEIVED', // In-flight
                'transfer_id' => 'tr_test_payout_1',
            ], 200),
        ]);

        $idempotencyKey = (string) Str::uuid();

        $response = $this->actingAs($user)->post('/wallet/withdraw', [
            'bank_account_id' => $bankAccount->id,
            'amount' => 400.00, // ₹400.00 = 40000 paise
            'idempotency_key' => $idempotencyKey,
        ]);

        $response->assertRedirect('/wallet/withdraw');

        // Funds moved from balance to reserved_balance
        $wallet = $user->wallet->fresh();
        $this->assertSame(60000, $wallet->balance); // 100000 - 40000
        $this->assertSame(40000, $wallet->reserved_balance);

        $withdrawal = Withdrawal::where('idempotency_key', $idempotencyKey)->first();
        $this->assertNotNull($withdrawal);
        $this->assertSame('processing', $withdrawal->status);

        // Incoming Payouts Webhook with SUCCESS + UTR
        $timestamp = (string) (time() * 1000);
        $rawPayload = json_encode([
            'event' => 'TRANSFER_SUCCESS',
            'data' => [
                'transfer' => [
                    'transfer_id' => $withdrawal->transfer_id,
                    'status' => 'SUCCESS',
                    'utr' => 'UTR1234567890',
                ],
            ],
        ]);

        $secret = config('cashfree.payouts.webhook_secret');
        $sig = base64_encode(hash_hmac('sha256', $timestamp . $rawPayload, $secret, true));

        $webhookResp = $this->call(
            'POST',
            '/webhooks/cashfree/payout',
            [],
            [],
            [],
            [
                'HTTP_x-webhook-timestamp' => $timestamp,
                'HTTP_x-webhook-signature' => $sig,
                'CONTENT_TYPE' => 'application/json',
            ],
            $rawPayload
        );

        $webhookResp->assertOk();

        // Reserved balance released to 0; withdrawal completed with UTR
        $wallet = $user->wallet->fresh();
        $this->assertSame(60000, $wallet->balance);
        $this->assertSame(0, $wallet->reserved_balance);

        $withdrawal = $withdrawal->fresh();
        $this->assertSame('completed', $withdrawal->status);
        $this->assertSame('UTR1234567890', $withdrawal->utr);
    }

    /**
     * Test failed and reversed payout releases reserved funds back to available balance.
     */
    public function test_failed_and_reversed_payout_restores_balance(): void
    {
        $user = User::factory()->create();
        $user->wallet()->update(['balance' => 50000]); // ₹500.00

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'is_verified' => true,
        ]);

        Http::fake([
            'https://sandbox.cashfree.com/payout/beneficiaries' => Http::response(['status' => 'SUCCESS'], 200),
            'https://sandbox.cashfree.com/payout/transfers' => Http::response([
                'status' => 'FAILED',
                'failure_reason' => 'Beneficiary bank down',
            ], 200),
        ]);

        $this->actingAs($user)->post('/wallet/withdraw', [
            'bank_account_id' => $bankAccount->id,
            'amount' => 200.00, // 20000 paise
            'idempotency_key' => (string) Str::uuid(),
        ]);

        // Reserved funds returned to balance on failure
        $wallet = $user->wallet->fresh();
        $this->assertSame(50000, $wallet->balance);
        $this->assertSame(0, $wallet->reserved_balance);

        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $user->id,
            'status' => 'failed',
            'failure_reason' => 'Beneficiary bank down',
        ]);
    }

    /**
     * Test insufficient balance, unverified bank account, and duplicate idempotency.
     */
    public function test_withdrawal_guardrails(): void
    {
        $user = User::factory()->create();
        $user->wallet()->update(['balance' => 1000]); // ₹10.00

        // 1. Unverified account rejection
        $unverifiedAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'is_verified' => false,
        ]);

        $resp = $this->actingAs($user)->post('/wallet/withdraw', [
            'bank_account_id' => $unverifiedAccount->id,
            'amount' => 5.00,
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $resp->assertSessionHas('error');

        // 2. Insufficient balance rejection
        $verifiedAccount = BankAccount::factory()->create([
            'user_id' => $user->id,
            'is_verified' => true,
        ]);

        $resp2 = $this->actingAs($user)->post('/wallet/withdraw', [
            'bank_account_id' => $verifiedAccount->id,
            'amount' => 500.00, // Exceeds balance of ₹10.00
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $resp2->assertSessionHas('error');

        // 3. Duplicate idempotency prevention
        $existingWithdrawal = Withdrawal::factory()->create([
            'user_id' => $user->id,
            'bank_account_id' => $verifiedAccount->id,
            'amount' => 500,
            'idempotency_key' => 'unique-token-abc',
            'status' => 'completed',
        ]);

        $walletService = app(WalletService::class);
        $returned = $walletService->reserveAndInitiateWithdrawal(
            $user,
            $verifiedAccount,
            500,
            'unique-token-abc'
        );

        $this->assertSame($existingWithdrawal->id, $returned->id);
    }

    /**
     * Test transaction history filtering and user isolation.
     */
    public function test_transaction_history_filtering_and_isolation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1 transactions
        WalletTransaction::factory()->create([
            'user_id' => $user1->id,
            'wallet_id' => $user1->wallet->id,
            'type' => 'credit',
            'transaction_type' => 'deposit',
            'description' => 'User1 Credit',
        ]);
        WalletTransaction::factory()->create([
            'user_id' => $user1->id,
            'wallet_id' => $user1->wallet->id,
            'type' => 'debit',
            'transaction_type' => 'withdrawal',
            'description' => 'User1 Debit',
        ]);

        // User 2 transaction (must be isolated)
        WalletTransaction::factory()->create([
            'user_id' => $user2->id,
            'wallet_id' => $user2->wallet->id,
            'description' => 'User2 Private Tx',
        ]);

        $response = $this->actingAs($user1)->get('/wallet/transactions');
        $response->assertOk();
        $response->assertSee('User1 Credit');
        $response->assertSee('User1 Debit');
        $response->assertDontSee('User2 Private Tx');

        // Test filter by type=credit
        $filterResponse = $this->actingAs($user1)->get('/wallet/transactions?type=credit');
        $filterResponse->assertOk();
        $filterResponse->assertSee('User1 Credit');
        $filterResponse->assertDontSee('User1 Debit');
    }

    /**
     * Test all wallet Blade views render cleanly with 200 OK.
     */
    public function test_all_wallet_views_render_cleanly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/wallet')->assertOk();
        $this->actingAs($user)->get('/wallet/add-money')->assertOk();
        $this->actingAs($user)->get('/wallet/withdraw')->assertOk();
        $this->actingAs($user)->get('/wallet/bank-accounts')->assertOk();
        $this->actingAs($user)->get('/wallet/transactions')->assertOk();
    }
}
