<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Không tìm thấy trang</title>
    <?php $baseUrl = dirname($_SERVER['SCRIPT_NAME']); ?>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
</head>
<body class="error-page">
    <div class="error-container">
        <h1>404</h1>
        <h2>Không tìm thấy trang</h2>
        <p>Trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.</p>
        <a href="<?php echo $baseUrl; ?>" class="btn-primary">Về trang chủ</a>
    </div>
</body>
</html>