<?php
require_once 'config.php';

// Kiểm tra session để biết trạng thái đăng nhập
$isLoggedIn = isset($_SESSION['username']);

$category_id = isset($_GET['category']) ? $_GET['category'] : null;

if ($category_id) {
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE category_id = ? ORDER BY release_date DESC");
    $stmt->execute([$category_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM movies ORDER BY release_date DESC");
    $stmt->execute();
}

$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($movies as $movie) {
    echo '<div class="movie-card">';
    echo '<img src="' . $movie['image'] . '" alt="' . $movie['title'] . '" />';
    echo '<h3>' . $movie['title'] . '</h3>';
    echo '<p><strong>Thể loại:</strong> ' . $movie['genre'] . '</p>';
    echo '<p><strong>Thời lượng:</strong> ' . $movie['duration'] . '</p>';
    echo '<p><strong>Khởi chiếu:</strong> ' . date('d-m-Y', strtotime($movie['release_date'])) . '</p>';
    echo '<div class="movie-buttons">';
    echo '<button onclick="window.location.href=\'movie_detail.php?id=' . $movie['id'] . '\'" class="btn-detail">';
    echo '<i class="fas fa-eye"></i> Chi tiết</button>';

    if ($isLoggedIn) {
        echo '<button onclick="window.location.href=\'datve.php?movie_id=' . $movie['id'] . '\'" class="btn-booking">';
        echo '<i class="fas fa-ticket-alt"></i> Đặt vé</button>';
    } else {
        echo '<button onclick="window.location.href=\'login.html\'" class="btn-login">';
        echo '<i class="fas fa-sign-in-alt"></i> Đăng nhập</button>';
    }

    echo '</div>';
    echo '</div>';
}
