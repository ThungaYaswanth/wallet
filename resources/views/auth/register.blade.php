@extends('layouts.app')

@section('title', 'Create Account')

@section('content')
<div class="auth-split-card">
    <div class="row g-0">
        <!-- Left Hero Branding Column -->
        <div class="col-lg-5 col-xl-5 d-none d-lg-block">
            @include('partials.auth-hero')
        </div>

        <!-- Right Register Form Column -->
        <div class="col-lg-7 col-xl-7 auth-form-panel">
            <div class="auth-card-container">
                <!-- Mobile Only Top Brand -->
                <div class="d-lg-none text-center mb-4">
                    <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none text-dark fw-bold fs-4">
                        <i class="bi bi-wallet2 text-primary"></i> Cashfree Wallet
                    </a>
                </div>

                <!-- Top Center Icon Badge -->
                <div class="text-center mb-4">
                    <div class="auth-card-icon-badge mx-auto mb-3">
                        <i class="bi bi-person-plus fs-2 text-primary"></i>
                    </div>
                    <h2 class="fw-bold text-dark mb-1">Create Account</h2>
                    <p class="text-secondary small mb-0">Join Cashfree Wallet and manage your money securely.</p>
                </div>

                @include('partials.alerts')

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-dark d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-person text-secondary"></i> Full Name
                        </label>
                        <input id="name" type="text" class="form-control form-control-auth @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus placeholder="John Doe">
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-dark d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-envelope text-secondary"></i> Email Address
                        </label>
                        <input id="email" type="email" class="form-control form-control-auth @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="name@example.com">
                        @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Mobile Number -->
                    <div class="mb-3">
                        <label for="phone" class="form-label small fw-semibold text-dark d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-telephone text-secondary"></i> Mobile Number (Optional)
                        </label>
                        <input id="phone" type="tel" class="form-control form-control-auth @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" placeholder="9876543210">
                        @error('phone')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password with Eye Toggle -->
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold text-dark d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-lock text-secondary"></i> Password (Min. 8 characters)
                        </label>
                        <div class="position-relative">
                            <input id="password" type="password" class="form-control form-control-auth pe-5 @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="••••••••">
                            <button type="button" class="btn btn-link text-secondary position-absolute top-50 end-0 translate-middle-y me-2 p-1 text-decoration-none btn-toggle-password" data-target="password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Confirm Password with Eye Toggle -->
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label small fw-semibold text-dark d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-lock-fill text-secondary"></i> Confirm Password
                        </label>
                        <div class="position-relative">
                            <input id="password_confirmation" type="password" class="form-control form-control-auth pe-5" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••">
                            <button type="button" class="btn btn-link text-secondary position-absolute top-50 end-0 translate-middle-y me-2 p-1 text-decoration-none btn-toggle-password" data-target="password_confirmation" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-auth-primary d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-arrow-right"></i> Register &amp; Activate Wallet
                        </button>
                    </div>

                    <!-- OR Divider -->
                    <div class="auth-divider my-3">
                        <span>or</span>
                    </div>

                    <!-- Sign In to Existing Account -->
                    <div class="d-grid">
                        <a href="{{ route('login') }}" class="btn btn-auth-outline d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In to Existing Account
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
