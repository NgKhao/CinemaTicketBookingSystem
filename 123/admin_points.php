<?php
session_start();
require_once 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.html");
    exit;
}

// Điều chỉnh điểm thủ công
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'adjust_points') {
    $user_id = $_POST['user_id'];
    $points = $_POST['points'];
    $description = $_POST['description'];
    $type = $points > 0 ? 'bonus' : 'adjust';

    try {
        $pdo->beginTransaction();

        // Thêm vào lịch sử
        $stmt = $pdo->prepare("INSERT INTO point_history (user_id, points, type, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $points, $type, $description]);

        // Cập nhật điểm user
        $stmt = $pdo->prepare("UPDATE users SET member_points = member_points + ? WHERE id = ?");
        $stmt->execute([$points, $user_id]);

        // Cập nhật hạng thành viên
        $stmt = $pdo->prepare("
            UPDATE users u
            SET member_tier_id = (
                SELECT id FROM member_tiers 
                WHERE min_points <= u.member_points 
                ORDER BY min_points DESC 
                LIMIT 1
            )
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);

        $pdo->commit();
        echo "<script>alert('Điều chỉnh điểm thành công!');</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Lỗi: " . $e->getMessage() . "');</script>";
    }
}

// Lấy thông tin user nếu có
$selected_user = null;
if (isset($_GET['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT u.*, mt.name as tier_name, mt.color as tier_color, mt.discount_percent
        FROM users u
        LEFT JOIN member_tiers mt ON u.member_tier_id = mt.id
        WHERE u.id = ? AND u.role = 'customer'
    ");
    $stmt->execute([$_GET['user_id']]);
    $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($selected_user) {
        // Lấy lịch sử điểm
        $stmt = $pdo->prepare("
            SELECT ph.*, b.id as booking_id, b.total_price, b.booking_date
            FROM point_history ph
            LEFT JOIN bookings b ON ph.booking_id = b.id
            WHERE ph.user_id = ?
            ORDER BY ph.created_at DESC
        ");
        $stmt->execute([$_GET['user_id']]);
        $point_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Thống kê
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status IN ('paid', 'confirmed') THEN 1 ELSE 0 END) as paid_bookings,
                SUM(CASE WHEN status IN ('paid', 'confirmed') THEN final_price ELSE 0 END) as total_spent
            FROM bookings
            WHERE user_id = ?
        ");
        $stmt->execute([$_GET['user_id']]);
        $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Danh sách tất cả user có điểm
$stmt = $pdo->query("
    SELECT u.*, mt.name as tier_name, mt.color as tier_color,
           COUNT(b.id) as total_bookings,
           SUM(b.points_earned) as total_earned
    FROM users u
    LEFT JOIN member_tiers mt ON u.member_tier_id = mt.id
    LEFT JOIN bookings b ON u.id = b.user_id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.member_points DESC
    LIMIT 50
");
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê tổng quan
$stmt = $pdo->query("
    SELECT 
        SUM(points) as total_points_given,
        COUNT(DISTINCT user_id) as users_with_points
    FROM point_history
    WHERE type IN ('earn', 'bonus')
");
$overview = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Quản lý Điểm Thành Viên - Admin CGV</title>
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
            background: linear-gradient(45deg, #00d2d3, #01a3a4);
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
        }

        .tier-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
        }

        .point-positive {
            color: #28a745;
            font-weight: bold;
        }

        .point-negative {
            color: #dc3545;
            font-weight: bold;
        }

        .table th {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-card">
            <h1><i class="bi bi-star-fill"></i> Quản lý Điểm Thành Viên</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin.php">Trang chủ Admin</a></li>
                    <li class="breadcrumb-item active">Quản lý Điểm</li>
                </ol>
            </nav>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stats-card">
                    <h3><?php echo number_format($overview['total_points_given'] ?? 0); ?></h3>
                    <p><i class="bi bi-star"></i> Tổng điểm đã phát</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <h3><?php echo $overview['users_with_points'] ?? 0; ?></h3>
                    <p><i class="bi bi-people"></i> Người dùng có điểm</p>
                </div>
            </div>
        </div>

        <!-- Chi tiết user được chọn -->
        <?php if ($selected_user): ?>
            <div class="admin-card">
                <h3>Thông tin: <?php echo $selected_user['username']; ?></h3>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table">
                            <tr>
                                <th>Email:</th>
                                <td><?php echo $selected_user['email']; ?></td>
                            </tr>
                            <tr>
                                <th>Hạng hiện tại:</th>
                                <td>
                                    <span class="tier-badge"
                                        style="background-color: <?php echo $selected_user['tier_color']; ?>">
                                        <?php echo $selected_user['tier_name']; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Điểm hiện tại:</th>
                                <td><strong
                                        style="font-size: 1.5em; color: #e50914;"><?php echo number_format($selected_user['member_points']); ?></strong>
                                </td>
                            </tr>
                            <tr>
                                <th>% Giảm giá:</th>
                                <td><strong><?php echo $selected_user['discount_percent']; ?>%</strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table">
                            <tr>
                                <th>Tổng đặt vé:</th>
                                <td><?php echo $user_stats['total_bookings']; ?> lần</td>
                            </tr>
                            <tr>
                                <th>Đã thanh toán:</th>
                                <td><?php echo $user_stats['paid_bookings']; ?> vé</td>
                            </tr>
                            <tr>
                                <th>Tổng chi tiêu:</th>
                                <td><strong><?php echo number_format($user_stats['total_spent'] ?? 0); ?>đ</strong></td>
                            </tr>
                            <tr>
                                <th>Ngày đăng ký:</th>
                                <td><?php echo date('d/m/Y', strtotime($selected_user['created_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Form điều chỉnh điểm -->
                <div class="mt-3">
                    <h5>Điều chỉnh điểm thủ công</h5>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="adjust_points">
                        <input type="hidden" name="user_id" value="<?php echo $selected_user['id']; ?>">

                        <div class="col-md-4">
                            <label class="form-label">Số điểm (+ hoặc -)</label>
                            <input type="number" name="points" class="form-control" required placeholder="VD: 100 hoặc -50">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lý do</label>
                            <input type="text" name="description" class="form-control" required
                                placeholder="VD: Thưởng khách hàng thân thiết">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Xác nhận
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lịch sử điểm -->
                <div class="mt-4">
                    <h5>Lịch sử điểm</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Loại</th>
                                    <th>Điểm</th>
                                    <th>Mô tả</th>
                                    <th>Booking ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($point_history as $history): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?></td>
                                        <td>
                                            <?php
                                            $badge_map = [
                                                'earn' => 'bg-success',
                                                'bonus' => 'bg-primary',
                                                'spend' => 'bg-warning',
                                                'adjust' => 'bg-secondary'
                                            ];
                                            $badge = $badge_map[$history['type']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badge; ?>">
                                                <?php
                                                $type_map = ['earn' => 'Tích điểm', 'bonus' => 'Thưởng', 'spend' => 'Tiêu', 'adjust' => 'Điều chỉnh'];
                                                echo $type_map[$history['type']] ?? $history['type'];
                                                ?>
                                            </span>
                                        </td>
                                        <td class="<?php echo $history['points'] > 0 ? 'point-positive' : 'point-negative'; ?>">
                                            <?php echo $history['points'] > 0 ? '+' : ''; ?><?php echo number_format($history['points']); ?>
                                        </td>
                                        <td><?php echo $history['description'] ?? '-'; ?></td>
                                        <td>
                                            <?php if ($history['booking_id']): ?>
                                                <a href="booking_invoice.php?id=<?php echo $history['booking_id']; ?>"
                                                    target="_blank" class="badge bg-info">
                                                    #<?php echo $history['booking_id']; ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="admin_points.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Quay lại danh sách
                    </a>
                </div>
            </div>
        <?php else: ?>

            <!-- Danh sách tất cả user -->
            <div class="admin-card">
                <h3>Danh sách Thành Viên (Top 50)</h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Hạng</th>
                                <th>Điểm hiện tại</th>
                                <th>Số vé đã mua</th>
                                <th>Điểm đã tích</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1;
                            foreach ($all_users as $user): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo $user['username']; ?></strong></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td>
                                        <span class="tier-badge" style="background-color: <?php echo $user['tier_color']; ?>">
                                            <?php echo $user['tier_name']; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo number_format($user['member_points']); ?></strong></td>
                                    <td><?php echo $user['total_bookings']; ?></td>
                                    <td><?php echo number_format($user['total_earned'] ?? 0); ?></td>
                                    <td>
                                        <a href="admin_points.php?user_id=<?php echo $user['id']; ?>"
                                            class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> Chi tiết
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="admin-card text-center">
            <a href="admin.php" class="btn btn-secondary me-2">
                <i class="bi bi-house"></i> Về trang chủ Admin
            </a>
            <a href="admin_vouchers.php" class="btn btn-danger me-2">
                <i class="bi bi-tag"></i> Quản lý Voucher
            </a>
            <a href="admin_members.php" class="btn btn-primary me-2">
                <i class="bi bi-trophy"></i> Quản lý Hạng
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="bi bi-power"></i> Đăng xuất
            </a>
        </div>
    </div>
</body>

</html>