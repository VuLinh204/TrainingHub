<?php
// subject.php
// Giả sử các biến sau đã được định nghĩa:
// $subject = ['ID', 'Name', 'Description', 'Duration', 'VideoURL', 'ExpireDate', ...]
// $lessons = danh sách bài học (nếu có)
// $isCompleted, $hasCertificate, $canTakeExam, $examInfo, v.v.
?>

<div class="subject-detail">
    <div class="subject-header">
        <div class="container">
            <h1><?= htmlspecialchars($subject['Name']) ?></h1>
            <div class="subject-meta">
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <?= floor($subject['Duration'] / 60) ?> phút
                </div>
                <?php if (!empty($subject['ExpireDate'])): ?>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        Hết hạn: <?= date('d/m/Y', strtotime($subject['ExpireDate'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($subject['VideoURL'])): ?>
            <div class="video-container">
                <div class="video-wrapper">
                    <video class="lesson-video" controls>
                        <source src="<?= htmlspecialchars($subject['VideoURL']) ?>" type="video/mp4">
                        Trình duyệt của bạn không hỗ trợ video.
                    </video>
                </div>
                <div class="video-info">
                    <h1><?= htmlspecialchars($subject['Name']) ?></h1>
                    <?php if (!empty($subject['Description'])): ?>
                        <div class="description-content">
                            <?= nl2br(htmlspecialchars($subject['Description'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabs: Mô tả | Tài liệu | Bài kiểm tra -->
        <div class="content-tabs">
            <div class="tab-header">
                <button class="tab-btn active" data-tab="description">Mô tả</button>
                <button class="tab-btn" data-tab="materials">Tài liệu</button>
                <button class="tab-btn" data-tab="exam">Bài kiểm tra</button>
            </div>

            <!-- Tab: Mô tả -->
            <div class="tab-pane active" id="description">
                <?php if (!empty($subject['Description'])): ?>
                    <div class="description-content">
                        <?= nl2br(htmlspecialchars($subject['Description'])) ?>
                    </div>
                <?php else: ?>
                    <p>Không có mô tả cho khóa học này.</p>
                <?php endif; ?>
            </div>

            <!-- Tab: Tài liệu -->
            <div class="tab-pane" id="materials">
                <?php if (!empty($lessons)): ?>
                    <div class="materials-list">
                        <?php foreach ($lessons as $lesson): ?>
                            <a href="<?= htmlspecialchars($lesson['FileURL']) ?>" target="_blank" class="material-item">
                                <i class="fas fa-file-alt"></i>
                                <span class="material-name"><?= htmlspecialchars($lesson['Title']) ?></span>
                                <i class="fas fa-download"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Không có tài liệu đính kèm.</p>
                <?php endif; ?>
            </div>

            <!-- Tab: Bài kiểm tra -->
            <div class="tab-pane" id="exam">
                <?php if ($hasCertificate): ?>
                    <div class="exam-result">
                        <div class="passed-message">
                            <i class="fas fa-check-circle"></i>
                            <strong>Chúc mừng!</strong> Bạn đã hoàn thành khóa học và nhận được chứng chỉ.
                        </div>
                    </div>
                <?php elseif ($canTakeExam): ?>
                    <div class="exam-info">
                        <p><strong>Bạn đã hoàn thành video!</strong></p>
                        <p>Hãy làm bài kiểm tra để nhận chứng chỉ.</p>
                        <ul>
                            <li>Thời gian: <?= $examInfo['duration'] ?? '30' ?> phút</li>
                            <li>Số câu hỏi: <?= $examInfo['question_count'] ?? '10' ?></li>
                            <li>Điểm đạt: <?= $examInfo['passing_score'] ?? '80' ?>%</li>
                        </ul>
                        <a href="<?= BASE_URL ?>/exam/<?= (int)$subject['ID'] ?>" class="btn-primary">Bắt đầu làm bài</a>
                    </div>
                <?php elseif ($isCompleted): ?>
                    <div class="exam-info">
                        <p>Bạn đã hoàn thành video. Vui lòng quay lại sau để làm bài kiểm tra.</p>
                    </div>
                <?php else: ?>
                    <div class="exam-info">
                        <p>Hãy xem hết video để mở khóa bài kiểm tra.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Nút hành động (nếu cần) -->
        <?php if (!$hasCertificate && !$canTakeExam && !$isCompleted): ?>
            <div class="exam-actions">
                <button class="complete-btn" id="markCompleteBtn">
                    <span>Đã xem xong video</span>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Đơn giản hóa chuyển tab
document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', () => {
        // Xóa active
        document.querySelectorAll('.tab-btn, .tab-pane').forEach(el => {
            el.classList.remove('active');
        });
        // Thêm active cho tab được chọn
        button.classList.add('active');
        const tabId = button.getAttribute('data-tab');
        document.getElementById(tabId).classList.add('active');
    });
});

// Xử lý nút "Đã xem xong"
document.getElementById('markCompleteBtn')?.addEventListener('click', function() {
    // Gọi API hoặc submit form để đánh dấu hoàn thành
    fetch('<?= BASE_URL ?>/api/mark-complete/<?= (int)$subject['ID'] ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
        }
    });
});
</script>