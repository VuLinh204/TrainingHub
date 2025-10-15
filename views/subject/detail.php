<?php
// views/subject/detail.php - Hoàn chỉnh, tích hợp với model và JS tracking
// Giả sử controller truyền $subject (array từ SubjectModel->getWithProgress())

// Kiểm tra nếu subject không tồn tại
if (!isset($subject) || empty($subject)) {
    echo '<div class="alert alert-danger">Khóa học không tồn tại.</div>';
    return;
}
?>

<div class="subject-detail">
    <div class="video-container">
        <video class="lesson-video" 
               data-lesson-id="<?= htmlspecialchars($subject['ID']) ?>"
               controls
               controlsList="nodownload"
               preload="metadata">
            <source src="<?= htmlspecialchars($subject['VideoURL']) ?>" type="video/mp4">
            Trình duyệt của bạn không hỗ trợ video HTML5.
        </video>
        
        <div class="video-info">
            <h1><?= htmlspecialchars($subject['Title']) ?></h1>
            <p class="description"><?= htmlspecialchars($subject['Description'] ?? '') ?></p>
            
            <!-- Progress bar dựa trên thời gian xem thực tế -->
            <?php if (isset($subject['watched_seconds']) && $subject['watched_seconds'] > 0 && isset($subject['Duration']) && $subject['Duration'] > 0): ?>
                <div class="progress-info">
                    <span>Tiến độ xem: <?= number_format(($subject['watched_seconds'] / $subject['Duration']) * 100, 1) ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress" style="width: <?= min(100, ($subject['watched_seconds'] / $subject['Duration']) * 100) ?>%"></div>
                </div>
            <?php else: ?>
                <div class="progress-bar">
                    <div class="progress" style="width: 0%"></div>
                </div>
            <?php endif; ?>
            
            <!-- Nút hành động: Exam hoặc Complete -->
            <?php if (isset($subject['QuestionCount']) && $subject['QuestionCount'] > 0): ?>
                <!-- Có bài thi -->
                <button class="btn btn-primary take-exam-btn <?= (isset($subject['watched_seconds']) && $subject['watched_seconds'] >= ($subject['Duration'] * ($subject['MinWatchPercent'] ?? 0.9))) ? '' : 'disabled' ?>"
                        data-subject-id="<?= $subject['ID'] ?>"
                        <?= (isset($subject['watched_seconds']) && $subject['watched_seconds'] >= ($subject['Duration'] * ($subject['MinWatchPercent'] ?? 0.9))) ? '' : 'disabled' ?>>
                    <?php if (isset($subject['last_exam_passed']) && $subject['last_exam_passed']): ?>
                        <i class="fas fa-chart-line"></i> Xem kết quả
                    <?php else: ?>
                        <?= (isset($subject['watched_seconds']) && $subject['watched_seconds'] >= ($subject['Duration'] * ($subject['MinWatchPercent'] ?? 0.9))) ? '<i class="fas fa-clipboard-list"></i> Làm bài kiểm tra' : '<i class="fas fa-lock"></i> Xem video để mở khóa bài kiểm tra' ?>
                    <?php endif; ?>
                </button>
            <?php else: ?>
                <!-- Không có bài thi, chỉ hoàn thành video -->
                <button class="btn btn-success complete-btn <?= (isset($subject['watched_seconds']) && $subject['watched_seconds'] >= ($subject['Duration'] * ($subject['MinWatchPercent'] ?? 0.9))) ? '' : 'disabled' ?>"
                        data-subject-id="<?= $subject['ID'] ?>"
                        <?= (isset($subject['watched_seconds']) && $subject['watched_seconds'] >= ($subject['Duration'] * ($subject['MinWatchPercent'] ?? 0.9))) ? '' : 'disabled' ?>>
                    <i class="fas fa-check-circle"></i> <?= (isset($subject['watched_seconds']) && $subject['watched_seconds'] >= ($subject['Duration'] * ($subject['MinWatchPercent'] ?? 0.9))) ? 'Đánh dấu hoàn thành' : 'Xem video để hoàn thành' ?>
                </button>
            <?php endif; ?>
            
            <!-- Thông tin thêm -->
            <?php if (!empty($subject['FileURL'])): ?>
                <div class="material-link mt-3">
                    <a href="<?= htmlspecialchars($subject['FileURL']) ?>" target="_blank" class="btn btn-outline-secondary">
                        <i class="fas fa-file-pdf"></i> Tải tài liệu
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Container cho bài thi (ẩn ban đầu) -->
    <?php if (isset($subject['QuestionCount']) && $subject['QuestionCount'] > 0): ?>
        <div class="exam-container" style="display: none;">
            <div class="exam-timer" style="display: none;">
                <span class="time-remaining">10:00</span>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Hãy chọn câu trả lời đúng nhất cho mỗi câu hỏi. Thời gian: <span class="time-limit">10</span> phút.
            </div>
            <form class="exam-form" data-exam-id="" data-subject-id="<?= $subject['ID'] ?>">
                <!-- Questions will be loaded here by JS -->
            </form>
            <div class="exam-actions" style="display: none;">
                <button type="submit" class="btn btn-primary submit-exam">Nộp bài</button>
                <button type="button" class="btn btn-secondary reset-exam">Làm lại</button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Templates cho JS render -->
<template id="question-template">
    <div class="question card mb-3" data-question-id="">
        <div class="card-body">
            <h5 class="question-text card-title"></h5>
            <div class="answers list-group list-group-flush">
                <!-- Answer options will be inserted here -->
            </div>
        </div>
    </div>
