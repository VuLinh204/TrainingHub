/**
 * Video Player Module
 * Handles video playback, progress tracking, and analytics
 */

const VideoPlayerModule = (function() {
    'use strict';

    let video = null;
    let subjectId = null;
    let lastSentProgress = 0;
    let progressInterval = null;
    let watchStartTime = null;

    /**
     * Initialize video player
     */
    function init() {
        video = document.getElementById('lessonVideo');
        
        if (!video) return;

        subjectId = video.dataset.subjectId || getSubjectIdFromUrl();
        
        if (!subjectId) {
            console.warn('Subject ID not found');
            return;
        }

        initEventListeners();
        initProgressTracking();
        preventSkipping();
    }

    /**
     * Get subject ID from URL
     */
    function getSubjectIdFromUrl() {
        const match = window.location.pathname.match(/\/subject\/(\d+)/);
        return match ? match[1] : null;
    }

    /**
     * Initialize video event listeners
     */
    function initEventListeners() {
        // Play event
        video.addEventListener('play', function() {
            watchStartTime = Date.now();
            sendEvent('play', {
                current_time: Math.floor(video.currentTime)
            });
        });

        // Pause event
        video.addEventListener('pause', function() {
            if (watchStartTime) {
                const watchDuration = Math.floor((Date.now() - watchStartTime) / 1000);
                sendEvent('pause', {
                    current_time: Math.floor(video.currentTime),
                    watch_duration: watchDuration
                });
                watchStartTime = null;
            }
        });

        // Ended event
        video.addEventListener('ended', function() {
            sendEvent('ended', {
                current_time: Math.floor(video.currentTime)
            });
            
            // Show completion message
            showToast('Bạn đã xem hết video!', 'success');
        });

        // Seeking event (user tries to skip)
        video.addEventListener('seeking', function() {
            const currentTime = Math.floor(video.currentTime);
            
            // Allow seeking backward, but limit forward seeking
            if (currentTime > lastSentProgress + 5) {
                video.currentTime = lastSentProgress;
                showToast('Vui lòng xem video theo thứ tự', 'warning');
            }
        });

        // Error handling
        video.addEventListener('error', function(e) {
            console.error('Video error:', e);
            showToast('Lỗi khi tải video. Vui lòng thử lại.', 'error');
        });
    }

    /**
     * Initialize progress tracking
     */
    function initProgressTracking() {
        // Track progress every 5 seconds
        progressInterval = setInterval(() => {
            if (!video.paused && !video.ended) {
                const currentTime = Math.floor(video.currentTime);
                
                // Only send if progressed significantly
                if (currentTime - lastSentProgress >= 5) {
                    sendProgress(currentTime);
                    lastSentProgress = currentTime;
                }
            }
        }, 5000);

        // Also track on timeupdate (backup)
        video.addEventListener('timeupdate', function() {
            const currentTime = Math.floor(video.currentTime);
            
            if (currentTime - lastSentProgress >= 10) {
                sendProgress(currentTime);
                lastSentProgress = currentTime;
            }

            // Update progress bar
            updateProgressBar(currentTime, video.duration);
        });
    }

    /**
     * Send progress to server
     */
    function sendProgress(watchedSeconds) {
        fetch('/subject/trackProgress', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                lesson_id: subjectId,
                watched_seconds: watchedSeconds,
                event: 'progress'
            })
        })
        .catch(error => {
            console.error('Error tracking progress:', error);
        });
    }

    /**
     * Send video event
     */
    function sendEvent(eventType, data = {}) {
        fetch('/subject/trackProgress', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                lesson_id: subjectId,
                event: eventType,
                ...data
            })
        })
        .catch(error => {
            console.error('Error sending event:', error);
        });
    }

    /**
     * Update progress bar
     */
    function updateProgressBar(current, total) {
        const progressBar = document.querySelector('.progress-bar .progress');
        if (progressBar && total > 0) {
            const percent = Math.min(100, (current / total) * 100);
            progressBar.style.width = percent + '%';
        }
    }

    /**
     * Prevent video skipping
     */
    function preventSkipping() {
        // Disable playback rate control
        video.playbackRate = 1.0;
        
        // Monitor playback rate changes
        video.addEventListener('ratechange', function() {
            if (video.playbackRate !== 1.0) {
                video.playbackRate = 1.0;
                showToast('Không thể tua nhanh video', 'warning');
            }
        });
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `training-toast ${type}`;
        
        const icon = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        }[type] || 'fa-info-circle';
        
        toast.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('visible'), 10);
        
        setTimeout(() => {
            toast.classList.remove('visible');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Cleanup on page unload
     */
    function cleanup() {
        if (progressInterval) {
            clearInterval(progressInterval);
        }
        
        // Send final progress
        if (video && !video.paused && !video.ended) {
            const currentTime = Math.floor(video.currentTime);
            sendProgress(currentTime);
        }
    }

    // Public API
    return {
        init: init,
        cleanup: cleanup
    };
})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', VideoPlayerModule.init);
} else {
    VideoPlayerModule.init();
}

// Cleanup on page unload
window.addEventListener('beforeunload', VideoPlayerModule.cleanup);