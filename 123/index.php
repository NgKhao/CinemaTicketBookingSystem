<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CGV-Trang chủ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="ASST1.css" />
    <link rel="icon" href="./img/4.png" />
</head>

<body>
    <?php
    session_start();
    // Kiểm tra trạng thái đăng nhập
    $isLoggedIn = isset($_SESSION['username']);
    $username = $isLoggedIn ? $_SESSION['username'] : '';
    $userRole = $isLoggedIn ? $_SESSION['role'] : '';
    ?>

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
                    <input type="text" name="q" placeholder="Tìm kiếm phim..." class="search-input"
                        value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
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

        <!-- Main Content Container -->
        <main class="main-container">


            <!-- Movies Section -->
            <section class="movies-section">
                <div class="section-header">
                    <h1><i class="fas fa-star"></i> PHIM ĐANG CHIẾU</h1>
                    <p class="section-subtitle">Khám phá những bộ phim hay nhất đang được chiếu tại CGV</p>
                </div>
                <div class="movie-grid">
                    <?php include 'get_movies.php'; ?>
                </div>
            </section>
            <!-- CGV Introduction Section -->
            <section class="intro-section">
                <div class="intro-content">
                    <h2><i class="fas fa-film"></i> Giới thiệu CGV</h2>
                    <div class="intro-text">
                        <p>
                            <strong>CJ CGV</strong> là một trong top 5 cụm rạp chiếu phim lớn nhất toàn cầu và là
                            nhà phát hành, cụm rạp chiếu phim lớn nhất Việt Nam. Mục tiêu của
                            chúng tôi là trở thành hình mẫu công ty điển hình đóng góp cho sự
                            phát triển không ngừng của ngành công nghiệp điện ảnh Việt Nam.
                        </p>
                        <p>
                            CJ CGV đã tạo nên khái niệm độc đáo về việc chuyển đổi rạp chiếu
                            phim truyền thống thành tổ hợp văn hóa <strong>"Cultureplex"</strong>, nơi khán giả
                            không chỉ đến thưởng thức điện ảnh đa dạng thông qua các công nghệ
                            tiên tiến như SCREENX, IMAX, STARIUM, 4DX, Dolby Atmos.
                        </p>
                        <p>
                            Thông qua những nỗ lực trong việc xây dựng chương trình Nhà biên
                            kịch tài năng, Dự án phim ngắn CJ, Lớp học làm phim TOTO, CGV
                            ArtHouse cùng việc tài trợ cho các hoạt động liên hoan phim lớn
                            trong nước như Liên hoan Phim quốc tế Hà Nội, Liên hoan Phim Việt
                            Nam, CJ CGV Việt Nam mong muốn sẽ khám phá và hỗ trợ phát triển cho
                            các nhà làm phim trẻ tài năng của Việt Nam.
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <footer>
            <span>chăm sóc khách hàng</span>
        </footer>
    </div>
</body>

</html>