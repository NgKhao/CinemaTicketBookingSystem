<?php
session_start();
require_once 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit;
}

// Kiểm tra có thông tin đặt vé trong session không
if (!isset($_SESSION['booking_data'])) {
    header('Location: datve.php');
    exit;
}

$booking_data = $_SESSION['booking_data'];

// Lấy thông tin chi tiết
$stmt = $pdo->prepare("
    SELECT m.title, m.genre, m.duration, c.name as cinema_name, s.show_date, s.show_time
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.id
    JOIN cinemas c ON s.cinema_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$booking_data['showtime_id']]);
$showtime_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Tính giá vé
$seat_prices = [
    'regular' => 60000,
    'vip' => 80000,
    'sweetbox' => 120000
];

$seats = explode(',', $booking_data['seats']);
$total_price = 0;
$seat_details = [];

foreach ($seats as $seat) {
    $seat = trim($seat);
    if (empty($seat)) continue;

    $seat_type = 'regular';
    if (strpos($seat, 'B') === 0 || strpos($seat, 'C') === 0) {
        $seat_type = 'vip';
    } elseif (strpos($seat, 'D') === 0) {
        $seat_type = 'sweetbox';
    }

    $price = $seat_prices[$seat_type];
    $total_price += $price;
    $seat_details[] = [
        'seat' => $seat,
        'type' => $seat_type,
        'price' => $price
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CGV - Chọn phương thức thanh toán</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="./img/4.png">

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    .container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }

    .header {
        background: #e50914;
        color: white;
        text-align: center;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
    }

    .nav {
        text-align: center;
        margin-bottom: 20px;
    }

    .nav a {
        color: #e50914;
        text-decoration: none;
        margin: 0 15px;
        font-weight: bold;
        transition: all 0.3s;
    }

    .nav a:hover {
        color: #b8070f;
    }

    .booking-summary {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .movie-info {
        background: linear-gradient(135deg, #e50914 0%, #b8070f 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .movie-info h3 {
        margin-bottom: 15px;
        font-size: 24px;
    }

    .movie-info p {
        margin: 8px 0;
        font-size: 16px;
    }

    .ticket-details {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 5px solid #e50914;
    }

    .seat-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin: 15px 0;
    }

    .seat-item {
        background: white;
        padding: 15px;
        border-radius: 8px;
        border: 2px solid #ddd;
        text-align: center;
    }

    .seat-item.vip {
        border-color: #ffc107;
        background: #fff8dc;
    }

    .seat-item.sweetbox {
        border-color: #e50914;
        background: #ffe6e6;
    }

    .seat-item.regular {
        border-color: #28a745;
        background: #f0fff0;
    }

    .total-section {
        background: #e50914;
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        margin-bottom: 30px;
    }

    .total-section h3 {
        font-size: 28px;
        margin-bottom: 10px;
    }

    .payment-section {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .payment-methods {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .payment-option {
        border: 3px solid #ddd;
        border-radius: 15px;
        padding: 25px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: white;
    }

    .payment-option:hover {
        border-color: #e50914;
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(229, 9, 20, 0.2);
    }

    .payment-option.selected {
        border-color: #e50914;
        background: #fff5f5;
    }

    .payment-option i {
        font-size: 48px;
        color: #e50914;
        margin-bottom: 15px;
    }

    .payment-option h4 {
        color: #333;
        margin-bottom: 10px;
        font-size: 20px;
    }

    .payment-option p {
        color: #666;
        font-size: 14px;
    }

    .confirm-btn {
        background: linear-gradient(135deg, #e50914 0%, #b8070f 100%);
        color: white;
        border: none;
        padding: 18px 40px;
        border-radius: 10px;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        width: 100%;
        margin-top: 20px;
    }

    .confirm-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(229, 9, 20, 0.4);
    }

    .confirm-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
    }

    .customer-info {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .customer-info h4 {
        color: #e50914;
        margin-bottom: 15px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin: 10px 0;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .back-btn {
        background: #6c757d;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin-right: 15px;
        transition: all 0.3s;
    }

    .back-btn:hover {
        background: #5a6268;
        color: white;
        text-decoration: none;
    }

    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #f5c6cb;
        display: none;
    }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-credit-card"></i> Chọn phương thức thanh toán</h1>
            <p>Hoàn tất đặt vé xem phim tại CGV</p>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
            <a href="datve.php"><i class="fas fa-arrow-left"></i> Quay lại đặt vé</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>

        <!-- Booking Summary -->
        <div class="booking-summary">
            <h3><i class="fas fa-ticket-alt"></i> Thông tin đặt vé</h3>

            <!-- Movie Information -->
            <div class="movie-info">
                <h3><i class="fas fa-video"></i> <?php echo $showtime_info['title']; ?></h3>
                <p><i class="fas fa-tags"></i> <strong>Thể loại:</strong> <?php echo $showtime_info['genre']; ?></p>
                <p><i class="fas fa-clock"></i> <strong>Thời lượng:</strong> <?php echo $showtime_info['duration']; ?>
                </p>
                <p><i class="fas fa-map-marker-alt"></i> <strong>Rạp:</strong>
                    <?php echo $showtime_info['cinema_name']; ?></p>
                <p><i class="fas fa-calendar"></i> <strong>Ngày chiếu:</strong>
                    <?php echo date('d/m/Y (l)', strtotime($showtime_info['show_date'])); ?></p>
                <p><i class="fas fa-clock"></i> <strong>Giờ chiếu:</strong>
                    <?php echo date('H:i', strtotime($showtime_info['show_time'])); ?></p>
            </div>

            <!-- Customer Information -->
            <div class="customer-info">
                <h4><i class="fas fa-user"></i> Thông tin khách hàng</h4>
                <div class="info-row">
                    <span><strong>Tên khách hàng:</strong></span>
                    <span><?php echo $booking_data['customer_name']; ?></span>
                </div>
                <div class="info-row">
                    <span><strong>Số điện thoại:</strong></span>
                    <span><?php echo $booking_data['phone']; ?></span>
                </div>
            </div>

            <!-- Ticket Details -->
            <div class="ticket-details">
                <h4><i class="fas fa-chair"></i> Chi tiết ghế đã chọn</h4>
                <div class="seat-list">
                    <?php foreach ($seat_details as $seat): ?>
                    <div class="seat-item <?php echo $seat['type']; ?>">
                        <div style="font-weight: bold; font-size: 18px;"><?php echo $seat['seat']; ?></div>
                        <div style="margin: 5px 0;">
                            <?php
                                $type_names = [
                                    'regular' => 'Ghế thường',
                                    'vip' => 'Ghế VIP',
                                    'sweetbox' => 'Ghế Sweetbox'
                                ];
                                echo $type_names[$seat['type']];
                                ?>
                        </div>
                        <div style="color: #e50914; font-weight: bold;">
                            <?php echo number_format($seat['price'], 0, ',', '.'); ?>đ
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Total -->
            <div class="total-section">
                <h3>Tổng tiền: <?php echo number_format($total_price, 0, ',', '.'); ?>đ</h3>
                <p><?php echo count($seat_details); ?> ghế đã chọn</p>
            </div>
        </div>

        <!-- Payment Method Selection -->
        <div class="payment-section">
            <h3><i class="fas fa-credit-card"></i> Chọn phương thức thanh toán</h3>

            <form id="paymentForm" action="process_payment.php" method="POST">
                <!-- Hidden inputs -->
                <input type="hidden" name="customer_name"
                    value="<?php echo htmlspecialchars($booking_data['customer_name']); ?>">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($booking_data['phone']); ?>">
                <input type="hidden" name="movie_id" value="<?php echo $booking_data['movie_id']; ?>">
                <input type="hidden" name="showtime_id" value="<?php echo $booking_data['showtime_id']; ?>">
                <input type="hidden" name="seats" value="<?php echo htmlspecialchars($booking_data['seats']); ?>">
                <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">

                <div class="payment-methods">
                    <!-- Thanh toán tại quầy -->
                    <div class="payment-option" data-method="counter">
                        <input type="radio" name="payment_method" value="counter" style="display: none;">
                        <i class="fas fa-store"></i>
                        <h4>Thanh toán tại quầy</h4>
                        <p>Thanh toán bằng tiền mặt hoặc thẻ tại quầy CGV khi nhận vé</p>
                        <div style="margin-top: 15px; color: #28a745; font-weight: bold;">
                            <i class="fas fa-check-circle"></i> Miễn phí
                        </div>
                    </div>

                    <!-- Thanh toán VNPay -->
                    <div class="payment-option" data-method="vnpay">
                        <input type="radio" name="payment_method" value="vnpay" style="display: none;">
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                            <i class="fas fa-credit-card" style="font-size: 48px; color: #0066cc;"></i>
                            <img src="https://stcd02206177151.cloud.edgevnpay.vn/assets/images/logo-icon/logo-primary.svg"
                                height="40" alt="VNPay" style="max-width: 100px;">
                        </div>
                        <h4>Thanh toán VNPay</h4>
                        <p>Thanh toán trực tuyến an toàn với VNPay - Hỗ trợ tất cả ngân hàng</p>

                        <div style="display: flex; justify-content: center; gap: 8px; margin: 15px 0;">
                            <img src="data:image/jpeg;base64,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"
                                height="25" alt="Vietcombank" title="Vietcombank">
                            <img src="data:image/png;base64,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"
                                height="25" alt="Techcombank" title="Techcombank">
                            <img src="data:image/png;base64,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"
                                height="25" alt="VietinBank" title="VietinBank">
                            <span style="color: #666; font-size: 12px; align-self: center;">+50 ngân hàng khác</span>
                        </div>

                        <div style="margin-top: 15px;">
                            <div style="color: #28a745; font-weight: bold; font-size: 14px; margin-bottom: 5px;">
                                <i class="fas fa-shield-alt"></i> Bảo mật SSL 256-bit
                            </div>
                            <div style="color: #0066cc; font-weight: bold; font-size: 14px;">
                                <i class="fas fa-bolt"></i> Thanh toán nhanh chóng
                            </div>
                        </div>
                    </div>
                </div>

                <div id="errorMessage" class="error-message"></div>

                <div style="margin-top: 30px; text-align: center;">
                    <a href="datve.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                    <button type="button" id="confirmPaymentBtn" class="confirm-btn" onclick="confirmPayment()">
                        <i class="fas fa-check"></i> Xác nhận thanh toán
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    let selectedPaymentMethod = null;

    // Xử lý chọn phương thức thanh toán
    document.querySelectorAll('.payment-option').forEach(option => {
        option.addEventListener('click', function() {
            // Bỏ chọn tất cả
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
                opt.querySelector('input[type="radio"]').checked = false;
            });

            // Chọn option hiện tại
            this.classList.add('selected');
            this.querySelector('input[type="radio"]').checked = true;
            selectedPaymentMethod = this.getAttribute('data-method');

            // Cập nhật nút xác nhận
            updateConfirmButton();
        });
    });

    function updateConfirmButton() {
        const btn = document.getElementById('confirmPaymentBtn');
        if (selectedPaymentMethod) {
            btn.disabled = false;
            if (selectedPaymentMethod === 'counter') {
                btn.innerHTML = '<i class="fas fa-store"></i> Xác nhận - Thanh toán tại quầy';
            } else {
                btn.innerHTML = '<i class="fas fa-credit-card"></i> Thanh toán qua VNPay';
            }
        } else {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-check"></i> Chọn phương thức thanh toán';
        }
    }

    function confirmPayment() {
        if (!selectedPaymentMethod) {
            showError('Vui lòng chọn phương thức thanh toán!');
            return;
        }

        // Submit form
        document.getElementById('paymentForm').submit();
    }

    function showError(message) {
        const errorDiv = document.getElementById('errorMessage');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';

        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }

    // Khởi tạo nút
    updateConfirmButton();
    </script>
</body>

</html>