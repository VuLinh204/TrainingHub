<aside class="sidebar">
    <?php 
    $progress = $sidebarData['progress'] ?? ['total_subjects' => 0, 'completed_subjects' => 0, 'total_certificates' => 0];
    $position = $sidebarData['position'] ?? 'Nhân viên';
    
    // FIX: Tính progress percentage an toàn (tránh chia 0)
    $progressPercent = ($progress['total_subjects'] > 0) 
        ? floor(($progress['completed_subjects'] / $progress['total_subjects']) * 100) 
        : 0;
    ?>
    <div class="sidebar-header">
        <div class="user-info">
            <div class="avatar">
                <?= !empty($employee['FirstName']) ? strtoupper(substr($employee['FirstName'], 0, 1)) : '?' ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($employee['FirstName'] ?? 'Unknown User') ?></div>
                <div class="user-position"><?= htmlspecialchars($position) ?></div>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>/dashboard" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Trang chủ</span>
        </a>
        
        <a href="<?= BASE_URL ?>/subjects" class="nav-item <?= $currentPage === 'subjects' ? 'active' : '' ?>">
            <i class="fas fa-book"></i>
            <span>Khóa học</span>
            <?php if ($progress['total_subjects'] > 0): ?>
                <div class="progress-badge">
                    <?= $progressPercent ?>%
                </div>
            <?php endif; ?>
        </a>
        
        <a href="<?= BASE_URL ?>/certificates" class="nav-item <?= $currentPage === 'certificates' ? 'active' : '' ?>">
            <i class="fas fa-certificate"></i>
            <span>Chứng chỉ</span>
            <?php if ($progress['total_certificates'] > 0): ?>
                <div class="badge"><?= $progress['total_certificates'] ?></div>
            <?php endif; ?>
        </a>
        
        <a href="<?= BASE_URL ?>/profile" class="nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>">
            <i class="fas fa-user"></i>
            <span>Hồ sơ</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="progress-summary">
            <div class="progress-title">Tiến độ học tập</div>
            <div class="progress-bar">
                <div class="progress" style="width: <?= $progressPercent ?>%"></div>
            </div>
            <div class="progress-stats">
                <?= $progress['completed_subjects'] ?>/<?= $progress['total_subjects'] ?> khóa học
            </div>
        </div>
        
        <a href="<?= BASE_URL ?>/logout" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Đăng xuất</span>
        </a>
    </div>
</aside>