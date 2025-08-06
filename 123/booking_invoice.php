<?php
session_start();
require_once 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit;
}

// Kiểm tra có thông tin booking success không
if (!isset($_SESSION['booking_success'])) {
    header('Location: datve.php');
    exit;
}

$booking_info = $_SESSION['booking_success'];

// Lấy thông tin chi tiết
try {
    $stmt = $pdo->prepare("
        SELECT b.*, s.show_date, s.show_time, m.title as movie_title, c.name as cinema_name
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.id
        JOIN movies m ON s.movie_id = m.id
        JOIN cinemas c ON s.cinema_id = c.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_info['booking_id']]);
    $booking_detail = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking_detail) {
        throw new Exception('Không tìm thấy thông tin đặt vé');
    }
} catch (Exception $e) {
    echo "<script>alert('Lỗi: " . $e->getMessage() . "'); window.location.href='index.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa Đơn Đặt Vé - CGV Cinemas</title>
    <link rel="stylesheet" href="ASST1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .invoice-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(229, 9, 20, 0.1);
            overflow: hidden;
        }

        .invoice-header {
            background: linear-gradient(135deg, #e50914, #8b0813);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .invoice-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }

        .invoice-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }

        .invoice-body {
            padding: 2rem;
        }

        .booking-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid #e50914;
        }

        .info-section h3 {
            margin: 0 0 1rem 0;
            color: #e50914;
            font-size: 1.2rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            color: #333;
        }

        .info-value {
            color: #666;
        }

        .payment-status {
            text-align: center;
            padding: 1.5rem;
            margin: 2rem 0;
            border-radius: 10px;
        }

        .status-pending {
            background: linear-gradient(135deg, #ffc107, #ff8f00);
            color: white;
        }

        .status-paid {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .status-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .status-text {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .status-desc {
            opacity: 0.9;
        }

        .total-section {
            background: linear-gradient(135deg, #e50914, #8b0813);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            margin: 2rem 0;
        }

        .total-amount {
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }

        .actions {
            text-align: center;
            margin-top: 2rem;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            background: #e50914;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #b8070f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(229, 9, 20, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #e50914;
            color: #e50914;
        }

        .btn-outline:hover {
            background: #e50914;
            color: white;
        }

        .seats-display {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .seat-tag {
            background: #e50914;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .booking-info {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .invoice-container {
                margin: 1rem;
            }

            .invoice-header {
                padding: 1.5rem 1rem;
            }

            .invoice-body {
                padding: 1.5rem 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <h1><i class="fas fa-ticket-alt"></i> HÓA ĐƠN ĐẶT VÉ</h1>
            <p>Mã đặt vé: #<?= str_pad($booking_info['booking_id'], 6, '0', STR_PAD_LEFT) ?></p>
            <p>Ngày đặt: <?= date('d/m/Y H:i:s') ?></p>
        </div>

        <div class="invoice-body">
            <div class="booking-info">
                <div class="info-section">
                    <h3><i class="fas fa-user"></i> Thông Tin Khách Hàng</h3>
                    <div class="info-item">
                        <span class="info-label">Họ tên:</span>
                        <span class="info-value"><?= htmlspecialchars($booking_info['customer_name']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Số điện thoại:</span>
                        <span class="info-value"><?= htmlspecialchars($booking_info['phone']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tài khoản:</span>
                        <span class="info-value"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    </div>
                </div>

                <div class="info-section">
                    <h3><i class="fas fa-film"></i> Thông Tin Phim</h3>
                    <div class="info-item">
                        <span class="info-label">Tên phim:</span>
                        <span class="info-value"><?= htmlspecialchars($booking_detail['movie_title']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Rạp chiếu:</span>
                        <span class="info-value"><?= htmlspecialchars($booking_detail['cinema_name']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Ngày chiếu:</span>
                        <span class="info-value"><?= date('d/m/Y', strtotime($booking_detail['show_date'])) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Giờ chiếu:</span>
                        <span class="info-value"><?= date('H:i', strtotime($booking_detail['show_time'])) ?></span>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h3><i class="fas fa-couch"></i> Ghế Đã Đặt</h3>
                <div class="seats-display">
                    <?php
                    $seats = explode(',', $booking_info['seats']);
                    foreach ($seats as $seat) {
                        $seat = trim($seat);
                        if (!empty($seat)) {
                            echo "<span class='seat-tag'>$seat</span>";
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="payment-status <?= $booking_info['status'] === 'paid' ? 'status-paid' : 'status-pending' ?>">
                <div class="status-icon">
                    <?php if ($booking_info['status'] === 'paid'): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php else: ?>
                        <i class="fas fa-clock"></i>
                    <?php endif; ?>
                </div>
                <div class="status-text">
                    <?php if ($booking_info['status'] === 'paid'): ?>
                        THANH TOÁN THÀNH CÔNG
                    <?php else: ?>
                        CHỜ THANH TOÁN
                    <?php endif; ?>
                </div>
                <div class="status-desc">
                    <?php if ($booking_info['status'] === 'paid'): ?>
                        Vé của bạn đã được thanh toán và xác nhận thành công
                    <?php elseif ($booking_info['payment_method'] === 'counter'): ?>
                        Vui lòng đến quầy thanh toán trước giờ chiếu 15 phút
                    <?php else: ?>
                        Đang chờ xử lý thanh toán online
                    <?php endif; ?>
                </div>
            </div>

            <div class="total-section">
                <p>TỔNG TIỀN</p>
                <p class="total-amount"><?= number_format($booking_info['total_price']) ?>đ</p>
                <p>Phương thức: <?= $booking_info['payment_method'] === 'counter' ? 'Thanh toán tại quầy' : 'VNPay' ?></p>
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-home"></i> Về Trang Chủ
                </a>
                <a href="datve.php" class="btn">
                    <i class="fas fa-plus"></i> Đặt Vé Khác
                </a>
                <button onclick="window.print()" class="btn">
                    <i class="fas fa-print"></i> In Hóa Đơn
                </button>
            </div>
        </div>
    </div>

    <?php
    // Xóa thông tin booking success khỏi session sau khi hiển thị
    unset($_SESSION['booking_success']);
    ?>
</body>

</html>