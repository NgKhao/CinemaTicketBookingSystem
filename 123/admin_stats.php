<?php
session_start();
require_once 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

// Thống kê tổng quan
$stats = [];

// Thống kê phim
$stmt = $pdo->prepare("
    SELECT m.title, COUNT(b.id) as bookings, COUNT(bs.id) as tickets_sold
    FROM movies m
    LEFT JOIN showtimes s ON m.id = s.movie_id
    LEFT JOIN bookings b ON s.id = b.showtime_id
    LEFT JOIN booked_seats bs ON b.id = bs.booking_id
    GROUP BY m.id
    ORDER BY tickets_sold DESC
    LIMIT 10
");
$stmt->execute();
$popular_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê doanh thu theo tháng
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(booking_date, '%Y-%m') as month,
           COUNT(*) as total_bookings,
           COUNT(bs.id) as total_tickets,
           SUM(b.total_price) as revenue
    FROM bookings b
    LEFT JOIN booked_seats bs ON b.id = bs.booking_id
    WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month DESC
");
$stmt->execute();
$monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê rạp
$stmt = $pdo->prepare("
    SELECT c.name, COUNT(b.id) as bookings, COUNT(bs.id) as tickets_sold
    FROM cinemas c
    LEFT JOIN showtimes s ON c.id = s.cinema_id
    LEFT JOIN bookings b ON s.id = b.showtime_id
    LEFT JOIN booked_seats bs ON b.id = bs.booking_id
    GROUP BY c.id
    ORDER BY tickets_sold DESC
");
$stmt->execute();
$cinema_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tổng quan hệ thống
$stmt = $pdo->prepare("SELECT COUNT(*) FROM movies");
$stmt->execute();
$total_movies = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$stmt->execute();
$total_customers = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings");
$stmt->execute();
$total_bookings = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(total_price) FROM bookings");
$stmt->execute();
$total_revenue = $stmt->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Thống Kê - Admin CGV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }

    .container {
        padding: 20px;
    }

    .admin-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    .stats-card {
        text-align: center;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        color: white;
    }

    .stats-movies {
        background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    }

    .stats-customers {
        background: linear-gradient(45deg, #4834d4, #686de0);
    }

    .stats-bookings {
        background: linear-gradient(45deg, #00d2d3, #01a3a4);
    }

    .stats-revenue {
        background: linear-gradient(45deg, #feca57, #ff9ff3);
    }

    .chart-container {
        position: relative;
        height: 300px;
        margin: 20px 0;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-card">
            <h1><i class="bi bi-graph-up"></i> Thống Kê Hệ Thống CGV</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin.php">Trang chủ Admin</a></li>
                    <li class="breadcrumb-item active">Thống Kê</li>
                </ol>
            </nav>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card stats-movies">
                    <i class="bi bi-film" style="font-size: 2rem;"></i>
                    <h3><?php echo $total_movies; ?></h3>
                    <p>Tổng số phim</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-customers">
                    <i class="bi bi-people" style="font-size: 2rem;"></i>
                    <h3><?php echo $total_customers; ?></h3>
                    <p>Khách hàng</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-bookings">
                    <i class="bi bi-ticket-perforated" style="font-size: 2rem;"></i>
                    <h3><?php echo $total_bookings; ?></h3>
                    <p>Vé đã đặt</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-revenue">
                    <i class="bi bi-currency-dollar" style="font-size: 2rem;"></i>
                    <h3><?php echo number_format($total_revenue / 1000); ?>K</h3>
                    <p>Doanh thu (VNĐ)</p>
                </div>
            </div>
        </div>

        <!-- Top phim bán chạy -->
        <div class="admin-card">
            <h3><i class="bi bi-trophy"></i> Top Phim Bán Chạy</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Hạng</th>
                            <th>Tên Phim</th>
                            <th>Số lượt đặt</th>
                            <th>Vé đã bán</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_movies as $index => $movie): ?>
                        <tr>
                            <td>
                                <?php if ($index < 3): ?>
                                <span class="badge bg-warning">#{<?php echo $index + 1; ?>}</span>
                                <?php else: ?>
                                #{<?php echo $index + 1; ?>}
                                <?php endif; ?>
                            </td>
                            <td><?php echo $movie['title']; ?></td>
                            <td><?php echo $movie['bookings'] ?: 0; ?> lượt</td>
                            <td><?php echo $movie['tickets_sold'] ?: 0; ?> vé</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card text-center">
            <a href="admin.php" class="btn btn-secondary me-2">
                <i class="bi bi-house"></i> Về trang chủ Admin
            </a>
            <a href="admin_tickets.php" class="btn btn-info me-2">
                <i class="bi bi-ticket-perforated"></i> Quản lý vé
            </a>
            <a href="admin_users.php" class="btn btn-primary me-2">
                <i class="bi bi-people"></i> Quản lý khách hàng
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="bi bi-power"></i> Đăng xuất
            </a>
        </div>
    </div>
</body>

</html>