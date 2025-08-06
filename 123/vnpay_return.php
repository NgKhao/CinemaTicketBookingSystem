<?php

/**
 * VNPay Return/Callback Handler
 * Xử lý kết quả thanh toán từ VNPay
 * Theo tài liệu: https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html
 */

session_start();
require_once 'config.php';
require_once 'vnpay_config.php';

// Log để debug
error_log("VNPay Return - GET Data: " . print_r($_GET, true));

// Lấy tất cả dữ liệu từ VNPay
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

// Các tham số quan trọng từ VNPay
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';           // Mã đơn hàng
$vnp_Amount = ($_GET['vnp_Amount'] ?? 0) / 100;    // Số tiền (chia 100 để về VNĐ)
$vnp_OrderInfo = $_GET['vnp_OrderInfo'] ?? '';     // Thông tin đơn hàng  
$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? ''; // Mã kết quả
$vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? ''; // Mã giao dịch tại VNPay
$vnp_BankCode = $_GET['vnp_BankCode'] ?? '';        // Mã ngân hàng
$vnp_PayDate = $_GET['vnp_PayDate'] ?? '';          // Thời gian thanh toán

// Log thông tin giao dịch
error_log("VNPay Transaction: TxnRef=$vnp_TxnRef, Amount=$vnp_Amount, ResponseCode=$vnp_ResponseCode");

// Khởi tạo biến kết quả
$paymentStatus = 'error';
$message = 'Có lỗi xảy ra trong quá trình xử lý';
$booking_id = null;

