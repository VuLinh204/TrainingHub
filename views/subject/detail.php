<?php
// Get subject info with progress
// $subject = $this->subjectModel->getWithProgress($id, $this->checkAuth());
?>

<div class="subject-detail">
    <div class="video-container">
        <video class="lesson-video" 
               data-lesson-id="<?= $subject['ID'] ?>"
               controls
               controlsList="nodownload">
            <source src="<?= htmlspecialchars($subject['VideoURL']) ?>" type="video/mp4">
        </video>
        
        <div class="video-info">
            <h1><?= htmlspecialchars($subject['Title']) ?></h1>
            
            <?php if (isset($subject['watched_seconds']) && $subject['watched_seconds'] > 0): ?>
                <div class="progress-bar">
                    <div class="progress" style="width: <?= min(100, ($subject['watched_seconds'] / $subject['Duration']) * 100) ?>%"></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($subject['QuestionCount']) && $subject['QuestionCount'] > 0): ?>
                <button class="take-exam-btn <?= (isset($subject['watched_seconds']) && $subject['watched_seconds'] >= ($subject['Duration'] * 0.9)) ? '' : 'disabled' ?>"
                        data-subject-id="<?= $subject['ID'] ?>"
                        <?= (isset($subject['watched_seconds']) && $subject['watched_seconds'] >= ($subject['Duration'] * 0.9)) ? '' : 'disabled' ?>>
                    <?php if (isset($subject['last_exam_passed']) && $subject['last_exam_passed']): ?>
                        Xem kết quả
                    <?php else: ?>
                        <?= (isset($subject['watched_seconds']) && $subject['watched_seconds'] >= ($subject['Duration'] * 0.9)) ? 'Làm bài kiểm tra' : 'Xem video để mở khóa bài kiểm tra' ?>
                    <?php endif; ?>
                </button>
            <?php else: ?>
                <button class="complete-btn <?= (isset($subject['watched_seconds']) && $subject['watched_seconds'] >= ($subject['Duration'] * 0.9)) ? '' : 'disabled' ?>"
                        data-subject-id="<?= $subject['ID'] ?>"
                        <?= (isset($subject['watched_seconds']) && $subject['watched_seconds'] >= ($subject['Duration'] * 0.9)) ? '' : 'disabled' ?>>
                    Đánh dấu hoàn thành
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($subject['QuestionCount']) && $subject['QuestionCount'] > 0): ?>
        <div class="exam-container" style="display: none;">
            <form class="exam-form" data-exam-id="" data-subject-id="<?= $subject['ID'] ?>">
                <!-- Questions will be loaded here by JS -->
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Template for exam questions -->
<template id="question-template">
    <div class="question" data-question-id="">
        <h3 class="question-text"></h3>
        <div class="answers">
            <!-- Answer options will be inserted here -->
        </div>
    </div>
</template>

<template id="answer-template">
    <div class="mcq-answer" data-answer-id="" data-question-id="">
        <span class="answer-text"></span>
    </div>
</template>