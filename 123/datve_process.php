<?php
session_start();
require_once 'config.php';

if ($_POST) {
    $user_id = $_SESSION['user_id'] ?? null;
    $customer_name = $_POST['customer_name'];
    $phone = $_POST['phone'];
    $movie_id = $_POST['movie_id'];
    $showtime_id = $_POST['showtime_id'] ?? null;
    $seats = $_POST['seats'] ?? '';

    if (!$user_id) {
        echo "<script>alert('Vui lòng đăng nhập để đặt vé!'); window.location.href='login.html';</script>";
        exit;
    }

    if (empty($seats)) {
        echo "<script>alert('Vui lòng chọn ghế!'); window.location.href='datve.php?movie_id=$movie_id';</script>";
        exit;
    }

    if (!$showtime_id) {
        echo "<script>alert('Vui lòng chọn suất chiếu!'); window.location.href='datve.php?movie_id=$movie_id';</script>";
        exit;
    }

    try {
        // Kiểm tra suất chiếu có tồn tại và còn ghế không
        $stmt = $pdo->prepare("SELECT * FROM showtimes WHERE id = ? AND available_seats > 0");
        $stmt->execute([$showtime_id]);
        $showtime = $stmt->fetch();

        if (!$showtime) {
            throw new Exception("Suất chiếu không tồn tại hoặc đã hết ghế!");
        }

        // Kiểm tra ghế đã được đặt chưa
        $seatArray = explode(',', $seats);
        $seatArray = array_filter(array_map('trim', $seatArray)); // Loại bỏ ghế rỗng

        if (count($seatArray) > $showtime['available_seats']) {
            throw new Exception("Số ghế bạn chọn vượt quá số ghế còn lại (" . $showtime['available_seats'] . " ghế)!");
        }

        foreach ($seatArray as $seat) {
            $stmt = $pdo->prepare("SELECT id FROM booked_seats WHERE showtime_id = ? AND seat_number = ?");
            $stmt->execute([$showtime_id, $seat]);
            if ($stmt->fetch()) {
                throw new Exception("Ghế $seat đã được đặt!");
            }
        }

        // Lưu thông tin đặt vé vào session để chuyển đến trang thanh toán
        $_SESSION['booking_data'] = [
            'user_id' => $user_id,
            'customer_name' => $customer_name,
            'phone' => $phone,
            'movie_id' => $movie_id,
            'showtime_id' => $showtime_id,
            'seats' => implode(',', $seatArray) // Đảm bảo không có ghế rỗng
        ];

        // Chuyển hướng đến trang chọn phương thức thanh toán
        header('Location: payment_method.php');
        exit;
    } catch (Exception $e) {
        echo "<script>alert('Lỗi: " . $e->getMessage() . "'); window.location.href='datve.php?movie_id=$movie_id';</script>";
    }
}
