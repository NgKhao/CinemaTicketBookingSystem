<?php
session_start();
require_once 'config.php';

// Lấy danh sách rạp từ database
$stmt = $pdo->prepare("SELECT * FROM cinemas ORDER BY name");
$stmt->execute();
$cinemas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$userRole = $isLoggedIn ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CGV - Danh sách rạp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="ASST1.css" />
    <link rel="icon" href="./img/4.png" />
</head>

<body>
    <div class="container">
        <marquee>CGV Trần Duy Hưng 1-6-2025 Kỉ niệm 22 năm thành lập tặng free bắp rang bơ và nước khi mua từ 2 vé trở
            lên</marquee>

        <!-- User Status Bar -->
        <div class="user-status-bar">
            <?php if ($isLoggedIn): ?>
            <div class="user-info">
                <i class="fas fa-user"></i> Xin chào, <strong><?php echo $username; ?></strong>
                <?php if ($userRole === 'admin'): ?>
                <a href="admin.php" class="admin-link"><i class="fas fa-cog"></i> Quản trị</a>
                <?php endif; ?>
            </div>
            <!-- Search Bar -->
            <div class="search-container">
                <form method="GET" action="search_movies.php" class="search-form">
                    <input type="text" name="q" placeholder="Tìm kiếm phim..." class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            <div class="user-actions">
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
            <?php else: ?>
            <div class="user-actions">
                <a href="login.html" class="login-btn"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                <a href="register.html" class="register-btn"><i class="fas fa-user-plus"></i> Đăng ký</a>
            </div>
            <?php endif; ?>
        </div>

        <header></header>
        <nav>
            <ul>
                <li><a href="./index.php">Trang chủ</a></li>
                <li class="dropdown">
                    <a href="#"> Phim <i class="fas fa-chevron-down"></i> </a>
                    <ul class="dropdown-content">
                        <li><a href="./movies.php?category=1">Phim Hành Động</a></li>
                        <li><a href="./movies.php?category=2">Phim Tình Cảm</a></li>
                        <li><a href="./movies.php?category=3">Phim Hoạt Hình</a></li>
                    </ul>
                </li>
                <li><a href="./list_cgv.php">Rạp CGV</a></li>
                <li><a href="./list_user.html">Thành viên</a></li>
                <li><a href="./ap.html">Tuyển dụng</a></li>
            </ul>


        </nav>

        <article>
            <div class="cinema">
                <h1>DANH SÁCH RẠP CGV</h1>
                <div class="cinema-grid">
                    <?php foreach ($cinemas as $cinema): ?>
                    <div class="cinema-card">
                        <img src="<?php echo $cinema['image']; ?>" alt="<?php echo $cinema['name']; ?>"
                            style="width: 200px; height: 150px; object-fit: cover;" />
                        <h3><?php echo $cinema['name']; ?></h3>
                        <p><?php echo $cinema['address']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
        <footer>
            <span>chăm sóc khách hàng</span>
        </footer>
    </div>
</body>

</html>