@extends('layouts.app')

@section('title', 'Withdraw Funds')

@section('content')
<div class="row g-4">
    <!-- Withdraw Funds Form -->
    <div class="col-lg-5">
        <div class="card card-flat p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                    <i class="bi bi-arrow-up-right-circle fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Withdraw to Bank</h5>
                    <p class="text-secondary small mb-0">
                        Available Balance: <strong class="text-dark">{{ $wallet->formatted_available_balance }}</strong>
                    </p>
                </div>
            </div>

            @if ($verifiedAccounts->isEmpty())
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div>
                        You do not have any verified bank accounts yet.
                        <a href="{{ route('wallet.bank-accounts.index') }}" class="alert-link d-block mt-1">
                            Add and verify a bank account first &rarr;
                        </a>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('wallet.withdraw.store') }}">
                    @csrf
                    <!-- UUID Idempotency Key -->
                    <input type="hidden" name="idempotency_key" value="{{ $newIdempotencyKey }}">

                    <!-- Bank Account Select -->
                    <div class="mb-3">
                        <label for="bank_account_id" class="form-label small fw-semibold text-secondary">
                            Select Verified Destination Account
                        </label>
                        <select name="bank_account_id" id="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror" required>
                            <option value="">-- Choose Bank Account --</option>
                            @foreach ($verifiedAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('bank_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->bank_name ?: 'Bank' }} &middot; {{ $acc->masked_account_number }} ({{ $acc->account_holder_name }})
                                </option>
                            @endforeach
                        </select>
                        @error('bank_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Withdrawal Amount with Max Button -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="withdraw-amount-input" class="form-label small fw-semibold text-secondary mb-0">
                                Withdrawal Amount (₹)
                            </label>
                            <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-0 fw-semibold" id="btn-max-amount" data-max="{{ $wallet->available_balance_rupees }}">
                                Withdraw Max
                            </button>
                        </div>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white fw-bold text-dark">₹</span>
                            <input type="number" step="0.01" min="1" max="{{ $wallet->available_balance_rupees }}" id="withdraw-amount-input" name="amount" class="form-control fw-bold fs-4 @error('amount') is-invalid @enderror" placeholder="100.00" value="{{ old('amount') }}" required>
                        </div>
                        @error('amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text small text-secondary">
                            Min: ₹1 &middot; Max Available: {{ $wallet->formatted_available_balance }}
                        </div>
                    </div>

                    <div class="alert alert-light border small text-secondary mb-4 p-2">
                        <i class="bi bi-info-circle-fill text-primary me-1"></i>
                        Payouts are transferred via IMPS/NEFT using Cashfree Payouts V2. Funds are temporarily held in reserved balance while in-flight.
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm" {{ $wallet->balance <= 0 ? 'disabled' : '' }}>
                            <i class="bi bi-send-check me-1"></i> Initiate Instant Transfer
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <!-- Withdrawal History with Status Sync Button -->
    <div class="col-lg-7">
        <div class="card card-flat">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">Withdrawal History &amp; Status</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-secondary">
                        <tr>
                            <th class="ps-3">Transfer ID</th>
                            <th>Bank</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>UTR / Ref</th>
                            <th class="text-end pe-3">Sync</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentWithdrawals as $w)
                            <tr>
                                <td class="ps-3">
                                    <code class="small text-secondary">{{ Str::limit($w->transfer_id, 14) }}</code>
                                    <div class="text-muted small">{{ $w->created_at->format('d M, H:i') }}</div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">{{ $w->bankAccount->masked_account_number }}</div>
                                    <div class="text-muted small">{{ $w->bankAccount->ifsc_code }}</div>
                                </td>
                                <td class="fw-bold text-danger">-{{ $w->formatted_amount }}</td>
                                <td>
                                    <span class="badge badge-status badge-status-{{ $w->status }}">
                                        {{ $w->status }}
                                    </span>
                                </td>
                                <td class="small">
                                    @if ($w->utr)
                                        <code class="text-success small fw-bold">{{ $w->utr }}</code>
                                    @elseif ($w->failure_reason)
                                        <span class="text-danger small" title="{{ $w->failure_reason }}">{{ Str::limit($w->failure_reason, 20) }}</span>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <form action="{{ route('wallet.withdrawals.sync', $w->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm py-0 px-2 small" title="Sync status with Cashfree">
                                            <i class="bi bi-arrow-repeat"></i> Sync
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted small">
                                    No withdrawals requested yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
