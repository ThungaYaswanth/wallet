<nav class="navbar navbar-expand navbar-clean sticky-top px-3 py-2">
    <div class="container-fluid px-0">
        @auth
            <!-- Mobile Sidebar Toggle -->
            <button class="btn btn-light border d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Toggle navigation">
                <i class="bi bi-list fs-5"></i>
            </button>

            <!-- Page Title / Section Indicator -->
            <div class="d-flex align-items-center">
                <span class="fw-semibold text-secondary small d-none d-sm-inline">
                    @if (request()->routeIs('wallet.index'))
                        <i class="bi bi-speedometer2 text-primary me-1"></i> Dashboard
                    @elseif (request()->routeIs('wallet.add-money'))
                        <i class="bi bi-plus-circle text-primary me-1"></i> Add Money
                    @elseif (request()->routeIs('wallet.withdraw.*'))
                        <i class="bi bi-arrow-up-right-circle text-primary me-1"></i> Withdraw Funds
                    @elseif (request()->routeIs('wallet.bank-accounts.*'))
                        <i class="bi bi-bank text-primary me-1"></i> Bank Accounts
                    @elseif (request()->routeIs('wallet.transactions'))
                        <i class="bi bi-receipt text-primary me-1"></i> Transactions
                    @endif
                </span>
            </div>

            <!-- Right Navbar Utilities -->
            <div class="ms-auto d-flex align-items-center gap-2">
                <!-- Top Balance Pill -->
                @php
                    $navWallet = Auth::user()->wallet;
                @endphp
                <div class="bg-light border rounded-pill px-3 py-1 d-flex align-items-center gap-2">
                    <span class="small text-muted fw-semibold">Balance:</span>
                    <span class="fw-bold text-dark small nav-available-balance">
                        {{ $navWallet ? $navWallet->formatted_available_balance : '₹0.00' }}
                    </span>
                    <button type="button" class="btn btn-sm btn-link text-secondary p-0 text-decoration-none btn-refresh-balance-trigger" title="Refresh balance">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>

                <!-- Add Money Quick Action Pill -->
                <a href="{{ route('wallet.add-money') }}" class="btn btn-primary btn-sm rounded-pill px-3 d-none d-md-inline-flex align-items-center shadow-sm">
                    <i class="bi bi-plus me-1"></i> Add Money
                </a>

                <!-- User Dropdown Menu -->
                <div class="dropdown">
                    <button class="btn btn-light border rounded-circle p-1 d-flex align-items-center justify-content-center" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="width: 36px; height: 36px;">
                        <span class="fw-bold text-primary small">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border" aria-labelledby="userMenuDropdown">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-semibold text-dark small">{{ Auth::user()->name }}</div>
                            <div class="text-secondary small">{{ Auth::user()->email }}</div>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 small" href="{{ route('wallet.index') }}">
                                <i class="bi bi-speedometer2 me-2 text-secondary"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 small" href="{{ route('wallet.bank-accounts.index') }}">
                                <i class="bi bi-bank me-2 text-secondary"></i> Bank Accounts
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 small" href="{{ route('wallet.transactions') }}">
                                <i class="bi bi-receipt me-2 text-secondary"></i> Transactions
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger small">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        @else
            <!-- Guest Top Bar -->
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                <i class="bi bi-wallet2 text-primary me-2 fs-4"></i>
                <span class="fw-bold">Cashfree Wallet</span>
            </a>
            <div class="ms-auto d-flex gap-2">
                <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('login') }}">Sign In</a>
                <a class="btn btn-primary btn-sm px-3" href="{{ route('register') }}">Create Account</a>
            </div>
        @endauth
    </div>
</nav>
