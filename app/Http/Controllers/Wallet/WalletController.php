<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletBalanceResource;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Wallet Dashboard.
     */
    public function index(): View
    {
        $user = auth()->user();
        $summary = $this->walletService->getDashboardSummary($user);

        return view('wallet.index', [
            'wallet' => $summary['wallet'],
            'totalAddedFormatted' => $summary['total_added_formatted'],
            'totalWithdrawnFormatted' => $summary['total_withdrawn_formatted'],
            'verifiedAccountsCount' => $summary['verified_accounts_count'],
            'recentTransactions' => $summary['recent_transactions'],
            'recentWithdrawals' => $summary['recent_withdrawals'],
        ]);
    }

    /**
     * Live JSON balance endpoint for dynamic frontend polling/refresh.
     */
    public function balance(): JsonResponse
    {
        $wallet = auth()->user()->wallet()->firstOrCreate(
            ['user_id' => auth()->id()],
            ['balance' => 0, 'reserved_balance' => 0]
        );

        return response()->json(new WalletBalanceResource($wallet));
    }

    /**
     * Filterable transaction ledger history.
     */
    public function transactions(Request $request): View
    {
        $query = auth()->user()->walletTransactions()->latest();

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_id', 'like', "%{$search}%")
                    ->orWhere('gateway_reference_id', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            if (in_array($type, ['credit', 'debit'])) {
                $query->where('type', $type);
            }
        }

        if ($category = $request->input('category')) {
            $query->where('transaction_type', $category);
        }

        if ($status = $request->input('status')) {
            if (in_array($status, ['pending', 'completed', 'failed'])) {
                $query->where('status', $status);
            }
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $transactions = $query->paginate(15)->withQueryString();

        return view('wallet.transactions', [
            'transactions' => $transactions,
            'filters' => $request->only(['search', 'type', 'category', 'status', 'date_from', 'date_to']),
        ]);
    }
}
