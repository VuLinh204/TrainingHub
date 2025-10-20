<div class="history-container">
    <div class="page-header">
        <h1>Lịch sử học tập</h1>
        <div class="header-actions">
            <a href="<?= BASE_URL ?>/profile" class="btn-secondary">
                <i class="fas fa-user"></i> Hồ sơ
            </a>
            <a href="<?= BASE_URL ?>/profile/statistics" class="btn-primary">
                <i class="fas fa-chart-bar"></i> Thống kê
            </a>
        </div>
    </div>

    <div class="history-tabs">
        <button class="tab-btn active" data-tab="completions">
            <i class="fas fa-check-circle"></i> Khóa học hoàn thành
        </button>
        <button class="tab-btn" data-tab="exams">
            <i class="fas fa-clipboard-check"></i> Lịch sử thi
        </button>
    </div>

    <!-- Completions Tab -->
    <div class="tab-content active" id="completions-tab">
        <?php if (empty($completions)): ?>
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>Chưa có khóa học nào được hoàn thành</h3>
                <p>Bắt đầu học để hoàn thành khóa học đầu tiên của bạn</p>
                <a href="<?= BASE_URL ?>/subjects" class="btn-primary">Xem khóa học</a>
            </div>
        <?php else: ?>
            <div class="completions-list">
                <?php foreach ($completions as $completion): ?>
                    <div class="completion-card">
                        <div class="completion-badge">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="completion-info">
                            <h3><?= htmlspecialchars($completion['SubjectName']) ?></h3>
                            <p class="completion-description">
                                <?= htmlspecialchars($completion['SubjectDescription'] ?? '') ?>
                            </p>
                            <div class="completion-meta">
                                <span class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    Hoàn thành: <?= date('d/m/Y H:i', strtotime($completion['CompletedAt'])) ?>
                                </span>
                                <span class="meta-item">
                                    <i class="fas fa-tag"></i>
                                    <?= htmlspecialchars($completion['KnowledgeGroupName'] ?? 'Chung') ?>
                                </span>
                                <?php if ($completion['Method'] === 'exam' && $completion['Score']): ?>
                                    <span class="meta-item score">
                                        <i class="fas fa-star"></i>
                                        Điểm: <?= round($completion['Score']) ?>%
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="completion-actions">
                            <?php if ($completion['Method'] === 'exam'): ?>
                                <span class="badge badge-exam">Qua thi</span>
                            <?php elseif ($completion['Method'] === 'video'): ?>
                                <span class="badge badge-video">Xem video</span>
                            <?php else: ?>
                                <span class="badge badge-manual">Thủ công</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Exams Tab -->
    <div class="tab-content" id="exams-tab">
        <?php if (empty($examAttempts)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>Chưa có lịch sử thi</h3>
                <p>Hoàn thành xem video và làm bài thi để xem lịch sử</p>
            </div>
        <?php else: ?>
            <div class="exams-table-wrapper">
                <table class="exams-table">
                    <thead>
                        <tr>
                            <th>Khóa học</th>
                            <th>Thời gian</th>
                            <th>Điểm</th>
                            <th>Kết quả</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($examAttempts as $exam): ?>
                            <tr>
                                <td>
                                    <div class="exam-subject">
                                        <?= htmlspecialchars($exam['SubjectName']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="exam-time">
                                        <?= date('d/m/Y H:i', strtotime($exam['StartTime'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="exam-score">
                                        <strong><?= round($exam['Score']) ?>%</strong>
                                        <small><?= $exam['CorrectAnswers'] ?>/<?= $exam['TotalQuestions'] ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($exam['Passed']): ?>
                                        <span class="result-badge passed">
                                            <i class="fas fa-check-circle"></i> Đạt
                                        </span>
                                    <?php else: ?>
                                        <span class="result-badge failed">
                                            <i class="fas fa-times-circle"></i> Chưa đạt
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge completed">
                                        <i class="fas fa-check"></i> Hoàn thành
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Simple tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetTab = btn.dataset.tab;

                // Remove active class from all buttons and contents
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));

                // Add active class to clicked button and corresponding content
                btn.classList.add('active');
                document.getElementById(targetTab + '-tab').classList.add('active');
            });
        });
    });
</script>