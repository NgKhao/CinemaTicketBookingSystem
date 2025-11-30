<?php
session_start();
require_once 'config.php';

$category_id = $_GET['category'] ?? 1;
$category_names = [1 => 'HÀNH ĐỘNG', 2 => 'TÌNH CẢM', 3 => 'HOẠT HÌNH'];
$category_name = $category_names[$category_id] ?? 'PHIM';

$stmt = $pdo->prepare("SELECT * FROM movies WHERE category_id = ?");
$stmt->execute([$category_id]);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$userRole = $isLoggedIn ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>CGV-Phim <?php echo $category_name; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="ASST1.css" />
    <link rel="icon" href="./img/4.png" />
</head>

<body>
    <?php
    // Include header component chung
    include 'header_user.php';
    ?>

    <article>
        <div class="main-content">
            <div class="movie-section">
                <h1>PHIM <?php echo $category_name; ?></h1>
                <div class="movie-grid">
                    <?php foreach ($movies as $movie): ?>
                        <div class="movie-card">
                            <img src="<?php echo $movie['image']; ?>" alt="<?php echo $movie['title']; ?>" />
                            <h3><?php echo $movie['title']; ?></h3>
                            <p><strong>Thể loại:</strong> <?php echo $movie['genre']; ?></p>
                            <p><strong>Thời lượng:</strong> <?php echo $movie['duration']; ?></p>
                            <p><strong>Khởi chiếu:</strong>
                                <?php echo date('d-m-Y', strtotime($movie['release_date'])); ?></p>
                            <div class="movie-buttons">
                                <button onclick="window.location.href='movie_detail.php?id=<?php echo $movie['id']; ?>'"
                                    class="btn-detail">
                                    <i class="fas fa-eye"></i> Chi tiết
                                </button>
                                <?php if ($isLoggedIn): ?>
                                    <button onclick="window.location.href='datve.php?movie_id=<?php echo $movie['id']; ?>'"
                                        class="btn-booking">
                                        <i class="fas fa-ticket-alt"></i> Đặt vé
                                    </button>
                                <?php else: ?>
                                    <button onclick="window.location.href='login.html'" class="btn-login">
                                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </article>

    <footer>
        <span>chăm sóc khách hàng</span>
    </footer>
    </div>
</body>

</html>