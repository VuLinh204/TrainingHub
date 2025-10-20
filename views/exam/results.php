<?php
if (!isset($exam) || empty($exam)) {
    echo '<div class="alert alert-danger">Không tìm thấy kết quả bài thi.</div>';
    return;
}

$passed = (bool)($exam['Passed'] ?? false);
$correctAnswers = (int)($exam['CorrectAnswers'] ?? 0);
$totalQuestions = (int)($exam['TotalQuestions'] ?? 0);
$score = $correctAnswers / $totalQuestions * 100;
$subjectName = $exam['SubjectName'] ?? $exam['Title'] ?? 'Khóa học';
$examDate = date('d/m/Y H:i', strtotime($exam['StartTime'] ?? 'now'));
$timeTaken = $exam['EndTime'] ? date_diff(
    date_create($exam['StartTime']),
    date_create($exam['EndTime'])
)->format('%H:%I:%S') : '--:--:--';

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả bài kiểm tra</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>

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
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .result-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border: 1px solid var(--gray-200);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header */
        .result-header {
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            background: <?= $passed ? 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)' : 'linear-gradient(135deg, #fee2e2 0%, #fecaca 100%)' ?>;
        }

        .result-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(20px); }
        }

        .result-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            animation: scaleIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            z-index: 1;
        }

        .result-header.passed .result-icon {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .result-header.failed .result-icon {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        @keyframes scaleIn {
            from {
                transform: scale(0.3);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .result-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .result-header.passed .result-title {
            color: var(--success);
        }

        .result-header.failed .result-title {
            color: var(--danger);
        }

        .result-subtitle {
            color: var(--gray-600);
            font-size: 16px;
            position: relative;
            z-index: 1;
        }

        /* Score Section */
        .score-section {
            padding: 50px 40px;
            border-bottom: 1px solid var(--gray-200);
        }

        .score-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 30px;
            text-align: center;
        }

        .score-item {
            animation: fadeInUp 0.6s ease 0.1s backwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .score-circle {
            width: 140px;
            height: 140px;
            margin: 0 auto 15px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .score-circle svg {
            position: absolute;
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .score-circle circle {
            fill: none;
            stroke-width: 6;
        }

        .score-circle .bg {
            stroke: var(--gray-200);
        }

        .score-circle .progress {
            stroke: <?= $passed ? 'var(--success)' : 'var(--danger)' ?>;
            stroke-linecap: round;
            animation: drawCircle 1.5s ease forwards;
        }

        @keyframes drawCircle {
            from {
                stroke-dashoffset: 360;
            }
            to {
                stroke-dashoffset: <?= $totalQuestions > 0 ? (360 - ($correctAnswers / $totalQuestions) * 360) : 360 ?>;
            }
        }

        .score-text {
            position: relative;
            z-index: 1;
            font-size: 36px;
            font-weight: 700;
            color: <?= $passed ? 'var(--success)' : 'var(--danger)' ?>;
        }

        .score-label {
            font-size: 12px;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-top: 12px;
        }

        /* Details Grid */
        .details-section {
            padding: 40px;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }

        .detail-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid var(--primary);
            animation: fadeInUp 0.6s ease 0.2s backwards;
        }

        .detail-label {
            font-size: 11px;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .detail-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
        }

        /* Subject Info */
        .subject-section {
            padding: 40px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(79, 70, 229, 0.05) 100%);
            display: flex;
            align-items: center;
            gap: 24px;
            border-bottom: 1px solid var(--gray-200);
        }

        .subject-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            flex-shrink: 0;
        }

        .subject-info h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .subject-info p {
            color: var(--gray-600);
            font-size: 14px;
        }

        /* Actions */
        .action-section {
            padding: 30px 40px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-900);
            border: 1px solid var(--gray-200);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        .btn-outline {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .result-header {
                padding: 40px 24px;
            }

            .result-title {
                font-size: 24px;
            }

            .score-section {
                padding: 30px 24px;
            }

            .details-section {
                padding: 24px;
            }

            .subject-section {
                padding: 24px;
                flex-direction: column;
                text-align: center;
            }

            .action-section {
                padding: 20px 24px;
                flex-direction: column;
            }

            .action-section .btn {
                width: 100%;
                justify-content: center;
            }

            .score-grid {
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-card">
            <!-- Header -->
            <div class="result-header <?= $passed ? 'passed' : 'failed' ?>">
                <div class="result-icon">
                    <i class="fas fa-<?= $passed ? 'trophy' : 'times-circle' ?>"></i>
                </div>
                <h1 class="result-title">
                    <?= $passed ? 'Chúc mừng bạn đã đạt!' : 'Chưa đạt yêu cầu' ?>
                </h1>
                <p class="result-subtitle">
                    <?= $passed 
                        ? 'Bạn đã hoàn thành bài kiểm tra xuất sắc!' 
                        : 'Hãy học tập thêm và cố gắng lần sau' 
                    ?>
                </p>
            </div>

            <!-- Score -->
            <div class="score-section">
                <div class="score-grid">
                    <div class="score-item">
                        <div class="score-circle">
                            <svg viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="54" class="bg" stroke-dasharray="360" stroke-dashoffset="0"/>
                                <circle cx="60" cy="60" r="54" class="progress" stroke-dasharray="360" stroke-dashoffset="<?= $totalQuestions > 0 ? (360 - ($correctAnswers / $totalQuestions) * 360) : 360 ?>"/>
                            </svg>
                            <div class="score-text"><?= round($score) ?>%</div>
                        </div>
                        <div class="score-label">Điểm tổng hợp</div>
                    </div>

                    <div class="score-item">
                        <div style="font-size: 50px; color: <?= $passed ? 'var(--success)' : 'var(--danger)' ?>; margin-bottom: 10px; margin-top: 25px;">
                            <i class="fas fa-<?= $passed ? 'check-circle' : 'exclamation-circle' ?>"></i>
                        </div>
                        <div class="score-label">Trạng thái</div>
                        <div class="score-text" style="font-size: 24px; margin-top: 8px;">
                            <?= $passed ? 'Đạt' : 'Chưa đạt' ?>
                        </div>
                    </div>

                    <div class="score-item">
                        <div style="font-size: 50px; color: var(--primary); margin-bottom: 10px; margin-top: 25px;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="score-label">Thời gian</div>
                        <div class="score-text" style="font-size: 24px; margin-top: 8px;">
                            <?= $timeTaken ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="details-section">
                <div class="details-grid">
                    <div class="detail-card">
                        <div class="detail-label">Câu trả lời đúng</div>
                        <div class="detail-value" style="color: var(--success);">
                            <?= $correctAnswers ?>/<?= $totalQuestions ?>
                        </div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Câu trả lời sai</div>
                        <div class="detail-value" style="color: var(--danger);">
                            <?= ($totalQuestions - $correctAnswers) ?>/<?= $totalQuestions ?>
                        </div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Tỷ lệ chính xác</div>
                        <div class="detail-value">
                            <?= $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 1) : 0 ?>%
                        </div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Ngày thi</div>
                        <div class="detail-value"><?= $examDate ?></div>
                    </div>
                </div>
            </div>

            <!-- Subject Info -->
            <div class="subject-section">
                <div class="subject-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="subject-info">
                    <h3><?= htmlspecialchars($subjectName) ?></h3>
                    <p>Bài kiểm tra đánh giá kiến thức cơ bản và nâng cao</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="action-section">
                <?php if ($passed): ?>
                    <a href="/Training/certificates" class="btn btn-primary">
                        <i class="fas fa-certificate"></i> Nhận chứng chỉ
                    </a>
                <?php endif; ?>
                <a href="/Training/exam/<?php echo htmlspecialchars($exam['SubjectID'] ?? 0); ?>/take" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <?php if (!$passed): ?>
                    <a href="/Training/exam/<?php echo htmlspecialchars($exam['SubjectID'] ?? 0); ?>/take" class="btn btn-outline">
                        <i class="fas fa-redo"></i> Làm lại
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>