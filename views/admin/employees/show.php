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

        .back-link:hover {
            text-decoration: underline;
        }

        .employee-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .employee-basic-info h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .employee-basic-info p {
            color: var(--gray-600);
            margin-bottom: 0.25rem;
        }

        .stats-grid {
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
            text-align: center;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: var(--gray-600);
        }

        .info-value {
            color: var(--gray-900);
            font-weight: 600;
        }

        .exam-item {
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .exam-info {
            flex: 1;
        }

        .exam-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .exam-date {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .exam-score {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge.passed {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .cert-item {
            padding: 1rem;
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .cert-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .cert-date {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .employee-header {
                flex-direction: column;
                text-align: center;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .exam-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .exam-score {
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?= BASE_URL ?>/admin/employees" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Quay lại danh sách
        </a>

        <!-- Employee Header -->
        <div class="employee-header">
            <div class="avatar-large">
                <?= strtoupper(substr($targetEmployee['FirstName'], 0, 1)) ?>
            </div>
            <div class="employee-basic-info">
                <h1><?= htmlspecialchars($targetEmployee['FirstName'] . ' ' . $targetEmployee['LastName']) ?></h1>
                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($targetEmployee['Email']) ?></p>
                <p><i class="fas fa-phone"></i> <?= htmlspecialchars($targetEmployee['Phone'] ?? 'N/A') ?></p>
                <p><i class="fas fa-building"></i> <?= htmlspecialchars($targetEmployee['Department'] ?? 'N/A') ?></p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Chứng chỉ</div>
                <div class="stat-value"><?= $stats['certificates'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Khóa học hoàn thành</div>
                <div class="stat-value"><?= $stats['completed_subjects'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Điểm TB</div>
                <div class="stat-value"><?= round($stats['avg_score'] ?? 0) ?>%</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Tổng bài thi</div>
                <div class="stat-value"><?= $stats['total_exams'] ?? 0 ?></div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Exam History -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-history"></i>
                    Lịch sử bài thi
                </div>
                <?php if (empty($examHistory)): ?>
                    <p style="color: var(--gray-600); text-align: center;">Chưa có bài thi nào</p>
                <?php else: ?>
                    <?php foreach ($examHistory as $exam): ?>
                        <div class="exam-item">
                            <div class="exam-info">
                                <div class="exam-name"><?= htmlspecialchars($exam['SubjectName']) ?></div>
                                <div class="exam-date"><?= date('d/m/Y H:i', strtotime($exam['CompletedAt'])) ?></div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <span class="badge <?= $exam['Passed'] ? 'passed' : 'failed' ?>">
                                    <?= $exam['Passed'] ? '✓ Đạt' : '✗ Chưa đạt' ?>
                                </span>
                                <div class="exam-score"><?= round($exam['Score']) ?>%</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Certificates -->
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-certificate"></i>
                    Chứng chỉ
                </div>
                <?php if (empty($certificates)): ?>
                    <p style="color: var(--gray-600); text-align: center;">Chưa có chứng chỉ nào</p>
                <?php else: ?>
                    <?php foreach ($certificates as $cert): ?>
                        <div class="cert-item">
                            <div class="cert-name"><?= htmlspecialchars($cert['SubjectName']) ?></div>
                            <div class="cert-date">
                                Cấp: <?= date('d/m/Y', strtotime($cert['IssuedAt'])) ?>
                                <?php if (!empty($cert['ExpiresAt'])): ?>
                                    <br>Hết hạn: <?= date('d/m/Y', strtotime($cert['ExpiresAt'])) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Learning Progress -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-chart-line"></i>
                Tiến độ học tập
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--gray-100);">
                            <th style="padding: 1rem; text-align: left; font-weight: 600;">Khóa học</th>
                            <th style="padding: 1rem; text-align: center; font-weight: 600;">Thời lượng</th>
                            <th style="padding: 1rem; text-align: center; font-weight: 600;">Hoàn thành</th>
                            <th style="padding: 1rem; text-align: center; font-weight: 600;">Điểm cao nhất</th>
                            <th style="padding: 1rem; text-align: center; font-weight: 600;">Lượt thi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($learningProgress as $progress): ?>
                            <tr style="border-top: 1px solid var(--gray-200);">
                                <td style="padding: 1rem; font-weight: 600;"><?= htmlspecialchars($progress['Title']) ?></td>
                                <td style="padding: 1rem; text-align: center;"><?= $progress['Duration'] ?>m</td>
                                <td style="padding: 1rem; text-align: center;">
                                    <?php if ($progress['completed']): ?>
                                        <span class="badge passed">✓ Đã hoàn thành</span>
                                    <?php else: ?>
                                        <span style="color: var(--gray-600);">Chưa</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <?= ($progress['best_score'] !== null) ? round($progress['best_score']) . '%' : '-' ?>
                                </td>
                                <td style="padding: 1rem; text-align: center;"><?= $progress['attempts'] ?? 0 ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>