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
            --gray-900: #111827;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--gray-50);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title i {
            color: var(--primary);
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .filters {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 250px;
        }

        .filter-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .filter-control {
            width: 100%;
            padding: 0.625rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .filter-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .subject-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .subject-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }

        .subject-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .subject-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .subject-status {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .subject-body {
            padding: 1.5rem;
        }

        .subject-description {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .subject-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-name {
            font-size: 0.75rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .subject-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            flex: 1;
            padding: 0.625rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            transition: all 0.2s;
        }

        .btn-sm.edit {
            background: var(--primary);
            color: white;
        }

        .btn-sm.edit:hover {
            background: var(--primary-dark);
        }

        .btn-sm.delete {
            background: var(--danger);
            color: white;
        }

        .btn-sm.delete:hover {
            opacity: 0.9;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-600);
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .filters {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            .subjects-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-book"></i>
                <?= $pageTitle ?>
            </div>
            <a href="<?= BASE_URL ?>/admin/subjects/create" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Tạo khóa học
            </a>
        </div>

        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <label class="filter-label">Tìm kiếm</label>
                <input type="text" class="filter-control" placeholder="Tên hoặc mô tả..." 
                       value="<?= htmlspecialchars($searchQuery ?? '') ?>">
            </div>
            <div class="filter-group">
                <label class="filter-label">Trạng thái</label>
                <select class="filter-control">
                    <option value="all" <?= ($currentStatus ?? 'all') == 'all' ? 'selected' : '' ?>>Tất cả</option>
                    <option value="active" <?= ($currentStatus ?? 'all') == 'active' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="inactive" <?= ($currentStatus ?? 'all') == 'inactive' ? 'selected' : '' ?>>Vô hiệu hóa</option>
                </select>
            </div>
            <button class="btn btn-primary">
                <i class="fas fa-search"></i>
                Tìm kiếm
            </button>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-label">Tổng khóa học</div>
                <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✓</div>
                <div class="stat-label">Đang hoạt động</div>
                <div class="stat-value"><?= $stats['active'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Tổng học viên</div>
                <div class="stat-value"><?= $stats['total_learners'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-label">Điểm TB</div>
                <div class="stat-value"><?= round($stats['avg_score'] ?? 0) ?>%</div>
            </div>
        </div>

        <!-- Subjects Grid -->
        <div class="subjects-grid">
            <?php if (empty($subjects)): ?>
                <div class="empty-state" style="grid-column: 1/-1;">
                    <i class="fas fa-inbox" style="font-size: 4rem; color: var(--gray-200); margin-bottom: 1rem;"></i>
                    <p>Chưa có khóa học nào</p>
                </div>
            <?php else: ?>
                <?php foreach ($subjects as $subject): ?>
                    <div class="subject-card">
                        <div class="subject-header">
                            <div>
                                <h3 class="subject-title"><?= htmlspecialchars($subject['Title']) ?></h3>
                                <span class="subject-status">
                                    <i class="fas fa-<?= $subject['Status'] == 1 ? 'check-circle' : 'pause-circle' ?>"></i>
                                    <?= $subject['Status'] == 1 ? 'Hoạt động' : 'Vô hiệu hóa' ?>
                                </span>
                            </div>
                        </div>
                        <div class="subject-body">
                            <p class="subject-description">
                                <?= htmlspecialchars(substr($subject['Description'] ?? '', 0, 100)) ?>...
                            </p>
                            
                            <div class="subject-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $subject['learner_count'] ?? 0 ?></div>
                                    <div class="stat-name">Học viên</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $subject['Duration'] ?? 0 ?></div>
                                    <div class="stat-name">Phút</div>
                                </div>
                            </div>

                            <div class="subject-actions">
                                <a href="<?= BASE_URL ?>/admin/subjects/<?= $subject['ID'] ?>/edit" class="btn-sm edit">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                                <form method="POST" action="<?= BASE_URL ?>/admin/subjects/<?= $subject['ID'] ?>/delete" style="flex: 1;">
                                    <button type="submit" class="btn-sm delete" onclick="return confirm('Bạn chắc chắn muốn xóa?')">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>