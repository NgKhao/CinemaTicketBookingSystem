<?php
session_start();
require_once 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin user và tier
$stmt = $pdo->prepare("
    SELECT u.*, 
           mt.name as tier_name, 
           mt.discount_percent as tier_discount,
           mt.min_points as tier_min_points
    FROM users u
    LEFT JOIN member_tiers mt ON u.member_tier_id = mt.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<script>alert('Không tìm thấy thông tin người dùng!'); window.location.href='login.html';</script>";
    exit;
}

// Lấy tier tiếp theo
$stmt = $pdo->prepare("
    SELECT * FROM member_tiers 
    WHERE min_points > ? 
    ORDER BY min_points ASC 
    LIMIT 1
");
$stmt->execute([$user['member_points']]);
$next_tier = $stmt->fetch(PDO::FETCH_ASSOC);

// Tính progress đến tier tiếp theo
$current_tier_min = $user['tier_min_points'] ?? 0;
$next_tier_min = $next_tier ? $next_tier['min_points'] : ($current_tier_min + 1000);
$progress_percent = min(100, (($user['member_points'] - $current_tier_min) / ($next_tier_min - $current_tier_min)) * 100);

// Lấy lịch sử tích điểm (10 giao dịch gần nhất)
$stmt = $pdo->prepare("
    SELECT ph.*, b.seats, b.total_price, m.title as movie_title
    FROM point_history ph
    LEFT JOIN bookings b ON ph.booking_id = b.id
    LEFT JOIN showtimes s ON b.showtime_id = s.id
    LEFT JOIN movies m ON s.movie_id = m.id
    WHERE ph.user_id = ?
    ORDER BY ph.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$point_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy lịch sử booking (5 đơn gần nhất)
$stmt = $pdo->prepare("
    SELECT b.*, 
           m.title as movie_title,
           m.image as poster_url,
           c.name as cinema_name,
           s.show_date,
           s.show_time,
           v.code as voucher_code,
           v.type as voucher_type,
           v.value as voucher_value
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN cinemas c ON s.cinema_id = c.id
    LEFT JOIN vouchers v ON b.voucher_id = v.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy voucher khả dụng
$stmt = $pdo->prepare("
    SELECT v.* 
    FROM vouchers v
    WHERE v.is_active = 1
    AND v.start_date <= CURDATE()
    AND v.end_date >= CURDATE()
    AND (v.usage_limit IS NULL OR v.used_count < v.usage_limit)
    ORDER BY v.value DESC
    LIMIT 6
");
$stmt->execute();
$available_vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hàm lấy màu tier
function getTierColor($tier_name)
{
    $colors = [
        'Đồng' => '#cd7f32',
        'Bạc' => '#c0c0c0',
        'Vàng' => '#ffd700',
        'Kim cương' => '#b9f2ff'
    ];
    return $colors[$tier_name] ?? '#999';
}

// Hàm lấy badge tier
function getTierBadge($tier_name)
{
    $badges = [
        'Đồng' => 'bronze',
        'Bạc' => 'silver',
        'Vàng' => 'gold',
        'Kim cương' => 'diamond'
    ];
    return $badges[$tier_name] ?? 'bronze';
}

// Thiết lập biến cho header component
$isLoggedIn = true;
$username = $user['username'];
$userRole = $user['role'] ?? 'member';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin thành viên - CGV</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="ASST1.css">
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

        .page-content {
            padding: 20px 0;
            max-width: 1400px;
            margin: 0 auto;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin: 20px 0 30px 0;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .card:hover {
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .tier-card {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .tier-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 60%);
            animation: rotate 10s linear infinite;
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .tier-badge {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .tier-badge.bronze {
            color: #cd7f32;
        }

        .tier-badge.silver {
            color: #c0c0c0;
        }

        .tier-badge.gold {
            color: #ffd700;
        }

        .tier-badge.diamond {
            color: #b9f2ff;
        }

        .tier-name {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .tier-discount {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .points-display {
            font-size: 48px;
            font-weight: bold;
            margin: 20px 0;
        }

        .progress-container {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            height: 30px;
            margin: 20px 0;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            height: 100%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .next-tier-info {
            margin-top: 15px;
            font-size: 14px;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .stat-item::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }

        .stat-item:hover::before {
            width: 200%;
            height: 200%;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .section-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #e50914;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .history-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        .history-table tbody tr:hover {
            background: #f8f9fa;
        }

        .points-earn {
            color: #28a745;
            font-weight: bold;
        }

        .points-spend {
            color: #dc3545;
            font-weight: bold;
        }

        .points-bonus {
            color: #ffc107;
            font-weight: bold;
        }

        .points-adjust {
            color: #17a2b8;
            font-weight: bold;
        }

        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .voucher-card {
            background: linear-gradient(135deg, #ff9800 0%, #ff5722 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }

        .voucher-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        .voucher-code {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .voucher-desc {
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .voucher-value {
            font-size: 32px;
            font-weight: bold;
            margin: 15px 0;
        }

        .voucher-expiry {
            font-size: 12px;
            opacity: 0.8;
        }

        .booking-card {
            display: flex;
            gap: 20px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .booking-card:hover {
            transform: translateY(-5px);
        }

        .booking-poster {
            width: 100px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        .booking-details {
            flex: 1;
        }

        .booking-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .booking-info {
            color: #666;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .booking-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .voucher-grid {
                grid-template-columns: 1fr;
            }

            .booking-card {
                flex-direction: column;
            }

            .booking-poster {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>

<body>
    <?php
    // Include header component chung
    include 'header_user.php';
    ?>

    <!-- Page Content Wrapper -->
    <div class="page-content">
        <!-- Profile Grid -->
        <div class="profile-grid">
            <!-- Tier Card -->
            <div class="card tier-card">
                <div class="tier-badge <?php echo getTierBadge($user['tier_name'] ?? 'Đồng'); ?>">
                    <i class="fas fa-award"></i>
                </div>
                <div class="tier-name">
                    <?php echo htmlspecialchars($user['tier_name'] ?? 'Đồng'); ?>
                </div>
                <div class="tier-discount">
                    Giảm giá: <?php echo $user['tier_discount'] ?? 0; ?>%
                </div>

                <div class="points-display">
                    <?php echo number_format($user['member_points']); ?>
                </div>
                <div style="font-size: 14px; opacity: 0.9;">điểm tích lũy</div>

                <?php if ($next_tier): ?>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo $progress_percent; ?>%">
                            <?php echo round($progress_percent); ?>%
                        </div>
                    </div>
                    <div class="next-tier-info">
                        <i class="fas fa-arrow-up"></i> Còn
                        <?php echo number_format($next_tier['min_points'] - $user['member_points']); ?> điểm để lên hạng
                        <strong><?php echo $next_tier['name']; ?></strong>
                    </div>
                <?php else: ?>
                    <div class="next-tier-info">
                        <i class="fas fa-crown"></i> Bạn đang ở hạng cao nhất!
                    </div>
                <?php endif; ?>
            </div>

            <!-- Stats Card -->
            <div class="card">
                <h3 class="section-title"><i class="fas fa-chart-line"></i> Thống kê hoạt động</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'paid'");
                            $stmt->execute([$user_id]);
                            echo $stmt->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Vé đã mua</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php
                            $stmt = $pdo->prepare("SELECT COALESCE(SUM(final_price), 0) FROM bookings WHERE user_id = ? AND status = 'paid'");
                            $stmt->execute([$user_id]);
                            echo number_format($stmt->fetchColumn() / 1000) . 'K';
                            ?>
                        </div>
                        <div class="stat-label">Tổng chi tiêu</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM voucher_usage WHERE user_id = ?");
                            $stmt->execute([$user_id]);
                            echo $stmt->fetchColumn();
                            ?>
                        </div>
                        <div class="stat-label">Voucher đã dùng</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Point History -->
        <div class="card">
            <h3 class="section-title"><i class="fas fa-history"></i> Lịch sử tích điểm</h3>
            <?php if (count($point_history) > 0): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Loại</th>
                            <th>Mô tả</th>
                            <th>Điểm</th>
                            <th>Phim</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($point_history as $history): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($history['created_at'])); ?></td>
                                <td>
                                    <?php
                                    $type_labels = [
                                        'earn' => '<i class="fas fa-plus-circle"></i> Tích điểm',
                                        'spend' => '<i class="fas fa-minus-circle"></i> Tiêu điểm',
                                        'bonus' => '<i class="fas fa-gift"></i> Thưởng',
                                        'adjust' => '<i class="fas fa-tools"></i> Điều chỉnh'
                                    ];
                                    echo $type_labels[$history['type']] ?? $history['type'];
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($history['description']); ?></td>
                                <td class="points-<?php echo $history['type']; ?>">
                                    <?php echo ($history['points'] > 0 ? '+' : '') . number_format($history['points']); ?>
                                </td>
                                <td>
                                    <?php
                                    if ($history['movie_title']) {
                                        echo htmlspecialchars($history['movie_title']);
                                    } else {
                                        echo '<span style="color: #999;">-</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Chưa có lịch sử tích điểm</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Bookings -->
        <div class="card" style="margin-top: 30px;">
            <h3 class="section-title"><i class="fas fa-ticket-alt"></i> Đơn hàng gần đây</h3>
            <?php if (count($bookings) > 0): ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>"
                            alt="<?php echo htmlspecialchars($booking['movie_title']); ?>" class="booking-poster"
                            onerror="this.src='https://via.placeholder.com/100x150?text=No+Image'">
                        <div class="booking-details">
                            <div class="booking-title"><?php echo htmlspecialchars($booking['movie_title']); ?></div>
                            <div class="booking-info">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($booking['cinema_name']); ?>
                            </div>
                            <div class="booking-info">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($booking['show_date'])); ?> -
                                <?php echo date('H:i', strtotime($booking['show_time'])); ?>
                            </div>
                            <div class="booking-info">
                                <i class="fas fa-chair"></i>
                                Ghế: <?php echo htmlspecialchars($booking['seats']); ?>
                            </div>
                            <div class="booking-info">
                                <i class="fas fa-money-bill-wave"></i>
                                <?php if ($booking['discount_amount'] > 0): ?>
                                    <span style="text-decoration: line-through; color: #999;">
                                        <?php echo number_format($booking['total_price']); ?>đ
                                    </span>
                                    <span style="color: #e50914; font-weight: bold; margin-left: 10px;">
                                        <?php echo number_format($booking['final_price']); ?>đ
                                    </span>
                                    <?php if ($booking['voucher_code']): ?>
                                        <span
                                            style="background: #ff9800; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 5px;">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($booking['voucher_code']); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php echo number_format($booking['total_price']); ?>đ
                                <?php endif; ?>
                            </div>
                            <?php if ($booking['points_earned'] > 0): ?>
                                <div class="booking-info" style="color: #28a745;">
                                    <i class="fas fa-star"></i> +<?php echo number_format($booking['points_earned']); ?> điểm
                                </div>
                            <?php endif; ?>
                            <span
                                class="booking-status status-<?php echo $booking['status']; ?>"><?php echo strtoupper($booking['status']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-ticket-alt"></i>
                    <p>Chưa có đơn hàng nào</p>
                    <a href="datve.php" class="btn" style="display: inline-block; margin-top: 20px;">Đặt vé ngay</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Available Vouchers -->
        <div class="card" style="margin-top: 30px;">
            <h3 class="section-title"><i class="fas fa-tags"></i> Voucher khả dụng</h3>
            <?php if (count($available_vouchers) > 0): ?>
                <div class="voucher-grid">
                    <?php foreach ($available_vouchers as $voucher): ?>
                        <div class="voucher-card">
                            <div class="voucher-code"><?php echo htmlspecialchars($voucher['code']); ?></div>
                            <div class="voucher-desc"><?php echo htmlspecialchars($voucher['description']); ?></div>
                            <div class="voucher-value">
                                <?php
                                if ($voucher['type'] == 'percent') {
                                    echo $voucher['value'] . '%';
                                } else {
                                    echo number_format($voucher['value']) . 'đ';
                                }
                                ?>
                            </div>
                            <?php if ($voucher['min_order'] > 0): ?>
                                <div class="voucher-desc">
                                    Đơn tối thiểu: <?php echo number_format($voucher['min_order']); ?>đ
                                </div>
                            <?php endif; ?>
                            <div class="voucher-expiry">
                                <i class="fas fa-clock"></i>
                                HSD: <?php echo date('d/m/Y', strtotime($voucher['end_date'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-tag"></i>
                    <p>Hiện tại không có voucher khả dụng</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- End Page Content -->
    </div>
    <!-- End Container -->
</body>

</html>