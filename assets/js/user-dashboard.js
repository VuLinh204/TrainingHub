/**
 * User Dashboard JavaScript
 * Handles dashboard interactions, animations, and functionality
 */

(function() {
    'use strict';

    // ===================================
    // TAB FUNCTIONALITY
    // ===================================
    function initTabs() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');

        if (tabBtns.length === 0) return;

        tabBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetTab = this.getAttribute('data-tab');
                if (!targetTab) return;

                // Remove active class from all
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));

                // Add active class to clicked
                this.classList.add('active');
                const targetPane = document.getElementById(targetTab);
                if (targetPane) {
                    targetPane.classList.add('active');
                }

                // Save to sessionStorage
                try {
                    sessionStorage.setItem('activeTab', targetTab);
                } catch (e) {
                    console.warn('SessionStorage not available');
                }
            });
        });

        // Restore last active tab
        const lastTab = sessionStorage.getItem('activeTab');
        if (lastTab) {
            const targetBtn = document.querySelector(`[data-tab="${lastTab}"]`);
            if (targetBtn) {
                targetBtn.click();
            }
        }
    }

    // ===================================
    // VIDEO TRACKING
    // ===================================
    function initVideoTracking() {
        const video = document.getElementById('lessonVideo');
        if (!video) return;

        const lessonId = video.dataset.lessonId;
        if (!lessonId) return;

        let lastSent = 0;
        const sendInterval = 5;

        video.addEventListener('timeupdate', () => {
            const now = Math.floor(video.currentTime);
            
            if (now - lastSent < sendInterval) return;
            lastSent = now;

            // Update progress bar
            const duration = video.duration;
            if (duration) {
                const percentage = (video.currentTime / duration) * 100;
                const progressBar = document.querySelector('.progress');
                if (progressBar) {
                    progressBar.style.width = Math.min(100, percentage) + '%';
                }
            }

            // Send progress to server
            fetch(window.location.pathname.replace(/\/[^\/]*$/, '') + '/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    lesson_id: lessonId,
                    watched_seconds: now,
                    current_time: now,
                    event: 'timeupdate'
                })
            }).catch(err => console.error('Tracking error:', err));
        });

        video.addEventListener('ended', () => {
            // Send completion
            fetch(window.location.pathname.replace(/\/[^\/]*$/, '') + '/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    lesson_id: lessonId,
                    watched_seconds: Math.floor(video.duration),
                    current_time: Math.floor(video.duration),
                    event: 'ended'
                })
            }).catch(err => console.error('Completion tracking error:', err));

            // Reload to update progress
            setTimeout(() => {
                location.reload();
            }, 1500);
        });
    }

    // ===================================
    // EXAM FUNCTIONALITY
    // ===================================
    function initExamButtons() {
        const takeExamBtns = document.querySelectorAll('.take-exam-btn');
        const completeBtns = document.querySelectorAll('.complete-btn');

        takeExamBtns.forEach(btn => {
            btn.addEventListener('click', handleTakeExam);
        });

        completeBtns.forEach(btn => {
            btn.addEventListener('click', handleCompleteLesson);
        });
    }

    async function handleTakeExam(e) {
        e.preventDefault();
        
        const btn = e.currentTarget;
        const subjectId = btn.dataset.subjectId;

        if (!subjectId) {
            showToast('Error: Subject ID not found', 'error');
            return;
        }

        if (!confirm('Start exam? You won\'t be able to go back to video.')) {
            return;
        }

        try {
            UIUtils.showLoading();
            const response = await fetch(`/exam/${subjectId}/start`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to start exam');
            }

            UIUtils.hideLoading();
            window.location.href = `/exam/${subjectId}/take`;

        } catch (error) {
            UIUtils.hideLoading();
            showToast(error.message, 'error');
        }
    }

    async function handleCompleteLesson(e) {
        e.preventDefault();

        const btn = e.currentTarget;
        const lessonId = btn.dataset.subjectId;

        if (!lessonId) {
            showToast('Error: Lesson ID not found', 'error');
            return;
        }

        if (!confirm('Mark this lesson as complete?')) {
            return;
        }

        try {
            UIUtils.showLoading();
            const response = await fetch(`/lesson/${lessonId}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to complete lesson');
            }

            UIUtils.hideLoading();
            showToast('Lesson completed! 🎉', 'success');
            
            setTimeout(() => {
                location.reload();
            }, 1500);

        } catch (error) {
            UIUtils.hideLoading();
            showToast(error.message, 'error');
        }
    }

    // ===================================
    // CERTIFICATE DOWNLOAD
    // ===================================
    function initCertificateActions() {
        const printBtns = document.querySelectorAll('.btn-print');
        const downloadBtns = document.querySelectorAll('.btn-download');

        printBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const url = btn.href;
                window.open(url, '_blank', 'width=800,height=600');
            });
        });

        downloadBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                // Trigger download
                const link = document.createElement('a');
                link.href = btn.href;
                link.download = '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    }

    // ===================================
    // TOAST NOTIFICATION
    // ===================================
    function showToast(message, type = 'info', duration = 3500) {
        if (typeof UIUtils !== 'undefined' && UIUtils.showToast) {
            UIUtils.showToast(message, type, duration);
            return;
        }

        // Fallback toast
        const toast = document.createElement('div');
        toast.className = `training-toast ${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.innerHTML = `
            <i class="fas ${icons[type] || icons.info}"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('visible');
        });

        setTimeout(() => {
            toast.classList.remove('visible');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // ===================================
    // UTILITY: SMOOTH SCROLL
    // ===================================
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(link.hash);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    }

    // ===================================
    // LOADING STATES
    // ===================================
    function initLoadingStates() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && typeof UIUtils !== 'undefined') {
                    UIUtils.showLoading();
                }
            });
        });
    }

    // ===================================
    // INITIALIZE
    // ===================================
    function init() {
        initTabs();
        initVideoTracking();
        initExamButtons();
        initCertificateActions();
        initSmoothScroll();
        initLoadingStates();
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose to global scope
    window.UserDashboard = {
        showToast
    };

})();