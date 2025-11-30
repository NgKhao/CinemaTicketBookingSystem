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

    // Lấy thông tin voucher và giá cuối cùng
    $voucher_id = !empty($_POST['voucher_id']) ? intval($_POST['voucher_id']) : null;
    $discount_amount = !empty($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0;
    $final_price = !empty($_POST['final_price']) ? floatval($_POST['final_price']) : 0;

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

        // Lưu đặt vé với status phù hợp và thông tin voucher
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, customer_name, phone, showtime_id, seats, total_price, discount_amount, final_price, voucher_id, status, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $booking_data['user_id'],
            $booking_data['customer_name'],
            $booking_data['phone'],
            $booking_data['showtime_id'],
            $booking_data['seats'],
            $total_price,
            $discount_amount,
            $final_price > 0 ? $final_price : $total_price, // Sử dụng final_price nếu có, không thì dùng total_price
            $voucher_id,
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

        // Xử lý voucher nếu có
        if ($voucher_id) {
            // Lưu lịch sử sử dụng voucher
            $stmt = $pdo->prepare("INSERT INTO voucher_usage (voucher_id, user_id, booking_id, discount_amount, used_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$voucher_id, $booking_data['user_id'], $booking_id, $discount_amount]);

            // Tăng số lần sử dụng voucher
            $stmt = $pdo->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
            $stmt->execute([$voucher_id]);
        }

        // Tích điểm cho thanh toán tại quầy (cho VNPay sẽ tích khi callback thành công)
        if ($payment_method === 'counter') {
            // Tính điểm: 1 điểm = 1,000 VND
            $points_earned = floor(($final_price > 0 ? $final_price : $total_price) / 1000);

            if ($points_earned > 0) {
                // Cộng điểm cho user
                $stmt = $pdo->prepare("UPDATE users SET member_points = member_points + ? WHERE id = ?");
                $stmt->execute([$points_earned, $booking_data['user_id']]);

                // Lấy tổng điểm hiện tại
                $stmt = $pdo->prepare("SELECT member_points FROM users WHERE id = ?");
                $stmt->execute([$booking_data['user_id']]);
                $current_points = $stmt->fetchColumn();

                // Kiểm tra và cập nhật hạng thành viên
                $stmt = $pdo->prepare("SELECT id FROM member_tiers WHERE min_points <= ? ORDER BY min_points DESC LIMIT 1");
                $stmt->execute([$current_points]);
                $new_tier_id = $stmt->fetchColumn();

                if ($new_tier_id) {
                    $stmt = $pdo->prepare("UPDATE users SET member_tier_id = ? WHERE id = ?");
                    $stmt->execute([$new_tier_id, $booking_data['user_id']]);
                }

                // Ghi log lịch sử tích điểm
                $stmt = $pdo->prepare("INSERT INTO point_history (user_id, booking_id, points, type, description, created_at) VALUES (?, ?, ?, 'earn', ?, NOW())");
                $stmt->execute([
                    $booking_data['user_id'],
                    $booking_id,
                    $points_earned,
                    "Tích điểm từ đặt vé #" . str_pad($booking_id, 6, '0', STR_PAD_LEFT)
                ]);

                // Cập nhật điểm vào booking
                $stmt = $pdo->prepare("UPDATE bookings SET points_earned = ? WHERE id = ?");
                $stmt->execute([$points_earned, $booking_id]);
            }
        }

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
