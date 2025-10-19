// Consolidated site JS for views
document.addEventListener('DOMContentLoaded', function () {
    // Service worker registration (moved from inline header)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            try {
                const base = window.BASE_URL || '';
                navigator.serviceWorker.register(base + '/sw.js')
                    .then(() => console.log('ServiceWorker registration successful'))
                    .catch(err => console.log('ServiceWorker registration failed: ', err));
            } catch (e) {
                console.warn('Service worker registration skipped', e);
            }
        });
    }
    
    // Fallback: expose a global showToast function that uses UIUtils or UserDashboard if available
    window.showToast = function (message, type = 'info', duration = 3500) {
        try {
            if (window.UIUtils && typeof window.UIUtils.showToast === 'function') {
                window.UIUtils.showToast(message, type, duration);
                return;
            }
            if (window.UserDashboard && typeof window.UserDashboard.showToast === 'function') {
                window.UserDashboard.showToast(message, type, duration);
                return;
            }
        } catch (e) {
            console.warn('Toast helpers unavailable, falling back', e);
        }

        // Minimal fallback
        const toast = document.createElement('div');
        toast.className = `training-toast ${type}`;
        toast.innerHTML = `<i class="fas fa-info-circle"></i><span>${message}</span>`;
        document.body.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('visible'));
        setTimeout(() => { toast.classList.remove('visible'); setTimeout(() => toast.remove(), 300); }, duration);
    };

    // Fallback menu toggle in case UIUtils didn't initialize
    (function attachMenuToggleFallback() {
        const menuToggle = document.querySelector('.menu-toggle');
        if (!menuToggle) return;
        // Avoid attaching duplicate handlers
        if (menuToggle.__hasFallback) return;
        menuToggle.__hasFallback = true;
        menuToggle.addEventListener('click', function (e) {
            try {
                // If admin sidebar exists, toggle its mobile-open and overlay
                const adminSidebar = document.getElementById('adminSidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                if (adminSidebar) {
                    adminSidebar.classList.toggle('mobile-open');
                    if (overlay) overlay.classList.toggle('active');
                    return;
                }

                // Otherwise toggle standard sidebar-open class
                document.body.classList.toggle('sidebar-open');
            } catch (err) {
                console.warn('Fallback menu toggle failed', err);
            }
        });
    })();
});
