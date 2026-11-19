@extends('layouts.app')

@section('title', 'Add Money')

@push('styles')
    <!-- Preconnect & DNS Prefetch to eliminate Cashfree TLS and DNS resolution latency -->
    <link rel="preconnect" href="https://sdk.cashfree.com" crossorigin>
    <link rel="preconnect" href="https://sandbox.cashfree.com" crossorigin>
    <link rel="dns-prefetch" href="https://sdk.cashfree.com">
    <link rel="dns-prefetch" href="https://sandbox.cashfree.com">
@endpush

@section('content')
<div class="row g-4 justify-content-center">
    <div class="col-lg-5">
        <div class="card card-flat p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                    <i class="bi bi-wallet2 fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Add Money to Wallet</h5>
                    <p class="text-secondary small mb-0">Current Balance: <strong class="text-dark">{{ $wallet->formatted_available_balance }}</strong></p>
                </div>
            </div>

            <!-- Error message container (populated by wallet-checkout.js) -->
            <div id="deposit-error-alert" class="alert alert-danger d-none" role="alert"></div>

            <form id="form-add-money" data-cashfree-env="{{ $cashfreeEnv }}">
                @csrf

                <div class="mb-3">
                    <label for="deposit-amount-input" class="form-label small fw-semibold text-secondary">
                        Enter Amount (₹)
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white fw-bold text-dark">₹</span>
                        <input type="number" step="1" min="1" max="500000" id="deposit-amount-input" name="amount" class="form-control fw-bold fs-4" placeholder="500" required autofocus>
                    </div>
                    <div class="form-text small text-secondary">Min: ₹1 &middot; Max: ₹5,00,000 per transaction</div>
                </div>

                <!-- Quick Select Amount Chips -->
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary d-block mb-2">Quick Select</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="amount-chip" data-amount="100">+₹100</button>
                        <button type="button" class="amount-chip" data-amount="500">+₹500</button>
                        <button type="button" class="amount-chip" data-amount="1000">+₹1,000</button>
                        <button type="button" class="amount-chip" data-amount="2000">+₹2,000</button>
                        <button type="button" class="amount-chip" data-amount="5000">+₹5,000</button>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="deposit-phone-input" class="form-label small fw-semibold text-secondary">
                        Mobile Number (Optional)
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary"><i class="bi bi-telephone"></i></span>
                        <input type="tel" id="deposit-phone-input" name="customer_phone" class="form-control" value="{{ Auth::user()->phone }}" placeholder="9876543210">
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" id="btn-submit-deposit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                        <i class="bi bi-shield-check me-1"></i> Proceed to Pay with Cashfree
                    </button>
                </div>
            </form>

            <div class="text-center mt-3">
                <small class="text-muted d-flex align-items-center justify-content-center gap-1">
                    <i class="bi bi-lock-fill text-success"></i> 256-bit Encrypted Checkout &middot; Instant Credit
                </small>
            </div>
        </div>
    </div>

    <!-- Recent Deposits Table with Verify Button -->
    <div class="col-lg-7">
        <div class="card card-flat">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">Recent Deposit Orders</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-secondary">
                        <tr>
                            <th class="ps-3">Order ID</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentDeposits as $deposit)
                            <tr>
                                <td class="ps-3">
                                    <code class="small text-secondary">{{ Str::limit($deposit->reference_id, 14) }}</code>
                                </td>
                                <td class="fw-bold text-dark">{{ $deposit->formatted_amount }}</td>
                                <td>
                                    <span class="badge badge-status badge-status-{{ $deposit->status }}">
                                        {{ $deposit->status }}
                                    </span>
                                </td>
                                <td class="small text-secondary">{{ $deposit->created_at->format('d M, H:i') }}</td>
                                <td class="text-end pe-3">
                                    @if ($deposit->status === 'pending')
                                        <form action="{{ route('wallet.deposit.verify', $deposit->reference_id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-primary btn-sm py-0 px-2 small">
                                                <i class="bi bi-arrow-repeat"></i> Verify
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-success small fw-semibold"><i class="bi bi-check-circle"></i> Credited</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">
                                    No deposit history found. Enter an amount on the left to add money.
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

@push('scripts')
    <!-- Official Cashfree JS SDK v3 -->
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <script src="{{ asset('js/wallet-checkout.js') }}"></script>
@endpush
