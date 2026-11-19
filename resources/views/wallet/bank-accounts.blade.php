@extends('layouts.app')

@section('title', 'Bank Accounts')

@section('content')
<div class="row g-4">
    <!-- Add Bank Account Form -->
    <div class="col-lg-5">
        <div class="card card-flat p-4">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                    <i class="bi bi-bank fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Add Bank Account</h5>
                    <p class="text-secondary small mb-0">Verified accounts can receive instant withdrawals</p>
                </div>
            </div>

            <form method="POST" action="{{ route('wallet.bank-accounts.store') }}" autocomplete="off">
                @csrf

                <div class="mb-3">
                    <label for="account_holder_name" class="form-label small fw-semibold text-secondary">
                        Account Holder Name (as per bank passbook)
                    </label>
                    <input type="text" id="account_holder_name" name="account_holder_name" class="form-control @error('account_holder_name') is-invalid @enderror" value="{{ old('account_holder_name') }}" required placeholder="e.g. Rahul Sharma" autocomplete="off">
                    @error('account_holder_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="account_number" class="form-label small fw-semibold text-secondary">
                        Account Number
                    </label>
                    <input type="text" id="account_number" name="account_number" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number') }}" required placeholder="9 to 18 digit account number" autocomplete="off" pattern="[0-9]{9,18}">
                    @error('account_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="account_number_confirmation" class="form-label small fw-semibold text-secondary">
                        Confirm Account Number
                    </label>
                    <input type="text" id="account_number_confirmation" name="account_number_confirmation" class="form-control @error('account_number_confirmation') is-invalid @enderror" required placeholder="Re-enter bank account number" autocomplete="off" pattern="[0-9]{9,18}">
                    @error('account_number_confirmation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-7">
                        <label for="ifsc_code" class="form-label small fw-semibold text-secondary">
                            IFSC Code
                        </label>
                        <input type="text" id="ifsc_code" name="ifsc_code" class="form-control text-uppercase @error('ifsc_code') is-invalid @enderror" value="{{ old('ifsc_code') }}" required placeholder="e.g. HDFC0001234" maxlength="11" autocomplete="off">
                        @error('ifsc_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-5">
                        <label for="bank_name" class="form-label small fw-semibold text-secondary">
                            Bank Name (Optional)
                        </label>
                        <input type="text" id="bank_name" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name') }}" placeholder="e.g. HDFC Bank" autocomplete="off">
                        @error('bank_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="alert alert-light border small text-secondary mb-4 p-2">
                    <i class="bi bi-shield-lock-fill text-primary me-1"></i>
                    Account numbers are stored with AES-256-CBC encryption and verified directly via Cashfree Verification Suite.
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                        <i class="bi bi-patch-check me-1"></i> Save &amp; Verify Bank Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Saved Bank Accounts List -->
    <div class="col-lg-7">
        <div class="card card-flat">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">Saved Bank Accounts</h6>
            </div>
            <div class="p-3">
                <div class="d-flex flex-column gap-3">
                    @forelse ($accounts as $account)
                        <div class="bank-account-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">
                                        <i class="bi bi-bank2 text-primary me-1"></i>
                                        {{ $account->bank_name ?: 'Bank Account' }}
                                    </h6>
                                    @if ($account->branch_name)
                                        <small class="text-secondary">{{ $account->branch_name }}</small>
                                    @endif
                                </div>
                                <div>
                                    @if ($account->is_verified)
                                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i> Verified
                                        </span>
                                    @elseif ($account->verification_status === 'pending')
                                        <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold">
                                            <i class="bi bi-clock me-1"></i> Verification Pending
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold">
                                            <i class="bi bi-x-circle me-1"></i> Unverified
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="row g-2 text-secondary small mb-3">
                                <div class="col-sm-6">
                                    <span class="text-muted">Account Holder:</span>
                                    <span class="fw-semibold text-dark">{{ $account->account_holder_name }}</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted">Account:</span>
                                    <span class="fw-semibold text-dark font-monospace">{{ $account->masked_account_number }}</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted">IFSC:</span>
                                    <span class="fw-semibold text-dark font-monospace">{{ $account->ifsc_code }}</span>
                                </div>
                                @if ($account->name_at_bank)
                                    <div class="col-sm-6">
                                        <span class="text-muted">Bank Records:</span>
                                        <span class="fw-semibold text-success">{{ $account->name_at_bank }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex justify-content-end gap-2 border-top pt-2">
                                @if (!$account->is_verified)
                                    <form action="{{ route('wallet.bank-accounts.verify', $account->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm py-1 px-3">
                                            <i class="bi bi-arrow-repeat me-1"></i> Retry Verify
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('wallet.bank-accounts.destroy', $account->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this bank account?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-3">
                                        <i class="bi bi-trash me-1"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5 text-muted small">
                            <i class="bi bi-bank fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            No bank accounts saved yet. Add your bank details to enable withdrawals.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
