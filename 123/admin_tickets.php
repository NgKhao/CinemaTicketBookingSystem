<?php
session_start();
require_once 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

// Cập nhật trạng thái booking
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $booking_id])) {
        echo "<script>alert('Cập nhật trạng thái thành công!');</script>";
    }
}

// Lấy danh sách tất cả vé đã đặt
$stmt = $pdo->prepare("
    SELECT b.*, u.username, m.title as movie_title, 
           c.name as cinema_name, s.show_date, s.show_time,
           GROUP_CONCAT(bs.seat_number ORDER BY bs.seat_number) as seats
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN cinemas c ON s.cinema_id = c.id
    LEFT JOIN booked_seats bs ON b.id = bs.booking_id
    GROUP BY b.id
    ORDER BY b.booking_date DESC
");
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê theo trạng thái
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count, SUM(total_price) as revenue
    FROM bookings 
    GROUP BY status
");
$stmt->execute();
$status_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê tổng quan
$stmt = $pdo->prepare("SELECT COUNT(*) as total_bookings FROM bookings");
$stmt->execute();
$total_bookings = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as total_seats FROM booked_seats");
$stmt->execute();
$total_seats = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(total_price) as total_revenue FROM bookings");
$stmt->execute();
$total_revenue = $stmt->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Quản lý Vé - Admin CGV</title>
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
            background: linear-gradient(45deg, #4834d4, #686de0);
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .table th {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-card">
            <h1><i class="bi bi-ticket-perforated"></i> Quản lý Vé Đã Đặt</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin.php">Trang chủ Admin</a></li>
                    <li class="breadcrumb-item active">Quản lý Vé</li>
                </ol>
            </nav>
        </div>

        <!-- Thống kê theo trạng thái -->
        <div class="row">
            <?php foreach ($status_stats as $stat): ?>
                <div class="col-md-3">
                    <div class="stats-card">
                        <h4><?php echo $stat['count']; ?></h4>
                        <p><?php
                            switch ($stat['status']) {
                                case 'pending':
                                    echo 'Chờ xử lý';
                                    break;
                                case 'paid':
                                    echo 'Đã thanh toán';
                                    break;
                                case 'confirmed':
                                    echo 'Đã xác nhận';
                                    break;
                                case 'cancelled':
                                    echo 'Đã hủy';
                                    break;
                                default:
                                    echo $stat['status'];
                                    break;
                            }
                            ?></p>
                        <small><?php echo number_format($stat['revenue'] ?: 0); ?> VNĐ</small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?php echo $total_bookings; ?></h3>
                    <p>Tổng số lượt đặt vé</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?php echo $total_seats; ?></h3>
                    <p>Tổng số ghế đã đặt</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?php echo number_format($total_revenue); ?> VNĐ</h3>
                    <p>Tổng doanh thu</p>
                </div>
            </div>
        </div>

        <!-- Danh sách vé đã đặt -->
        <div class="admin-card">
            <h3>Danh sách vé đã đặt</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th>Thông tin đặt vé</th>
                            <th>Phim</th>
                            <th>Rạp</th>
                            <th>Ngày chiếu</th>
                            <th>Giờ chiếu</th>
                            <th>Ghế đã đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['id']; ?></td>
                                <td>
                                    <strong>@<?php echo $booking['username']; ?></strong>
                                </td>
                                <td>
                                    <?php if ($booking['customer_name']): ?>
                                        <strong><?php echo $booking['customer_name']; ?></strong><br>
                                        <small><?php echo $booking['phone']; ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Không có thông tin</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $booking['movie_title']; ?></td>
                                <td><?php echo $booking['cinema_name']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($booking['show_date'])); ?></td>
                                <td><?php echo date('H:i', strtotime($booking['show_time'])); ?></td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo $booking['seats'] ?: 'N/A'; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($booking['total_price']); ?> VNĐ</td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    switch ($booking['status']) {
                                        case 'pending':
                                            $status_class = 'bg-warning';
                                            $status_text = 'Chờ xử lý';
                                            break;
                                        case 'paid':
                                            $status_class = 'bg-info';
                                            $status_text = 'Đã thanh toán';
                                            break;
                                        case 'confirmed':
                                            $status_class = 'bg-success';
                                            $status_text = 'Đã xác nhận';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'bg-danger';
                                            $status_text = 'Đã hủy';
                                            break;
                                        default:
                                            $status_class = 'bg-secondary';
                                            $status_text = $booking['status'];
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <select name="status" class="form-select form-select-sm" style="width: auto; display: inline-block;" onchange="this.form.submit()">
                                            <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                            <option value="paid" <?php echo $booking['status'] == 'paid' ? 'selected' : ''; ?>>Đã thanh toán</option>
                                            <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                            <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                        </select>
                                    </form>
                                </td>
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
            <a href="admin_stats.php" class="btn btn-info me-2">
                <i class="bi bi-graph-up"></i> Xem thống kê chi tiết
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="bi bi-power"></i> Đăng xuất
            </a>
        </div>
    </div>
</body>

</html>