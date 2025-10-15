<?php
// views/subject/detail.php - FIXED VERSION
if (!isset($subject) || empty($subject)) {
    echo '<div class="alert alert-danger">Khóa học không tồn tại.</div>';
    return;
}

$baseUrl = $baseUrl ?? '';
$minWatchPercent = $subject['MinWatchPercent'] ?? 0.9;
$watchedSeconds = $subject['watched_seconds'] ?? 0;
$duration = $subject['Duration'] ?? 0;
$canTakeExam = $duration > 0 && $watchedSeconds >= ($duration * $minWatchPercent);
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
            
            <!-- Progress bar -->
            <?php if ($watchedSeconds > 0 && $duration > 0): ?>
                <div class="progress-info">
                    <span>Tiến độ xem: <?= number_format(($watchedSeconds / $duration) * 100, 1) ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress" style="width: <?= min(100, ($watchedSeconds / $duration) * 100) ?>%"></div>
                </div>
            <?php else: ?>
                <div class="progress-bar">
                    <div class="progress" style="width: 0%"></div>
                </div>
            <?php endif; ?>
            
            <!-- Action buttons -->
            <?php if (isset($subject['QuestionCount']) && $subject['QuestionCount'] > 0): ?>
                <?php if (isset($subject['last_exam_passed']) && $subject['last_exam_passed']): ?>
                    <a href="<?= $baseUrl ?>/exam/results/<?= $subject['ID'] ?>" class="btn btn-success complete-btn">
                        <i class="fas fa-chart-line"></i> Xem kết quả
                    </a>
                <?php else: ?>
                    <button class="btn btn-primary take-exam-btn <?= $canTakeExam ? '' : 'disabled' ?>"
                            data-subject-id="<?= $subject['ID'] ?>"
                            <?= $canTakeExam ? '' : 'disabled' ?>>
                        <i class="fas <?= $canTakeExam ? 'fa-clipboard-list' : 'fa-lock' ?>"></i> 
                        <?= $canTakeExam ? 'Làm bài kiểm tra' : 'Xem video để mở khóa' ?>
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <button class="btn btn-success complete-btn <?= $canTakeExam ? '' : 'disabled' ?>"
                        data-subject-id="<?= $subject['ID'] ?>"
                        <?= $canTakeExam ? '' : 'disabled' ?>>
                    <i class="fas fa-check-circle"></i> 
                    <?= $canTakeExam ? 'Đánh dấu hoàn thành' : 'Xem video để hoàn thành' ?>
                </button>
            <?php endif; ?>
            
            <?php if (!empty($subject['FileURL'])): ?>
                <div class="material-link mt-3">
                    <a href="<?= htmlspecialchars($subject['FileURL']) ?>" target="_blank" class="btn btn-outline-secondary">
                        <i class="fas fa-file-pdf"></i> Tải tài liệu
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Exam container (hidden initially) -->
    <?php if (isset($subject['QuestionCount']) && $subject['QuestionCount'] > 0): ?>
        <div class="exam-container" style="display: none;">
            <div class="exam-header">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Số câu hỏi: <strong id="total-questions">0</strong> | 
                    Điểm yêu cầu: <strong><?= $subject['RequiredScore'] ?? 70 ?>%</strong>
                </div>
                <div class="exam-timer" style="display: none;">
                    <i class="fas fa-clock"></i> Thời gian còn lại: <span class="time-remaining">--:--</span>
                </div>
            </div>
            
            <form class="exam-form" data-subject-id="<?= $subject['ID'] ?>">
                <input type="hidden" name="exam_id" id="exam-id" value="">
                <!-- Questions will be loaded here -->
            </form>
            
            <div class="exam-actions" style="display: none; margin-top: 30px; text-align: center;">
                <button type="button" class="btn btn-primary btn-lg submit-exam">
                    <i class="fas fa-paper-plane"></i> Nộp bài
                </button>
                <button type="button" class="btn btn-secondary cancel-exam">
                    <i class="fas fa-times"></i> Hủy
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Result Modal -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kết quả bài thi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="result-icon mb-3">
                    <i class="fas fa-circle-notch fa-spin fa-3x text-primary" id="result-loading"></i>
                    <i class="fas fa-check-circle fa-5x text-success d-none" id="result-pass"></i>
                    <i class="fas fa-times-circle fa-5x text-danger d-none" id="result-fail"></i>
                </div>
                <h3 id="result-message">Đang xử lý...</h3>
                <div id="result-details" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="view-results">Xem chi tiết</button>
            </div>
        </div>
    </div>
