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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title i {
            color: var(--primary);
        }

        .badge-count {
            background: var(--warning);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
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
            transform: translateY(-1px);
        }

        .btn-outline {
            background: white;
            color: var(--gray-600);
            border: 1px solid var(--gray-200);
        }

        .btn-outline:hover {
            background: var(--gray-50);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        .certificates-table {
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

        .cert-code {
            font-family: 'Courier New', monospace;
            background: var(--gray-100);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .employee-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .employee-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #8b5cf6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
        }

        .employee-details {
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

        .subject-name {
            font-weight: 500;
            color: var(--gray-900);
        }

        .score-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .score-badge.high {
            background: #d1fae5;
            color: #065f46;
        }

        .score-badge.medium {
            background: #fef3c7;
            color: #92400e;
        }

        .date-info {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .btn-info:hover {
            background: #2563eb;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-600);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--gray-200);
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-900);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
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

            .header-actions {
                flex-direction: column;
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
    <?php include __DIR__ . '/../../layout/admin/admin_sidebar.php'; ?>

    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-clock"></i>
                Chứng chỉ chờ duyệt
                <?php if (count($certificates) > 0): ?>
                    <span class="badge-count"><?= count($certificates) ?></span>
                <?php endif; ?>
            </div>
            <div class="header-actions">
                <a href="<?= BASE_URL ?>/admin/certificates" class="btn btn-outline">
                    <i class="fas fa-list"></i>
                    Tất cả chứng chỉ
                </a>
                <a href="<?= BASE_URL ?>/admin/certificates/statistics" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i>
                    Thống kê
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="certificates-table">
            <?php if (empty($certificates)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="empty-title">Không có chứng chỉ chờ duyệt</div>
                    <p>Tất cả chứng chỉ đã được xử lý</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Mã chứng chỉ</th>
                            <th>Nhân viên</th>
                            <th>Khóa học</th>
                            <th>Điểm thi</th>
                            <th>Ngày hoàn thành</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificates as $cert): ?>
                            <tr>
                                <td>
                                    <span class="cert-code"><?= htmlspecialchars($cert['CertificateCode']) ?></span>
                                </td>
                                <td>
                                    <div class="employee-info">
                                        <div class="employee-avatar">
                                            <?= strtoupper(substr($cert['EmployeeName'], 0, 1)) ?>
                                        </div>
                                        <div class="employee-details">
                                            <span class="employee-name"><?= htmlspecialchars($cert['EmployeeName']) ?></span>
                                            <span class="employee-email"><?= htmlspecialchars($cert['EmployeeEmail']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="subject-name"><?= htmlspecialchars($cert['SubjectName']) ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $score = $cert['ExamScore'] ?? 0;
                                    $scoreClass = $score >= 85 ? 'high' : 'medium';
                                    ?>
                                    <span class="score-badge <?= $scoreClass ?>">
                                        <i class="fas fa-star"></i>
                                        <?= round($score) ?>%
                                    </span>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d/m/Y H:i', strtotime($cert['CompletionDate'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm" onclick="viewDetail(<?= $cert['ID'] ?>)">
                                            <i class="fas fa-eye"></i>
                                            Xem
                                        </button>
                                        <form method="POST" action="<?= BASE_URL ?>/admin/certificates/approve/<?= $cert['ID'] ?>" style="display: inline;">
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Phê duyệt chứng chỉ này?')">
                                                <i class="fas fa-check"></i>
                                                Duyệt
                                            </button>
                                        </form>
                                        <button class="btn btn-danger btn-sm" onclick="showRejectModal(<?= $cert['ID'] ?>, '<?= htmlspecialchars($cert['CertificateCode']) ?>')">
                                            <i class="fas fa-times"></i>
                                            Từ chối
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Từ chối chứng chỉ</h2>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/admin/certificates/reject">
                <input type="hidden" id="rejectCertId" name="cert_id">
                <div class="form-group">
                    <label class="form-label">Mã chứng chỉ</label>
                    <input type="text" id="rejectCertCode" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Lý do từ chối *</label>
                    <textarea name="reason" class="form-control" required placeholder="Nhập lý do từ chối chứng chỉ..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Hủy</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i>
                        Từ chối
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewDetail(certId) {
            window.location.href = `<?= BASE_URL ?>/admin/certificates/review/${certId}`;
        }

        function showRejectModal(certId, certCode) {
            document.getElementById('rejectCertId').value = certId;
            document.getElementById('rejectCertCode').value = certCode;
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    </script>
</body>
</html>