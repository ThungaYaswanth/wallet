<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\CreateDepositOrderRequest;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\CashfreePaymentService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class WalletPaymentController extends Controller
{
    public function __construct(
        protected CashfreePaymentService $paymentService,
        protected WalletService $walletService
    ) {}

    /**
     * Add Money page.
     */
    public function create(): View
    {
        $user = auth()->user();
        $wallet = $user->wallet()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'reserved_balance' => 0]
        );

        $recentDeposits = $user->walletTransactions()
            ->where('transaction_type', 'deposit')
            ->latest()
            ->take(5)
            ->get();

        return view('wallet.add-money', [
            'wallet' => $wallet,
            'recentDeposits' => $recentDeposits,
            'cashfreeEnv' => config('cashfree.payment_gateway.env', 'sandbox'),
        ]);
    }

    /**
     * Create payment order on Cashfree and record pending transaction.
     */
    public function store(CreateDepositOrderRequest $request): JsonResponse
    {
        $user = auth()->user();
        $amountPaise = Wallet::rupeesToPaise($request->input('amount'));

        $orderId = 'ord_' . Str::lower(Str::random(16));

        try {
            $orderData = $this->paymentService->createOrder(
                $orderId,
                $amountPaise,
                $user,
                $request->input('customer_phone')
            );

            $sessionId = $orderData['payment_session_id'] ?? null;

            if (empty($sessionId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cashfree did not return a valid payment session ID.',
                ], 502);
            }

            // Record pending credit in database
            $this->walletService->createPendingDeposit($user, $amountPaise, $orderId, $sessionId);

            return response()->json([
                'success' => true,
                'order_id' => $orderId,
                'payment_session_id' => $sessionId,
                'cashfree_env' => config('cashfree.payment_gateway.env', 'sandbox'),
            ]);
        } catch (Throwable $e) {
            Log::error('Create deposit order error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Could not initialize payment order with Cashfree.',
            ], 500);
        }
    }

    /**
     * Return URL handler after Cashfree checkout.
     * Always verifies strictly server-side before crediting.
     */
    public function returnUrl(Request $request): RedirectResponse
    {
        $orderId = $request->query('order_id');

        if (!$orderId) {
            return redirect()->route('wallet.add-money')
                ->with('error', 'Missing order reference in return redirect.');
        }

        try {
            $order = $this->paymentService->getOrder($orderId);
            $orderStatus = strtoupper($order['order_status'] ?? '');

            if ($orderStatus === 'PAID') {
                // High-performance path: cf_order_id is already present on the order.
                // Avoid redundant getOrderPayments() network hop when cf_order_id is available.
                $paymentId = (string) ($order['cf_order_id'] ?? '');
                if (empty($paymentId)) {
                    $payments = $this->paymentService->getOrderPayments($orderId);
                    $successfulPayment = collect($payments)->firstWhere('payment_status', 'SUCCESS');
                    $paymentId = (string) ($successfulPayment['cf_payment_id'] ?? $orderId);
                }

                $orderAmountPaise = Wallet::rupeesToPaise($order['order_amount'] ?? 0);

                $this->walletService->creditDeposit($orderId, $orderAmountPaise, $paymentId, [
                    'verified_via' => 'return_url',
                    'cashfree_order' => $order,
                ]);

                return redirect()->route('wallet.add-money')
                    ->with('success', 'Payment successful! ₹' . number_format($order['order_amount'], 2) . ' credited to your wallet.');
            }

            return redirect()->route('wallet.add-money')
                ->with('warning', "Payment status: {$orderStatus}. If your account was debited, it will update shortly.");
        } catch (Throwable $e) {
            Log::error("Return URL verification failed for {$orderId}", ['error' => $e->getMessage()]);

            return redirect()->route('wallet.add-money')
                ->with('error', 'Unable to verify order payment status: ' . $e->getMessage());
        }
    }

    /**
     * On-demand manual verification for pending deposit orders.
     */
    public function verify(Request $request, string $orderId): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        $transaction = WalletTransaction::where('reference_id', $orderId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($transaction->status === 'completed') {
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'status' => 'completed', 'message' => 'Order is already credited.']);
            }
            return back()->with('info', 'This deposit is already credited to your wallet.');
        }

        try {
            $order = $this->paymentService->getOrder($orderId);
            $orderStatus = strtoupper($order['order_status'] ?? '');

            if ($orderStatus === 'PAID') {
                // High-performance path: cf_order_id is already present on the order.
                // Avoid redundant getOrderPayments() network hop when cf_order_id is available.
                $paymentId = (string) ($order['cf_order_id'] ?? '');
                if (empty($paymentId)) {
                    $payments = $this->paymentService->getOrderPayments($orderId);
                    $successfulPayment = collect($payments)->firstWhere('payment_status', 'SUCCESS');
                    $paymentId = (string) ($successfulPayment['cf_payment_id'] ?? $orderId);
                }

                $orderAmountPaise = Wallet::rupeesToPaise($order['order_amount'] ?? 0);

                $this->walletService->creditDeposit($orderId, $orderAmountPaise, $paymentId, [
                    'verified_via' => 'manual_verify_button',
                    'cashfree_order' => $order,
                ]);

                if ($request->wantsJson()) {
                    return response()->json(['success' => true, 'status' => 'completed', 'message' => 'Payment verified and credited!']);
                }
                return back()->with('success', 'Payment verified! Wallet has been credited.');
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'status' => $orderStatus, 'message' => "Order status on Cashfree is: {$orderStatus}"]);
            }
            return back()->with('warning', "Order is not paid yet. Current status: {$orderStatus}");
        } catch (Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Cashfree Payment Gateway Webhook handler.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->json('data') ?? $request->all();
        $order = $payload['order'] ?? $payload;
        $orderId = $order['order_id'] ?? $request->json('order_id');

        if (!$orderId) {
            return response()->json(['status' => 'ignored', 'message' => 'No order_id in webhook payload'], 200);
        }

        try {
            // Strictly fetch official state directly from Cashfree to prevent payload spoofing
            $verifiedOrder = $this->paymentService->getOrder($orderId);
            $orderStatus = strtoupper($verifiedOrder['order_status'] ?? '');

            if ($orderStatus === 'PAID') {
                $payments = $this->paymentService->getOrderPayments($orderId);
                $successfulPayment = collect($payments)->firstWhere('payment_status', 'SUCCESS');
                $paymentId = (string) ($successfulPayment['cf_payment_id'] ?? $orderId);

                $orderAmountPaise = Wallet::rupeesToPaise($verifiedOrder['order_amount'] ?? 0);

                $this->walletService->creditDeposit($orderId, $orderAmountPaise, $paymentId, [
                    'verified_via' => 'pg_webhook',
                    'webhook_payload' => $request->all(),
                ]);
            }

            return response()->json(['status' => 'OK']);
        } catch (Throwable $e) {
            Log::error('PG Webhook handling exception for order ' . $orderId, [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
