<?php include __DIR__ . '/../../layout/admin/admin_sidebar.php'; ?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
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

        .filter-section {
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
            min-width: 150px;
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

        .btn {
            padding: 0.625rem 1.25rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn:hover {
            background: var(--primary-dark);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
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

        .stat-icon.orange {
            background: #fed7aa;
            color: #92400e;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .chart-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .chart-title i {
            color: var(--primary);
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            padding: 1.5rem 2rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .table-title i {
            color: var(--primary);
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
        }

        td {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-200);
        }

        tbody tr:hover {
            background: var(--gray-50);
        }

        .progress-bar {
            width: 100px;
            height: 6px;
            background: var(--gray-200);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), #8b5cf6);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .filter-section {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .chart-wrapper {
                height: 250px;
            }

            table {
                font-size: 0.875rem;
            }

            th, td {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-chart-bar"></i>
                <?= $pageTitle ?>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <div class="filter-group">
                <label class="filter-label">Từ ngày</label>
                <input type="date" class="filter-control" value="<?= $dateFrom ?>">
            </div>
            <div class="filter-group">
                <label class="filter-label">Đến ngày</label>
                <input type="date" class="filter-control" value="<?= $dateTo ?>">
            </div>
            <button class="btn">
                <i class="fas fa-search"></i>
                Cập nhật
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">Tổng nhân viên</div>
                        <div class="stat-value"><?= $overview['total_employees'] ?? 0 ?></div>
                    </div>
                    <div class="stat-icon green">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">Bài thi</div>
                        <div class="stat-value"><?= $overview['total_exams'] ?? 0 ?></div>
                    </div>
                    <div class="stat-icon orange">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">Chứng chỉ cấp</div>
                        <div class="stat-value"><?= $overview['total_certificates'] ?? 0 ?></div>
                    </div>
                    <div class="stat-icon" style="background: #fecaca; color: #dc2626;">
                        <i class="fas fa-certificate"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <!-- Chart: Trend -->
            <div class="chart-container">
                <div class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Xu hướng hoạt động
                </div>
                <div class="chart-wrapper">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Chart: By Department -->
            <div class="chart-container">
                <div class="chart-title">
                    <i class="fas fa-building"></i>
                    Thống kê theo phòng ban
                </div>
                <div class="chart-wrapper">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Table: By Subject -->
        <div class="table-container">
            <div class="table-title">
                <i class="fas fa-book-open"></i>
                Thống kê theo khóa học
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Khóa học</th>
                        <th>Học viên</th>
                        <th>Bài thi</th>
                        <th>Điểm TB</th>
                        <th>Chứng chỉ</th>
                        <th>Tỷ lệ hoàn thành</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bySubject as $subject): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($subject['Title']) ?></strong></td>
                            <td><?= $subject['learner_count'] ?? 0 ?></td>
                            <td><?= $subject['exam_count'] ?? 0 ?></td>
                            <td><?= round($subject['avg_score'] ?? 0) ?>%</td>
                            <td><?= $subject['cert_count'] ?? 0 ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= ($subject['learner_count'] > 0 ? round(($subject['cert_count'] / $subject['learner_count']) * 100) : 0) ?>%"></div>
                                    </div>
                                    <span><?= ($subject['learner_count'] > 0 ? round(($subject['cert_count'] / $subject['learner_count']) * 100) : 0) ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Table: By Department -->
        <div class="table-container">
            <div class="table-title">
                <i class="fas fa-building"></i>
                Thống kê theo phòng ban
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Phòng ban</th>
                        <th>Nhân viên</th>
                        <th>Bài thi</th>
                        <th>Điểm TB</th>
                        <th>Chứng chỉ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byDepartment as $dept): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($dept['DepartmentName']) ?></strong></td>
                            <td><?= $dept['employee_count'] ?? 0 ?></td>
                            <td><?= $dept['exam_count'] ?? 0 ?></td>
                            <td><?= round($dept['avg_score'] ?? 0) ?>%</td>
                            <td><?= $dept['cert_count'] ?? 0 ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Trend Chart
        const trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            const trendData = <?= json_encode($trends ?? []) ?>;
            const dates = trendData.map(d => d.date);
            const exams = trendData.map(d => parseInt(d.exams) || 0);
            const certs = trendData.map(d => parseInt(d.certificates) || 0);

            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Bài thi',
                            data: exams,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Chứng chỉ',
                            data: certs,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
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

        // Department Chart
        const deptCtx = document.getElementById('departmentChart');
        if (deptCtx) {
            const deptData = <?= json_encode($byDepartment ?? []) ?>;
            const departments = deptData.map(d => d.DepartmentName);
            const certCounts = deptData.map(d => parseInt(d.cert_count) || 0);

            new Chart(deptCtx, {
                type: 'bar',
                data: {
                    labels: departments,
                    datasets: [{
                        label: 'Chứng chỉ',
                        data: certCounts,
                        backgroundColor: [
                            'rgba(99, 102, 241, 0.8)',
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(236, 72, 153, 0.8)'
                        ],
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>