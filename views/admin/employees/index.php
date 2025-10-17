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
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
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
            min-width: 200px;
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

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.blue {
            background: #dbeafe;
            color: #1e40af;
        }

        .stat-icon.green {
            background: #d1fae5;
            color: #065f46;
        }

        .stat-icon.gray {
            background: #f3f4f6;
            color: #6b7280;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 0.25rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--gray-100);
        }

        th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        td {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--gray-200);
        }

        tbody tr:hover {
            background: var(--gray-50);
        }

        .employee-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .employee-info {
            display: flex;
            flex-direction: column;
        }

        .employee-name {
            font-weight: 600;
            color: var(--gray-900);
        }

        .employee-email {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.inactive {
            background: #f3f4f6;
            color: #6b7280;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            transition: all 0.2s;
        }

        .btn-info {
            background: var(--primary);
            color: white;
        }

        .btn-info:hover {
            background: var(--primary-dark);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-600);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

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

            table {
                font-size: 0.875rem;
            }

            th, td {
                padding: 0.75rem 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-users"></i>
                <?= $pageTitle ?>
            </div>
            <div class="header-actions">
                <a href="<?= BASE_URL ?>/admin/employees/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Thêm nhân viên
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="stat-label">Tổng nhân viên</div>
                    <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="stat-label">Đang hoạt động</div>
                    <div class="stat-value"><?= $stats['active'] ?? 0 ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon gray">
                    <i class="fas fa-ban"></i>
                </div>
                <div>
                    <div class="stat-label">Đã vô hiệu hóa</div>
                    <div class="stat-value"><?= $stats['inactive'] ?? 0 ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #fef3c7; color: #92400e;">
                    <i class="fas fa-certificate"></i>
                </div>
                <div>
                    <div class="stat-label">Có chứng chỉ</div>
                    <div class="stat-value"><?= $stats['with_certificates'] ?? 0 ?></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <label class="filter-label">Tìm kiếm</label>
                <input type="text" class="filter-control" placeholder="Tên hoặc email..." 
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <div class="filter-group">
                <label class="filter-label">Vị trí</label>
                <select class="filter-control">
                    <option value="">Tất cả</option>
                    <?php foreach ($positions as $pos): ?>
                        <option value="<?= $pos['ID'] ?>" <?= ($filters['position'] ?? '') == $pos['ID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pos['PositionName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Trạng thái</label>
                <select class="filter-control">
                    <option value="all" <?= ($filters['status'] ?? 'active') == 'all' ? 'selected' : '' ?>>Tất cả</option>
                    <option value="active" <?= ($filters['status'] ?? 'active') == 'active' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="inactive" <?= ($filters['status'] ?? 'active') == 'inactive' ? 'selected' : '' ?>>Vô hiệu hóa</option>
                </select>
            </div>
        </div>

        <!-- Employees Table -->
        <div class="table-container">
            <?php if (empty($employees)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox" style="font-size: 4rem; color: var(--gray-200); margin-bottom: 1rem;"></i>
                    <p>Không tìm thấy nhân viên nào</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Email</th>
                            <th>Vị trí</th>
                            <th>Chứng chỉ</th>
                            <th>Khóa học hoàn thành</th>
                            <th>Điểm TB</th>
                            <th>Hoạt động</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td>
                                    <div class="employee-cell">
                                        <div class="avatar">
                                            <?= strtoupper(substr($emp['FirstName'], 0, 1)) ?>
                                        </div>
                                        <div class="employee-info">
                                            <span class="employee-name">
                                                <?= htmlspecialchars($emp['FirstName'] . ' ' . $emp['LastName']) ?>
                                            </span>
                                            <span class="employee-email"><?= htmlspecialchars($emp['Email']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($emp['Email']) ?></td>
                                <td><?= htmlspecialchars($emp['PositionName'] ?? 'N/A') ?></td>
                                <td><?= $emp['cert_count'] ?? 0 ?></td>
                                <td><?= $emp['completed_subjects'] ?? 0 ?></td>
                                <td><?= round($emp['avg_score'] ?? 0) ?>%</td>
                                <td>
                                    <?php if ($emp['last_activity']): ?>
                                        <small><?= date('d/m H:i', strtotime($emp['last_activity'])) ?></small>
                                    <?php else: ?>
                                        <small style="color: var(--gray-400);">Chưa</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $emp['Status'] == 1 ? 'active' : 'inactive' ?>">
                                        <?= $emp['Status'] == 1 ? 'Hoạt động' : 'Vô hiệu hóa' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= BASE_URL ?>/admin/employees/<?= $emp['ID'] ?>" class="btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Xem
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>