<?php include __DIR__ . '/../layout/header.php'; ?>
<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<style>
    :root {
        --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --primary-solid: #667eea;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --bg-main: #f8fafc;
        --card-bg: #ffffff;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --border: #e2e8f0;
        --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 10px 25px -3px rgba(0, 0, 0, 0.1);
        --shadow-xl: 0 20px 35px -5px rgba(0, 0, 0, 0.15);
        --radius: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        background: var(--bg-main);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    .main-content {
        min-height: 100vh;
        padding: 2rem;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        margin-bottom: 3rem;
        animation: slideDown 0.5s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .header-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .header-content {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .header-icon {
        width: 64px;
        height: 64px;
        background: var(--primary);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 28px;
        box-shadow: var(--shadow-lg);
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    .header-text h1 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        background: var(--primary);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .header-text p {
        color: var(--text-secondary);
        font-size: 1rem;
    }

    .back-button {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        background: white;
        color: var(--primary-solid);
        border: 2px solid var(--border);
        padding: 0.875rem 1.75rem;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
    }

    .back-button:hover {
        background: var(--primary-solid);
        color: white;
        border-color: var(--primary-solid);
        transform: translateX(-4px);
        box-shadow: var(--shadow-md);
    }

    .certificates-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 2rem;
        animation: fadeIn 0.6s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .certificate-card {
        background: var(--card-bg);
        border-radius: var(--radius);
        padding: 2rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        animation: slideUp 0.5s ease forwards;
        opacity: 0;
    }

    .certificate-card:nth-child(1) { animation-delay: 0.1s; }
    .certificate-card:nth-child(2) { animation-delay: 0.2s; }
    .certificate-card:nth-child(3) { animation-delay: 0.3s; }
    .certificate-card:nth-child(4) { animation-delay: 0.4s; }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .certificate-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: var(--primary);
        transform: scaleX(0);
        transition: var(--transition);
    }

    .certificate-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-xl);
        border-color: var(--primary-solid);
    }

    .certificate-card:hover::before {
        transform: scaleX(1);
    }

    .certificate-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        gap: 1rem;
    }

    .certificate-info {
        flex: 1;
    }

    .certificate-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.75rem;
        line-height: 1.4;
    }

    .certificate-code {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.875rem;
        color: var(--text-secondary);
        font-family: 'JetBrains Mono', monospace;
        font-weight: 600;
        border: 1px solid var(--border);
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .status-active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .status-expired {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .certificate-meta {
        background: var(--bg-main);
        padding: 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }

    .date-info {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .date-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .date-icon {
        width: 36px;
        height: 36px;
        background: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-solid);
        font-size: 16px;
        box-shadow: var(--shadow-sm);
    }

    .date-label {
        font-weight: 600;
        color: var(--text-primary);
        min-width: 100px;
    }

    .actions {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }

    .action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.875rem;
        border: none;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 600;
        transition: var(--transition);
        text-decoration: none;
        color: white;
        cursor: pointer;
        box-shadow: var(--shadow-sm);
    }

    .btn-view {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .btn-view:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
    }

    .btn-print {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .btn-print:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4);
    }

    .btn-download {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .btn-download:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
    }

    .empty-state {
        text-align: center;
        padding: 6rem 2rem;
        background: white;
        border-radius: var(--radius);
        box-shadow: var(--shadow-md);
        max-width: 600px;
        margin: 4rem auto;
        animation: fadeIn 0.6s ease;
    }

    .empty-illustration {
        width: 200px;
        height: 200px;
        margin: 0 auto 2rem;
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .empty-icon {
        font-size: 5rem;
        color: var(--primary-solid);
        opacity: 0.3;
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
            opacity: 0.3;
        }
        50% {
            transform: scale(1.1);
            opacity: 0.5;
        }
    }

    .empty-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 1rem;
    }

    .empty-description {
        color: var(--text-secondary);
        margin-bottom: 2.5rem;
        line-height: 1.6;
        font-size: 1.1rem;
    }

    .learn-button {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        background: var(--primary);
        color: white;
        border: none;
        padding: 1.25rem 2.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1.1rem;
        transition: var(--transition);
        box-shadow: var(--shadow-lg);
        text-decoration: none;
    }

    .learn-button:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-xl);
        color: white;
    }

    @media (max-width: 1024px) {
        .certificates-grid {
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .main-content {
            padding: 1.5rem;
        }

        .header-top {
            flex-direction: column;
            align-items: flex-start;
        }

        .header-icon {
            width: 56px;
            height: 56px;
            font-size: 24px;
        }

        .header-text h1 {
            font-size: 1.5rem;
        }

        .certificates-grid {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .actions {
            grid-template-columns: 1fr;
        }

        .empty-state {
            padding: 4rem 1.5rem;
        }

        .empty-illustration {
            width: 150px;
            height: 150px;
        }

        .empty-icon {
            font-size: 4rem;
        }
    }
</style>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div class="header-top">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="header-text">
                        <h1>Chứng chỉ của tôi</h1>
                        <p>Quản lý và tải xuống chứng chỉ của bạn</p>
                    </div>
                </div>
                <a href="./dashboard" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại
                </a>
            </div>
        </div>

        <?php if (!empty($certificates)): ?>
            <div class="certificates-grid">
                <?php foreach ($certificates as $cert): ?>
                    <div class="certificate-card">
                        <div class="certificate-header">
                            <div class="certificate-info">
                                <div class="certificate-name"><?php echo htmlspecialchars($cert['SubjectName']); ?></div>
                                <div class="certificate-code">
                                    <i class="fas fa-hashtag"></i>
                                    <?php echo htmlspecialchars($cert['CertificateCode']); ?>
                                </div>
                            </div>
                            <span class="status-badge <?php echo $cert['Status'] == 1 ? 'status-active' : 'status-expired'; ?>">
                                <?php echo $cert['Status'] == 1 ? 'Hiệu lực' : 'Hết hạn'; ?>
                            </span>
                        </div>
                        
                        <div class="certificate-meta">
                            <div class="date-info">
                                <div class="date-item">
                                    <div class="date-icon">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <span class="date-label">Ngày cấp:</span>
                                    <span><?php echo date('d/m/Y', strtotime($cert['IssuedAt'])); ?></span>
                                </div>
                                <div class="date-item">
                                    <div class="date-icon">
                                        <i class="fas <?php echo !empty($cert['ExpiresAt']) ? 'fa-calendar-times' : 'fa-infinity'; ?>"></i>
                                    </div>
                                    <span class="date-label">Hết hạn:</span>
                                    <span>
                                        <?php if (!empty($cert['ExpiresAt'])): ?>
                                            <?php echo date('d/m/Y', strtotime($cert['ExpiresAt'])); ?>
                                        <?php else: ?>
                                            Không giới hạn
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="actions">
                            <a href="/certificates/<?php echo urlencode($cert['CertificateCode']); ?>" class="action-btn btn-view">
                                <i class="fas fa-eye"></i>
                                <span>Xem</span>
                            </a>
                            <a href="/certificates/print/<?php echo urlencode($cert['CertificateCode']); ?>" target="_blank" class="action-btn btn-print">
                                <i class="fas fa-print"></i>
                                <span>In</span>
                            </a>
                            <a href="/certificates/download/<?php echo urlencode($cert['CertificateCode']); ?>" class="action-btn btn-download">
                                <i class="fas fa-download"></i>
                                <span>Tải</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-illustration">
                    <i class="fas fa-certificate empty-icon"></i>
                </div>
                <h3 class="empty-title">Chưa có chứng chỉ nào</h3>
                <p class="empty-description">Hoàn thành các khóa học để nhận chứng chỉ của bạn!</p>
                <a href="./dashboard" class="learn-button">
                    <i class="fas fa-book-open"></i>
                    Bắt đầu học ngay
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../layout/footer.php'; ?>