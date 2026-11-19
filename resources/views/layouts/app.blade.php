<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Wallet') - {{ config('app.name', 'Cashfree Wallet') }}</title>

    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons via CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Wallet Stylesheet -->
    <link href="{{ asset('css/wallet.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="{{ Auth::check() ? 'has-sidebar' : (Request::is('login') || Request::is('register') || Request::is('password/*') ? 'auth-layout' : 'guest-layout') }}">
    <div id="app" class="min-vh-100 d-flex flex-column">
        @auth
            <!-- Desktop Fixed Sidebar -->
            <aside class="app-sidebar d-none d-lg-block border-end bg-white">
                @include('partials.sidebar')
            </aside>

            <!-- Mobile Offcanvas Sidebar -->
            <div class="offcanvas offcanvas-start border-0" tabindex="-1" id="sidebarOffcanvas" aria-label="Sidebar Navigation" style="width: 270px;">
                <div class="offcanvas-body p-0 bg-white">
                    @include('partials.sidebar')
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="app-main-wrapper flex-grow-1 d-flex flex-column min-vh-100">
                @include('partials.navbar')

                <main class="py-4 flex-grow-1">
                    <div class="container-fluid px-3 px-md-4">
                        @include('partials.alerts')
                        @yield('content')
                    </div>
                </main>

                <footer class="py-3 text-center text-muted border-top bg-white">
                    <div class="container-fluid px-3 px-md-4">
                        <small>&copy; {{ date('Y') }} Cashfree Wallet Management System &middot; Secured with Cashfree PG &amp; Payouts V2</small>
                    </div>
                </footer>
            </div>
        @else
            @if (Request::is('login') || Request::is('register') || Request::is('password/*'))
                <!-- Immersive 2-Column Split Authentication View (Exact Figma/Dribbble Design) -->
                <div class="auth-fullscreen-wrapper flex-grow-1 d-flex align-items-center justify-content-center p-0 p-lg-4">
                    @yield('content')
                </div>
            @else
                <!-- Guest Full Page View -->
                <div class="flex-grow-1 d-flex flex-column min-vh-100">
                    @include('partials.navbar')

                    <main class="py-5 flex-grow-1 d-flex align-items-center">
                        <div class="container">
                            @include('partials.alerts')
                            @yield('content')
                        </div>
                    </main>

                    <footer class="py-3 text-center text-muted border-top bg-white">
                        <div class="container">
                            <small>&copy; {{ date('Y') }} Cashfree Wallet Management System &middot; Secured with Cashfree PG &amp; Payouts V2</small>
                        </div>
                    </footer>
                </div>
            @endif
        @endauth
    </div>

    <!-- Bootstrap 5 Bundle with Popper via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Custom Wallet JavaScript -->
    <script src="{{ asset('js/wallet.js') }}"></script>
    @stack('scripts')
</body>
</html>
