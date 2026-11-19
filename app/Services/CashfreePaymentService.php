<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CashfreePaymentService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $apiVersion;
    protected string $baseUrl;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->clientId = config('cashfree.payment_gateway.client_id', '');
        $this->clientSecret = config('cashfree.payment_gateway.client_secret', '');
        $this->apiVersion = config('cashfree.payment_gateway.api_version', '2025-01-01');
        $this->baseUrl = config('cashfree.payment_gateway.base_url', 'https://sandbox.cashfree.com/pg');
        $this->webhookSecret = config('cashfree.payment_gateway.webhook_secret', '');
    }

    /**
     * Common HTTP client headers for Cashfree Payment Gateway.
     *
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return [
            'x-client-id' => $this->clientId,
            'x-client-secret' => $this->clientSecret,
            'x-api-version' => $this->apiVersion,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Build an optimized HTTP client instance with IPv4 and low-latency TCP settings.
     */
    protected function client(int $timeout = 10, int $connectTimeout = 4)
    {
        return Http::withOptions([
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_TCP_NODELAY => 1,
            ],
        ])
        ->timeout($timeout)
        ->connectTimeout($connectTimeout)
        ->withHeaders($this->headers());
    }

    /**
     * Create an order on Cashfree Payment Gateway.
     *
     * @param  string  $orderId
     * @param  int  $amountPaise
     * @param  \App\Models\User  $user
     * @param  string|null  $phone
     * @return array<string, mixed>
     */
    public function createOrder(string $orderId, int $amountPaise, User $user, ?string $phone = null): array
    {
        $amountRupees = (float) Wallet::paiseToRupees($amountPaise);
        $customerPhone = $phone ?: ($user->phone ?: '9999999999');

        $returnUrl = route('wallet.deposit.return', ['order_id' => $orderId]);

        $orderMeta = [
            'return_url' => $returnUrl,
        ];

        // Cashfree strictly requires HTTPS for notify_url; omit if local HTTP
        $notifyUrl = url('/webhooks/cashfree/payment');
        if (str_starts_with(strtolower($notifyUrl), 'https://')) {
            $orderMeta['notify_url'] = $notifyUrl;
        }

        $payload = [
            'order_id' => $orderId,
            'order_amount' => $amountRupees,
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => 'user_' . $user->id,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $customerPhone,
            ],
            'order_meta' => $orderMeta,
            'order_note' => 'Wallet recharge for ' . $user->name,
        ];

        $response = $this->client(10, 4)
            ->post($this->baseUrl . '/orders', $payload);

        if (!$response->successful()) {
            Log::error('Cashfree PG createOrder failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'payload' => $payload,
            ]);

            $message = $response->json('message') ?? 'Failed to create payment order on Cashfree.';
            throw new RuntimeException($message, $response->status());
        }

        return $response->json();
    }

    /**
     * Fetch order details by order_id from Cashfree Payment Gateway.
     *
     * @param  string  $orderId
     * @return array<string, mixed>
     */
    public function getOrder(string $orderId): array
    {
        $response = $this->client(8, 3)
            ->get($this->baseUrl . '/orders/' . urlencode($orderId));

        if (!$response->successful()) {
            Log::error('Cashfree PG getOrder failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            $message = $response->json('message') ?? 'Failed to retrieve order from Cashfree.';
            throw new RuntimeException($message, $response->status());
        }

        return $response->json();
    }

    /**
     * Fetch all payments attempted for a specific order.
     *
     * @param  string  $orderId
     * @return array<int, mixed>
     */
    public function getOrderPayments(string $orderId): array
    {
        $response = $this->client(8, 3)
            ->get($this->baseUrl . '/orders/' . urlencode($orderId) . '/payments');

        if (!$response->successful()) {
            Log::error('Cashfree PG getOrderPayments failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Verify Cashfree webhook signature using HMAC-SHA256.
     */
    public function verifyWebhookSignature(string $rawBody, string $timestamp, string $signature): bool
    {
        if (empty($this->webhookSecret) || empty($timestamp) || empty($signature)) {
            return false;
        }

        $payload = $timestamp . $rawBody;
        $expectedBase64 = base64_encode(hash_hmac('sha256', $payload, $this->webhookSecret, true));
        $expectedHex = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expectedBase64, $signature) || hash_equals($expectedHex, $signature);
    }
}
