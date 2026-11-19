<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCashfreeWebhookSignature
{
    /**
     * Handle an incoming request and verify the Cashfree webhook signature.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $channel 'payment' or 'payout'
     */
    public function handle(Request $request, Closure $next, string $channel = 'payment'): Response
    {
        $signature = $request->header('x-webhook-signature');
        $timestamp = $request->header('x-webhook-timestamp');

        if (!$signature || !$timestamp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing Cashfree webhook signature or timestamp header.',
            ], 401);
        }

        $secret = $channel === 'payout'
            ? config('cashfree.payouts.webhook_secret')
            : config('cashfree.payment_gateway.webhook_secret');

        if (empty($secret)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Webhook secret is not configured on the server.',
            ], 401);
        }

        $rawBody = $request->getContent();
        $payload = $timestamp . $rawBody;

        // Cashfree webhook signature is Base64-encoded HMAC-SHA256, but also allow hex digest for compatibility
        $expectedBase64 = base64_encode(hash_hmac('sha256', $payload, $secret, true));
        $expectedHex = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedBase64, $signature) && !hash_equals($expectedHex, $signature)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        return $next($request);
    }
}