/*
    Complete Training Platform JavaScript
    Main orchestrator - delegates to modular components
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
    };

    // Shorthand helpers (kept minimal)
    const qs = UIUtils.qs;
    const qsa = UIUtils.qsa;

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

            let realWatched = 0;
            let lastUpdateTime = Date.now();
            let lastCurrentTime = 0;
            let playing = false;
            let heartbeatTimer = null;
            let failedHeartbeats = 0;
            const maxDeltaPerUpdate = 2000;

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
                lastUpdateTime = Date.now();
                startHeartbeat();
            });

            video.addEventListener('pause', () => {
                playing = false;
                stopHeartbeat();
                sendHeartbeat('pause');
                const delta = Date.now() - lastUpdateTime;
                if (delta < maxDeltaPerUpdate) {
                    realWatched += delta / 1000;
                }
            });

            video.addEventListener('timeupdate', () => {
                if (!playing) return;

                const now = Date.now();
                const deltaTime = now - lastUpdateTime;
                const currentTime = video.currentTime * 1000;
                const timeDiff = currentTime - lastCurrentTime * 1000;

                if (timeDiff > deltaTime + maxDeltaPerUpdate) {
                    console.log('Seek detected, ignoring jump');
                    lastCurrentTime = video.currentTime;
                } else {
                    realWatched += deltaTime / 1000;
                }

                lastUpdateTime = now;
                lastCurrentTime = video.currentTime;

                updateProgressBar(video, realWatched);

                if (video.duration && realWatched >= CONFIG.minWatchPercentToComplete * video.duration) {
                    markLessonCompleted(lessonId, video);
                }
            });

            video.addEventListener('seeked', () => {
                lastCurrentTime = video.currentTime;
                if (playing) {
                    lastUpdateTime = Date.now();
                }
            });

            video.addEventListener('ended', () => {
                playing = false;
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
                UIUtils.showToast('Lỗi tải video. Vui lòng thử lại.', 'error');
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
                    watched_seconds: Math.floor(realWatched),
                    duration: Math.floor(video.duration || 0),
                    current_time: Math.floor(video.currentTime),
                    event: eventName,
                };

                try {
                    await apiRequest('/lesson/track', {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                    failedHeartbeats = 0;
                } catch (e) {
                    failedHeartbeats++;
                    console.warn('Heartbeat failed', failedHeartbeats, e);

                    if (failedHeartbeats >= 3) {
                        UIUtils.showToast('Mất kết nối. Tiến độ có thể không được lưu.', 'warning');
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

            UIUtils.showToast('Bạn đã xem đủ video để làm bài kiểm tra!', 'success');
        }
    }

    async function completeLesson(lessonId) {
        try {
            UIUtils.showLoading();
            const result = await apiRequest('/lesson/complete', {
                method: 'POST',
                body: JSON.stringify({ lesson_id: lessonId }),
            });

            UIUtils.hideLoading();

            if (result.status === 'completed') {
                UIUtils.showToast('Chúc mừng! Bạn đã hoàn thành khóa học.', 'success');
                setTimeout(() => window.location.reload(), 2000);
            }
        } catch (e) {
            UIUtils.hideLoading();
            UIUtils.showToast('Không thể hoàn thành. Vui lòng thử lại.', 'error');
        }
    }

    /* ===================================   
       MCQ HANDLING
    =================================== */
    function initMCQ() {
        qsa('.answer-option').forEach((el) => {
            el.addEventListener('click', onAnswerSelect);
        });

        qsa('.exam-form input[type="radio"]').forEach((radio) => {
            radio.addEventListener('change', (ev) => {
                const label = ev.target.closest('.answer-option');
                if (label) {
                    onAnswerSelect({ currentTarget: label, preventDefault: () => {} });
                }
            });
        });
    }

    async function onAnswerSelect(ev) {
        const el = ev.currentTarget;
        const answerId = el.dataset.answerId;
        const questionId = el.dataset.questionId;

        if (!answerId || !questionId) return;

        if (el.classList.contains('selected')) return;

        qsa(`.answer-option[data-question-id="${questionId}"]`).forEach((a) => {
            a.classList.remove('selected', 'correct', 'incorrect');
        });

        el.classList.add('selected');
        state.selectedAnswers.set(questionId, answerId);

        el.style.transform = 'scale(0.98)';
        setTimeout(() => {
            el.style.transform = '';
        }, 150);

        updateSubmitButton();
    }

    function updateSubmitButton() {
        const submitBtn = qs('.exam-form button[type="submit"]');
        const totalQuestions = qsa('.question-card').length;

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
        qsa('.take-exam-btn').forEach((btn) => {
            btn.addEventListener('click', handleExamStartOverride);
        });

        qsa('.complete-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const lessonId = e.currentTarget.dataset.subjectId;
                if (lessonId && !e.currentTarget.disabled) {
                    completeLesson(lessonId);
                }
            });
        });
    }

    async function handleExamStartOverride(ev) {
        ev.preventDefault();
        const btn = ev.currentTarget;
        const subjectId = btn.dataset.subjectId;
        console.log(subjectId);

        if (!subjectId) {
            UIUtils.showToast('Lỗi: Không tìm thấy thông tin khóa học', 'error');
            return;
        }

        if (!confirm('Bạn có chắc chắn muốn bắt đầu bài kiểm tra? Bạn sẽ không thể quay lại video sau khi bắt đầu.')) {
            return;
        }

        try {
            UIUtils.showLoading();
            const result = await apiRequest(`/exam/${subjectId}/start`, {
                method: 'POST',
                body: JSON.stringify({ subject_id: subjectId }),
            });

            if (!result.success) {
                throw new Error(result.error || 'Unknown error');
            }

            state.currentExamId = result.exam_id;
            renderExam(result.questions, result.time_limit);
            history.pushState({}, '', `/Training/exam/${subjectId}/start`);

            UIUtils.hideLoading();

            const videoContainer = qs('.video-container');
            const examContainer = qs('.exam-container');

            if (videoContainer) videoContainer.style.display = 'none';
            if (examContainer) {
                examContainer.style.display = 'block';
                examContainer.scrollIntoView({ behavior: 'smooth' });
            }

            if (result.time_limit) {
                startExamTimer(result.time_limit);
            }
        } catch (e) {
            UIUtils.hideLoading();
            if (e.message.includes('HTTP 403') && e.message.includes('Bạn đã vượt qua bài kiểm tra này')) {
                UIUtils.showToast('Bạn đã vượt qua bài kiểm tra này và không thể làm lại.', 'info');
            } else {
                UIUtils.showToast('Không thể bắt đầu bài kiểm tra: ' + e.message, 'error');
            }
            console.error('Exam start error:', e);
        }
    }

    function renderExam(questions, timeLimit) {
        const examForm = qs('.exam-form');
        if (!examForm) return;

        examForm.setAttribute('data-exam-id', state.currentExamId);
        examForm.innerHTML = '';

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
                    <div class="question-card">
                        <div class="question-header">
                            <span class="question-number">Câu ${index + 1}</span>
                        </div>
                        <h4 class="question-text">${UIUtils.escapeHtml(q.QuestionText)}</h4>
                        <div class="answers">
                            ${q.answers
                                .map(
                                    (a) => `
                                    <label class="answer-option"
                                        data-answer-id="${a.id}" 
                                        data-question-id="${q.ID}">
                                        <input type="radio" 
                                            name="question_${q.ID}"
                                            value="${a.id}">
                                        <span class="answer-text">${UIUtils.escapeHtml(a.text)}</span>
                                    </label>
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

        initMCQ();
        examForm.addEventListener('submit', handleExamSubmit);
    }

    function startExamTimer(minutes) {
        const timerEl = qs('.timer-display');
        if (!timerEl) return;

        let timeRemaining = minutes * 60;

        const timerInterval = setInterval(() => {
            timeRemaining--;

            const mins = Math.floor(timeRemaining / 60);
            const secs = timeRemaining % 60;
            timerEl.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;

            if (timeRemaining === 300) {
                UIUtils.showToast('Còn 5 phút!', 'warning');
                timerEl.closest('.exam-timer').classList.add('warning');
            }

            if (timeRemaining === 60) {
                UIUtils.showToast('Còn 1 phút!', 'warning');
                timerEl.closest('.exam-timer').classList.add('danger');
            }

            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                UIUtils.showToast('Hết giờ! Tự động nộp bài.', 'error');

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

        const answers = Array.from(state.selectedAnswers.entries()).map(([qId, aId]) => ({
            question_id: qId,
            answer_id: aId,
        }));

        if (!confirm(`Bạn đã trả lời ${answers.length} câu hỏi. Bạn có chắc chắn muốn nộp bài?`)) {
            state.isSubmitting = false;
            return;
        }

        try {
            UIUtils.showLoading();
            const result = await apiRequest(`/exam/${subjectId}/submit`, {
                method: 'POST',
                body: JSON.stringify({
                    exam_id: examId,
                    answers: answers,
                }),
            });

            UIUtils.hideLoading();

            if (!result.success) {
                throw new Error(result.error || 'Submit failed');
            }

            showExamResult(result);
        } catch (e) {
            UIUtils.hideLoading();
            UIUtils.showToast('Không thể nộp bài: ' + e.message, 'error');
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
        const subjectId = qs('[data-subject-id]')?.dataset.subjectId;

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

        if (passed) {
            const certBtn = qs('.btn-certificate', overlay);
            certBtn.addEventListener('click', () => {
                window.location.href = CONFIG.apiBase + '/certificates';
            });
        } else {
            const retryBtn = qs('.btn-retry', overlay);
            retryBtn.addEventListener('click', () => {
                window.location.href = `${CONFIG.apiBase}/exam/${subjectId}/start`;
            });
        }

        const closeBtn = qs('.btn-close', overlay);
        closeBtn.addEventListener('click', () => {
            window.location.href = CONFIG.apiBase + '/dashboard';
        });

        // Vô hiệu hóa nút "Làm lại" nếu đã vượt qua
        if (passed) {
            const retryBtn = qs('.btn-retry', overlay);
            if (retryBtn) {
                retryBtn.disabled = true;
                retryBtn.classList.add('disabled');
                retryBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    UIUtils.showToast('Bạn đã vượt qua bài kiểm tra này và không thể làm lại.', 'info');
                });
            }
        }
    }

    /* ===================================
       SEARCH FUNCTIONALITY
    =================================== */
    function initSearch() {
        const searchForm = qs('.search-form');
        const searchInput = qs('.search-form input');

        if (!searchForm || !searchInput) return;

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
                    UIUtils.showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'warning');
                }
            });
        });

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

        notificationBtn.addEventListener('click', async () => {
            const dropdown = notificationBtn.closest('.dropdown');
            const menu = qs('.dropdown-menu', dropdown);

            // ensure dropdown visually opens
            if (dropdown && !dropdown.classList.contains('active')) {
                dropdown.classList.add('active');
            }

            if (!menu) return;

            if (menu.children.length === 0) {
                menu.innerHTML = '<div class="dropdown-loading">Đang tải...</div>';

                try {
                    const notifications = await apiRequest('/notifications');
                    if (notifications.success) {
                        renderNotifications(menu, notifications.notifications);
                    } else {
                        throw new Error(notifications.error || 'No notifications');
                    }
                } catch (e) {
                    console.error('Notifications load failed:', e);
                    menu.innerHTML = '<div class="dropdown-error">Không thể tải thông báo: ' + e.message + '</div>';
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
                    <div class="notification-title">${UIUtils.escapeHtml(n.title)}</div>
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
       PROGRESS TRACKING
    =================================== */
    function initProgressTracking() {
        updateProgressDisplay();
        setInterval(updateProgressDisplay, 60000);
    }

    function updateProgressDisplay() {
        const progressBars = qsa('.progress-bar .progress');

        progressBars.forEach((bar) => {
            const targetWidth = bar.style.width;
            if (targetWidth) {
                UIUtils.animateProgress(bar, targetWidth);
            }
        });
    }

    /* ===================================
       AUTO SAVE FORMS
    =================================== */
    function initAutoSave() {
        qsa('form[data-autosave]').forEach((form) => {
            const formId = form.dataset.autosave;

            loadFormData(form, formId);

            let saveTimeout;
            form.addEventListener('input', () => {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    saveFormData(form, formId);
                }, 1000);
            });

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
       PWA INSTALL PROMPT
    =================================== */
    function initPWAInstall() {
        let deferredPrompt;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;

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
            UIUtils.showToast('Ứng dụng đã được cài đặt thành công!', 'success');
            deferredPrompt = null;
        });
    }

    /* ===================================
       ERROR TRACKING
    =================================== */
    function initErrorTracking() {
        window.addEventListener('error', (e) => {
            console.error('Global error:', e.error);
        });

        window.addEventListener('unhandledrejection', (e) => {
            console.error('Unhandled promise rejection:', e.reason);
        });
    }

    /* ===================================
       API REQUEST HELPER
    =================================== */
    async function apiRequest(url, options = {}) {
        try {
            console.log(CONFIG.apiBase + url);
            const response = await fetch(CONFIG.apiBase + url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers,
                },
            });

            const responseClone = response.clone();

            if (!response.ok) {
                const errorText = await responseClone.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const text = await responseClone.text();
            console.log('[API RAW RESPONSE]', text);

            let jsonStr = text.trim();
            if (jsonStr.includes('}{')) {
                const parts = jsonStr.split('}{');
                jsonStr = '{' + parts.pop();
            }

            if (!jsonStr.startsWith('{')) {
                throw new Error(`Invalid JSON response: Response starts with "${jsonStr.substring(0, 50)}..."`);
            }

            return JSON.parse(jsonStr);
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }

    /* ===================================
       INITIALIZE ALL
    =================================== */
    function initAll() {
        // Initialize modular components
        UIUtils.init();
        TabsModule.init();

        // Platform-specific logic
        initVideoTracking();
        initExamHandlers();
        initMCQ();
        initSearch();
        initFormValidation();
        initNotifications();
        initProgressTracking();
        initAutoSave();
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
        showToast: UIUtils.showToast,
        showLoading: UIUtils.showLoading,
        hideLoading: UIUtils.hideLoading,
        apiRequest,
    };
})();
