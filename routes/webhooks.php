<?php

use App\Http\Controllers\Wallet\WalletPaymentController;
use App\Http\Controllers\Wallet\WalletWithdrawalController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/cashfree/payment', [WalletPaymentController::class, 'webhook'])
    ->middleware(['throttle:cashfree-webhooks', 'cashfree.webhook:payment'])
    ->name('webhooks.cashfree.payment');

Route::post('/webhooks/cashfree/payout', [WalletWithdrawalController::class, 'webhook'])
    ->middleware(['throttle:cashfree-webhooks', 'cashfree.webhook:payout'])
    ->name('webhooks.cashfree.payout');
