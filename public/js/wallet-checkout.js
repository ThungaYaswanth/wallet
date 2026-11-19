/**
 * Cashfree JS SDK v3 Modal Checkout Integration (High Performance)
 */

document.addEventListener('DOMContentLoaded', () => {
    const depositForm = document.getElementById('form-add-money');
    const depositSubmitBtn = document.getElementById('btn-submit-deposit');
    const depositErrorBox = document.getElementById('deposit-error-alert');

    if (!depositForm || !depositSubmitBtn) return;

    const envAttr = depositForm.getAttribute('data-cashfree-env') || 'sandbox';
    let cachedCashfree = null;

    // Pre-initialize Cashfree SDK early to eliminate runtime warmup lag
    function initCashfree(env) {
        if (!cachedCashfree && window.Cashfree) {
            cachedCashfree = window.Cashfree({
                mode: (env === 'production') ? 'production' : 'sandbox'
            });
        }
        return cachedCashfree;
    }

    if (window.Cashfree) {
        initCashfree(envAttr);
    }

    depositForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Clear existing errors
        if (depositErrorBox) {
            depositErrorBox.classList.add('d-none');
            depositErrorBox.textContent = '';
        }

        const amountInput = document.getElementById('deposit-amount-input');
        const phoneInput = document.getElementById('deposit-phone-input');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const amount = parseFloat(amountInput?.value || '0');
        if (isNaN(amount) || amount <= 0) {
            showError('Please enter a valid deposit amount greater than ₹0.');
            return;
        }

        // Disable submit button & show spinner
        const originalBtnHtml = depositSubmitBtn.innerHTML;
        depositSubmitBtn.disabled = true;
        depositSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Connecting to Cashfree...';

        try {
            const response = await fetch('/wallet/add-money', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    amount: amount,
                    customer_phone: phoneInput?.value || null
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to initialize payment order.');
            }

            if (!window.Cashfree) {
                throw new Error('Cashfree JS SDK failed to load. Please check your internet connection.');
            }

            // Retrieve pre-warmed Cashfree SDK instance
            depositSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Opening Checkout...';
            const cashfree = initCashfree(data.cashfree_env || envAttr) || window.Cashfree({
                mode: (data.cashfree_env === 'production') ? 'production' : 'sandbox'
            });

            // Launch modal checkout
            cashfree.checkout({
                paymentSessionId: data.payment_session_id,
                redirectTarget: '_modal'
            }).then((result) => {
                if (result && result.error) {
                    showError(result.error.message || 'Payment was cancelled or failed.');
                    resetButton();
                    return;
                }

                // Instant verification path: skip iframe countdown delay and verify immediately
                if (result && (result.paymentDetails || result.redirect)) {
                    depositSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Crediting wallet...';
                    window.location.href = '/wallet/add-money/return?order_id=' + encodeURIComponent(data.order_id);
                }
            });

        } catch (err) {
            showError(err.message || 'An unexpected error occurred.');
            resetButton();
        }

        function resetButton() {
            depositSubmitBtn.disabled = false;
            depositSubmitBtn.innerHTML = originalBtnHtml;
        }

        function showError(msg) {
            if (depositErrorBox) {
                depositErrorBox.textContent = msg;
                depositErrorBox.classList.remove('d-none');
            } else {
                alert(msg);
            }
        }
    });
});
