@extends('layouts.app')

@section('title', 'Transaction History')

@section('content')
<div class="card card-flat mb-4">
    <div class="card-body p-3">
        <!-- Compact Single-Row Filter Bar -->
        <form method="GET" action="{{ route('wallet.transactions') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-secondary"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search reference or desc..." value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>

            <div class="col-6 col-md-2">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="credit" {{ ($filters['type'] ?? '') === 'credit' ? 'selected' : '' }}>Credit (+)</option>
                    <option value="debit" {{ ($filters['type'] ?? '') === 'debit' ? 'selected' : '' }}>Debit (-)</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <option value="deposit" {{ ($filters['category'] ?? '') === 'deposit' ? 'selected' : '' }}>Deposit</option>
                    <option value="withdrawal" {{ ($filters['category'] ?? '') === 'withdrawal' ? 'selected' : '' }}>Withdrawal</option>
                    <option value="withdrawal_reversal" {{ ($filters['category'] ?? '') === 'withdrawal_reversal' ? 'selected' : '' }}>Reversal</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3 flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('wallet.transactions') }}" class="btn btn-outline-secondary btn-sm px-2" title="Reset Filters">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Ledger Table -->
<div class="card card-flat">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">Wallet Ledger</h6>
        <span class="text-secondary small">{{ $transactions->total() }} total entries</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-secondary">
                <tr>
                    <th class="ps-3">Type</th>
                    <th>Reference ID</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Timestamp</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $tx)
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
                            <code class="text-secondary small">{{ $tx->reference_id }}</code>
                            @if ($tx->gateway_reference_id)
                                <div class="text-muted small">Ref: {{ $tx->gateway_reference_id }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border small">
                                {{ ucfirst(str_replace('_', ' ', $tx->transaction_type)) }}
                            </span>
                        </td>
                        <td class="small text-secondary">{{ $tx->description ?: '-' }}</td>
                        <td class="fw-bold {{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }}">
                            {{ $tx->type === 'credit' ? '+' : '-' }}{{ $tx->formatted_amount }}
                        </td>
                        <td>
                            <span class="badge badge-status badge-status-{{ $tx->status }}">
                                {{ $tx->status }}
                            </span>
                        </td>
                        <td class="text-end pe-3 text-secondary small">
                            {{ $tx->created_at->format('d M Y, H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted small">
                            No transactions found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($transactions->hasPages())
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-center">
            {{ $transactions->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
