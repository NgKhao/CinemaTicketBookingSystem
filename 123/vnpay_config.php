<?php

// Thông tin VNPay đã đăng ký
define('VNP_TMN_CODE', 'GH7VR2LJ');
define('VNP_HASH_SECRET', 'TKV7KDG8VX77OCDOJFGDAULWEU7HV9WR');
define('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNP_RETURN_URL', 'http://localhost/CinemaTicketBookingSystem/123/vnpay_return.php');

// Cấu hình timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

/**
 * Tạo URL thanh toán VNPay
 * @param string $orderId Mã đơn hàng
 * @param int $amount Số tiền (VND)
 * @param string $orderInfo Thông tin đơn hàng
 * @param string $returnUrl URL return (optional)
 * @return string VNPay payment URL
 */
function createVNPayUrl($orderId, $amount, $orderInfo, $returnUrl = null)
{
    $vnp_TmnCode = VNP_TMN_CODE;
    $vnp_HashSecret = VNP_HASH_SECRET;
    $vnp_Url = VNP_URL;
    $vnp_ReturnUrl = $returnUrl ?: VNP_RETURN_URL;

    // Các tham số theo tài liệu VNPay
    $inputData = array(
        "vnp_Version" => "2.1.0",                    // Phiên bản API
        "vnp_TmnCode" => $vnp_TmnCode,               // Mã website merchant
        "vnp_Amount" => $amount * 100,               // Số tiền thanh toán (VNĐ * 100)
        "vnp_Command" => "pay",                      // Mã API
        "vnp_CreateDate" => date('YmdHis'),          // Thời gian phát sinh giao dịch
        "vnp_CurrCode" => "VND",                     // Đơn vị tiền tệ
        "vnp_IpAddr" => $_SERVER['REMOTE_ADDR'],     // IP address khách hàng
        "vnp_Locale" => "vn",                        // Ngôn ngữ giao diện (vn/en)
        "vnp_OrderInfo" => $orderInfo,               // Thông tin mô tả nội dung thanh toán
        "vnp_OrderType" => "other",                  // Mã danh mục hàng hóa
        "vnp_ReturnUrl" => $vnp_ReturnUrl,           // URL thông báo kết quả giao dịch
        "vnp_TxnRef" => $orderId,                    // Mã tham chiếu của giao dịch
    );

    // Sắp xếp dữ liệu theo thứ tự alphabet của key
    ksort($inputData);
    $query = "";
    $i = 0;
    $hashdata = "";

    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }

    $vnp_Url = $vnp_Url . "?" . $query;

    // Tạo vnp_SecureHash
    if (isset($vnp_HashSecret)) {
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
    }

    return $vnp_Url;
}

/**
 * Xác thực chữ ký từ VNPay callback
 * @param array $inputData Dữ liệu từ VNPay
 * @param string $vnp_SecureHash Chữ ký từ VNPay
 * @return bool True nếu hợp lệ
 */
function verifyVNPayCallback($inputData, $vnp_SecureHash)
{
    $vnp_HashSecret = VNP_HASH_SECRET;

    // Loại bỏ vnp_SecureHash và vnp_SecureHashType khỏi dữ liệu
    unset($inputData['vnp_SecureHash']);
    if (isset($inputData['vnp_SecureHashType'])) {
        unset($inputData['vnp_SecureHashType']);
    }

    // Sắp xếp dữ liệu theo alphabet
    ksort($inputData);
    $hashdata = "";
    $i = 0;

    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
    }

    $secureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);

    return $secureHash === $vnp_SecureHash;
}

/**
 * Lấy mô tả lỗi từ response code
 * @param string $responseCode Mã response từ VNPay
 * @return string Mô tả lỗi
 */
function getVNPayResponseMessage($responseCode)
{
    $messages = [
        '00' => 'Giao dịch thành công',
        '07' => 'Trừ tiền thành công. Giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường).',
        '09' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking tại ngân hàng.',
        '10' => 'Giao dịch không thành công do: Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
        '11' => 'Giao dịch không thành công do: Đã hết hạn chờ thanh toán. Xin quý khách vui lòng thực hiện lại giao dịch.',
        '12' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng bị khóa.',
        '13' => 'Giao dịch không thành công do Quý khách nhập sai mật khẩu xác thực giao dịch (OTP). Xin quý khách vui lòng thực hiện lại giao dịch.',
        '24' => 'Giao dịch không thành công do: Khách hàng hủy giao dịch',
        '51' => 'Giao dịch không thành công do: Tài khoản của quý khách không đủ số dư để thực hiện giao dịch.',
        '65' => 'Giao dịch không thành công do: Tài khoản của Quý khách đã vượt quá hạn mức giao dịch trong ngày.',
        '75' => 'Ngân hàng thanh toán đang bảo trì.',
        '79' => 'Giao dịch không thành công do: KH nhập sai mật khẩu thanh toán quá số lần quy định. Xin quý khách vui lòng thực hiện lại giao dịch',
        '99' => 'Các lỗi khác (lỗi còn lại, không có trong danh sách mã lỗi đã liệt kê)'
    ];

    return $messages[$responseCode] ?? 'Lỗi không xác định: ' . $responseCode;
}