<div class="sidebar-wrapper d-flex flex-column h-100 p-3">
    <!-- Brand Header -->
    <div class="sidebar-brand d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
        <a href="{{ route('wallet.index') }}" class="d-flex align-items-center text-decoration-none">
            <div class="brand-icon bg-primary text-white rounded-3 p-2 me-2 d-flex align-items-center justify-content-center">
                <i class="bi bi-wallet2 fs-5"></i>
            </div>
            <div>
                <div class="fw-bold text-dark fs-6 lh-1">Cashfree Wallet</div>
                <small class="text-secondary small">{{ strtoupper(config('cashfree.payment_gateway.env', 'sandbox')) }} MODE</small>
            </div>
        </a>
    </div>

    <!-- Mini Balance Card in Sidebar -->
    @auth
        @php
            $sidebarWallet = Auth::user()->wallet;
        @endphp
        <div class="sidebar-balance-widget p-3 rounded-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small text-secondary fw-semibold">AVAILABLE BALANCE</span>
                <button type="button" class="btn btn-sm btn-link text-secondary p-0 text-decoration-none btn-refresh-balance-trigger" title="Refresh balance">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
            <div class="fs-5 fw-bold text-dark sidebar-available-balance">
                {{ $sidebarWallet ? $sidebarWallet->formatted_available_balance : '₹0.00' }}
            </div>
            @if ($sidebarWallet && $sidebarWallet->reserved_balance > 0)
                <div class="small text-warning mt-1">
                    <i class="bi bi-lock-fill"></i> Reserved: {{ $sidebarWallet->formatted_reserved_balance }}
                </div>
            @endif
        </div>
    @endauth

    <!-- Navigation Menu -->
    <div class="sidebar-nav flex-grow-1">
        <div class="text-uppercase small fw-bold text-muted mb-2 px-2" style="font-size: 0.7rem; letter-spacing: 0.05em;">Menu</div>
        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link sidebar-link {{ request()->routeIs('wallet.index') ? 'active' : '' }}" href="{{ route('wallet.index') }}">
                    <i class="bi bi-speedometer2 me-2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link sidebar-link {{ request()->routeIs('wallet.add-money') ? 'active' : '' }}" href="{{ route('wallet.add-money') }}">
                    <i class="bi bi-plus-circle me-2"></i>
                    <span>Add Money</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link sidebar-link {{ request()->routeIs('wallet.withdraw.create') ? 'active' : '' }}" href="{{ route('wallet.withdraw.create') }}">
                    <i class="bi bi-arrow-up-right-circle me-2"></i>
                    <span>Withdraw Funds</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link sidebar-link {{ request()->routeIs('wallet.bank-accounts.*') ? 'active' : '' }}" href="{{ route('wallet.bank-accounts.index') }}">
                    <i class="bi bi-bank me-2"></i>
                    <span>Bank Accounts</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link sidebar-link {{ request()->routeIs('wallet.transactions') ? 'active' : '' }}" href="{{ route('wallet.transactions') }}">
                    <i class="bi bi-receipt me-2"></i>
                    <span>Transactions</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Bottom User Section -->
    @auth
        <div class="sidebar-user pt-3 border-top mt-auto">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center overflow-hidden me-2">
                    <div class="user-avatar bg-light border text-primary fw-bold rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="overflow-hidden">
                        <div class="fw-semibold text-dark text-truncate small">{{ Auth::user()->name }}</div>
                        <div class="text-secondary text-truncate small" style="font-size: 0.75rem;">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm p-1 px-2" title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    @endauth
</div>
