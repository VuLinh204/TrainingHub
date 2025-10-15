/*
    Complete Training Platform JavaScript
    Includes all event handlers and functionality
*/

(function () {
    'use strict';

    const CONFIG = {
        heartbeatIntervalSec: 10,
        minWatchPercentToComplete: 0.9,
        allowedSeekSeconds: 5,
        apiBase: getApiBase(),
    };

    function getApiBase() {
        const pathParts = window.location.pathname.split('/');
        return pathParts.length > 1 && pathParts[1] ? '/' + pathParts[1] : '';
    }

    // State management
    const state = {
        currentExamId: null,
        selectedAnswers: new Map(),
        isSubmitting: false,
        sidebarOpen: false,
    };

    // Utilities
    function qs(selector, root = document) {
        return root.querySelector(selector);
    }

    function qsa(selector, root = document) {
        return Array.from(root.querySelectorAll(selector));
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `training-toast ${type}`;
        toast.innerHTML = `
            <i class="fas fa-${getToastIcon(type)}"></i>
            <span>${escapeHtml(message)}</span>
        `;
        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('visible');
        });

        setTimeout(() => {
            toast.classList.remove('visible');
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    function getToastIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle',
        };
        return icons[type] || icons.info;
    }

    function showLoading() {
        let loader = qs('.page-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.className = 'page-loader';
            loader.innerHTML = '<div class="loading-spinner"></div>';
            document.body.appendChild(loader);
        }
        loader.style.display = 'flex';
    }

    function hideLoading() {
        const loader = qs('.page-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }

    async function apiRequest(url, options = {}) {
        try {
            const response = await fetch(CONFIG.apiBase + url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers,
                },
            });

            // Clone response để có thể đọc body nhiều lần nếu cần
            const responseClone = response.clone();

            if (!response.ok) {
                const errorText = await responseClone.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            // Log raw response nếu cần (sử dụng clone)
            const text = await responseClone.text();
            console.log('[API RAW RESPONSE]', text);

            // Xử lý trường hợp response bị ghép hai JSON objects (ví dụ: {"status":"ok"}{"success":true})
            let jsonStr = text.trim();
            if (jsonStr.includes('}{')) {
                // Lấy phần JSON cuối cùng
                const parts = jsonStr.split('}{');
                jsonStr = '{' + parts.pop();
            }

            // Kiểm tra nếu không phải JSON hợp lệ (ví dụ: HTML error)
            if (!jsonStr.startsWith('{')) {
                throw new Error(`Invalid JSON response: Response starts with "${jsonStr.substring(0, 50)}..."`);
            }

            // Parse JSON từ chuỗi đã xử lý
            return JSON.parse(jsonStr);
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /* ===================================
       SIDEBAR TOGGLE
    =================================== */
    function initSidebarToggle() {
        const menuToggle = qs('.menu-toggle');
        const sidebar = qs('.sidebar');

        if (!menuToggle || !sidebar) return;

        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            state.sidebarOpen = !state.sidebarOpen;
            document.body.classList.toggle('sidebar-open', state.sidebarOpen);
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (state.sidebarOpen && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                state.sidebarOpen = false;
                document.body.classList.remove('sidebar-open');
            }
        });

        // Close sidebar on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && state.sidebarOpen) {
                state.sidebarOpen = false;
                document.body.classList.remove('sidebar-open');
            }
        });

        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (window.innerWidth > 1024) {
                    state.sidebarOpen = false;
                    document.body.classList.remove('sidebar-open');
                }
            }, 250);
        });
    }

    /* ===================================
       DROPDOWN MENUS
    =================================== */
    function initDropdowns() {
        qsa('.dropdown-toggle').forEach((toggle) => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const dropdown = toggle.closest('.dropdown');
                const isActive = dropdown.classList.contains('active');

                // Close all other dropdowns
                qsa('.dropdown.active').forEach((d) => {
                    if (d !== dropdown) {
                        d.classList.remove('active');
                    }
                });

                // Toggle current dropdown
                dropdown.classList.toggle('active', !isActive);
            });
        });

        // Close dropdowns on outside click
        document.addEventListener('click', () => {
            qsa('.dropdown.active').forEach((d) => d.classList.remove('active'));
        });

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                qsa('.dropdown.active').forEach((d) => d.classList.remove('active'));
            }
        });
    }

    /* ===================================
       VIDEO TRACKING
    =================================== */
    function initVideoTracking() {
        qsa('video.lesson-video').forEach(setupVideo);

        function setupVideo(video) {
            const lessonId = video.dataset.lessonId;
            if (!lessonId) {
                console.warn('Video missing lesson-id');
                return;
            }

            let realWatched = 0; // Thời gian xem thực tế (play time)
            let lastUpdateTime = Date.now(); // Timestamp cho delta time
            let lastCurrentTime = 0; // Theo dõi currentTime để detect seek
            let playing = false;
            let heartbeatTimer = null;
            let failedHeartbeats = 0;
            const maxDeltaPerUpdate = 2000; // Ngưỡng delta (ms) để detect seek (2s)

            video.addEventListener('loadedmetadata', () => {
                console.log('Video loaded:', {
                    duration: video.duration,
                    lessonId: lessonId,
                });
                lastCurrentTime = video.currentTime;
                lastUpdateTime = Date.now();
            });

            video.addEventListener('play', () => {
                playing = true;
                lastUpdateTime = Date.now(); // Reset timestamp khi play
                startHeartbeat();
            });

            video.addEventListener('pause', () => {
                playing = false;
                stopHeartbeat();
                sendHeartbeat('pause');
                // Cộng delta cuối cùng khi pause
                const delta = Date.now() - lastUpdateTime;
                if (delta < maxDeltaPerUpdate) {
                    realWatched += delta / 1000; // Chuyển sang giây
                }
            });

            video.addEventListener('timeupdate', () => {
                if (!playing) return;

                const now = Date.now();
                const deltaTime = now - lastUpdateTime; // Delta thời gian thực (ms)
                const currentTime = video.currentTime * 1000; // currentTime sang ms
                const timeDiff = currentTime - lastCurrentTime * 1000; // Diff vị trí video

                // Detect seek: Nếu delta vị trí > delta thời gian thực + ngưỡng, coi như seek
                if (timeDiff > deltaTime + maxDeltaPerUpdate) {
                    console.log('Seek detected, ignoring jump');
                    // Không cộng vào realWatched, chỉ reset lastCurrentTime
                    lastCurrentTime = video.currentTime;
                } else {
                    // Bình thường: Cộng delta thời gian thực
                    realWatched += deltaTime / 1000;
                }

                lastUpdateTime = now;
                lastCurrentTime = video.currentTime;

                // Update progress bar dựa trên realWatched
                updateProgressBar(video, realWatched);

                // Check completion dựa trên realWatched
                if (video.duration && realWatched >= CONFIG.minWatchPercentToComplete * video.duration) {
                    markLessonCompleted(lessonId, video);
                }
            });

            video.addEventListener('seeking', () => {
                // Optional: Giới hạn seek nếu cần, nhưng không ảnh hưởng realWatched
                console.log('Seeking to:', video.currentTime);
            });

            video.addEventListener('seeked', () => {
                lastCurrentTime = video.currentTime; // Update sau seek hợp lệ
                if (playing) {
                    lastUpdateTime = Date.now(); // Reset timestamp
                }
            });

            video.addEventListener('ended', () => {
                playing = false;
                // Cộng delta cuối
                const delta = Date.now() - lastUpdateTime;
                if (delta < maxDeltaPerUpdate) {
                    realWatched += delta / 1000;
                }
                sendHeartbeat('ended');
                markLessonCompleted(lessonId, video);
                console.log('Total real watched time:', realWatched, 'seconds');
            });

            video.addEventListener('error', (e) => {
                console.error('Video error:', e);
                showToast('Lỗi tải video. Vui lòng thử lại.', 'error');
            });

            function startHeartbeat() {
                if (heartbeatTimer) return;
                heartbeatTimer = setInterval(() => {
                    sendHeartbeat('heartbeat');
                }, CONFIG.heartbeatIntervalSec * 1000);
            }

            function stopHeartbeat() {
                if (heartbeatTimer) {
                    clearInterval(heartbeatTimer);
                    heartbeatTimer = null;
                }
            }

            async function sendHeartbeat(eventName) {
                const payload = {
                    lesson_id: lessonId,
                    watched_seconds: Math.floor(realWatched), // Gửi realWatched lên server
                    duration: Math.floor(video.duration || 0),
                    current_time: Math.floor(video.currentTime),
                    event: eventName,
                };

                try {
                    await apiRequest('/api/lesson/track', {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                    failedHeartbeats = 0;
                } catch (e) {
                    failedHeartbeats++;
                    console.warn('Heartbeat failed', failedHeartbeats, e);

                    if (failedHeartbeats >= 3) {
                        showToast('Mất kết nối. Tiến độ có thể không được lưu.', 'warning');
                    }
                }
            }

            function updateProgressBar(video, watched) {
                const progressBar = qs('.video-info .progress');
                if (progressBar && video.duration) {
                    const percent = Math.min(100, (watched / video.duration) * 100);
                    progressBar.style.width = percent + '%';
                }
            }
        }
    }

    /* ===================================
       LESSON COMPLETION
    =================================== */
    async function markLessonCompleted(lessonId, videoEl) {
        const container = videoEl.closest('[data-subject-id]') || document;
        const btn = container.querySelector('.take-exam-btn, .complete-btn');

        if (btn && btn.disabled) {
            btn.disabled = false;
            btn.classList.remove('disabled');

            if (btn.classList.contains('take-exam-btn')) {
                btn.textContent = 'Làm bài kiểm tra';
            } else {
                btn.textContent = 'Đánh dấu hoàn thành';
            }

            showToast('Bạn đã xem đủ video để làm bài kiểm tra!', 'success');
        }
    }

    async function completeLesson(lessonId) {
        try {
            showLoading();
            const result = await apiRequest('/api/lesson/complete', {
                method: 'POST',
                body: JSON.stringify({ lesson_id: lessonId }),
            });

            hideLoading();

            if (result.status === 'completed') {
                showToast('Chúc mừng! Bạn đã hoàn thành khóa học.', 'success');
                setTimeout(() => window.location.reload(), 2000);
            }
        } catch (e) {
            hideLoading();
            showToast('Không thể hoàn thành. Vui lòng thử lại.', 'error');
        }
    }

    /* ===================================
       MCQ HANDLING
    =================================== */
    function initMCQ() {
        qsa('.mcq-answer').forEach((el) => {
            el.addEventListener('click', onAnswerSelect);
        });
    }

    async function onAnswerSelect(ev) {
        const el = ev.currentTarget;
        const answerId = el.dataset.answerId;
        const questionId = el.dataset.questionId;

        if (!answerId || !questionId) return;

        // Prevent double-click
        if (el.classList.contains('selected')) return;

        // Clear previous selections for this question
        qsa(`.mcq-answer[data-question-id="${questionId}"]`).forEach((a) => {
            a.classList.remove('selected', 'correct', 'incorrect');
        });

        el.classList.add('selected');
        state.selectedAnswers.set(questionId, answerId);

        // Visual feedback
        el.style.transform = 'scale(0.98)';
        setTimeout(() => {
            el.style.transform = '';
        }, 150);

        // Update submit button state
        updateSubmitButton();
    }

    function updateSubmitButton() {
        const submitBtn = qs('.exam-form button[type="submit"]');
        const totalQuestions = qsa('.question').length;

        if (submitBtn) {
            const answered = state.selectedAnswers.size;
            submitBtn.disabled = answered < totalQuestions;

            if (answered < totalQuestions) {
                submitBtn.textContent = `Đã trả lời ${answered}/${totalQuestions}`;
                submitBtn.classList.add('disabled');
            } else {
                submitBtn.textContent = 'Nộp bài';
                submitBtn.classList.remove('disabled');
            }
        }
    }

    /* ===================================
       EXAM FLOW
    =================================== */
    function initExamHandlers() {
        // Take exam button
        qsa('.take-exam-btn').forEach((btn) => {
            btn.addEventListener('click', handleExamStart);
        });

        // Complete lesson button
        qsa('.complete-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const lessonId = e.currentTarget.dataset.subjectId;
                if (lessonId && !e.currentTarget.disabled) {
                    completeLesson(lessonId);
                }
            });
        });
    }

    async function handleExamStart(ev) {
        ev.preventDefault();
        const btn = ev.currentTarget;
        const subjectId = btn.dataset.subjectId;

        if (!subjectId) {
            showToast('Lỗi: Không tìm thấy thông tin khóa học', 'error');
            return;
        }

        // Confirm before starting
        if (!confirm('Bạn có chắc chắn muốn bắt đầu bài kiểm tra? Bạn sẽ không thể quay lại video sau khi bắt đầu.')) {
            return;
        }

        try {
            showLoading();
            const result = await apiRequest(`/exam/${subjectId}/start`, {
                method: 'POST',
                body: JSON.stringify({ subject_id: subjectId }),
            });

            if (!result.success) {
                throw new Error(result.error || 'Unknown error');
            }

            state.currentExamId = result.exam_id;
            renderExam(result.questions, result.time_limit);
            hideLoading();

            // Hide video, show exam
            const videoContainer = qs('.video-container');
            const examContainer = qs('.exam-container');

            if (videoContainer) videoContainer.style.display = 'none';
            if (examContainer) {
                examContainer.style.display = 'block';
                examContainer.scrollIntoView({ behavior: 'smooth' });
            }

            // Start timer if time limit exists
            if (result.time_limit) {
                startExamTimer(result.time_limit);
            }
        } catch (e) {
            hideLoading();
            showToast('Không thể bắt đầu bài kiểm tra. ' + e.message, 'error');
            console.error('Exam start error:', e);
        }
    }

    function renderExam(questions, timeLimit) {
        const examForm = qs('.exam-form');
        if (!examForm) return;

        examForm.setAttribute('data-exam-id', state.currentExamId);
        examForm.innerHTML = '';

        // Add timer if exists
        let timerHTML = '';
        if (timeLimit) {
            timerHTML = `
                <div class="exam-timer" data-time-limit="${timeLimit}">
                    <i class="fas fa-clock"></i>
                    <span class="timer-display">${timeLimit}:00</span>
                </div>
            `;
        }

        const questionsHTML = questions
            .map(
                (q, index) => `
            <div class="question" data-question-id="${q.ID}">
                <h3>Câu ${index + 1}: ${escapeHtml(q.QuestionText)}</h3>
                <div class="answers">
                    ${q.answers
                        .map(
                            (a) => `
                        <div class="mcq-answer" 
                             data-answer-id="${a.id}" 
                             data-question-id="${q.ID}">
                            <span class="answer-text">${escapeHtml(a.text)}</span>
                        </div>
                    `
                        )
                        .join('')}
                </div>
            </div>
        `
            )
            .join('');

        examForm.innerHTML = `
            ${timerHTML}
            <div class="exam-info">
                <p>Tổng số câu hỏi: <strong>${questions.length}</strong></p>
                <p>Hãy chọn câu trả lời đúng nhất cho mỗi câu hỏi.</p>
            </div>
            ${questionsHTML}
            <div class="exam-actions">
                <button type="submit" class="btn-primary" disabled>
                    Đã trả lời 0/${questions.length}
                </button>
            </div>
        `;

        // Re-init MCQ handlers
        initMCQ();

        // Add submit handler
        examForm.addEventListener('submit', handleExamSubmit);
    }

    function startExamTimer(minutes) {
        const timerEl = qs('.timer-display');
        if (!timerEl) return;

        let timeRemaining = minutes * 60; // Convert to seconds

        const timerInterval = setInterval(() => {
            timeRemaining--;

            const mins = Math.floor(timeRemaining / 60);
            const secs = timeRemaining % 60;
            timerEl.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;

            // Warning at 5 minutes
            if (timeRemaining === 300) {
                showToast('Còn 5 phút!', 'warning');
                timerEl.closest('.exam-timer').classList.add('warning');
            }

            // Warning at 1 minute
            if (timeRemaining === 60) {
                showToast('Còn 1 phút!', 'warning');
                timerEl.closest('.exam-timer').classList.add('danger');
            }

            // Time's up
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                showToast('Hết giờ! Tự động nộp bài.', 'error');

                // Auto submit
                const examForm = qs('.exam-form');
                if (examForm) {
                    handleExamSubmit({ preventDefault: () => {}, currentTarget: examForm });
                }
            }
        }, 1000);
    }

    async function handleExamSubmit(ev) {
        ev.preventDefault();

        if (state.isSubmitting) return;
        state.isSubmitting = true;

        const examForm = ev.currentTarget;
        const examId = examForm.dataset.examId;
        const subjectId = qs('[data-subject-id]')?.dataset.subjectId;

        // Collect answers
        const answers = Array.from(state.selectedAnswers.entries()).map(([qId, aId]) => ({
            question_id: qId,
            answer_id: aId,
        }));

        // Final confirmation
        if (!confirm(`Bạn đã trả lời ${answers.length} câu hỏi. Bạn có chắc chắn muốn nộp bài?`)) {
            state.isSubmitting = false;
            return;
        }

        try {
            showLoading();
            const result = await apiRequest(`/exam/${subjectId}/submit`, {
                method: 'POST',
                body: JSON.stringify({
                    exam_id: examId,
                    answers: answers,
                }),
            });

            hideLoading();

            if (!result.success) {
                throw new Error(result.error || 'Submit failed');
            }

            showExamResult(result);
        } catch (e) {
            hideLoading();
            showToast('Không thể nộp bài. ' + e.message, 'error');
            console.error('Exam submit error:', e);
        } finally {
            state.isSubmitting = false;
        }
    }

    function showExamResult(data) {
        const passed = data && data.passed;
        const score = data.correct_answers || 0;
        const total = data.total_questions || 0;
        const percentage = data.percentage || 0;

        const overlay = document.createElement('div');
        overlay.className = 'exam-result-overlay';
        overlay.innerHTML = `
            <div class="exam-result-card ${passed ? 'passed' : 'failed'}">
                <div class="result-icon">
                    <i class="fas fa-${passed ? 'check-circle' : 'times-circle'}"></i>
                </div>
                <h2>${passed ? 'Chúc mừng bạn đã đạt!' : 'Chưa đạt yêu cầu'}</h2>
                <p class="result-score">
                    Điểm của bạn: <strong>${score}/${total}</strong>
                    <span class="percentage">(${percentage}%)</span>
                </p>
                <p class="result-message">
                    ${
                        passed
                            ? 'Bạn đã hoàn thành khóa học xuất sắc! Chứng chỉ của bạn đã sẵn sàng.'
                            : 'Đừng nản lòng! Hãy xem lại bài giảng và thử lại. Bạn cần đạt tối thiểu ' + data.required + ' câu đúng.'
                    }
                </p>
                <div class="exam-result-actions">
                    ${
                        passed
                            ? '<button class="btn-primary btn-certificate"><i class="fas fa-certificate"></i> Nhận chứng chỉ</button>'
                            : '<button class="btn-primary btn-retry"><i class="fas fa-redo"></i> Làm lại</button>'
                    }
                    <button class="btn-secondary btn-close">Đóng</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
        });

        // Handle buttons
        if (passed) {
            const certBtn = qs('.btn-certificate', overlay);
            certBtn.addEventListener('click', () => {
                window.location.href = CONFIG.apiBase + '/certificates';
            });
        } else {
            const retryBtn = qs('.btn-retry', overlay);
            retryBtn.addEventListener('click', () => {
                window.location.reload();
            });
        }

        const closeBtn = qs('.btn-close', overlay);
        closeBtn.addEventListener('click', () => {
            window.location.href = CONFIG.apiBase + '/dashboard';
        });
    }

    /* ===================================
       SEARCH FUNCTIONALITY
    =================================== */
    function initSearch() {
        const searchForm = qs('.search-form');
        const searchInput = qs('.search-form input');

        if (!searchForm || !searchInput) return;

        // Debounce search
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();

            if (query.length < 2) return;

            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 500);
        });

        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = CONFIG.apiBase + '/search?q=' + encodeURIComponent(query);
            }
        });
    }

    async function performSearch(query) {
        // Implement live search results if needed
        console.log('Searching for:', query);
    }

    /* ===================================
       FORM VALIDATION
    =================================== */
    function initFormValidation() {
        qsa('form').forEach((form) => {
            form.addEventListener('submit', (e) => {
                const invalidInputs = qsa('input:invalid, textarea:invalid, select:invalid', form);

                if (invalidInputs.length > 0) {
                    e.preventDefault();
                    invalidInputs[0].focus();
                    showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'warning');
                }
            });
        });

        // Real-time validation feedback
        qsa('input[required], textarea[required], select[required]').forEach((input) => {
            input.addEventListener('blur', () => {
                if (!input.validity.valid) {
                    input.classList.add('error');
                } else {
                    input.classList.remove('error');
                }
            });

            input.addEventListener('input', () => {
                if (input.classList.contains('error') && input.validity.valid) {
                    input.classList.remove('error');
                }
            });
        });
    }

    /* ===================================
       NOTIFICATIONS
    =================================== */
    function initNotifications() {
        const notificationBtn = qs('.notifications .dropdown-toggle');

        if (!notificationBtn) return;

        // Load notifications on click
        notificationBtn.addEventListener('click', async () => {
            const dropdown = notificationBtn.closest('.dropdown');
            const menu = qs('.dropdown-menu', dropdown);

            if (!menu) return;

            if (menu.children.length === 0) {
                menu.innerHTML = '<div class="dropdown-loading">Đang tải...</div>';

                try {
                    const notifications = await apiRequest('/api/notifications');
                    renderNotifications(menu, notifications);
                } catch (e) {
                    menu.innerHTML = '<div class="dropdown-error">Không thể tải thông báo</div>';
                }
            }
        });
    }

    function renderNotifications(menu, notifications) {
        if (!notifications || notifications.length === 0) {
            menu.innerHTML = '<div class="dropdown-empty">Không có thông báo mới</div>';
            return;
        }

        menu.innerHTML =
            notifications
                .map(
                    (n) => `
            <a href="${CONFIG.apiBase}${n.link}" class="notification-item ${n.read ? '' : 'unread'}">
                <i class="fas fa-${n.icon}"></i>
                <div class="notification-content">
                    <div class="notification-title">${escapeHtml(n.title)}</div>
                    <div class="notification-time">${n.time}</div>
                </div>
            </a>
        `
                )
                .join('') +
            '<a href="' +
            CONFIG.apiBase +
            '/notifications" class="view-all">Xem tất cả</a>';
    }

    /* ===================================
       KEYBOARD SHORTCUTS
    =================================== */
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
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
                qsa('.dropdown.active').forEach((d) => d.classList.remove('active'));
                qsa('.modal.active').forEach((m) => m.classList.remove('active'));
            }
        });
    }

    /* ===================================
       PROGRESS TRACKING
    =================================== */
    function initProgressTracking() {
        updateProgressDisplay();

        // Update every minute
        setInterval(updateProgressDisplay, 60000);
    }

    function updateProgressDisplay() {
        const progressBars = qsa('.progress-bar .progress');

        progressBars.forEach((bar) => {
            const targetWidth = bar.style.width;
            if (targetWidth) {
                animateProgress(bar, targetWidth);
            }
        });
    }

    function animateProgress(element, targetWidth) {
        const target = parseFloat(targetWidth);
        let current = 0;
        const increment = target / 50;

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

    /* ===================================
       LAZY LOADING IMAGES
    =================================== */
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
            // Fallback for browsers without IntersectionObserver
            qsa('img[data-src]').forEach((img) => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
        }
    }

    /* ===================================
       SCROLL TO TOP BUTTON
    =================================== */
    function initScrollToTop() {
        let scrollBtn = qs('.scroll-to-top');

        if (!scrollBtn) {
            scrollBtn = document.createElement('button');
            scrollBtn.className = 'scroll-to-top';
            scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            scrollBtn.setAttribute('aria-label', 'Scroll to top');
            document.body.appendChild(scrollBtn);
        }

        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollBtn.classList.add('visible');
            } else {
                scrollBtn.classList.remove('visible');
            }
        });

        scrollBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
        });
    }

    /* ===================================
       COPY TO CLIPBOARD
    =================================== */
    function initCopyButtons() {
        qsa('[data-copy]').forEach((btn) => {
            btn.addEventListener('click', async (e) => {
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
                } catch (e) {
                    showToast('Không thể sao chép', 'error');
                }
            });
        });
    }

    /* ===================================
       CONFIRM DIALOGS
    =================================== */
    function initConfirmDialogs() {
        qsa('[data-confirm]').forEach((element) => {
            element.addEventListener('click', (e) => {
                const message = element.dataset.confirm || 'Bạn có chắc chắn?';
                if (!confirm(message)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        });
    }

    /* ===================================
       AUTO SAVE FORMS
    =================================== */
    function initAutoSave() {
        qsa('form[data-autosave]').forEach((form) => {
            const formId = form.dataset.autosave;

            // Load saved data
            loadFormData(form, formId);

            // Save on change
            let saveTimeout;
            form.addEventListener('input', () => {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    saveFormData(form, formId);
                }, 1000);
            });

            // Clear on submit
            form.addEventListener('submit', () => {
                clearFormData(formId);
            });
        });
    }

    function saveFormData(form, formId) {
        const formData = new FormData(form);
        const data = {};

        formData.forEach((value, key) => {
            data[key] = value;
        });

        try {
            sessionStorage.setItem('form_' + formId, JSON.stringify(data));
            console.log('Form data saved:', formId);
        } catch (e) {
            console.warn('Could not save form data:', e);
        }
    }

    function loadFormData(form, formId) {
        try {
            const saved = sessionStorage.getItem('form_' + formId);
            if (!saved) return;

            const data = JSON.parse(saved);

            Object.keys(data).forEach((key) => {
                const input = form.elements[key];
                if (input) {
                    input.value = data[key];
                }
            });

            console.log('Form data loaded:', formId);
        } catch (e) {
            console.warn('Could not load form data:', e);
        }
    }

    function clearFormData(formId) {
        try {
            sessionStorage.removeItem('form_' + formId);
        } catch (e) {
            console.warn('Could not clear form data:', e);
        }
    }

    /* ===================================
       TOOLTIP INITIALIZATION
    =================================== */
    function initTooltips() {
        qsa('[data-tooltip]').forEach((element) => {
            element.addEventListener('mouseenter', (e) => {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = element.dataset.tooltip;
                document.body.appendChild(tooltip);

                const rect = element.getBoundingClientRect();
                tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
                tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';

                element.addEventListener(
                    'mouseleave',
                    () => {
                        tooltip.remove();
                    },
                    { once: true }
                );
            });
        });
    }

    /* ===================================
       OFFLINE DETECTION
    =================================== */
    function initOfflineDetection() {
        window.addEventListener('online', () => {
            showToast('Đã kết nối lại internet', 'success');
        });

        window.addEventListener('offline', () => {
            showToast('Mất kết nối internet. Một số chức năng có thể không hoạt động.', 'warning');
        });
    }

    /* ===================================
       PWA INSTALL PROMPT
    =================================== */
    function initPWAInstall() {
        let deferredPrompt;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;

            // Show install button if exists
            const installBtn = qs('.pwa-install-btn');
            if (installBtn) {
                installBtn.style.display = 'block';

                installBtn.addEventListener('click', async () => {
                    if (!deferredPrompt) return;

                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;

                    console.log(`User response to install prompt: ${outcome}`);
                    deferredPrompt = null;
                    installBtn.style.display = 'none';
                });
            }
        });

        window.addEventListener('appinstalled', () => {
            showToast('Ứng dụng đã được cài đặt thành công!', 'success');
            deferredPrompt = null;
        });
    }

    /* ===================================
       ERROR TRACKING
    =================================== */
    function initErrorTracking() {
        window.addEventListener('error', (e) => {
            console.error('Global error:', e.error);
            // Send to error tracking service if needed
        });

        window.addEventListener('unhandledrejection', (e) => {
            console.error('Unhandled promise rejection:', e.reason);
            // Send to error tracking service if needed
        });
    }

    /* ===================================
       INITIALIZE ALL
    =================================== */
    function initAll() {
        console.log('Initializing Training Platform...');

        // Core functionality
        initSidebarToggle();
        initDropdowns();
        initVideoTracking();
        initExamHandlers();
        initMCQ();

        // UI enhancements
        initSearch();
        initFormValidation();
        initNotifications();
        initKeyboardShortcuts();
        initProgressTracking();
        initLazyLoading();
        initScrollToTop();
        initCopyButtons();
        initConfirmDialogs();
        initAutoSave();
        initTooltips();

        // System features
        initOfflineDetection();
        initPWAInstall();
        initErrorTracking();

        console.log('Training Platform initialized successfully');
    }

    /* ===================================
       AUTO INITIALIZE
    =================================== */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    // Expose some functions globally for external use
    window.TrainingPlatform = {
        showToast,
        showLoading,
        hideLoading,
        apiRequest,
    };
})();
