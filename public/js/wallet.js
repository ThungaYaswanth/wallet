/**
 * Wallet Management System - Vanilla JavaScript Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Balance Live Refresh Functionality
    const refreshButtons = document.querySelectorAll('#btn-refresh-balance, .btn-refresh-balance-trigger');
    const availableBalanceEl = document.getElementById('wallet-available-balance');
    const reservedBalanceEl = document.getElementById('wallet-reserved-balance');
    const reservedContainer = document.getElementById('wallet-reserved-container');
    const navBalanceEls = document.querySelectorAll('.nav-available-balance');
    const sidebarBalanceEls = document.querySelectorAll('.sidebar-available-balance');

    async function refreshLiveBalance(triggerBtn) {
        const icons = document.querySelectorAll('#btn-refresh-balance i, .btn-refresh-balance-trigger i');
        icons.forEach(icon => icon.classList.add('spin-anim'));

        try {
            const response = await fetch('/wallet/balance', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const data = await response.json();

                if (availableBalanceEl) {
                    availableBalanceEl.textContent = data.formatted_available_balance;
                }

                if (reservedBalanceEl) {
                    reservedBalanceEl.textContent = data.formatted_reserved_balance;
                }

                if (reservedContainer) {
                    if (data.reserved_balance_paise > 0) {
                        reservedContainer.classList.remove('d-none');
                    } else {
                        reservedContainer.classList.add('d-none');
                    }
                }

                navBalanceEls.forEach(el => el.textContent = data.formatted_available_balance);
                sidebarBalanceEls.forEach(el => el.textContent = data.formatted_available_balance);
            }
        } catch (err) {
            console.error('Balance refresh failed:', err);
        } finally {
            setTimeout(() => {
                icons.forEach(icon => icon.classList.remove('spin-anim'));
            }, 400);
        }
    }

    refreshButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            refreshLiveBalance(btn);
        });
    });

    // 2. Quick Select Amount Chips (Add Money page)
    const amountInput = document.getElementById('deposit-amount-input');
    const chips = document.querySelectorAll('.amount-chip');

    if (amountInput && chips.length > 0) {
        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                const addValue = parseFloat(chip.dataset.amount || '0');
                const currentValue = parseFloat(amountInput.value || '0');
                amountInput.value = (chip.dataset.mode === 'add')
                    ? (currentValue + addValue).toFixed(2)
                    : addValue.toFixed(2);

                chips.forEach(c => c.classList.remove('active'));
                chip.classList.add('active');

                // Trigger change event
                amountInput.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        amountInput.addEventListener('input', () => {
            chips.forEach(c => c.classList.remove('active'));
        });
    }

    // 3. Max Amount Button (Withdraw page)
    const maxBtn = document.getElementById('btn-max-amount');
    const withdrawInput = document.getElementById('withdraw-amount-input');

    if (maxBtn && withdrawInput) {
        maxBtn.addEventListener('click', () => {
            const maxVal = maxBtn.dataset.max || '0';
            withdrawInput.value = parseFloat(maxVal).toFixed(2);
            withdrawInput.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    // 4. Copy to Clipboard utility
    document.querySelectorAll('[data-copy]').forEach(el => {
        el.addEventListener('click', async () => {
            const text = el.dataset.copy;
            if (!text) return;

            try {
                await navigator.clipboard.writeText(text);
                const originalTitle = el.getAttribute('title') || '';
                el.setAttribute('title', 'Copied!');
                setTimeout(() => el.setAttribute('title', originalTitle), 1500);
            } catch (err) {
                console.error('Copy failed:', err);
            }
        });
    });

    // 5. Auto-dismiss alerts after 6 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });

    // 6. Password Visibility Toggle
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-toggle-password');
        if (!btn) return;
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (!input) return;

        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            if (icon) {
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        } else {
            input.type = 'password';
            if (icon) {
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    });
});
