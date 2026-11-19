<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\StoreBankAccountRequest;
use App\Models\BankAccount;
use App\Services\CashfreePayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class BankAccountController extends Controller
{
    public function __construct(
        protected CashfreePayoutService $payoutService
    ) {}

    /**
     * List user's saved bank accounts.
     */
    public function index(): View
    {
        $accounts = auth()->user()->bankAccounts()->latest()->get();

        return view('wallet.bank-accounts', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Store and automatically verify a new bank account via Cashfree.
     */
    public function store(StoreBankAccountRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $accountNumber = $request->input('account_number');
        $hash = BankAccount::makeAccountHash($accountNumber);

        // Prevent duplicate bank account entries for this user
        $exists = BankAccount::where('user_id', $user->id)
            ->where('account_number_hash', $hash)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'This bank account is already registered in your wallet.');
        }

        $bankAccount = BankAccount::create([
            'user_id' => $user->id,
            'account_holder_name' => $request->input('account_holder_name'),
            'account_number' => $accountNumber,
            'account_number_last4' => substr($accountNumber, -4),
            'account_number_hash' => $hash,
            'ifsc_code' => strtoupper($request->input('ifsc_code')),
            'bank_name' => $request->input('bank_name'),
            'is_verified' => false,
            'verification_status' => 'pending',
        ]);

        // Attempt verification and activation via Cashfree Payout Service
        $result = $this->payoutService->verifyAndActivateAccount($bankAccount, $user);

        if ($result['success']) {
            return redirect()->route('wallet.bank-accounts.index')
                ->with('success', $result['message']);
        }

        $flashType = ($result['status'] === 'invalid') ? 'warning' : 'info';

        return redirect()->route('wallet.bank-accounts.index')
            ->with($flashType, $result['message']);
    }

    /**
     * Retry verification for an existing bank account.
     */
    public function verify(BankAccount $bankAccount): RedirectResponse
    {
        Gate::authorize('verify', $bankAccount);

        $result = $this->payoutService->verifyAndActivateAccount($bankAccount, auth()->user());

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        $flashType = ($result['status'] === 'invalid') ? 'error' : 'warning';

        return back()->with($flashType, $result['message']);
    }

    /**
     * Delete a saved bank account.
     */
    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        Gate::authorize('delete', $bankAccount);

        // Check if there are active pending/processing withdrawals for this account
        $hasActiveWithdrawals = $bankAccount->withdrawals()
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($hasActiveWithdrawals) {
            return back()->with('error', 'Cannot delete this bank account while a withdrawal is in progress.');
        }

        $bankAccount->delete();

        return redirect()->route('wallet.bank-accounts.index')
            ->with('success', 'Bank account removed from your wallet.');
    }
}
