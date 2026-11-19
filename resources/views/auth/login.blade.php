@extends('layouts.app')

@section('title', 'Sign In')

@section('content')
<div class="auth-split-card">
    <div class="row g-0">
        <!-- Left Hero Branding Column -->
        <div class="col-lg-5 col-xl-5 d-none d-lg-block">
            @include('partials.auth-hero')
        </div>

        <!-- Right Sign In Form Column (Exact Layout Match) -->
        <div class="col-lg-7 col-xl-7 auth-form-panel">
            <div class="auth-card-container">
                <!-- Mobile Only Top Brand -->
                <div class="d-lg-none text-center mb-4">
                    <a href="{{ url('/') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none text-dark fw-bold fs-4">
                        <i class="bi bi-wallet2 text-primary"></i> Cashfree Wallet
                    </a>
                </div>

                <!-- Top Center Wallet Icon Badge -->
                <div class="text-center mb-4">
                    <div class="auth-card-icon-badge mx-auto mb-3">
                        <i class="bi bi-wallet2 fs-2 text-primary"></i>
                    </div>
                    <h2 class="fw-bold text-dark mb-1">Welcome Back</h2>
                    <p class="text-secondary small mb-0">Sign in to manage your wallet and transactions.</p>
                </div>

                @include('partials.alerts')

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-dark d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-envelope text-secondary"></i> Email Address
                        </label>
                        <input id="email" type="email" class="form-control form-control-auth @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="name@example.com">
                        @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password with Eye Toggle -->
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold text-dark d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-lock text-secondary"></i> Password
                        </label>
                        <div class="position-relative">
                            <input id="password" type="password" class="form-control form-control-auth pe-5 @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="••••••••">
                            <button type="button" class="btn btn-link text-secondary position-absolute top-50 end-0 translate-middle-y me-2 p-1 text-decoration-none btn-toggle-password" data-target="password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="mb-4 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label small text-secondary fw-medium" for="remember">
                                Remember Me
                            </label>
                        </div>
                        @if (Route::has('password.request'))
                            <a class="small text-primary fw-semibold text-decoration-none" href="{{ route('password.request') }}">
                                Forgot Password?
                            </a>
                        @endif
                    </div>

                    <!-- Sign In Button -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-auth-primary d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In
                        </button>
                    </div>

                    <!-- OR Divider -->
                    <div class="auth-divider my-3">
                        <span>or</span>
                    </div>

                    <!-- Create New Account Button -->
                    <div class="d-grid">
                        <a href="{{ route('register') }}" class="btn btn-auth-outline d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-person-plus"></i> Create New Account
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
