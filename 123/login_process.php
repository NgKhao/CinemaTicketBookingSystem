<?php
session_start();
require_once 'config.php';

if ($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
    } else {
        echo "<script>alert('Tên đăng nhập hoặc mật khẩu không đúng!'); window.location.href='login.html';</script>";
    }
}
