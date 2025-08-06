<?php
session_start();
require_once 'config.php';

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$movies = [];

if (!empty($searchQuery)) {
    // Tìm kiếm phim theo tên hoặc thể loại
    $stmt = $pdo->prepare("
        SELECT m.*, c.name as category_name 
        FROM movies m 
        LEFT JOIN categories c ON m.category_id = c.id 
        WHERE m.title LIKE ? OR m.genre LIKE ? OR m.description LIKE ?
        ORDER BY m.title ASC
    ");
    $searchTerm = "%$searchQuery%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$userRole = $isLoggedIn ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tìm kiếm phim - CGV</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="ASST1.css" />
    <link rel="icon" href="./img/4.png" />
</head>

<body>
    <div class="container">
        <marquee>CGV Trần Duy Hưng - Tìm kiếm phim yêu thích của bạn!</marquee>

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
                    <input type="text" name="q" placeholder="Tìm kiếm phim..." class="search-input"
                        value="<?php echo htmlspecialchars($searchQuery); ?>">
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
            <div class="main-content">
                <div class="search-results" style="padding: 20px;">
                    <h1 style="color: #e50914; margin-bottom: 20px;">
                        Kết quả tìm kiếm<?php if (!empty($searchQuery)): ?> cho
                        "<?php echo htmlspecialchars($searchQuery); ?>"<?php endif; ?>
                    </h1>

                    <?php if (empty($searchQuery)): ?>
                    <div style="text-align: center; padding: 50px;">
                        <i class="fas fa-search" style="font-size: 3em; color: #ddd; margin-bottom: 20px;"></i>
                        <p style="font-size: 1.2em; color: #666;">Nhập từ khóa để tìm kiếm phim</p>
                    </div>
                    <?php elseif (empty($movies)): ?>
                    <div style="text-align: center; padding: 50px;">
                        <i class="fas fa-film" style="font-size: 3em; color: #ddd; margin-bottom: 20px;"></i>
                        <p style="font-size: 1.2em; color: #666;">Không tìm thấy phim nào phù hợp với từ khóa
                            "<?php echo htmlspecialchars($searchQuery); ?>"</p>
                        <p style="color: #999;">Hãy thử tìm kiếm với từ khóa khác</p>
                    </div>
                    <?php else: ?>
                    <p style="margin-bottom: 30px; color: #666;">
                        Tìm thấy <strong><?php echo count($movies); ?></strong> kết quả
                    </p>

                    <div class="movie-grid">
                        <?php foreach ($movies as $movie): ?>
                        <div class="movie-card">
                            <img src="<?php echo $movie['image']; ?>" alt="<?php echo $movie['title']; ?>" />
                            <h3><?php echo $movie['title']; ?></h3>
                            <p><strong>Thể loại:</strong> <?php echo $movie['genre']; ?></p>
                            <p><strong>Thời lượng:</strong> <?php echo $movie['duration']; ?></p>
                            <p><strong>Khởi chiếu:</strong>
                                <?php echo date('d/m/Y', strtotime($movie['release_date'])); ?></p>
                            <button onclick="window.location.href='movie_detail.php?id=<?php echo $movie['id']; ?>'">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </article>

        <footer>
            <span>Chăm sóc khách hàng: 1900 6017</span>
        </footer>
    </div>
</body>

</html>