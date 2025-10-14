<?php
$password = "password"; // Mật khẩu cần hash
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";

// Verify thử
$verify = password_verify($password, $hash);
echo "Verify result: " . ($verify ? "true" : "false") . "\n";