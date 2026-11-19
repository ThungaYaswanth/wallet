<?php

use App\Http\Controllers\Wallet\BankAccountController;
use App\Http\Controllers\Wallet\WalletController;
use App\Http\Controllers\Wallet\WalletPaymentController;
use App\Http\Controllers\Wallet\WalletWithdrawalController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Authentication routes
Auth::routes();

// Redirect entry points
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('wallet.index')
        : redirect()->route('login');
});

Route::get('/home', function () {
    return redirect()->route('wallet.index');
})->name('home');

// Auth-protected Wallet routes
Route::middleware(['auth'])->prefix('wallet')->name('wallet.')->group(function () {
    // Dashboard & Balance
    Route::get('/', [WalletController::class, 'index'])->name('index');
    Route::get('/balance', [WalletController::class, 'balance'])->name('balance');
    Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');

    // Add Money / Cashfree Payment Gateway
    Route::get('/add-money', [WalletPaymentController::class, 'create'])->name('add-money');
    Route::post('/add-money', [WalletPaymentController::class, 'store'])
        ->middleware('throttle:wallet-payments')
        ->name('deposit.store');
    Route::get('/add-money/return', [WalletPaymentController::class, 'returnUrl'])->name('deposit.return');
    Route::post('/orders/{orderId}/verify', [WalletPaymentController::class, 'verify'])->name('deposit.verify');

    // Bank Account Management
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('/bank-accounts', [BankAccountController::class, 'store'])
        ->middleware('throttle:wallet-bank-accounts')
        ->name('bank-accounts.store');
    Route::post('/bank-accounts/{bankAccount}/verify', [BankAccountController::class, 'verify'])->name('bank-accounts.verify');
    Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

    // Withdraw Funds / Cashfree Payouts
    Route::get('/withdraw', [WalletWithdrawalController::class, 'create'])->name('withdraw.create');
    Route::post('/withdraw', [WalletWithdrawalController::class, 'store'])
        ->middleware('throttle:wallet-withdrawals')
        ->name('withdraw.store');
    Route::post('/withdrawals/{withdrawal}/sync', [WalletWithdrawalController::class, 'sync'])->name('withdrawals.sync');
});
