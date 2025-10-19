/**
 * UI Utilities Module
 * Common UI functions: sidebar, dropdown, scroll-to-top, etc.
 */

const UIUtils = (function () {
    'use strict';

    /**
     * Initialize all UI components
     */
    function init() {
        initSidebar();
        initDropdowns();
        initScrollToTop();
        initPageLoader();
        initTooltips();
        initLazyLoading();
        initKeyboardShortcuts();
        initOfflineDetection();
    }

    /**
     * Initialize sidebar toggle for mobile
     */
    function initSidebar() {
        const menuToggle = document.querySelector('.menu-toggle');
        const body = document.body;

        if (menuToggle) {
            menuToggle.addEventListener('click', function () {
                body.classList.toggle('sidebar-open');
            });

            // Close sidebar when clicking outside
            document.addEventListener('click', function (e) {
                if (body.classList.contains('sidebar-open') && !e.target.closest('.sidebar') && !e.target.closest('.menu-toggle')) {
                    body.classList.remove('sidebar-open');
                }
            });

            // Close sidebar on escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && body.classList.contains('sidebar-open')) {
                    body.classList.remove('sidebar-open');
                }
            });
        }
    }

    /**
     * Initialize dropdown menus
     */
    function initDropdowns() {
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

        dropdownToggles.forEach((toggle) => {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.addEventListener('click', function (e) {
                console.log('[UIUtils] dropdown toggle clicked', this);
                // prevent other click handlers from running and interfering
                if (e.stopImmediatePropagation) e.stopImmediatePropagation();
                else e.stopPropagation();

                const dropdown = this.closest('.dropdown');
                const isActive = dropdown.classList.contains('active');

                // Close all dropdowns first
                closeAllDropdowns();

                // Small delay to avoid races with other scripts that may run on click
                // Ensure current dropdown toggles correctly
                const self = this;
                setTimeout(function () {
                    if (!isActive) {
                        dropdown.classList.add('active');
                        self.setAttribute('aria-expanded', 'true');
                    } else {
                        self.setAttribute('aria-expanded', 'false');
                    }
                }, 10);
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.dropdown')) {
                closeAllDropdowns();
            }
        });

        // Close on escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAllDropdowns();
            }
        });
    }

    /**
     * Close all dropdown menus
     */
    function closeAllDropdowns() {
        document.querySelectorAll('.dropdown.active').forEach((dropdown) => {
            dropdown.classList.remove('active');
            const toggle = dropdown.querySelector('.dropdown-toggle');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    }

    /**
     * Initialize scroll to top button
     */
    function initScrollToTop() {
        const scrollBtn = document.querySelector('.scroll-to-top');

        if (!scrollBtn) {
            // Create scroll to top button if it doesn't exist
            const btn = document.createElement('button');
            btn.className = 'scroll-to-top';
            btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            btn.setAttribute('aria-label', 'Scroll to top');
            document.body.appendChild(btn);
        }

        const button = document.querySelector('.scroll-to-top');

        // Show/hide button based on scroll position
        window.addEventListener('scroll', function () {
            if (window.pageYOffset > 300) {
                button.classList.add('visible');
            } else {
                button.classList.remove('visible');
            }
        });

        // Scroll to top on click
        button.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
        });
    }

    /**
     * Initialize page loader
     */
    function initPageLoader() {
        const loader = document.querySelector('.page-loader');

        if (!loader) return;

        // Hide loader when page is fully loaded
        window.addEventListener('load', function () {
            loader.style.display = 'none';
        });
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        const elements = document.querySelectorAll('[data-tooltip]');

        elements.forEach((element) => {
            element.addEventListener('mouseenter', function (e) {
                showTooltip(this, this.getAttribute('data-tooltip'));
            });

            element.addEventListener('mouseleave', function () {
                hideTooltip();
            });
        });
    }

    /**
     * Show tooltip
     */
    function showTooltip(element, text) {
        hideTooltip(); // Remove any existing tooltip

        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = text;
        tooltip.id = 'active-tooltip';

        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
    }

    /**
     * Hide tooltip
     */
    function hideTooltip() {
        const tooltip = document.getElementById('active-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'info', duration = 3500) {
        const toast = document.createElement('div');
        toast.className = `training-toast ${type}`;

        const iconMap = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle',
        };

        const icon = iconMap[type] || iconMap.info;

        toast.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${escapeHtml(message)}</span>
        `;

        document.body.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.add('visible');
        });

        // Auto remove
        setTimeout(() => {
            toast.classList.remove('visible');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    /**
     * Show loading overlay
     */
    function showLoading() {
        let loader = document.querySelector('.page-loader');

        if (!loader) {
            loader = document.createElement('div');
            loader.className = 'page-loader';
            loader.innerHTML = '<div class="loading-spinner"></div>';
            document.body.appendChild(loader);
        }

        loader.style.display = 'flex';
    }

    /**
     * Hide loading overlay
     */
    function hideLoading() {
        const loader = document.querySelector('.page-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }

    /**
     * Confirm dialog
     */
    function confirm(message, callback) {
        if (window.confirm(message)) {
            if (typeof callback === 'function') {
                callback();
            }
            return true;
        }
        return false;
    }

    /**
     * Copy to clipboard
     */
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard
                .writeText(text)
                .then(() => {
                    showToast('Đã sao chép vào clipboard', 'success');
                })
                .catch(() => {
                    fallbackCopyToClipboard(text);
                });
        } else {
            fallbackCopyToClipboard(text);
        }
    }

    /**
     * Fallback copy to clipboard
     */
    function fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        document.body.appendChild(textArea);
        textArea.select();

        try {
            document.execCommand('copy');
            showToast('Đã sao chép vào clipboard', 'success');
        } catch (err) {
            showToast('Không thể sao chép', 'error');
        }

        document.body.removeChild(textArea);
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Throttle function
     */
    function throttle(func, limit) {
        let inThrottle;
        return function (...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => (inThrottle = false), limit);
            }
        };
    }

    /**
     * Format time (seconds to MM:SS)
     */
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    /**
     * Format date
     */
    function formatDate(date, format = 'DD/MM/YYYY') {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }

        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        return format.replace('DD', day).replace('MM', month).replace('YYYY', year).replace('HH', hours).replace('mm', minutes);
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Query selector helper
     */
    function qs(selector, root = document) {
        return root.querySelector(selector);
    }

    /**
     * Query selector all helper
     */
    function qsa(selector, root = document) {
        return Array.from(root.querySelectorAll(selector));
    }

    /**
     * Initialize lazy loading for images
     */
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        const src = img.dataset.src;

                        if (src) {
                            img.src = src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            });

            qsa('img[data-src]').forEach((img) => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback
            qsa('img[data-src]').forEach((img) => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
        }
    }

    /**
     * Initialize keyboard shortcuts
     */
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', function (e) {
            // Ctrl/Cmd + K: Focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = qs('.search-form input');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }

            // Escape: Close modals/dropdowns
            if (e.key === 'Escape') {
                closeAllDropdowns();
                qsa('.modal.active').forEach((m) => m.classList.remove('active'));
            }
        });
    }

    /**
     * Initialize offline detection
     */
    function initOfflineDetection() {
        window.addEventListener('online', function () {
            showToast('Đã kết nối lại internet', 'success');
        });

        window.addEventListener('offline', function () {
            showToast('Mất kết nối internet. Một số chức năng có thể không hoạt động.', 'warning');
        });
    }

    /**
     * Animate progress bar
     */
    function animateProgress(element, targetWidth) {
        const target = parseFloat(targetWidth);
        let current = parseFloat(element.style.width) || 0;
        const increment = (target - current) / 50;

        const animation = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.style.width = target + '%';
                clearInterval(animation);
            } else {
                element.style.width = current + '%';
            }
        }, 20);
    }

    /**
     * Smooth scroll to element
     */
    function scrollToElement(element, offset = 0) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }

        if (!element) return;

        const top = element.getBoundingClientRect().top + window.pageYOffset - offset;

        window.scrollTo({
            top: top,
            behavior: 'smooth',
        });
    }

    /**
     * Check if element is in viewport
     */
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Get URL parameter
     */
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        const results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }

    /**
     * Set URL parameter without reload
     */
    function setUrlParameter(key, value) {
        const url = new URL(window.location);
        url.searchParams.set(key, value);
        window.history.pushState({}, '', url);
    }

    /**
     * Initialize confirm dialogs
     */
    function initConfirmDialogs() {
        qsa('[data-confirm]').forEach((element) => {
            element.addEventListener('click', function (e) {
                const message = element.dataset.confirm || 'Bạn có chắc chắn?';
                if (!window.confirm(message)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        });
    }

    /**
     * Initialize copy buttons
     */
    function initCopyButtons() {
        qsa('[data-copy]').forEach((btn) => {
            btn.addEventListener('click', async function (e) {
                e.preventDefault();
                const textToCopy = btn.dataset.copy || btn.textContent;

                try {
                    await navigator.clipboard.writeText(textToCopy);
                    showToast('Đã sao chép vào clipboard', 'success');

                    // Visual feedback
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> Đã sao chép';
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                    }, 2000);
                } catch (err) {
                    showToast('Không thể sao chép', 'error');
                }
            });
        });
    }

    // Auto initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Public API
    return {
        init: init,
        showToast: showToast,
        showLoading: showLoading,
        hideLoading: hideLoading,
        confirm: confirm,
        copyToClipboard: copyToClipboard,
        debounce: debounce,
        throttle: throttle,
        formatTime: formatTime,
        formatDate: formatDate,
        escapeHtml: escapeHtml,
        qs: qs,
        qsa: qsa,
        animateProgress: animateProgress,
        scrollToElement: scrollToElement,
        isInViewport: isInViewport,
        getUrlParameter: getUrlParameter,
        setUrlParameter: setUrlParameter,
        closeAllDropdowns: closeAllDropdowns,
        showTooltip: showTooltip,
        hideTooltip: hideTooltip,
    };
})();

// Expose globally
window.UIUtils = UIUtils;
