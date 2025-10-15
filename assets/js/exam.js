/**
 * Exam Module
 * Handles MCQ exam functionality including timer, answer selection, and submission
 */

const ExamModule = (function() {
    'use strict';

    let examTimer = null;
    let timeRemaining = 0;
    let examStartTime = null;

    /**
     * Initialize exam
     */
    function init() {
        initTimer();
        initAnswerSelection();
        initExamSubmission();
        initTakeExamButton();
        initCompleteButton();
    }

    /**
     * Initialize exam timer
     */
    function initTimer() {
        const timerDisplay = document.querySelector('.timer-display');
        const timerElement = document.querySelector('.exam-timer');
        
        if (!timerDisplay || !timerElement) return;

        const duration = parseInt(timerElement.dataset.duration) || 1800; // 30 minutes default
        timeRemaining = duration;
        examStartTime = Date.now();

        updateTimerDisplay(timerDisplay, timeRemaining);

        examTimer = setInterval(() => {
            timeRemaining--;
            updateTimerDisplay(timerDisplay, timeRemaining);

            // Warning state at 5 minutes
            if (timeRemaining === 300) {
                timerElement.classList.add('warning');
            }

            // Danger state at 1 minute
            if (timeRemaining === 60) {
                timerElement.classList.remove('warning');
                timerElement.classList.add('danger');
            }

            // Time's up
            if (timeRemaining <= 0) {
                clearInterval(examTimer);
                submitExam(true); // Auto-submit
            }
        }, 1000);
    }

    /**
     * Update timer display
     */
    function updateTimerDisplay(element, seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        element.textContent = `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    /**
     * Initialize answer selection
     */
    function initAnswerSelection() {
        const answerElements = document.querySelectorAll('.mcq-answer');
        
        answerElements.forEach(answer => {
            answer.addEventListener('click', function() {
                const questionId = this.dataset.questionId;
                
                // Remove selected class from all answers in this question
                document.querySelectorAll(`[data-question-id="${questionId}"]`).forEach(el => {
                    el.classList.remove('selected');
                });
                
                // Add selected class to clicked answer
                this.classList.add('selected');
                
                // Update hidden input if exists
                const hiddenInput = document.querySelector(`input[name="answers[${questionId}]"]`);
                if (hiddenInput) {
                    hiddenInput.value = this.dataset.answerId;
                }
            });
        });
    }

    /**
     * Initialize exam submission
     */
    function initExamSubmission() {
        const submitButton = document.querySelector('.submit-exam-btn');
        const examForm = document.getElementById('examForm');
        
        if (submitButton) {
            submitButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (confirm('Bạn có chắc chắn muốn nộp bài?')) {
                    submitExam(false);
                }
            });
        }

        if (examForm) {
            examForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitExam(false);
            });
        }
    }

    /**
     * Submit exam
     */
    function submitExam(isAutoSubmit) {
        if (examTimer) {
            clearInterval(examTimer);
        }

        const examForm = document.getElementById('examForm');
        if (!examForm) return;

        const formData = new FormData(examForm);
        const submitButton = document.querySelector('.submit-exam-btn');
        
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang chấm bài...';
        }

        // Add time taken
        const timeTaken = Math.floor((Date.now() - examStartTime) / 1000);
        formData.append('time_taken', timeTaken);
        formData.append('auto_submit', isAutoSubmit ? '1' : '0');

        fetch(examForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showExamResult(data);
            } else {
                alert('Có lỗi xảy ra: ' + (data.message || 'Vui lòng thử lại'));
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Nộp bài';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi nộp bài. Vui lòng thử lại.');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Nộp bài';
            }
        });
    }

    /**
     * Show exam result overlay
     */
    function showExamResult(data) {
        const overlay = document.createElement('div');
        overlay.className = 'exam-result-overlay';
        overlay.style.opacity = '0';
        
        const passed = data.passed || false;
        const score = data.score || 0;
        const correctAnswers = data.correct_answers || 0;
        const totalQuestions = data.total_questions || 0;
        
        overlay.innerHTML = `
            <div class="exam-result-card ${passed ? 'passed' : 'failed'}">
                <div class="result-icon">
                    <i class="fas ${passed ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                </div>
                <h2>${passed ? 'Chúc mừng!' : 'Chưa đạt'}</h2>
                <div class="result-score">
                    Điểm số: <strong>${Math.round(score)}%</strong>
                </div>
                <div class="result-message">
                    Bạn trả lời đúng ${correctAnswers}/${totalQuestions} câu hỏi
                    ${passed ? '<br>Bạn đã vượt qua bài kiểm tra!' : '<br>Vui lòng thử lại để đạt điểm cao hơn.'}
                </div>
                <div class="exam-result-actions">
                    <button onclick="window.location.href='${data.redirect_url || '/dashboard'}'" class="btn-primary">
                        ${passed ? 'Xem chứng chỉ' : 'Về trang chủ'}
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // Fade in
        setTimeout(() => {
            overlay.style.opacity = '1';
        }, 10);

        // Show correct/incorrect answers
        if (data.answers) {
            showAnswerFeedback(data.answers);
        }
    }

    /**
     * Show answer feedback
     */
    function showAnswerFeedback(answers) {
        Object.keys(answers).forEach(questionId => {
            const answer = answers[questionId];
            const selectedAnswer = document.querySelector(
                `[data-question-id="${questionId}"][data-answer-id="${answer.selected}"]`
            );
            const correctAnswer = document.querySelector(
                `[data-question-id="${questionId}"][data-answer-id="${answer.correct}"]`
            );

            if (selectedAnswer) {
                if (answer.is_correct) {
                    selectedAnswer.classList.add('correct');
                } else {
                    selectedAnswer.classList.add('incorrect');
                }
            }

            if (correctAnswer && !answer.is_correct) {
                correctAnswer.classList.add('correct');
            }
        });
    }

    /**
     * Initialize "Take Exam" button
     */
    function initTakeExamButton() {
        const takeExamBtn = document.querySelector('.take-exam-btn');
        
        if (takeExamBtn && !takeExamBtn.classList.contains('disabled')) {
            takeExamBtn.addEventListener('click', function() {
                const subjectId = this.dataset.subjectId;
                if (subjectId) {
                    window.location.href = `/exam/${subjectId}`;
                }
            });
        }
    }

    /**
     * Initialize "Complete" button (for subjects without exam)
     */
    function initCompleteButton() {
        const completeBtn = document.querySelector('.complete-btn');
        
        if (completeBtn && !completeBtn.classList.contains('disabled')) {
            completeBtn.addEventListener('click', function() {
                const subjectId = this.dataset.subjectId;
                
                if (!subjectId) return;
                
                if (confirm('Xác nhận đã hoàn thành khóa học này?')) {
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                    
                    fetch(`/api/subject/${subjectId}/complete`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Có lỗi xảy ra: ' + (data.message || 'Vui lòng thử lại'));
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-check-circle"></i> Đánh dấu hoàn thành';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra. Vui lòng thử lại.');
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-check-circle"></i> Đánh dấu hoàn thành';
                    });
                }
            });
        }
    }

    /**
     * Cleanup on page unload
     */
    function cleanup() {
        if (examTimer) {
            clearInterval(examTimer);
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
    document.addEventListener('DOMContentLoaded', ExamModule.init);
} else {
    ExamModule.init();
}

// Cleanup on page unload
window.addEventListener('beforeunload', ExamModule.cleanup);