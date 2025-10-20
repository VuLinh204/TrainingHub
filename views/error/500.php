<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Lỗi máy chủ</title>
    <?php $baseUrl = dirname($_SERVER['SCRIPT_NAME']); ?>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/main.css">
</head>
<body class="error-page">
    <div class="error-container">
        <h1>500</h1>
        <h2>Lỗi máy chủ</h2>
        <p>Đã xảy ra lỗi trong quá trình xử lý yêu cầu của bạn.</p>
        <p>Vui lòng thử lại sau hoặc liên hệ quản trị viên nếu lỗi vẫn tiếp tục.</p>
        <a href="<?php echo $baseUrl; ?>" class="btn-primary">Về trang chủ</a>
    </div>
</body>
</html>