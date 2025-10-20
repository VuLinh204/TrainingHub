<div class="edit-profile-container">
    <div class="page-header">
        <a href="<?= BASE_URL ?>/profile" class="back-link">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
        <h1>Chỉnh sửa hồ sơ</h1>
    </div>

    <?php if (isset($_SESSION['profile_errors'])): ?>
        <div class="alert alert-danger">
            <ul class="error-list">
                <?php foreach ($_SESSION['profile_errors'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php unset($_SESSION['profile_errors']); ?>
    <?php endif; ?>

    <?php 
    // FIX: Kiểm tra nếu là admin (dựa trên session role hoặc position)
    $isAdmin = ($employee['role'] ?? '') === 'admin';
    ?>
    
    <div class="edit-profile-card">
        <form method="POST" action="<?= BASE_URL ?>/profile/update" class="profile-form">
            <div class="form-section">
                <h3>Thông tin cá nhân</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">
                            Tên <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="first_name" 
                               name="first_name" 
                               class="form-control"
                               value="<?= htmlspecialchars($employee['FirstName']) ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">
                            Họ <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="last_name" 
                               name="last_name" 
                               class="form-control"
                               value="<?= htmlspecialchars($employee['LastName']) ?>"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control"
                           value="<?= htmlspecialchars($employee['Email']) ?>"
                           <?= !$isAdmin ? 'disabled' : '' ?>
                    >
                    <small class="form-help">Email không thể thay đổi</small>
                </div>

                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           class="form-control"
                           value="<?= htmlspecialchars($employee['Phone'] ?? '') ?>"
                           placeholder="0912345678">
                </div>
            </div>

            <div class="form-section">
                <h3>Thông tin công việc</h3>

                <div class="form-group">
                    <label for="department">Phòng ban</label>
                    <input type="text" 
                           id="department" 
                           name="department" 
                           class="form-control"
                           value="<?= htmlspecialchars($employee['Department'] ?? '') ?>"
                           <?= !$isAdmin ? 'disabled' : '' ?>
                           >
                    <small class="form-help">Được quản lý bởi quản trị viên</small>
                </div>

                <div class="form-group">
                    <label for="position">Chức vụ</label>
                    <input type="text" 
                           id="position" 
                           name="position" 
                           class="form-control"
                           value="<?= htmlspecialchars($employee['Position'] ?? '') ?>"
                            <?= !$isAdmin ? 'disabled' : '' ?>
                           >
                    <small class="form-help">Được quản lý bởi quản trị viên</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
                <a href="<?= BASE_URL ?>/profile" class="btn-secondary">
                    <i class="fas fa-times"></i> Hủy
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.edit-profile-container {
    max-width: 800px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 30px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--primary-color);
    text-decoration: none;
    margin-bottom: 10px;
    font-size: 14px;
}

.back-link:hover {
    text-decoration: underline;
}

.page-header h1 {
    margin: 0;
    color: #333;
}

.edit-profile-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    color: #333;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.required {
    color: var(--danger-color);
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
}

.form-control:disabled {
    background: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
}

.form-control option {
    padding: 8px;
}

.form-help {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #6c757d;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    padding-top: 20px;
}

.error-list {
    margin: 0;
    padding-left: 20px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions button,
    .form-actions a {
        width: 100%;
    }
}
</style>