// Xử lý kết quả
try {
    // Bước 1: Xác thực chữ ký
    $isValidSignature = verifyVNPayCallback($inputData, $vnp_SecureHash);

    if (!$isValidSignature) {
        throw new Exception('Chữ ký không hợp lệ - Giao dịch có thể bị giả mạo');
    }

    // Bước 2: Lấy booking_id từ session hoặc từ mã đơn hàng
    if (isset($_SESSION['vnpay_booking_id'])) {
        $booking_id = $_SESSION['vnpay_booking_id'];
    } else {
        // Fallback: extract booking_id từ TxnRef nếu cần
        preg_match('/CGV\d+(\d+)$/', $vnp_TxnRef, $matches);
        $booking_id = $matches[1] ?? null;
    }

    if (!$booking_id) {
        throw new Exception('Không tìm thấy thông tin đặt vé');
    }

    // Bước 3: Kiểm tra giao dịch trong database
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND payment_method = 'vnpay'");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Không tìm thấy đơn hàng hoặc phương thức thanh toán không đúng');
    }

    // Bước 4: Kiểm tra số tiền
    if (abs($booking['total_price'] - $vnp_Amount) > 1) { // Cho phép sai số 1 VNĐ
        throw new Exception('Số tiền không khớp với đơn hàng');
    }

    // Bước 5: Xử lý kết quả thanh toán
    if ($vnp_ResponseCode == "00") {
        // Thanh toán thành công
        $pdo->beginTransaction();

        // Cập nhật trạng thái booking
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET status = 'paid', 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$booking_id]);

        // Lưu thông tin giao dịch VNPay (tạo bảng payment_transactions nếu cần)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payment_transactions 
                (booking_id, vnp_txn_ref, vnp_transaction_no, vnp_amount, vnp_response_code, vnp_bank_code, vnp_pay_date, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $booking_id,
                $vnp_TxnRef,
                $vnp_TransactionNo,
                $vnp_Amount,
                $vnp_ResponseCode,
                $vnp_BankCode,
                $vnp_PayDate
            ]);
        } catch (Exception $e) {
            // Bảng payment_transactions chưa tồn tại - không sao, tiếp tục
            error_log("Payment transactions table not exists: " . $e->getMessage());
        }

        $pdo->commit();

        // Lưu thông tin thành công vào session
        $booking_data = $_SESSION['vnpay_booking_data'] ?? [];
        $_SESSION['booking_success'] = [
            'booking_id' => $booking_id,
            'customer_name' => $booking_data['customer_name'] ?? $booking['customer_name'],
            'phone' => $booking_data['phone'] ?? $booking['phone'],
            'seats' => $booking_data['seats'] ?? $booking['seats'],
            'total_price' => $vnp_Amount,
            'payment_method' => 'vnpay',
            'status' => 'paid',
            'vnpay_transaction' => $vnp_TransactionNo,
            'vnp_txn_ref' => $vnp_TxnRef,
            'vnp_bank_code' => $vnp_BankCode
        ];

        $paymentStatus = 'success';
        $message = 'Thanh toán thành công!';

        // Xóa dữ liệu tạm thời
        unset($_SESSION['vnpay_booking_id']);
        unset($_SESSION['vnpay_booking_data']);
        unset($_SESSION['booking_data']);
    } else {
        // Thanh toán thất bại
        $pdo->beginTransaction();

        // Cập nhật trạng thái booking thành cancelled
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$booking_id]);

        // Hoàn lại ghế đã đặt
        $stmt = $pdo->prepare("DELETE FROM booked_seats WHERE booking_id = ?");
        $stmt->execute([$booking_id]);

        // Cập nhật lại số ghế available
        $stmt = $pdo->prepare("
            UPDATE showtimes s 
            SET available_seats = available_seats + (
                SELECT CHAR_LENGTH(seats) - CHAR_LENGTH(REPLACE(seats, ',', '')) 
                FROM bookings WHERE id = ?
            )
            WHERE s.id = (SELECT showtime_id FROM bookings WHERE id = ?)
        ");
        $stmt->execute([$booking_id, $booking_id]);

        $pdo->commit();

        $paymentStatus = 'failed';
        $message = getVNPayResponseMessage($vnp_ResponseCode);

        // Xóa dữ liệu tạm thời
        unset($_SESSION['vnpay_booking_id']);
        unset($_SESSION['vnpay_booking_data']);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $paymentStatus = 'error';
    $message = $e->getMessage();
    error_log("VNPay processing error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả thanh toán VNPay - CGV</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="./img/4.png">
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .result-container {
        max-width: 600px;
        background: white;
        border-radius: 15px;
        padding: 40px;
        text-align: center;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    }

    .success-icon {
        color: #28a745;
        font-size: 80px;
        margin-bottom: 20px;
    }

    .error-icon {
        color: #dc3545;
        font-size: 80px;
        margin-bottom: 20px;
    }

    .warning-icon {
        color: #ffc107;
        font-size: 80px;
        margin-bottom: 20px;
    }

    .result-title {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 15px;
    }

    .success-title {
        color: #28a745;
    }

    .error-title {
        color: #dc3545;
    }

    .warning-title {
        color: #ffc107;
    }

    .result-message {
        color: #666;
        margin-bottom: 30px;
        line-height: 1.6;
        font-size: 16px;
    }

    .transaction-info {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
        text-align: left;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #eee;
    }

    .info-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .info-label {
        font-weight: bold;
        color: #333;
        flex: 1;
    }

    .info-value {
        flex: 2;
        text-align: right;
        color: #666;
    }

    .btn {
        display: inline-block;
        padding: 15px 30px;
        background: #e50914;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: bold;
        margin: 0 10px 10px 0;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }

    .btn:hover {
        background: #b8070f;
        transform: translateY(-2px);
        text-decoration: none;
        color: white;
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

    .btn-success {
        background: #28a745;
    }

    .btn-success:hover {
        background: #218838;
    }

    @media (max-width: 768px) {
        .result-container {
            margin: 10px;
            padding: 30px 20px;
        }

        .info-row {
            flex-direction: column;
            text-align: left;
        }

        .info-value {
            text-align: left;
            margin-top: 5px;
            font-weight: bold;
        }
    }
    </style>
</head>

<body>
    <div class="result-container">
        <?php if ($paymentStatus == 'success'): ?>
        <!-- Thanh toán thành công -->
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2 class="result-title success-title">Thanh toán thành công!</h2>
        <p class="result-message">
            Cảm ơn bạn đã thanh toán qua VNPay. Vé xem phim của bạn đã được xác nhận và thanh toán thành công.
        </p>

        <div class="transaction-info">
            <div class="info-row">
                <span class="info-label">Mã đơn hàng:</span>
                <span class="info-value"><?= htmlspecialchars($vnp_TxnRef) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Mã giao dịch VNPay:</span>
                <span class="info-value"><?= htmlspecialchars($vnp_TransactionNo) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Số tiền:</span>
                <span class="info-value"><?= number_format($vnp_Amount) ?>đ</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ngân hàng:</span>
                <span class="info-value"><?= htmlspecialchars($vnp_BankCode) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Thời gian thanh toán:</span>
                <span class="info-value">
                    <?= $vnp_PayDate ? date('d/m/Y H:i:s', strtotime($vnp_PayDate)) : date('d/m/Y H:i:s') ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Trạng thái:</span>
                <span class="info-value" style="color: #28a745; font-weight: bold;">Thành công</span>
            </div>
        </div>

        <a href="booking_invoice.php" class="btn btn-success">
            <i class="fas fa-receipt"></i> Xem hóa đơn chi tiết
        </a>
        <br>
        <a href="index.php" class="btn btn-outline">
            <i class="fas fa-home"></i> Về trang chủ
        </a>
        <a href="datve.php" class="btn">
            <i class="fas fa-plus"></i> Đặt vé khác
        </a>

        <?php else: ?>
        <!-- Thanh toán thất bại hoặc lỗi -->
        <div class="<?= $paymentStatus == 'failed' ? 'warning-icon' : 'error-icon' ?>">
            <i class="fas fa-<?= $paymentStatus == 'failed' ? 'exclamation-triangle' : 'times-circle' ?>"></i>
        </div>
        <h2 class="result-title <?= $paymentStatus == 'failed' ? 'warning-title' : 'error-title' ?>">
            <?= $paymentStatus == 'failed' ? 'Thanh toán không thành công!' : 'Có lỗi xảy ra!' ?>
        </h2>
        <p class="result-message">
            <?= htmlspecialchars($message) ?>
        </p>

        <?php if ($vnp_TxnRef): ?>
        <div class="transaction-info">
            <div class="info-row">
                <span class="info-label">Mã đơn hàng:</span>
                <span class="info-value"><?= htmlspecialchars($vnp_TxnRef) ?></span>
            </div>
            <?php if ($vnp_ResponseCode): ?>
            <div class="info-row">
                <span class="info-label">Mã lỗi:</span>
                <span class="info-value"><?= htmlspecialchars($vnp_ResponseCode) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($vnp_Amount > 0): ?>
            <div class="info-row">
                <span class="info-label">Số tiền:</span>
                <span class="info-value"><?= number_format($vnp_Amount) ?>đ</span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Thời gian:</span>
                <span class="info-value"><?= date('d/m/Y H:i:s') ?></span>
            </div>
        </div>
        <?php endif; ?>

        <a href="datve.php" class="btn">
            <i class="fas fa-redo"></i> Thử đặt vé lại
        </a>
        <a href="index.php" class="btn btn-outline">
            <i class="fas fa-home"></i> Về trang chủ
        </a>
        <?php endif; ?>
    </div>

    <script>
    // Auto redirect to invoice after 3 seconds if payment successful
    <?php if ($paymentStatus == 'success'): ?>
    setTimeout(function() {
        // Uncomment below line to enable auto redirect
        window.location.href = 'booking_invoice.php';
    }, 5000);
    <?php endif; ?>
    </script>
</body>

</html>