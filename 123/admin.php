<?php
session_start();
require_once 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <title>Quản trị hệ thống CGV</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .welcome-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .admin-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .admin-card:hover {
            transform: translateY(-5px);
        }

        .feature-btn {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-movies {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
        }

        .btn-showtimes {
            background: linear-gradient(45deg, #4834d4, #686de0);
            color: white;
        }

        .btn-tickets {
            background: linear-gradient(45deg, #00d2d3, #01a3a4);
            color: white;
        }

        .btn-users {
            background: linear-gradient(45deg, #feca57, #ff9ff3);
            color: white;
        }

        .btn-stats {
            background: linear-gradient(45deg, #48dbfb, #0abde3);
            color: white;
        }

        .btn-logout {
            background: linear-gradient(45deg, #ff3838, #ff4757);
            color: white;
        }

        .feature-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <div class="welcome-card">
            <h1><i class="bi bi-shield-check"></i> Chào mừng đến Admin CGV</h1>
            <p class="lead">Hệ thống quản trị rạp chiếu phim</p>
            <hr>
            <p>Xin chào <strong><?php echo $_SESSION['username']; ?></strong>! Quản lý toàn bộ hoạt động của hệ thống CGV</p>
        </div>

        <div class="row">
            <!-- Quản lý Phim -->
            <div class="col-md-6 col-lg-4">
                <div class="admin-card">
                    <h4><i class="bi bi-film"></i> Quản lý Phim</h4>
                    <p>Thêm, sửa, xóa thông tin phim</p>
                    <a href="admin_movies.php" class="feature-btn btn-movies">
                        <i class="bi bi-plus-circle"></i> Quản lý Phim
                    </a>
                </div>
            </div>

            <!-- Quản lý Suất Chiếu -->
            <div class="col-md-6 col-lg-4">
                <div class="admin-card">
                    <h4><i class="bi bi-calendar-event"></i> Quản lý Suất Chiếu</h4>
                    <p>Tạo lịch chiếu cho các phim</p>
                    <a href="admin_showtimes.php" class="feature-btn btn-showtimes">
                        <i class="bi bi-clock"></i> Quản lý Suất Chiếu
                    </a>
                </div>
            </div>

            <!-- Quản lý Rạp -->
            <div class="col-md-6 col-lg-4">
                <div class="admin-card">
                    <h4><i class="bi bi-building"></i> Quản lý Rạp</h4>
                    <p>Thêm, sửa, xóa thông tin rạp</p>
                    <a href="admin_cinemas.php" class="feature-btn btn-movies">
                        <i class="bi bi-building"></i> Quản lý Rạp
                    </a>
                </div>
            </div>

            <!-- Quản lý Vé -->
            <div class="col-md-6 col-lg-4">
                <div class="admin-card">
                    <h4><i class="bi bi-ticket-perforated"></i> Quản lý Vé</h4>
                    <p>Xem danh sách vé đã đặt</p>
                    <a href="admin_tickets.php" class="feature-btn btn-tickets">
                        <i class="bi bi-list-check"></i> Xem Vé Đã Đặt
                    </a>
                </div>
            </div>

            <!-- Quản lý Khách Hàng -->
            <div class="col-md-6 col-lg-4">
                <div class="admin-card">
                    <h4><i class="bi bi-people"></i> Quản lý Khách Hàng</h4>
                    <p>Xem thông tin khách hàng</p>
                    <a href="admin_users.php" class="feature-btn btn-users">
                        <i class="bi bi-person-lines-fill"></i> Danh Sách Khách Hàng
                    </a>
                </div>
            </div>

            <!-- Thống Kê -->
            <div class="col-md-6 col-lg-4">
                <div class="admin-card">
                    <h4><i class="bi bi-graph-up"></i> Thống Kê</h4>
                    <p>Báo cáo doanh thu và số liệu</p>
                    <a href="admin_stats.php" class="feature-btn btn-stats">
                        <i class="bi bi-bar-chart"></i> Xem Thống Kê
                    </a>
                </div>
            </div>

            <!-- Đăng Xuất -->
            <div class="col-md-6 col-lg-4">
                <div class="admin-card">
                    <h4><i class="bi bi-box-arrow-right"></i> Đăng Xuất</h4>
                    <p>Thoát khỏi hệ thống</p>
                    <a href="logout.php" class="feature-btn btn-logout">
                        <i class="bi bi-power"></i> Đăng Xuất
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>