/*
	main.js
	- Video watch-time tracking (heartbeat to server)
	- Anti-skip protection (prevent seeking far ahead)
	- Enable "Take Exam" when lesson completed
	- MCQ selection UI with immediate green/red outline feedback
	- Exam completion screens (pass/fail) with retry / certificate actions

	Notes:
	- Server endpoints expected (examples):
			POST /lesson/track    { lesson_id, watched_seconds, duration, event }
			POST /lesson/complete { lesson_id }
			POST /exam/check     { question_id, answer_id }
			POST /exam/submit    { exam_id, answers: [...] }
	- You can adapt endpoint URLs to match your PHP routes.
*/

(function () {
    'use strict';

    const CONFIG = {
        heartbeatIntervalSec: 10,
        minWatchPercentToComplete: 0.9, // 90% watched to auto-complete
        allowedSeekSeconds: 5, // small allowed jump
        trackUrl: '/lesson/track',
        completeUrl: '/lesson/complete',
        checkAnswerUrl: '/exam/check',
        submitExamUrl: '/exam/submit',
    };

    // Utilities
    function qs(selector, root = document) {
        return root.querySelector(selector);
    }
    function qsa(selector, root = document) {
        return Array.from(root.querySelectorAll(selector));
    }

    /* -----------------------------
		 Video watch-time tracking
		 - expects <video class="lesson-video" data-lesson-id="..."> in DOM
		 - server must accept heartbeat posts
	------------------------------*/
    function initVideoTracking() {
        qsa('video.lesson-video').forEach(setupVideo);

        function setupVideo(video) {
            const lessonId = video.dataset.lessonId;
            if (!lessonId) return;

            let watched = 0; // seconds accumulated while playing
            let lastTime = 0; // last observed currentTime
            let lastSavedTime = 0; // last persisted time marker
            let playing = false;
            let heartbeatTimer = null;

            // accumulate when playing
            video.addEventListener('play', () => {
                playing = true;
                startHeartbeat();
            });

            video.addEventListener('pause', () => {
                playing = false;
                stopHeartbeat();
            });

            video.addEventListener('timeupdate', () => {
                const t = Math.floor(video.currentTime);
                if (t > lastTime) {
                    watched += t - lastTime;
                }
                lastTime = t;

                // small anti-skip: if user seeks forward beyond allowed, revert
                if (video.currentTime - lastSavedTime > CONFIG.allowedSeekSeconds + 1 && video.currentTime > lastSavedTime + CONFIG.allowedSeekSeconds) {
                    // mark as suspicious and prevent skip
                    showToast('Bạn không được tua vượt quá ' + CONFIG.allowedSeekSeconds + 's', 'warning');
                    video.currentTime = lastSavedTime + CONFIG.allowedSeekSeconds;
                }

                // check completion threshold
                if (video.duration && watched / video.duration >= CONFIG.minWatchPercentToComplete) {
                    markLessonCompleted(lessonId, video);
                }
            });

            video.addEventListener('seeking', () => {
                // if seeking far ahead, cancel and warn
                if (video.currentTime - lastSavedTime > CONFIG.allowedSeekSeconds) {
                    showToast('Tua nhanh bị giới hạn để đảm bảo tính trung thực.', 'warning');
                    video.currentTime = Math.max(lastSavedTime, 0);
                }
            });

            video.addEventListener('ended', () => {
                // finalize and mark completed
                watched = Math.max(watched, Math.floor(video.duration || 0));
                sendHeartbeat('ended');
                markLessonCompleted(lessonId, video);
            });

            function startHeartbeat() {
                if (heartbeatTimer) return;
                heartbeatTimer = setInterval(() => sendHeartbeat('heartbeat'), CONFIG.heartbeatIntervalSec * 1000);
            }

            function stopHeartbeat() {
                if (heartbeatTimer) {
                    clearInterval(heartbeatTimer);
                    heartbeatTimer = null;
                }
            }

            async function sendHeartbeat(eventName) {
                lastSavedTime = Math.floor(video.currentTime || lastSavedTime);
                const payload = {
                    lesson_id: lessonId,
                    watched_seconds: watched,
                    duration: Math.floor(video.duration || 0),
                    event: eventName,
                };

                try {
                    await fetch(CONFIG.trackUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });
                } catch (e) {
                    console.warn('Heartbeat failed', e);
                }
            }
        }
    }

    /* -----------------------------
		 Mark lesson completed
		 - enables .take-exam-btn[data-subject-id] button if exists
		 - if no exam -> call server to mark complete
	------------------------------*/
    async function markLessonCompleted(lessonId, videoEl) {
        // find take exam button for this lesson subject
        const container = videoEl.closest('[data-subject-id]') || document;
        const btn = container.querySelector('.take-exam-btn');
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('disabled');
            btn.textContent = 'Làm bài kiểm tra';
        } else {
            // auto-complete by calling server
            try {
                await fetch(CONFIG.completeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lesson_id: lessonId }),
                });
                showToast('Hoàn thành bài học', 'success');
            } catch (e) {
                console.warn('Complete request failed', e);
            }
        }
    }

    /* -----------------------------
		 MCQ behavior
		 - answers: .mcq-answer[data-answer-id][data-question-id][data-is-correct?]
		 - if server needs to check, we call CONFIG.checkAnswerUrl
	------------------------------*/
    function initMCQ() {
        qsa('.mcq-answer').forEach((el) => el.addEventListener('click', onSelect));

        async function onSelect(ev) {
            const el = ev.currentTarget;
            const answerId = el.dataset.answerId;
            const questionId = el.dataset.questionId;
            if (!answerId || !questionId) return;

            // clear previous outlines for this question
            qsa(`.mcq-answer[data-question-id="${questionId}"]`).forEach((a) => {
                a.classList.remove('selected', 'correct', 'incorrect');
            });

            el.classList.add('selected');

            // if server rendered correctness info, use it
            if (el.dataset.isCorrect !== undefined) {
                const ok = el.dataset.isCorrect === '1' || el.dataset.isCorrect === 'true';
                el.classList.add(ok ? 'correct' : 'incorrect');
                return;
            }

            // otherwise check with server
            try {
                const res = await fetch(CONFIG.checkAnswerUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ question_id: questionId, answer_id: answerId }),
                });
                const data = await res.json();
                const ok = data && data.is_correct;
                el.classList.add(ok ? 'correct' : 'incorrect');
            } catch (e) {
                console.warn('Answer check failed', e);
            }
        }
    }

    /* -----------------------------
		 Exam submit flow and final UI
		 - expects a form .exam-form with inputs or data attributes mapping answers
	------------------------------*/
    function initExamSubmit() {
        const forms = qsa('.exam-form');
        forms.forEach((form) =>
            form.addEventListener('submit', async (ev) => {
                ev.preventDefault();
                const formEl = ev.currentTarget;
                const examId = formEl.dataset.examId;
                // collect answers
                const answers = [];
                qsa('.mcq-answer.selected', formEl).forEach((a) => {
                    answers.push({ question_id: a.dataset.questionId, answer_id: a.dataset.answerId });
                });

                try {
                    const res = await fetch(CONFIG.submitExamUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ exam_id: examId, answers }),
                    });
                    const data = await res.json();
                    showExamResult(data, formEl);
                } catch (e) {
                    console.error('Exam submit failed', e);
                }
            })
        );
    }

    function showExamResult(data, container) {
        // data: { passed: bool, score: number, required: number }
        const passed = data && data.passed;
        const score = (data && data.score) || 0;
        const required = (data && data.required) || 0;

        // create overlay
        const overlay = document.createElement('div');
        overlay.className = 'exam-result-overlay';
        overlay.innerHTML = `
			<div class="exam-result-card ${passed ? 'passed' : 'failed'}">
				<h2>${passed ? 'Chúc mừng!' : 'Ôi không...'}</h2>
				<p>Điểm của bạn: <strong>${score}</strong> / ${required}</p>
				<div class="exam-result-actions">
					${passed ? '<button class="btn-certificate">Nhận chứng chỉ</button>' : '<button class="btn-retry">Làm lại</button>'}
				</div>
			</div>`;

        document.body.appendChild(overlay);

        if (passed) {
            qs('.btn-certificate', overlay).addEventListener('click', () => {
                // open certificate download/print page - implement server-side
                const subjectId = container.dataset.subjectId || '';
                window.location.href = `/certificate/generate?subject_id=${subjectId}`;
            });
        } else {
            qs('.btn-retry', overlay).addEventListener('click', () => {
                overlay.remove();
            });
        }
    }

    /* -----------------------------
		 Small UI helpers
	------------------------------*/
    function showToast(message, type = 'info') {
        // minimal toast
        const t = document.createElement('div');
        t.className = 'training-toast ' + type;
        t.textContent = message;
        document.body.appendChild(t);
        setTimeout(() => t.classList.add('visible'), 10);
        setTimeout(() => t.classList.remove('visible'), 3500);
        setTimeout(() => t.remove(), 4200);
    }

    /* -----------------------------
		 Init all
	------------------------------*/
    function initAll() {
        initVideoTracking();
        initMCQ();
        initExamSubmit();
    }

    // Auto init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else initAll();
})();
