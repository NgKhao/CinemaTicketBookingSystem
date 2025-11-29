<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

$code = strtoupper(trim($_POST['code'] ?? ''));
$total_price = floatval($_POST['total_price'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã voucher!']);
    exit;
}

if ($total_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Giá trị đơn hàng không hợp lệ!']);
    exit;
}

try {
    // Lấy thông tin voucher
    $stmt = $pdo->prepare("
        SELECT * FROM vouchers 
        WHERE code = ? AND is_active = 1
    ");
    $stmt->execute([$code]);
    $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voucher) {
        echo json_encode(['success' => false, 'message' => 'Mã voucher không tồn tại hoặc đã bị vô hiệu hóa!']);
        exit;
    }

    // Kiểm tra thời hạn
    $today = date('Y-m-d');
    if ($today < $voucher['start_date']) {
        echo json_encode(['success' => false, 'message' => 'Voucher chưa đến ngày sử dụng!']);
        exit;
    }

    if ($today > $voucher['end_date']) {
        echo json_encode(['success' => false, 'message' => 'Voucher đã hết hạn!']);
        exit;
    }

    // Kiểm tra số lần sử dụng
    if ($voucher['used_count'] >= $voucher['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'Voucher đã hết lượt sử dụng!']);
        exit;
    }

    // Kiểm tra đơn hàng tối thiểu
    if ($total_price < $voucher['min_order']) {
        $min_formatted = number_format($voucher['min_order']);
        echo json_encode([
            'success' => false,
            'message' => "Đơn hàng tối thiểu {$min_formatted}đ để sử dụng voucher này!"
        ]);
        exit;
    }

    // Kiểm tra user đã dùng voucher này chưa (cho voucher giới hạn 1 lần/người)
    if ($voucher['usage_limit'] == 1) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM voucher_usage 
            WHERE voucher_id = ? AND user_id = ?
        ");
        $stmt->execute([$voucher['id'], $_SESSION['user_id']]);
        $used_by_user = $stmt->fetchColumn();

        if ($used_by_user > 0) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã sử dụng voucher này rồi!']);
            exit;
        }
    }

    // Tính toán giảm giá
    $discount_amount = 0;
    if ($voucher['type'] == 'percent') {
        $discount_amount = ($total_price * $voucher['value']) / 100;

        // Áp dụng giảm tối đa nếu có
        if ($voucher['max_discount'] && $discount_amount > $voucher['max_discount']) {
            $discount_amount = $voucher['max_discount'];
        }
    } else {
        // Fixed amount
        $discount_amount = $voucher['value'];
    }

    // Đảm bảo giảm giá không vượt quá tổng tiền
    if ($discount_amount > $total_price) {
        $discount_amount = $total_price;
    }

    $final_price = $total_price - $discount_amount;

    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'message' => 'Áp dụng voucher thành công!',
        'voucher' => [
            'id' => $voucher['id'],
            'code' => $voucher['code'],
            'type' => $voucher['type'],
            'value' => $voucher['value'],
            'description' => $voucher['description']
        ],
        'discount_amount' => $discount_amount,
        'discount_formatted' => number_format($discount_amount) . 'đ',
        'final_price' => $final_price,
        'final_price_formatted' => number_format($final_price) . 'đ'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
