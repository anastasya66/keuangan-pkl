/**
 * =====================================================================
 * FINTOKO — JAVASCRIPT LOGIKA LOGIN (login.js)
 * =====================================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Fitur Interaktif Show / Hide Password
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const eyeIcon = document.getElementById('eyeIcon');

    if (toggleBtn && passwordInput && eyeIcon) {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

            if (isPassword) {
                eyeIcon.className = 'fa-regular fa-eye-slash';
                toggleBtn.setAttribute('aria-label', 'Sembunyikan Kata Sandi');
            } else {
                eyeIcon.className = 'fa-regular fa-eye';
                toggleBtn.setAttribute('aria-label', 'Tampilkan Kata Sandi');
            }
            passwordInput.focus();
        });
    }

    // 2. State Loading pada Form Submission
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');

    if (loginForm && submitBtn) {
        loginForm.addEventListener('submit', () => {
            if (!loginForm.checkValidity()) {
                return;
            }

            // Ubah tombol ke state loading
            submitBtn.disabled = true;
            submitBtn.classList.add('is-loading');

            const btnText = submitBtn.querySelector('.btn-text');
            const btnIcon = submitBtn.querySelector('.btn-icon-arrow, .btn-icon, .btn-arrow');
            const btnSpinner = submitBtn.querySelector('.btn-icon-spinner, .spinner-icon, .btn-spinner');

            if (btnText) btnText.textContent = 'Memverifikasi...';
            if (btnIcon) btnIcon.style.display = 'none';
            if (btnSpinner) btnSpinner.style.display = 'inline-block';
        });
    }

    // 3. Efek Auto-dismiss Alert halus jika pengguna mulai mengetik ulang
    const emailInput = document.getElementById('email');
    const errorAlert = document.querySelector('.auth-error-alert, .error-alert');

    if (errorAlert && (emailInput || passwordInput)) {
        const clearAlertOnInput = () => {
            errorAlert.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            errorAlert.style.opacity = '0';
            errorAlert.style.transform = 'translateY(-6px)';
            setTimeout(() => {
                if (errorAlert.parentNode) {
                    errorAlert.style.display = 'none';
                }
            }, 300);
            if (emailInput) emailInput.removeEventListener('input', clearAlertOnInput);
            if (passwordInput) passwordInput.removeEventListener('input', clearAlertOnInput);
        };

        if (emailInput) emailInput.addEventListener('input', clearAlertOnInput, { once: true });
        if (passwordInput) passwordInput.addEventListener('input', clearAlertOnInput, { once: true });
    }
});
