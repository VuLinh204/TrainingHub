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
            --success: #10b981;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--gray-600);
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
            height: 400px;
            margin-bottom: 1rem;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: var(--primary);
        }

        .progress-item {
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), #8b5cf6);
            transition: width 0.3s ease;
        }

        .progress-percent {
            font-weight: 700;
            color: var(--primary);
            min-width: 50px;
            text-align: right;
        }

        .activity-item {
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary);
        }

        .activity-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .activity-date {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #d1fae5;
            color: #065f46;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .chart-wrapper {
                height: 300px;
            }

            .progress-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .progress-percent {
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?= BASE_URL ?>/admin/employees/<?= $targetEmployee['ID'] ?>" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Quay lại
        </a>

        <div class="header">
            <h1 class="page-title">
                <i class="fas fa-chart-line"></i>
                Tiến độ học tập
            </h1>
            <p class="page-subtitle">
                <?= htmlspecialchars($targetEmployee['FirstName'] . ' ' . $targetEmployee['LastName']) ?>
            </p>
        </div>

        <!-- Monthly Activity Chart -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="fas fa-calendar"></i>
                Hoạt động theo tháng (12 tháng gần nhất)
            </div>
            <div class="chart-wrapper">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <!-- Subject Progress -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-book"></i>
                Tiến độ theo khóa học
            </div>
            <?php if (empty($subjectProgress)): ?>
                <p style="text-align: center; color: var(--gray-600);">Chưa có dữ liệu</p>
            <?php else: ?>
                <?php foreach ($subjectProgress as $subject): ?>
                    <div class="progress-item">
                        <div style="flex: 1;">
                            <div class="progress-name"><?= htmlspecialchars($subject['Title']) ?></div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $subject['completed'] ? 100 : 0 ?>%"></div>
                            </div>
                            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--gray-600);">
                                Điểm cao nhất: <?= ($subject['best_score'] !== null) ? round($subject['best_score']) . '%' : 'N/A' ?> 
                                | Lượt thi: <?= $subject['attempts'] ?? 0 ?>
                            </div>
                        </div>
                        <div class="progress-percent">
                            <?php if ($subject['completed']): ?>
                                <span class="badge">✓ Hoàn thành</span>
                            <?php else: ?>
                                <span style="color: var(--gray-600);">Chưa</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-history"></i>
                Hoạt động gần đây
            </div>
            <?php if (empty($recentActivity)): ?>
                <p style="text-align: center; color: var(--gray-600);">Chưa có hoạt động</p>
            <?php else: ?>
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-title">
                            <?php if ($activity['type'] == 'exam'): ?>
                                <i class="fas fa-clipboard-check"></i>
                                Thi: <?= htmlspecialchars($activity['subject_name']) ?>
                            <?php else: ?>
                                <i class="fas fa-certificate"></i>
                                Nhận chứng chỉ: <?= htmlspecialchars($activity['subject_name']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="activity-date">
                            <?= date('d/m/Y H:i', strtotime($activity['activity_date'])) ?>
                            <?php if ($activity['type'] == 'exam' && $activity['Score']): ?>
                                - Điểm: <?= round($activity['Score']) ?>%
                                <?php if ($activity['Passed']): ?>
                                    <span class="badge">✓ Đạt</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const monthlyData = <?= json_encode($monthlyActivity ?? []) ?>;
        
        if (monthlyData.length > 0) {
            const months = monthlyData.map(d => d.month);
            const examCounts = monthlyData.map(d => parseInt(d.exam_count) || 0);
            const avgScores = monthlyData.map(d => parseFloat(d.avg_score) || 0);

            const ctx = document.getElementById('monthlyChart');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Bài thi',
                            data: examCounts,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Điểm TB (%)',
                            data: avgScores,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>