</div>

<style>
.video-container { max-width: 1200px; margin: 0 auto; }
.lesson-video { width: 100%; height: auto; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.video-info { margin-top: 30px; }
.progress-bar { background: #e9ecef; height: 10px; border-radius: 5px; overflow: hidden; margin: 15px 0; }
.progress { background: linear-gradient(90deg, #28a745, #20c997); height: 100%; transition: width 0.5s ease; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.exam-container { margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
.exam-header { margin-bottom: 30px; }
.exam-timer { font-size: 1.5em; font-weight: bold; color: #dc3545; text-align: center; padding: 15px; background: white; border-radius: 8px; }
.material-link {
    margin-top: 30px;
}

.material-link .btn {
    padding: 10px 20px;
    border: 2px solid #28a745;
    color: #28a745;
    background: transparent;
    text-decoration: none;
    border-radius: 30px;
}

.material-link .btn:hover {
    background: #28a745;
    color: white;
}
.question-card { 
    background: white; 
    border-radius: 30px; 
    padding: 25px; 
    margin-bottom: 25px; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #007bff;
}
.question-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.question-number {
    background: #007bff;
    color: white;
    padding: 5px 12px;
    border-radius: 30px;
    font-size: 0.85em;
    font-weight: 600;
}
.question-text { 
    font-size: 1.1em; 
    font-weight: 600; 
    margin-bottom: 20px;
    line-height: 1.6;
    color: #2c3e50;
}
.answers {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.answer-option { 
    display: flex;
    align-items: flex-start;
    padding: 15px; 
    margin: 0;
    border: 2px solid #dee2e6; 
    border-radius: 30px; 
    cursor: pointer; 
    transition: all 0.2s;
    background: #fff;
}
.answer-option:hover { 
    background: #f8f9fa; 
    border-color: #007bff;
    transform: translateX(5px);
}
.answer-option input[type="radio"] { 
    margin-right: 12px;
    margin-top: 3px;
    width: 18px;
    height: 18px;
    cursor: pointer;
    flex-shrink: 0;
}
.answer-option .answer-text {
    flex: 1;
    line-height: 1.5;
    color: #495057;
}
.answer-option.selected { 
    background: #e7f3ff; 
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0,123,255,0.2);
}
.answer-option.selected .answer-text {
    color: #007bff;
    font-weight: 500;
}
.exam-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid #dee2e6;
}
.exam-actions .btn {
    padding: 12px 30px;
    font-size: 1.1em;
    font-weight: 600;
    border-radius: 30px;
    transition: all 0.3s;
}
.exam-actions .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}
.exam-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '<?= $baseUrl ?>';
    const subjectId = <?= $subject['ID'] ?>;
    const video = document.querySelector('.lesson-video');
    
    // Video is handled by main.js initVideoTracking()
    // We only need to handle exam button click here
    
    const takeExamBtn = document.querySelector('.take-exam-btn');
    const completeBtn = document.querySelector('.complete-btn');
    
    // Complete button (no exam required)
    if (completeBtn) {
        completeBtn.addEventListener('click', async function() {
            if (this.disabled) return;
            
            try {
                window.TrainingPlatform.showLoading();
                
                const response = await fetch(`${baseUrl}/api/lesson/complete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lesson_id: subjectId })
                });
                
                const data = await response.json();
                
                window.TrainingPlatform.hideLoading();
                
                if (data.status === 'completed') {
                    window.TrainingPlatform.showToast('Đã đánh dấu hoàn thành!', 'success');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    window.TrainingPlatform.showToast(data.message || 'Lỗi đánh dấu hoàn thành', 'error');
                }
            } catch (error) {
                window.TrainingPlatform.hideLoading();
                console.error('Complete error:', error);
                window.TrainingPlatform.showToast('Lỗi đánh dấu hoàn thành', 'error');
            }
        });
    }
    
    // Exam button is handled by main.js handleExamStart()
    // But we need to ensure data transformation
    console.log('Subject detail page initialized for subject:', subjectId);
});
</script>