<?php
session_start();
require_once 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $movie_id = $_POST['movie_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    // Validate input
    if (empty($movie_id) || empty($rating) || empty($comment)) {
        $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin.';
        header("Location: movie_detail.php?id=$movie_id");
        exit();
    }

    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = 'Đánh giá sao phải từ 1 đến 5.';
        header("Location: movie_detail.php?id=$movie_id");
        exit();
    }

    try {
        // Kiểm tra xem user đã đánh giá phim này chưa
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND movie_id = ?");
        $stmt->execute([$user_id, $movie_id]);

        if ($stmt->fetch()) {
            // Cập nhật đánh giá
            $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ?, created_at = NOW() WHERE user_id = ? AND movie_id = ?");
            $stmt->execute([$rating, $comment, $user_id, $movie_id]);
            $_SESSION['success'] = 'Đánh giá của bạn đã được cập nhật!';
        } else {
            // Tạo đánh giá mới
            $stmt = $pdo->prepare("INSERT INTO reviews (user_id, movie_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $movie_id, $rating, $comment]);
            $_SESSION['success'] = 'Cảm ơn bạn đã đánh giá phim!';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Có lỗi xảy ra khi gửi đánh giá. Vui lòng thử lại.';
    }

    header("Location: movie_detail.php?id=$movie_id");
    exit();
} else {
    header('Location: index.php');
    exit();
}
