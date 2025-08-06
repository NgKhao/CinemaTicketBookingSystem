<?php
require_once 'config.php';

// Tạo tài khoản admin mặc định
$admin_username = 'admin';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_email = 'admin@cgv.com';

// Kiểm tra xem admin đã tồn tại chưa
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND role = 'admin'");
$stmt->execute([$admin_username]);

if (!$stmt->fetch()) {
    // Tạo tài khoản admin
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");

    if ($stmt->execute([$admin_username, $admin_email, $admin_password])) {
        echo "Tạo tài khoản admin thành công!<br>";
        echo "Tên đăng nhập: admin<br>";
        echo "Mật khẩu: admin123<br>";
        echo "<a href='admin_login.html'>Đăng nhập Admin</a>";
    } else {
        echo "Lỗi tạo tài khoản admin!";
    }
} else {
    echo "Tài khoản admin đã tồn tại!<br>";
    echo "Tên đăng nhập: admin<br>";
    echo "Mật khẩu: admin123<br>";
    echo "<a href='admin_login.html'>Đăng nhập Admin</a>";
}