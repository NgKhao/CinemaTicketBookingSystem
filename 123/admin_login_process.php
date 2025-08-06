<?php
session_start();
require_once 'config.php';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kiểm tra thông tin đăng nhập admin
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        // Đăng nhập thành công
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['role'] = $admin['role'];

        header("Location: admin.php");
        exit;
    } else {
        // Đăng nhập thất bại
        header("Location: admin_login.html?error=1");
        exit;
    }
}