</template>

<template id="answer-template">
    <div class="mcq-answer list-group-item list-group-item-action" data-answer-id="">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="answer[]" value="" id="">
            <label class="form-check-label answer-text"></label>
        </div>
    </div>
</template>

<!-- CSS bổ sung cho trang (nếu cần, inline hoặc external) -->
<style>
.video-container { position: relative; }
.lesson-video { width: 100%; height: auto; border-radius: 8px; }
.video-info { margin-top: 20px; }
.progress-bar { background: #e9ecef; height: 8px; border-radius: 4px; overflow: hidden; margin: 10px 0; }
.progress { background: linear-gradient(90deg, #28a745, #20c997); height: 100%; transition: width 0.3s ease; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.exam-container { margin-top: 30px; }
.question { margin-bottom: 20px; }
.mcq-answer { cursor: pointer; transition: background 0.2s; }
.mcq-answer:hover { background: #f8f9fa; }
.mcq-answer input:checked + .form-check-label { font-weight: bold; }
.exam-timer { text-align: center; font-size: 1.2em; font-weight: bold; color: #dc3545; margin-bottom: 20px; }
.exam-actions { text-align: center; margin-top: 30px; }
</style>

<script>
// JS để load và render exam questions/answers
document.addEventListener('DOMContentLoaded', function() {
    // Auto-play video nếu cần (tùy config)
    const video = document.querySelector('.lesson-video');
    if (video && !video.paused) {
        video.play().catch(e => console.log('Auto-play prevented:', e));
    }

    // Exam handling
    const takeExamBtn = document.querySelector('.take-exam-btn');
    const examContainer = document.querySelector('.exam-container');
    const examForm = document.querySelector('.exam-form');
    const timeLimit = parseInt(document.querySelector('.time-limit')?.textContent) || 10; // minutes
    let examTimer = null;
    let timeLeft = timeLimit * 60; // seconds

    if (takeExamBtn && examContainer) {
        takeExamBtn.addEventListener('click', function(e) {
            if (this.disabled) return;

            e.preventDefault();
            if (examContainer.style.display === 'none') {
                // Load questions via AJAX
                const subjectId = this.dataset.subjectId;
                fetch(`/subjects/${subjectId}`, {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.questions) {
                        renderQuestions(data.questions);
                        examContainer.style.display = 'block';
                        takeExamBtn.style.display = 'none';
                        startTimer();
                    } else {
                        alert(data.error || 'Không thể tải bài thi');
                    }
                })
                .catch(error => {
                    console.error('Error loading exam:', error);
                    alert('Lỗi tải bài thi');
                });
            }
        });
    }

    function renderQuestions(questions) {
        const examForm = document.querySelector('.exam-form');
        examForm.innerHTML = ''; // Clear previous

        questions.forEach(question => {
            const questionTemplate = document.getElementById('question-template').content.cloneNode(true);
            const questionEl = questionTemplate.querySelector('.question');
            questionEl.dataset.questionId = question.ID;
            questionEl.querySelector('.question-text').textContent = question.QuestionText;

            const answersContainer = questionEl.querySelector('.answers');

            question.answers.forEach((answer, index) => {
                const answerTemplate = document.getElementById('answer-template').content.cloneNode(true);
                const answerEl = answerTemplate.querySelector('.mcq-answer');
                answerEl.dataset.answerId = answer.ID;
                const radio = answerEl.querySelector('input[type="radio"]');
                radio.value = answer.ID;
                radio.name = `answer_${question.ID}`;
                radio.id = `answer_${question.ID}_${answer.ID}`;
                answerEl.querySelector('.answer-text').textContent = answer.AnswerText;
                answerEl.querySelector('.form-check-label').setAttribute('for', radio.id);

                answersContainer.appendChild(answerEl);
            });

            examForm.appendChild(questionTemplate);
        });

        // Enable submit button
        document.querySelector('.exam-actions').style.display = 'block';
    }

    function startTimer() {
        const timerEl = document.querySelector('.time-remaining');
        document.querySelector('.exam-timer').style.display = 'block';

        examTimer = setInterval(() => {
            timeLeft--;
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (timeLeft <= 0) {
                clearInterval(examTimer);
                submitExam(true); // Timeout
            }
        }, 1000);
    }

    // Form submit
    if (examForm) {
        examForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitExam(false);
        });
    }

    document.querySelector('.submit-exam')?.addEventListener('click', function() {
        submitExam(false);
    });

    document.querySelector('.reset-exam')?.addEventListener('click', function() {
        clearInterval(examTimer);
        timeLeft = timeLimit * 60;
        examForm.reset();
        // Re-render if needed
    });

    function submitExam(isTimeout = false) {
        clearInterval(examTimer);
        const formData = new FormData(examForm);
        const subjectId = examForm.dataset.subjectId;

        fetch('/subjects/submitExam', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                subject_id: subjectId,
                answers: Object.fromEntries(new FormData(examForm)),
                is_timeout: isTimeout
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Kết quả: ${data.score}/${data.total} - ${data.passed ? 'Đạt!' : 'Chưa đạt'}`);
                // Reload page or update UI
                location.reload();
            } else {
                alert(data.error || 'Lỗi nộp bài');
            }
        })
        .catch(error => {
            console.error('Error submitting exam:', error);
            alert('Lỗi nộp bài');
        });
    }
});
</script>