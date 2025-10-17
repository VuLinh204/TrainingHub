<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-900: #111827;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
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

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
        }

        .btn-outline {
            background: white;
            color: var(--gray-600);
            border: 1px solid var(--gray-200);
        }

        .btn-outline:hover {
            background: var(--gray-50);
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            flex-shrink: 0;
        }

        .stat-icon.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stat-icon.pending {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .stat-icon.approved {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .stat-icon.revoked {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .stat-icon.active {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: var(--gray-900);
        }

        .stat-icon.expired {
            background: linear-gradient(135deg, #d299c2 0%, #fef9d7 100%);
            color: var(--gray-900);
        }

        .stat-info {
            flex: 1;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chart-title i {
            color: var(--primary);
        }

        .top-subjects {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .subject-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .subject-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 8px;
            transition: all 0.2s;
        }

        .subject-item:hover {
            background: var(--gray-100);
            transform: translateX(4px);
        }

        .subject-rank {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        .subject-rank.gold {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        }

        .subject-rank.silver {
            background: linear-gradient(135deg, #e0e0e0 0%, #bdbdbd 100%);
        }

        .subject-rank.bronze {
            background: linear-gradient(135deg, #cd7f32 0%, #8b4513 100%);
        }

        .subject-info {
            flex: 1;
        }

        .subject-name {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .subject-count {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .subject-bar {
            flex: 1;
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
            max-width: 200px;
        }

        .subject-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--info));
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .filter-section {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .filter-title {
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-controls {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        @media (max-width: 1024px) {
            .container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .stats-overview {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .subject-bar {
                max-width: 150px;
            }
        }

        @media (max-width: 768px) {
            .stats-overview {
                grid-template-columns: 1fr;
            }

            .filter-controls {
                flex-direction: column;
            }

            .form-group {
                width: 100%;
            }

            .subject-item {
                flex-wrap: wrap;
            }

            .subject-bar {
                width: 100%;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../layout/admin/admin_sidebar.php'; ?>

    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-chart-bar"></i>
                Thống kê chứng chỉ
            </div>
            <div class="header-actions">
                <a href="<?= BASE_URL ?>/admin/certificates" class="btn btn-outline">
                    <i class="fas fa-list"></i>
                    Danh sách chứng chỉ
                </a>
                <a href="<?= BASE_URL ?>/admin/certificates/export" class="btn btn-primary">
                    <i class="fas fa-download"></i>
                    Xuất báo cáo
                </a>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="filter-section">
            <div class="filter-title">
                <i class="fas fa-filter"></i>
                Bộ lọc thời gian
            </div>
            <form method="GET" action="<?= BASE_URL ?>/admin/certificates/statistics">
                <div class="filter-controls">
                    <div class="form-group">
                        <label class="form-label">Từ ngày</label>
                        <input type="date" name="date_from" class="form-control" 
                               value="<?= $_GET['date_from'] ?? date('Y-m-01') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Đến ngày</label>
                        <input type="date" name="date_to" class="form-control" 
                               value="<?= $_GET['date_to'] ?? date('Y-m-d') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Lọc
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-certificate"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Tổng số chứng chỉ</div>
                    <div class="stat-value"><?= $stats['total'] ?? 0 ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Chờ duyệt</div>
                    <div class="stat-value"><?= $stats['pending'] ?? 0 ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Đã duyệt</div>
                    <div class="stat-value"><?= $stats['approved'] ?? 0 ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon revoked">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Đã thu hồi</div>
                    <div class="stat-value"><?= $stats['revoked'] ?? 0 ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Còn hiệu lực</div>
                    <div class="stat-value"><?= $stats['active'] ?? 0 ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon expired">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">Hết hạn</div>
                    <div class="stat-value"><?= $stats['expired'] ?? 0 ?></div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <!-- Monthly Trend Chart -->
            <div class="chart-card">
                <div class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Xu hướng cấp chứng chỉ (12 tháng)
                </div>
                <canvas id="monthlyChart"></canvas>
            </div>

            <!-- Status Distribution Chart -->
            <div class="chart-card">
                <div class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Phân bổ trạng thái
                </div>
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Top Subjects -->
        <div class="top-subjects">
            <div class="chart-title">
                <i class="fas fa-trophy"></i>
                Top 10 khóa học có nhiều chứng chỉ nhất
            </div>
            <div class="subject-list">
                <?php 
                $maxCount = !empty($stats['top_subjects']) ? $stats['top_subjects'][0]['cert_count'] : 1;
                foreach ($stats['top_subjects'] ?? [] as $index => $subject): 
                    $rankClass = '';
                    if ($index === 0) $rankClass = 'gold';
                    elseif ($index === 1) $rankClass = 'silver';
                    elseif ($index === 2) $rankClass = 'bronze';
                    
                    $percentage = ($subject['cert_count'] / $maxCount) * 100;
                ?>
                <div class="subject-item">
                    <div class="subject-rank <?= $rankClass ?>">
                        <?= $index + 1 ?>
                    </div>
                    <div class="subject-info">
                        <div class="subject-name"><?= htmlspecialchars($subject['Title']) ?></div>
                        <div class="subject-count">
                            <i class="fas fa-certificate"></i>
                            <?= $subject['cert_count'] ?> chứng chỉ
                        </div>
                    </div>
                    <div class="subject-bar">
                        <div class="subject-bar-fill" style="width: <?= $percentage ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($stats['top_subjects'])): ?>
                    <div style="text-align: center; padding: 2rem; color: var(--gray-600);">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>Chưa có dữ liệu thống kê</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Monthly Trend Chart
        const monthlyCtx = document.getElementById('monthlyChart');
        if (monthlyCtx) {
            const monthlyData = <?= json_encode($stats['monthly'] ?? []) ?>;
            const months = monthlyData.map(item => {
                // Kiểm tra item.month có phải chuỗi hợp lệ không
                if (typeof item.month !== 'string' || !item.month.includes('-')) {
                    console.warn('Invalid month format:', item);
                    return 'N/A';
                }
                const [year, month] = item.month.split('-');
                return `${month}/${year}`;
            });
            const counts = monthlyData.map(item => parseInt(item.count) || 0);

            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Số chứng chỉ',
                        data: counts,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            borderRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Chờ duyệt', 'Đã duyệt', 'Đã thu hồi'],
                    datasets: [{
                        data: [
                            <?= $stats['pending'] ?? 0 ?>,
                            <?= $stats['approved'] ?? 0 ?>,
                            <?= $stats['revoked'] ?? 0 ?>
                        ],
                        backgroundColor: [
                            '#f59e0b',
                            '#10b981',
                            '#ef4444'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 13
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            borderRadius: 8
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>