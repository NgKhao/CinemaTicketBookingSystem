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

    // Include header component chung
    include 'header_user.php';
    ?>

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