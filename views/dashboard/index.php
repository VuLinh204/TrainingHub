<?php
// $assignedSubjects is already provided by the controller
?>

<div class="dashboard-container">
    <h1>Khóa học của bạn</h1>
    
    <div class="subjects-grid">
        <?php foreach ($assignedSubjects as $subject): ?>
            <div class="subject-card">
                <?php if ($subject['VideoURL']): ?>
                    <div class="subject-thumbnail">
                        <img src="<?= htmlspecialchars(str_replace('.mp4', '.png', $subject['VideoURL'])) ?>" alt="">
                        <span class="duration"><?= floor($subject['Duration'] / 60) ?>:<?= str_pad($subject['Duration'] % 60, 2, '0', STR_PAD_LEFT) ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="subject-info">
                    <h3><?= htmlspecialchars($subject['Name']) ?></h3>
                    
                    <?php if ($subject['has_certificate']): ?>
                        <div class="status completed">
                            <i class="fas fa-check-circle"></i> Đã hoàn thành
                        </div>
                    <?php elseif ($subject['is_completed']): ?>
                        <div class="status in-progress">
                            <i class="fas fa-certificate"></i> Chờ nhận chứng chỉ
                        </div>
                    <?php else: ?>
                        <div class="status pending">
                            <i class="fas fa-clock"></i> Chưa học
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($subject['ExpireDate']): ?>
                        <div class="expire-date">
                            Hết hạn: <?= date('d/m/Y', strtotime($subject['ExpireDate'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="<?= BASE_URL ?>/subject/<?= $subject['ID'] ?>" class="btn-primary">
                        <?php if ($subject['has_certificate']): ?>
                            Xem lại
                        <?php elseif ($subject['is_completed']): ?>
                            Làm bài kiểm tra
                        <?php else: ?>
                            Bắt đầu học
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>