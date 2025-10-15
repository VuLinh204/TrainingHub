<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Training Platform' ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Progressive Web App support -->
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <meta name="theme-color" content="#2a73dd">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/img/icon-1.svg">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/icon-1.svg">
    
    <!-- Open Graph tags -->
    <meta property="og:title" content="<?= $pageTitle ?? 'Training Platform' ?>">
    <meta property="og:description" content="Nền tảng đào tạo trực tuyến dành cho nhân viên">
    <meta property="og:image" content="<?= BASE_URL ?>/assets/img/og-image.jpg">
</head>
<body class="<?= isset($_SESSION['employee_id']) ? 'with-sidebar' : '' ?>">
    <?php if (isset($_SESSION['employee_id'])): ?>
        <?php 
        $currentPage = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 1) ?: 'dashboard';
        require_once BASE_PATH . '/views/layout/sidebar.php';
        ?>
        
        <div class="content-wrapper">
            <header class="top-header">
                <button class="menu-toggle" aria-label="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>
                
                <form class="search-form" action="<?= BASE_URL ?>/search" method="GET">
                    <input type="search" name="q" placeholder="Tìm kiếm khóa học..." 
                           value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    <button type="submit" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <div class="header-actions">
                    <div class="notifications dropdown">
                        <button class="dropdown-toggle" aria-label="Notifications">
                            <i class="fas fa-bell"></i>
                            <?php if (($notificationCount ?? 0) > 0): ?>
                                <span class="badge"><?= $notificationCount ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu">
                            <!-- Will be populated by JS -->
                        </div>
                    </div>
                    
                    <div class="user-menu dropdown">
                        <button class="dropdown-toggle">
                            <div class="avatar">
                                <?= !empty($_SESSION['employee_name']) ? strtoupper(substr($_SESSION['employee_name'], 0, 1)) : '?' ?>
                            </div>
                        </button>
                        <div class="dropdown-menu">
                            <a href="<?= BASE_URL ?>/profile">
                                <i class="fas fa-user"></i> Hồ sơ
                            </a>
                            <a href="<?= BASE_URL ?>/settings">
                                <i class="fas fa-cog"></i> Cài đặt
                            </a>
                            <div class="divider"></div>
                            <a href="<?= BASE_URL ?>/logout" class="text-danger">
                                <i class="fas fa-sign-out-alt"></i> Đăng xuất
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="main-content">
    <?php else: ?>
        <main class="main-content no-auth">
    <?php endif; ?>

    <script>
        // Register Service Worker for PWA support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(err => {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
    </script>