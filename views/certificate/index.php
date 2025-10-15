<?php include __DIR__ . '/../layout/header.php'; ?>
<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<style>
    :root {
        --primary-color: #6c5ce7;
        --primary-light: #a29bfe;
        --secondary-color: #fd79a8;
        --background-color: #f8f9fa;
        --card-bg: #ffffff;
        --text-primary: #2d3436;
        --text-secondary: #636e72;
        --border-color: #dfe6e9;
        --shadow-light: 0 2px 4px rgba(0, 0, 0, 0.05);
        --shadow-medium: 0 4px 12px rgba(0, 0, 0, 0.08);
        --border-radius: 12px;
        --transition: all 0.2s ease;
    }

    body {
        background-color: var(--background-color);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: var(--text-primary);
    }

    .main-content {
        margin-left: 0; /* Assuming sidebar is handled in layout */
        padding: 2rem 0;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
        background: var(--card-bg);
        padding: 1.5rem;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
    }

    .header-title {
        display: flex;
        align-items: center;
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
    }

    .header-title i {
        margin-right: 0.75rem;
        color: var(--primary-color);
    }

    .back-button {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
        box-shadow: var(--shadow-light);
    }

    .back-button:hover {
        background: #5a52d3;
        transform: translateY(-1px);
        box-shadow: var(--shadow-medium);
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--card-bg);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-light);
        margin: 2rem 0;
    }

    .empty-icon {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 1.5rem;
    }

    .empty-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .empty-description {
        color: var(--text-secondary);
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .learn-button {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 1rem 2rem;
        border-radius: 8px;
        font-weight: 500;
        font-size: 1rem;
        transition: var(--transition);
        box-shadow: var(--shadow-light);
        text-decoration: none;
    }

    .learn-button:hover {
        background: #5a52d3;
        transform: translateY(-1px);
        box-shadow: var(--shadow-medium);
        color: white;
    }

    .learn-button i {
        font-size: 20px;
        margin: 0;
    }

    /* For when there are certificates */
    .certificates-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin: 2rem 0;
    }

    .certificate-card {
        background: var(--card-bg);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--shadow-light);
        transition: var(--transition);
        border: 1px solid var(--border-color);
    }

    .certificate-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }

    .certificate-name {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--text-primary);
    }

    .certificate-code {
        background: #f8f9fa;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.85rem;
        color: var(--text-secondary);
        font-family: monospace;
    }

    .certificate-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 1rem 0;
    }

    .date-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .date-badge {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        background: #f8f9fa;
        color: var(--text-secondary);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        border: 1px solid var(--border-color);
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .status-active {
        background: #00b894;
        color: white;
    }

    .status-expired {
        background: #e17055;
        color: white;
    }

    .actions {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    .action-btn {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 6px;
        font-size: 0.85rem;
        transition: var(--transition);
        text-decoration: none;
        color: white;
    }

    .btn-view {
        background: var(--primary-color);
    }

    .btn-print {
        background: #fdcb6e;
        color: #2d3436;
    }

    .btn-download {
        background: #00b894;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-light);
        color: white;
    }

    .btn-print:hover {
        color: #2d3436;
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .certificates-grid {
            grid-template-columns: 1fr;
        }

        .empty-state {
            padding: 3rem 1rem;
        }

        .empty-icon {
            font-size: 3rem;
        }
    }
</style>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div class="header-title">
                <i class="fas fa-certificate"></i>
                Chứng chỉ của tôi
            </div>
            <a href="./dashboard" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Quay lại
            </a>
        </div>

        <?php if (!empty($certificates)): ?>
            <div class="certificates-grid">
                <?php foreach ($certificates as $cert): ?>
                    <div class="certificate-card">
                        <div class="certificate-name"><?php echo htmlspecialchars($cert['SubjectName']); ?></div>
                        <div class="certificate-code"><?php echo htmlspecialchars($cert['CertificateCode']); ?></div>
                        <div class="certificate-meta">
                            <div class="date-info">
                                <div class="date-badge">
                                    <i class="fas fa-calendar-check"></i>
                                    <?php echo date('d/m/Y', strtotime($cert['IssuedAt'])); ?>
                                </div>
                                <?php if (!empty($cert['ExpiresAt'])): ?>
                                    <div class="date-badge">
                                        <i class="fas fa-calendar-times"></i>
                                        <?php echo date('d/m/Y', strtotime($cert['ExpiresAt'])); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="date-badge">
                                        <i class="fas fa-infinity"></i>
                                        Không hết hạn
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="status-badge <?php echo $cert['Status'] == 1 ? 'status-active' : 'status-expired'; ?>">
                                <?php echo $cert['Status'] == 1 ? 'Còn hiệu lực' : 'Hết hạn'; ?>
                            </div>
                        </div>
                        <div class="actions">
                            <a href="/certificate/<?php echo urlencode($cert['CertificateCode']); ?>" class="action-btn btn-view">
                                <i class="fas fa-eye"></i> Xem
                            </a>
                            <a href="/certificate/print/<?php echo urlencode($cert['CertificateCode']); ?>" target="_blank" class="action-btn btn-print">
                                <i class="fas fa-print"></i> In
                            </a>
                            <a href="/certificate/download/<?php echo urlencode($cert['CertificateCode']); ?>" class="action-btn btn-download">
                                <i class="fas fa-download"></i> Tải
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-certificate empty-icon"></i>
                <h3 class="empty-title">Bạn chưa có chứng chỉ nào</h3>
                <p class="empty-description">Hãy bắt đầu hành trình học tập để nhận chứng chỉ đầu tiên của bạn!</p>
                <a href="./dashboard" class="learn-button">
                    <i class="fas fa-book-open"></i>
                    Học ngay
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../layout/footer.php'; ?>