<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar">
            <?= strtoupper(substr($employee['FirstName'], 0, 1)) ?>
        </div>
        <div class="profile-info">
            <h1><?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?></h1>
            <p class="profile-position"><?= htmlspecialchars($sidebarData['position']) ?></p>
            <p class="profile-email">
                <i class="fas fa-envelope"></i> 
                <?= htmlspecialchars($employee['Email']) ?>
            </p>
            <?php if (!empty($employee['Phone'])): ?>
                <p class="profile-phone">
                    <i class="fas fa-phone"></i> 
                    <?= htmlspecialchars($employee['Phone']) ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="profile-actions">
            <a href="<?= BASE_URL ?>/profile/edit" class="btn-primary">
                <i class="fas fa-edit"></i> Chỉnh sửa hồ sơ
            </a>
            <a href="<?= BASE_URL ?>/profile/change-password" class="btn-secondary">
                <i class="fas fa-key"></i> Đổi mật khẩu
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['profile_success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['profile_success']) ?>
        </div>
        <?php unset($_SESSION['profile_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['profile_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['profile_error']) ?>
        </div>
        <?php unset($_SESSION['profile_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['password_success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['password_success']) ?>
        </div>
        <?php unset($_SESSION['password_success']); ?>
    <?php endif; ?>

    <div class="profile-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-details">
                <div class="stat-value"><?= $completionStats['total_completed'] ?></div>
                <div class="stat-label">Khóa học hoàn thành</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-certificate"></i>
            </div>
            <div class="stat-details">
                <div class="stat-value"><?= count($certificates) ?></div>
                <div class="stat-label">Chứng chỉ</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-details">
                <div class="stat-value"><?= round($completionStats['completion_rate']) ?>%</div>
                <div class="stat-label">Tỷ lệ hoàn thành</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-details">
                <div class="stat-value"><?= $completionStats['total_assigned'] ?></div>
                <div class="stat-label">Khóa học được gán</div>
            </div>
        </div>
    </div>

    <div class="profile-content">
        <div class="profile-section">
            <h2>Hoạt động gần đây</h2>
            
            <?php if (empty($recentCompletions)): ?>
                <p class="no-data">Chưa có hoạt động nào</p>
            <?php else: ?>
                <div class="activity-list">
                    <?php foreach ($recentCompletions as $completion): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    Hoàn thành khóa học: <?= htmlspecialchars($completion['SubjectName']) ?>
                                </div>
                                <div class="activity-meta">
                                    <span>
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d/m/Y H:i', strtotime($completion['CompletedAt'])) ?>
                                    </span>
                                    <?php if ($completion['Method'] === 'exam' && $completion['Score']): ?>
                                        <span class="activity-score">
                                            <i class="fas fa-star"></i>
                                            Điểm: <?= round($completion['Score']) ?>%
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <a href="<?= BASE_URL ?>/profile/history" class="view-all-link">
                    Xem tất cả lịch sử <i class="fas fa-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>

        <div class="profile-section">
            <h2>Chứng chỉ của tôi</h2>
            
            <?php if (empty($certificates)): ?>
                <p class="no-data">Bạn chưa có chứng chỉ nào</p>
            <?php else: ?>
                <div class="certificates-grid">
                    <?php foreach (array_slice($certificates, 0, 4) as $cert): ?>
                        <div class="certificate-card">
                            <div class="certificate-icon">
                                <i class="fas fa-award"></i>
                            </div>
                            <div class="certificate-info">
                                <h4><?= htmlspecialchars($cert['SubjectName']) ?></h4>
                                <p class="certificate-date">
                                    Cấp ngày: <?= date('d/m/Y', strtotime($cert['IssuedAt'])) ?>
                                </p>
                                <p class="certificate-code">
                                    Mã: <?= htmlspecialchars($cert['CertificateCode']) ?>
                                </p>
                            </div>
                            <a href="<?= BASE_URL ?>/certificates/<?= $cert['CertificateCode'] ?>" class="certificate-view">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($certificates) > 4): ?>
                    <a href="<?= BASE_URL ?>/certificates" class="view-all-link">
                        Xem tất cả chứng chỉ <i class="fas fa-arrow-right"></i>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-section">
        <h2>Thông tin tài khoản</h2>
        <div class="info-table">
            <div class="info-row">
                <span class="info-label">Ngày tham gia:</span>
                <span class="info-value">
                    <?= date('d/m/Y', strtotime($employee['CreatedAt'])) ?>
                </span>
            </div>
            <?php if (!empty($employee['LastLoginAt'])): ?>
                <div class="info-row">
                    <span class="info-label">Đăng nhập lần cuối:</span>
                    <span class="info-value">
                        <?= date('d/m/Y H:i', strtotime($employee['LastLoginAt'])) ?>
                    </span>
                </div>
            <?php endif; ?>
            <?php if (!empty($employee['Department'])): ?>
                <div class="info-row">
                    <span class="info-label">Phòng ban:</span>
                    <span class="info-value">
                        <?= htmlspecialchars($employee['Department']) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
:root {
    --primary-color: #2a73dd;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --sidebar-width: 280px;
    --primary-dark: #ccc
}

.profile-container {
    max-width: 1200px;
    margin: 0 auto;
}

.profile-header {
    background: white;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 30px;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: bold;
}

.profile-info {
    flex: 1;
}

.profile-info h1 {
    margin: 0 0 10px 0;
    color: #333;
}

.profile-position {
    font-size: 16px;
    color: var(--primary-color);
    font-weight: 500;
    margin-bottom: 10px;
}

.profile-email,
.profile-phone {
    color: #666;
    margin: 5px 0;
}

.profile-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.profile-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #333;
}

.stat-label {
    font-size: 14px;
    color: #666;
}

.profile-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.profile-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
}

.profile-section h2 {
    margin: 0 0 20px 0;
    font-size: 20px;
    color: #333;
}

.no-data {
    text-align: center;
    color: #999;
    padding: 40px;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.activity-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--success-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 500;
    color: #333;
    margin-bottom: 5px;
}

.activity-meta {
    font-size: 12px;
    color: #666;
    display: flex;
    gap: 15px;
}

.activity-score {
    color: var(--warning-color);
    font-weight: 500;
}

.certificates-grid {
    display: grid;
    gap: 15px;
}

.certificate-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.certificate-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.certificate-info {
    flex: 1;
}

.certificate-info h4 {
    margin: 0 0 5px 0;
    color: #333;
}

.certificate-date,
.certificate-code {
    font-size: 12px;
    color: #666;
    margin: 2px 0;
}

.certificate-view {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: background 0.2s;
}

.certificate-view:hover {
    background: var(--primary-dark);
}

.view-all-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--primary-color);
    text-decoration: none;
    margin-top: 15px;
    font-weight: 500;
}

.view-all-link:hover {
    text-decoration: underline;
}

.info-table {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.info-label {
    font-weight: 500;
    color: #666;
}

.info-value {
    color: #333;
}

@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-actions {
        width: 100%;
    }
    
    .profile-content {
        grid-template-columns: 1fr;
    }
    
    .profile-stats {
        grid-template-columns: 1fr;
    }
}
</style>