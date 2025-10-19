<?php
// Ensure template variables are defined and have safe defaults to avoid notices/warnings
if (!isset($stats) || !is_array($stats)) {
    $stats = [
        'total_assigned' => 0,
        'completed' => 0,
        'certificates' => 0,
        'avg_score' => 0,
    ];
}

if (!isset($ongoingSubjects) || !is_array($ongoingSubjects)) {
    $ongoingSubjects = [];
}

if (!isset($recentActivity) || !is_array($recentActivity)) {
    $recentActivity = [];
}

if (!isset($completedSubjects) || !is_array($completedSubjects)) {
    $completedSubjects = [];
}
?>

<div class="dashboard">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-top">
            <div class="page-title">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </div>
            <div class="header-actions">
                <a href="<?= BASE_URL ?>/subjects" class="btn btn-primary">
                    <i class="fas fa-book"></i>
                    Xem khóa học
                </a>
                <a href="<?= BASE_URL ?>/certificates" class="btn btn-outline">
                    <i class="fas fa-certificate"></i>
                    Chứng chỉ
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Khóa học</div>
                <div class="stat-value"><?= $stats['total_assigned'] ?? 0 ?></div>
                <div class="stat-change">
                    <i class="fas fa-check"></i>
                    <?= $stats['completed'] ?? 0 ?> hoàn thành
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-certificate"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Chứng chỉ</div>
                <div class="stat-value"><?= $stats['certificates'] ?? 0 ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Tỷ lệ</div>
                <div class="stat-value"><?= round((($stats['completed'] ?? 0) / max(($stats['total_assigned'] ?? 0), 1)) * 100) ?>%</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Điểm TB</div>
                <div class="stat-value"><?= round($stats['avg_score'] ?? 0) ?>%</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-grid">
        <!-- Ongoing Courses -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-hourglass-half"></i>
                    Khóa học đang học
                </div>
                <a href="<?= BASE_URL ?>/subjects" class="card-action">Xem tất cả →</a>
            </div>

            <?php if (empty($ongoingSubjects)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-book-open"></i></div>
                    <p class="empty-text">Chưa bắt đầu khóa học nào</p>
                </div>
            <?php else: ?>
                <div class="subjects-list">
                    <?php foreach (array_slice($ongoingSubjects, 0, 5) as $subject): ?>
                        <div class="subject-item in-progress">
                            <div class="subject-thumbnail">
                                <i class="fas fa-play"></i>
                            </div>
                            <div class="subject-details">
                                <div class="subject-name"><?= htmlspecialchars($subject['Title']) ?></div>
                                <div class="subject-meta">
                                    <span><i class="fas fa-clock"></i> <?= $subject['watched_percent'] ?? 0 ?>%</span>
                                    <span><i class="fas fa-video"></i> <?= $subject['Duration'] ?? 0 ?>p</span>
                                </div>
                            </div>
                            <div class="progress-mini">
                                <div class="progress-mini-bar" style="width: <?= $subject['watched_percent'] ?? 0 ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Sidebar -->
        <div>
            <!-- Quick Actions -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-lightning-bolt"></i>
                        Thao tác nhanh
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <a href="<?= BASE_URL ?>/subjects" class="btn btn-primary" style="justify-content: center;">
                        <i class="fas fa-plus"></i> Bắt đầu khóa học
                    </a>
                    <a href="<?= BASE_URL ?>/certificates" class="btn btn-outline" style="justify-content: center;">
                        <i class="fas fa-download"></i> Tải chứng chỉ
                    </a>
                    <a href="<?= BASE_URL ?>/profile" class="btn btn-outline" style="justify-content: center;">
                        <i class="fas fa-user"></i> Hồ sơ
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-history"></i>
                        Hoạt động gần đây
                    </div>
                </div>

                <?php if (empty($recentActivity)): ?>
                    <div class="empty-state" style="padding: 2rem; border: none;">
                        <p class="empty-text">Không có hoạt động</p>
                    </div>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach (array_slice($recentActivity, 0, 5) as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?= (($activity['type'] ?? '') === 'exam') ? 'clipboard-check' : 'certificate' ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?= htmlspecialchars($activity['subject_name'] ?? '') ?>
                                    </div>
                                    <div class="activity-time">
                                        <?= !empty($activity['created_at']) ? date('d/m H:i', strtotime($activity['created_at'])) : '' ?>
                                        <?php if (!empty($activity['score'])): ?>
                                            <span class="activity-score"> • <?= round($activity['score'] ?? 0) ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Completed Courses -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-check-circle"></i>
                Khóa học đã hoàn thành
            </div>
            <a href="<?= BASE_URL ?>/profile/history" class="card-action">Xem lịch sử →</a>
        </div>

        <?php if (empty($completedSubjects)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-award"></i></div>
                <p class="empty-text">Chưa hoàn thành khóa học nào</p>
            </div>
        <?php else: ?>
            <div class="subjects-list">
                <?php foreach (array_slice($completedSubjects, 0, 10) as $subject): ?>
                    <div class="subject-item completed">
                        <div class="subject-thumbnail" style="background: linear-gradient(135deg, var(--success), var(--success-light));">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="subject-details">
                            <div class="subject-name"><?= htmlspecialchars($subject['Title'] ?? '') ?></div>
                            <div class="subject-meta">
                                <span><i class="fas fa-calendar"></i> <?= !empty($subject['completed_at']) ? date('d/m/Y', strtotime($subject['completed_at'])) : '' ?></span>
                                <?php if (!empty($subject['score'])): ?>
                                    <span><i class="fas fa-star"></i> <?= round($subject['score'] ?? 0) ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="subject-status status-completed">
                            <i class="fas fa-check"></i> Hoàn thành
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>