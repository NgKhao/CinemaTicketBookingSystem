<?php
require_once 'config.php';

if ($_POST) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Kiểm tra mật khẩu
    if ($password !== $confirm_password) {
        echo "<script>alert('Mật khẩu không khớp!'); window.location.href='register.html';</script>";
        exit;
    }

    // Kiểm tra username đã tồn tại
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Tên đăng nhập hoặc email đã tồn tại!'); window.location.href='register.html';</script>";
        exit;
    }

    // Thêm user mới
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')");

    if ($stmt->execute([$username, $email, $hashed_password])) {
        echo "<script>alert('Đăng ký thành công!'); window.location.href='login.html';</script>";
    } else {
        echo "<script>alert('Đăng ký thất bại!'); window.location.href='register.html';</script>";
    }
}