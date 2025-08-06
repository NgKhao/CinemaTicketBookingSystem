<?php
session_start();
require_once 'config.php';
require_once 'vnpay_config.php'; // Thêm config VNPay

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

if ($_POST) {
    $payment_method = $_POST['payment_method'] ?? '';
    $booking_data = $_SESSION['booking_data'];

    if (empty($payment_method)) {
        echo "<script>alert('Vui lòng chọn phương thức thanh toán!'); window.location.href='payment_method.php';</script>";
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Kiểm tra lại ghế có còn trống không
        $seatArray = explode(',', $booking_data['seats']);
        $seatArray = array_filter(array_map('trim', $seatArray));

        foreach ($seatArray as $seat) {
            $stmt = $pdo->prepare("SELECT id FROM booked_seats WHERE showtime_id = ? AND seat_number = ?");
            $stmt->execute([$booking_data['showtime_id'], $seat]);
            if ($stmt->fetch()) {
                throw new Exception("Ghế $seat đã được đặt bởi người khác! Vui lòng chọn ghế khác.");
            }
        }

        // Tính giá vé theo loại ghế
        $total_price = 0;
        foreach ($seatArray as $seat) {
            $row = substr($seat, 0, 1); // Lấy hàng ghế (A, B, C, D)

            if ($row == 'A') {
                $total_price += 60000; // Ghế thường
            } elseif ($row == 'B' || $row == 'C') {
                $total_price += 80000; // Ghế VIP
            } elseif ($row == 'D') {
                $total_price += 120000; // Ghế Sweetbox
            }
        }

        // Xác định status dựa trên phương thức thanh toán
        $status = 'pending'; // Mặc định là pending

        if ($payment_method === 'counter') {
            $status = 'pending'; // Thanh toán tại quầy - chờ thanh toán
        } elseif ($payment_method === 'vnpay') {
            // Sẽ xử lý VNPay ở prompt sau
            // Tạm thời set là pending, sau khi thanh toán VNPay thành công sẽ update thành 'paid'
            $status = 'pending';
        }

        // Lưu đặt vé với status phù hợp
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, customer_name, phone, showtime_id, seats, total_price, status, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $booking_data['user_id'],
            $booking_data['customer_name'],
            $booking_data['phone'],
            $booking_data['showtime_id'],
            $booking_data['seats'],
            $total_price,
            $status,
            $payment_method
        ]);
        $booking_id = $pdo->lastInsertId();

        // Lưu từng ghế đã đặt
        foreach ($seatArray as $seat) {
            $stmt = $pdo->prepare("INSERT INTO booked_seats (showtime_id, seat_number, booking_id) VALUES (?, ?, ?)");
            $stmt->execute([$booking_data['showtime_id'], $seat, $booking_id]);
        }

        // Cập nhật số ghế còn lại
        $stmt = $pdo->prepare("UPDATE showtimes SET available_seats = available_seats - ? WHERE id = ?");
        $stmt->execute([count($seatArray), $booking_data['showtime_id']]);

        $pdo->commit();

        // Xóa thông tin đặt vé khỏi session
        unset($_SESSION['booking_data']);

        if ($payment_method === 'counter') {
            // Thanh toán tại quầy - hiển thị thông báo và chuyển đến trang hóa đơn
            $_SESSION['booking_success'] = [
                'booking_id' => $booking_id,
                'customer_name' => $booking_data['customer_name'],
                'phone' => $booking_data['phone'],
                'seats' => $booking_data['seats'],
                'total_price' => $total_price,
                'payment_method' => 'counter',
                'status' => 'pending'
            ];
            header('Location: booking_invoice.php');
            exit;
        } elseif ($payment_method === 'vnpay') {
            // Lưu booking_id vào session để xử lý callback
            $_SESSION['vnpay_booking_id'] = $booking_id;
            $_SESSION['vnpay_booking_data'] = [
                'customer_name' => $booking_data['customer_name'],
                'phone' => $booking_data['phone'],
                'seats' => $booking_data['seats'],
                'total_price' => $total_price
            ];

            // Tạo thông tin đơn hàng cho VNPay
            $orderId = 'CGV' . date('YmdHis') . $booking_id; // Mã đơn hàng unique
            $orderInfo = "Thanh toan ve xem phim CGV - Don hang " . str_pad($booking_id, 6, '0', STR_PAD_LEFT);

            // Tạo URL thanh toán VNPay
            $vnpayUrl = createVNPayUrl($orderId, $total_price, $orderInfo);

            // Log để debug (có thể xóa khi deploy production)
            error_log("VNPay URL: " . $vnpayUrl);

            // Chuyển hướng đến VNPay
            header("Location: " . $vnpayUrl);
            exit;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Lỗi: " . $e->getMessage() . "'); window.location.href='datve.php';</script>";
    }
} else {
    header('Location: payment_method.php');
    exit;
}
