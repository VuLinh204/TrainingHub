<?php include __DIR__ . '/../../layout/admin/admin_sidebar.php'; ?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--gray-50);
            line-height: 1.5;
            color: var(--gray-900);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .page-title .icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid var(--gray-100);
        }

        .form-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary);
            font-size: 1.25rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-label.required::after {
            content: '*';
            color: var(--danger);
            margin-left: 0.25rem;
        }

        .form-help {
            display: block;
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-top: 0.375rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s;
            background: var(--gray-50);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .status-toggle {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 10px;
            border: 2px solid var(--gray-200);
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 28px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--gray-300);
            transition: 0.3s;
            border-radius: 28px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        .toggle-switch input:checked + .toggle-slider {
            background-color: var(--success);
        }

        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(22px);
        }

        .status-label {
            flex: 1;
        }

        .status-label strong {
            display: block;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .status-label small {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert i {
            font-size: 1.25rem;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .info-box p {
            color: #1e40af;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-box i {
            color: #3b82f6;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            padding-top: 2rem;
            border-top: 2px solid var(--gray-100);
        }

        .btn {
            padding: 0.875rem 2rem;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 1rem;
        }

        .btn i {
            font-size: 1.125rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: var(--shadow);
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: white;
            color: var(--gray-700);
            border: 2px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .form-card {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="page-header">
            <div class="page-title">
                <div class="icon">
                    <i class="fas fa-edit"></i>
                </div>
                <span><?= $pageTitle ?></span>
            </div>
            <div class="breadcrumb">
                <a href="<?= BASE_URL ?>/admin/dashboard">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <i class="fas fa-chevron-right"></i>
                <a href="<?= BASE_URL ?>/admin/subjects">Khóa học</a>
                <i class="fas fa-chevron-right"></i>
                <span>Chỉnh sửa</span>
            </div>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/admin/subjects/<?= $subject['ID'] ?>/update" class="form-card">
            <div class="info-box">
                <p>
                    <i class="fas fa-info-circle"></i>
                    <span>ID khóa học: <strong>#<?= $subject['ID'] ?></strong> | Tạo lúc: <?= date('d/m/Y H:i', strtotime($subject['CreatedAt'])) ?></span>
                </p>
            </div>

            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Thông tin cơ bản
                </h2>

                <div class="form-group">
                    <label class="form-label required" for="title">Tên khóa học</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           class="form-control" 
                           placeholder="Nhập tên khóa học..." 
                           value="<?= htmlspecialchars($subject['Title']) ?>"
                           required>
                    <small class="form-help">Tên khóa học sẽ hiển thị cho học viên</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">
                        Mô tả
                    </label>
                    <textarea id="description" 
                              name="description" 
                              class="form-control" 
                              placeholder="Nhập mô tả khóa học..."><?= htmlspecialchars($subject['Description'] ?? '') ?></textarea>
                    <small class="form-help">Mô tả ngắn gọn về nội dung và mục tiêu của khóa học</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label required" for="duration">Thời lượng (phút)</label>
                        <input type="number" 
                               id="duration" 
                               name="duration" 
                               class="form-control" 
                               placeholder="30" 
                               value="<?= htmlspecialchars($subject['Duration'] ?? '30') ?>"
                               min="1"
                               required>
                        <small class="form-help">Thời lượng video bài học</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="passing_score">Điểm đạt (%)</label>
                        <input type="number" 
                               id="passing_score" 
                               name="passing_score" 
                               class="form-control" 
                               placeholder="70" 
                               value="<?= htmlspecialchars($subject['PassingScore'] ?? '70') ?>"
                               min="0"
                               max="100"
                               required>
                        <small class="form-help">Điểm tối thiểu để đạt khóa học</small>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-toggle-on"></i>
                    Trạng thái
                </h2>

                <div class="status-toggle">
                    <label class="toggle-switch">
                        <input type="checkbox" 
                               name="status" 
                               value="1" 
                               id="status"
                               <?= ($subject['Status'] == 1) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="status-label">
                        <strong id="status-text"><?= ($subject['Status'] == 1) ? 'Hoạt động' : 'Vô hiệu hóa' ?></strong>
                        <small>Khóa học <?= ($subject['Status'] == 1) ? 'đang' : 'không' ?> hiển thị cho học viên</small>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/admin/subjects" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    <span>Hủy bỏ</span>
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <span>Lưu thay đổi</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const statusCheckbox = document.getElementById('status');
            const statusText = document.getElementById('status-text');
            
            // Handle status toggle
            if (statusCheckbox) {
                statusCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        statusText.textContent = 'Hoạt động';
                        statusText.nextElementSibling.textContent = 'Khóa học đang hiển thị cho học viên';
                    } else {
                        statusText.textContent = 'Vô hiệu hóa';
                        statusText.nextElementSibling.textContent = 'Khóa học không hiển thị cho học viên';
                    }
                });
            }
            
            // Form validation
            form.addEventListener('submit', function(e) {
                const title = document.getElementById('title').value.trim();
                
                if (!title) {
                    e.preventDefault();
                    alert('Vui lòng nhập tên khóa học');
                    document.getElementById('title').focus();
                    return;
                }
            });
        });
    </script>
</body>
</html>