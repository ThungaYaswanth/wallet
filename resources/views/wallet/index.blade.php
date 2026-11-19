@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row g-4">
    <!-- Hero Navy Balance Card -->
    <div class="col-12">
        <div class="card card-balance-hero p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col-md-7 mb-3 mb-md-0">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="balance-label">Available Balance</span>
                        <button type="button" class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" id="btn-refresh-balance" title="Refresh live balance">
                            <i class="bi bi-arrow-clockwise fs-5"></i>
                        </button>
                    </div>
                    <div class="balance-amount text-white mb-2" id="wallet-available-balance">
                        {{ $wallet->formatted_available_balance }}
                    </div>
                    <div class="d-flex align-items-center text-white-50 small {{ $wallet->reserved_balance > 0 ? '' : 'd-none' }}" id="wallet-reserved-container">
                        <i class="bi bi-lock-fill text-warning me-1"></i>
                        <span>Reserved for processing transfers: </span>
                        <strong class="text-white ms-1" id="wallet-reserved-balance">{{ $wallet->formatted_reserved_balance }}</strong>
                    </div>
                </div>
                <div class="col-md-5 text-md-end d-flex flex-wrap gap-2 justify-content-md-end">
                    <a href="{{ route('wallet.add-money') }}" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> Add Money
                    </a>
                    <a href="{{ route('wallet.withdraw.create') }}" class="btn btn-light px-4 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-arrow-up-right me-1"></i> Withdraw
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 3 Summary Metric Cards -->
    <div class="col-md-4">
        <div class="card card-metric p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-arrow-down-left-circle-fill"></i>
                </div>
                <div>
                    <div class="metric-value">{{ $totalAddedFormatted }}</div>
                    <div class="metric-label">Total Money Added</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-metric p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-arrow-up-right-circle-fill"></i>
                </div>
                <div>
                    <div class="metric-value">{{ $totalWithdrawnFormatted }}</div>
                    <div class="metric-label">Total Withdrawn</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-metric p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="metric-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-bank2"></i>
                </div>
                <div>
                    <div class="metric-value">{{ $verifiedAccountsCount }}</div>
                    <div class="metric-label">Verified Bank Accounts</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="col-lg-8">
        <div class="card card-flat">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Recent Transactions</h6>
                <a href="{{ route('wallet.transactions') }}" class="small text-primary text-decoration-none fw-semibold">
                    View All <i class="bi bi-chevron-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-secondary">
                        <tr>
                            <th class="ps-3">Type</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentTransactions as $tx)
                            <tr>
                                <td class="ps-3">
                                    @if ($tx->type === 'credit')
                                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold">
                                            <i class="bi bi-arrow-down-left"></i> Credit
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold">
                                            <i class="bi bi-arrow-up-right"></i> Debit
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <code class="text-secondary small">{{ Str::limit($tx->reference_id, 14) }}</code>
                                </td>
                                <td class="small">{{ $tx->description ?: ucfirst($tx->transaction_type) }}</td>
                                <td class="fw-bold {{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->type === 'credit' ? '+' : '-' }}{{ $tx->formatted_amount }}
                                </td>
                                <td>
                                    <span class="badge badge-status badge-status-{{ $tx->status }}">
                                        {{ $tx->status }}
                                    </span>
                                </td>
                                <td class="text-end pe-3 text-secondary small">
                                    {{ $tx->created_at->format('d M, H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">
                                    No transactions recorded yet. <a href="{{ route('wallet.add-money') }}">Add money</a> to start.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Quick Links & Recent Withdrawals -->
    <div class="col-lg-4">
        <!-- Compact Payout Status List (shown only when withdrawals exist) -->
        @if ($recentWithdrawals->isNotEmpty())
            <div class="card card-flat mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Recent Payouts</h6>
                    <a href="{{ route('wallet.withdraw.create') }}" class="small text-primary text-decoration-none fw-semibold">
                        Withdraw Page
                    </a>
                </div>
                <div class="list-group list-group-flush">
                    @foreach ($recentWithdrawals as $w)
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark">{{ $w->formatted_amount }}</span>
                                <span class="badge badge-status badge-status-{{ $w->status }}">{{ $w->status }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small text-secondary">
                                <span>{{ $w->bankAccount->masked_account_number }} ({{ $w->bankAccount->ifsc_code }})</span>
                                <span>{{ $w->created_at->format('d M') }}</span>
                            </div>
                            @if ($w->utr)
                                <div class="mt-1 small text-success">
                                    <i class="bi bi-check2-all me-1"></i>UTR: <code>{{ $w->utr }}</code>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Quick Actions Card -->
        <div class="card card-flat p-3">
            <h6 class="fw-bold mb-3">Quick Navigation</h6>
            <div class="d-grid gap-2">
                <a href="{{ route('wallet.bank-accounts.index') }}" class="btn btn-outline-secondary text-start d-flex justify-content-between align-items-center py-2">
                    <span><i class="bi bi-bank text-primary me-2"></i> Manage Bank Accounts</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                <a href="{{ route('wallet.transactions') }}" class="btn btn-outline-secondary text-start d-flex justify-content-between align-items-center py-2">
                    <span><i class="bi bi-receipt text-primary me-2"></i> Transaction History</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
