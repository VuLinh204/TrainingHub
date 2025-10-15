/**
 * Enhanced Training Platform JavaScript
 * Features: Video tracking, Exam handling, Notifications, PWA support
 */

(function () {
    'use strict';

    const CONFIG = {
        heartbeatIntervalSec: 10,
        minWatchPercentToComplete: 0.9,
        allowedSeekSeconds: 5,
        baseUrl: window.location.origin + (window.BASE_URL || ''),
        endpoints: {
            track: '/api/lesson/track',
            complete: '/api/lesson/complete',
            examStart: '/api/exam/start',
            checkAnswer: '/api/exam/check-answer',
            examSubmit: '/api/exam/submit'
        }
    };

    // Utility functions
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    // Toast notification system
    class ToastManager {
        constructor() {
            this.container = this.createContainer();
        }

        createContainer() {
            let container = $('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container';
                document.body.appendChild(container);
            }
            return container;
        }

        show(message, type = 'info', duration = 3500) {
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

            this.container.appendChild(toast);
            setTimeout(() => toast.classList.add('visible'), 10);
            
            setTimeout(() => {
                toast.classList.remove('visible');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
    }

    const toast = new ToastManager();

    // Video tracking system
    class VideoTracker {
        constructor(videoElement) {
            this.video = videoElement;
            this.lessonId = videoElement.dataset.lessonId;
            this.watched = 0;
            this.lastTime = 0;
            this.lastSavedTime = 0;
            this.playing = false;
            this.heartbeatTimer = null;
            this.completed = false;

            this.init();
        }

        init() {
            this.video.addEventListener('play', () => this.onPlay());
            this.video.addEventListener('pause', () => this.onPause());
            this.video.addEventListener('timeupdate', () => this.onTimeUpdate());
            this.video.addEventListener('seeking', () => this.onSeeking());
            this.video.addEventListener('ended', () => this.onEnded());
            
            // Load saved progress
            this.loadProgress();
        }

        async loadProgress() {
            try {
                const response = await fetch(`${CONFIG.baseUrl}/api/subject/${this.lessonId}/progress`);
                const data = await response.json();
                if (data.watched_seconds) {
                    this.video.currentTime = Math.min(data.watched_seconds, this.video.duration);
                    this.watched = data.watched_seconds;
                    this.lastSavedTime = data.watched_seconds;
                }
            } catch (e) {
                console.warn('Could not load progress', e);
            }
        }

        onPlay() {
            this.playing = true;
            this.startHeartbeat();
        }

        onPause() {
            this.playing = false;
            this.stopHeartbeat();
            this.sendHeartbeat('pause');
        }

        onTimeUpdate() {
            const t = Math.floor(this.video.currentTime);
            
            // Accumulate watched time
            if (t > this.lastTime && this.playing) {
                this.watched += (t - this.lastTime);
            }
            this.lastTime = t;

            // Anti-skip protection
            if (this.video.currentTime - this.lastSavedTime > CONFIG.allowedSeekSeconds + 1) {
                toast.show('Không được tua video quá nhanh!', 'warning');
                this.video.currentTime = this.lastSavedTime + CONFIG.allowedSeekSeconds;
                return;
            }

            // Update progress bar
            this.updateProgressBar();

            // Check completion
            if (!this.completed && this.video.duration && 
                this.watched / this.video.duration >= CONFIG.minWatchPercentToComplete) {
                this.markCompleted();
            }
        }

        onSeeking() {
            if (this.video.currentTime - this.lastSavedTime > CONFIG.allowedSeekSeconds) {
                toast.show('Bạn cần xem video theo thứ tự', 'warning');
                this.video.currentTime = Math.max(this.lastSavedTime, 0);
            }
        }

        onEnded() {
            this.watched = Math.max(this.watched, Math.floor(this.video.duration || 0));
            this.sendHeartbeat('ended');
            this.markCompleted();
        }

        updateProgressBar() {
            const progressBar = $('.progress', this.video.closest('.video-container'));
            if (progressBar && this.video.duration) {
                const percent = Math.min(100, (this.watched / this.video.duration) * 100);
                progressBar.style.width = `${percent}%`;
            }
        }

        startHeartbeat() {
            if (this.heartbeatTimer) return;
            this.heartbeatTimer = setInterval(() => {
                this.sendHeartbeat('heartbeat');
            }, CONFIG.heartbeatIntervalSec * 1000);
        }

        stopHeartbeat() {
            if (this.heartbeatTimer) {
                clearInterval(this.heartbeatTimer);
                this.heartbeatTimer = null;
            }
        }

        async sendHeartbeat(eventName) {
            this.lastSavedTime = Math.floor(this.video.currentTime || this.lastSavedTime);
            
            const payload = {
                lesson_id: this.lessonId,
                watched_seconds: this.watched,
                current_time: this.lastSavedTime,
                duration: Math.floor(this.video.duration || 0),
                event: eventName
            };

            try {
                await fetch(CONFIG.baseUrl + CONFIG.endpoints.track, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });
            } catch (e) {
                console.warn('Heartbeat failed', e);
            }
        }

        async markCompleted() {
            if (this.completed) return;
            this.completed = true;

            // Enable exam button if exists
            const examBtn = $('.take-exam-btn', this.video.closest('[data-subject-id]'));
            if (examBtn) {
                examBtn.disabled = false;
                examBtn.classList.remove('disabled');
                examBtn.textContent = 'Làm bài kiểm tra';
                toast.show('Đã hoàn thành video! Bạn có thể làm bài kiểm tra', 'success');
            } else {
                // No exam, mark as complete
                try {
                    const response = await fetch(CONFIG.baseUrl + CONFIG.endpoints.complete, {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ lesson_id: this.lessonId })
                    });
                    const data = await response.json();
                    if (data.status === 'completed') {
                        toast.show('Chúc mừng! Bạn đã hoàn thành khóa học', 'success');
                        setTimeout(() => window.location.reload(), 2000);
                    }
                } catch (e) {
                    console.warn('Complete request failed', e);
                }
            }
        }
    }

    // Exam system
    class ExamManager {
        constructor(container) {
            this.container = container;
            this.examId = null;
            this.questions = [];
            this.answers = {};
            this.timeLimit = 0;
            this.startTime = null;
            this.timerInterval = null;
        }

        async start(subjectId) {
            try {
                const response = await fetch(`${CONFIG.baseUrl}/exam/${subjectId}/start`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    const error = await response.json();
                    toast.show(error.error || 'Không thể bắt đầu bài kiểm tra', 'error');
                    return;
                }

                const data = await response.json();
                this.examId = data.exam_id;
                this.questions = data.questions;
                this.timeLimit = data.time_limit;
                this.startTime = Date.now();

                this.render();
                this.startTimer();
                toast.show('Bài kiểm tra đã bắt đầu. Chúc bạn may mắn!', 'info');
            } catch (e) {
                console.error('Exam start error:', e);
                toast.show('Lỗi khi bắt đầu bài kiểm tra', 'error');
            }
        }

        render() {
            const examForm = $('.exam-form', this.container);
            if (!examForm) return;

            examForm.dataset.examId = this.examId;
            examForm.innerHTML = `
                <div class="exam-header">
                    <h2>Bài kiểm tra (${this.questions.length} câu hỏi)</h2>
                    <div class="exam-timer">
                        <i class="fas fa-clock"></i>
                        <span id="timer">${this.timeLimit}:00</span>
                    </div>
                </div>
                <div class="questions-container">
                    ${this.questions.map((q, idx) => this.renderQuestion(q, idx)).join('')}
                </div>
                <div class="exam-actions">
                    <button type="button" class="btn-primary" onclick="examManager.submit()">
                        <i class="fas fa-paper-plane"></i> Nộp bài
                    </button>
                </div>
            `;

            this.container.style.display = 'block';
            this.attachEventListeners();
            
            // Scroll to exam
            this.container.scrollIntoView({ behavior: 'smooth' });
        }

        renderQuestion(question, index) {
            return `
                <div class="question" data-question-id="${question.ID}">
                    <h3>Câu ${index + 1}: ${question.QuestionText}</h3>
                    <div class="answers">
                        ${question.answers.map(ans => this.renderAnswer(ans, question.ID)).join('')}
                    </div>
                </div>
            `;
        }

        renderAnswer(answer, questionId) {
            return `
                <div class="mcq-answer" 
                     data-answer-id="${answer.ID}" 
                     data-question-id="${questionId}">
                    ${answer.AnswerText}
                </div>
            `;
        }

        attachEventListeners() {
            $$('.mcq-answer', this.container).forEach(el => {
                el.addEventListener('click', (e) => this.selectAnswer(e.currentTarget));
            });
        }

        selectAnswer(element) {
            const questionId = element.dataset.questionId;
            const answerId = element.dataset.answerId;

            // Clear previous selection for this question
            $$(`.mcq-answer[data-question-id="${questionId}"]`, this.container).forEach(el => {
                el.classList.remove('selected');
            });

            // Select this answer
            element.classList.add('selected');
            this.answers[questionId] = answerId;
        }

        startTimer() {
            const timerEl = $('#timer');
            if (!timerEl) return;

            this.timerInterval = setInterval(() => {
                const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                const remaining = (this.timeLimit * 60) - elapsed;

                if (remaining <= 0) {
                    clearInterval(this.timerInterval);
                    toast.show('Hết thời gian! Đang tự động nộp bài...', 'warning');
                    this.submit();
                    return;
                }

                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                timerEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

                // Warning when 5 minutes left
                if (remaining === 300) {
                    toast.show('Còn 5 phút!', 'warning');
                }
            }, 1000);
        }

        async submit() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
            }

            // Validate all questions answered
            const unanswered = this.questions.filter(q => !this.answers[q.ID]);
            if (unanswered.length > 0) {
                const confirm = window.confirm(
                    `Bạn còn ${unanswered.length} câu chưa trả lời. Bạn có chắc muốn nộp bài?`
                );
                if (!confirm) return;
            }

            // Prepare submission
            const submission = {
                exam_id: this.examId,
                answers: Object.entries(this.answers).map(([qId, aId]) => ({
                    question_id: qId,
                    answer_id: aId
                }))
            };

            try {
                const response = await fetch(`${CONFIG.baseUrl}${CONFIG.endpoints.examSubmit}`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(submission)
                });

                if (!response.ok) {
                    throw new Error('Submit failed');
                }

                const result = await response.json();
                this.showResult(result);
            } catch (e) {
                console.error('Submit error:', e);
                toast.show('Lỗi khi nộp bài. Vui lòng thử lại', 'error');
            }
        }

        showResult(result) {
            const overlay = document.createElement('div');
            overlay.className = 'exam-result-overlay';
            overlay.style.opacity = '1';

            const passed = result.passed;
            const percentage = result.percentage || 0;

            overlay.innerHTML = `
                <div class="exam-result-card ${passed ? 'passed' : 'failed'}">
                    <div class="result-icon">
                        <i class="fas fa-${passed ? 'check-circle' : 'times-circle'}"></i>
                    </div>
                    <h2>${passed ? 'Chúc mừng!' : 'Chưa đạt'}</h2>
                    <div class="result-score">
                        <strong>${percentage.toFixed(1)}%</strong>
                    </div>
                    <div class="result-message">
                        <p>Bạn đã trả lời đúng <strong>${result.correct_answers}/${result.total_questions}</strong> câu</p>
                        <p>${result.message}</p>
                    </div>
                    <div class="exam-result-actions">
                        ${passed 
                            ? '<button class="btn-primary" onclick="window.location.reload()">Xem chứng chỉ</button>'
                            : '<button class="btn-primary" onclick="window.location.reload()">Làm lại</button>'
                        }
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);
        }
    }

    // Global exam manager instance
    window.examManager = null;

    // Sidebar & Dropdown handling
    class UIManager {
        constructor() {
            this.initSidebar();
            this.initDropdowns();
            this.initSearch();
        }

        initSidebar() {
            const toggle = $('.menu-toggle');
            if (toggle) {
                toggle.addEventListener('click', () => {
                    document.body.classList.toggle('sidebar-open');
                });

                // Close sidebar when clicking outside
                document.addEventListener('click', (e) => {
                    if (document.body.classList.contains('sidebar-open') &&
                        !e.target.closest('.sidebar') &&
                        !e.target.closest('.menu-toggle')) {
                        document.body.classList.remove('sidebar-open');
                    }
                });
            }
        }

        initDropdowns() {
            $$('.dropdown-toggle').forEach(toggle => {
                toggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const dropdown = toggle.closest('.dropdown');
                    
                    // Close other dropdowns
                    $$('.dropdown.active').forEach(d => {
                        if (d !== dropdown) d.classList.remove('active');
                    });
                    
                    dropdown.classList.toggle('active');
                });
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', () => {
                $$('.dropdown.active').forEach(d => d.classList.remove('active'));
            });
        }

        initSearch() {
            const searchForm = $('.search-form');
            if (searchForm) {
                const input = $('input', searchForm);
                input.addEventListener('keyup', (e) => {
                    if (e.key === 'Escape') {
                        input.value = '';
                        input.blur();
                    }
                });
            }
        }
    }

    // Initialize everything
    function init() {
        // Video tracking
        $$('video.lesson-video').forEach(video => {
            new VideoTracker(video);
        });

        // Exam button handlers
        $$('.take-exam-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const subjectId = btn.dataset.subjectId || 
                                 btn.closest('[data-subject-id]')?.dataset.subjectId;
                if (!subjectId) return;

                const examContainer = $('.exam-container');
                if (examContainer) {
                    window.examManager = new ExamManager(examContainer);
                    window.examManager.start(subjectId);
                    
                    // Hide video container
                    const videoContainer = $('.video-container');
                    if (videoContainer) {
                        videoContainer.style.display = 'none';
                    }
                }
            });
        });

        // UI enhancements
        new UIManager();

        // Form validation
        $$('form[data-validate]').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }

    // Form validation helper
    function validateForm(form) {
        let valid = true;
        $$('input[required], textarea[required], select[required]', form).forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('error');
                valid = false;
            } else {
                field.classList.remove('error');
            }
        });
        return valid;
    }

    // Auto-init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose toast globally
    window.showToast = (message, type) => toast.show(message, type);

})();