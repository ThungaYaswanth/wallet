<div class="auth-hero-panel p-4 p-md-5 d-flex flex-column justify-content-between h-100">
    <!-- Top Branding -->
    <div class="mb-4">
        <a href="{{ url('/') }}" class="auth-brand-logo">
            <div class="auth-brand-icon">
                <i class="bi bi-wallet2"></i>
            </div>
            <span>Cashfree Wallet</span>
        </a>
    </div>

    <!-- Main Value Proposition & Features -->
    <div class="my-auto py-3">
        <h1 class="auth-hero-title mb-3">
            Manage Your<br>Wallet &amp; Transactions<br>Securely
        </h1>
        <p class="auth-hero-subtitle mb-4">
            Add money, make payments, and track your transactions — all in one place with Cashfree.
        </p>

        <!-- Feature List with Glassmorphic Badges -->
        <div class="auth-feature-list mb-4">
            <div class="auth-feature-item">
                <span class="auth-feature-icon">
                    <i class="bi bi-lightning-charge"></i>
                </span>
                <span>Instant Add Money</span>
            </div>
            <div class="auth-feature-item">
                <span class="auth-feature-icon">
                    <i class="bi bi-bank"></i>
                </span>
                <span>Withdraw to Bank</span>
            </div>
            <div class="auth-feature-item">
                <span class="auth-feature-icon">
                    <i class="bi bi-clock-history"></i>
                </span>
                <span>Real-time Transactions</span>
            </div>
            <div class="auth-feature-item">
                <span class="auth-feature-icon">
                    <i class="bi bi-shield-check"></i>
                </span>
                <span>Secure &amp; Reliable</span>
            </div>
        </div>
    </div>

    <!-- 3D Blue Leather Wallet & Golden ₹ Coin Illustration -->
    <div class="mt-auto pt-2 text-center">
        <svg viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg" class="auth-wallet-illustration" aria-hidden="true">
            <defs>
                <!-- Wallet Front Blue Gradient -->
                <linearGradient id="walletBlue" x1="120" y1="100" x2="300" y2="270" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#3b82f6" />
                    <stop offset="45%" stop-color="#1d4ed8" />
                    <stop offset="100%" stop-color="#172554" />
                </linearGradient>
                <!-- Wallet Back Flap Gradient -->
                <linearGradient id="walletBackBlue" x1="110" y1="80" x2="280" y2="240" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#1e40af" />
                    <stop offset="100%" stop-color="#0f172a" />
                </linearGradient>
                <!-- Gold Coin Face Gradient -->
                <linearGradient id="goldCoin" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#fef08a" />
                    <stop offset="35%" stop-color="#fbbf24" />
                    <stop offset="85%" stop-color="#f59e0b" />
                    <stop offset="100%" stop-color="#d97706" />
                </linearGradient>
                <!-- Gold Coin Rim Gradient -->
                <linearGradient id="goldRim" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#fde047" />
                    <stop offset="100%" stop-color="#92400e" />
                </linearGradient>
                <!-- Wallet Strap Gradient -->
                <linearGradient id="strapGrad" x1="250" y1="160" x2="300" y2="220" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#2563eb" />
                    <stop offset="100%" stop-color="#1e3a8a" />
                </linearGradient>
                <!-- Strap Snap Button Gold -->
                <linearGradient id="goldButton" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#fef08a" />
                    <stop offset="100%" stop-color="#d97706" />
                </linearGradient>
                <!-- Soft Ambient Shadow -->
                <filter id="softShadow" x="-30%" y="-30%" width="160%" height="160%">
                    <feDropShadow dx="0" dy="14" stdDeviation="16" flood-color="#000000" flood-opacity="0.32" />
                </filter>
            </defs>

            <!-- Ground Ellipse Shadow -->
            <ellipse cx="210" cy="265" rx="150" ry="22" fill="#000000" fill-opacity="0.28" filter="url(#softShadow)" />

            <!-- Gold Coin Stack on Left -->
            <g class="coin-stack">
                <!-- Coin 1 (Bottom) -->
                <path d="M72,246 C72,252 89,257 110,257 C131,257 148,252 148,246 L148,255 C148,261 131,266 110,266 C89,266 72,261 72,255 Z" fill="url(#goldRim)" />
                <ellipse cx="110" cy="246" rx="38" ry="11" fill="url(#goldCoin)" />

                <!-- Coin 2 (Middle) -->
                <path d="M72,232 C72,238 89,243 110,243 C131,243 148,238 148,232 L148,241 C148,247 131,252 110,252 C89,252 72,247 72,241 Z" fill="url(#goldRim)" />
                <ellipse cx="110" cy="232" rx="38" ry="11" fill="url(#goldCoin)" />

                <!-- Coin 3 (Top) -->
                <path d="M75,218 C75,224 91,229 110,229 C129,229 145,224 145,218 L145,227 C145,233 129,238 110,238 C91,238 75,233 75,227 Z" fill="url(#goldRim)" />
                <ellipse cx="110" cy="218" rx="35" ry="10" fill="url(#goldCoin)" />
                <ellipse cx="110" cy="218" rx="28" ry="7" fill="none" stroke="#d97706" stroke-width="1.2" stroke-dasharray="2,2" opacity="0.6" />
            </g>

            <!-- Wallet Back Inner Wall -->
            <path d="M125,95 C125,84 135,76 146,78 L285,98 C296,100 304,110 304,122 L294,228 C294,240 284,249 272,249 L146,249 C134,249 125,240 125,228 Z" fill="url(#walletBackBlue)" />

            <!-- Main Gold ₹ Rupee Coin Emerging from Wallet -->
            <g transform="translate(202, 45)">
                <!-- Coin Outer Thick Rim -->
                <circle cx="48" cy="48" r="46" fill="url(#goldRim)" />
                <!-- Coin Front Face -->
                <circle cx="46" cy="46" r="42" fill="url(#goldCoin)" stroke="#fef08a" stroke-width="2" />
                <circle cx="46" cy="46" r="36" fill="none" stroke="#d97706" stroke-width="1.5" stroke-dasharray="3,3" opacity="0.6" />
                <!-- Rupee ₹ Symbol with 3D drop shadow -->
                <text x="46" y="58" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="36" font-weight="900" fill="#ffffff" text-anchor="middle" filter="drop-shadow(0 2px 4px rgba(180,83,9,0.7))">₹</text>
            </g>

            <!-- Floating Sparkle Stars -->
            <path d="M182,58 Q182,66 174,66 Q182,66 182,74 Q182,66 190,66 Q182,66 182,58 Z" fill="#fde047" />
            <path d="M318,65 Q318,72 312,72 Q318,72 318,79 Q318,72 324,72 Q318,72 318,65 Z" fill="#fef08a" />
            <circle cx="160" cy="100" r="3" fill="#facc15" />
            <circle cx="330" cy="98" r="2.5" fill="#fde047" />

            <!-- Wallet Front 3D Flap Body -->
            <g id="wallet-front-body">
                <!-- Front Leather Face -->
                <path d="M128,110 C128,99 138,91 150,93 L294,112 C306,114 314,124 314,136 L302,238 C302,250 292,260 280,260 L146,260 C133,260 124,250 124,237 Z" fill="url(#walletBlue)" filter="url(#softShadow)" />
                
                <!-- Stitched Edge Accent -->
                <path d="M136,118 L288,136 L278,250 L132,250 Z" fill="none" stroke="#93c5fd" stroke-width="2" stroke-dasharray="4,4" opacity="0.75" />
                
                <!-- Glossy Top Edge Highlight -->
                <path d="M132,111 L296,131" stroke="#bfdbfe" stroke-width="3" stroke-linecap="round" opacity="0.85" />

                <!-- Leather Clasp Strap -->
                <path d="M265,168 L290,171 C302,173 310,184 309,196 C308,208 298,217 286,216 L265,214 Z" fill="url(#strapGrad)" filter="url(#softShadow)" />
                <!-- Strap Stitching -->
                <path d="M270,175 L289,177 C296,178 302,185 301,192 C300,199 294,206 287,205 L270,204" fill="none" stroke="#93c5fd" stroke-width="1.5" stroke-dasharray="3,3" opacity="0.8" />
                
                <!-- Gold Clasp Button -->
                <circle cx="286" cy="191" r="10" fill="#1e3a8a" opacity="0.35" />
                <circle cx="284" cy="190" r="8" fill="url(#goldButton)" stroke="#fef08a" stroke-width="1.5" />
                <circle cx="284" cy="190" r="3.5" fill="#f59e0b" />
            </g>
        </svg>
    </div>
</div>
