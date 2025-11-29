<?php
session_start();
require_once 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.html");
    exit;
}

// Cập nhật hạng thành viên
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_tier') {
    $id = $_POST['tier_id'];
    $name = $_POST['name'];
    $min_points = $_POST['min_points'];
    $discount_percent = $_POST['discount_percent'];
    $color = $_POST['color'];
    $benefits = $_POST['benefits'];

    $stmt = $pdo->prepare("UPDATE member_tiers SET name=?, min_points=?, discount_percent=?, color=?, benefits=? WHERE id=?");

    if ($stmt->execute([$name, $min_points, $discount_percent, $color, $benefits, $id])) {
        echo "<script>alert('Cập nhật hạng thành viên thành công!');</script>";
    }
}

// Lấy danh sách hạng thành viên
$stmt = $pdo->query("
    SELECT mt.*, COUNT(u.id) as member_count
    FROM member_tiers mt
    LEFT JOIN users u ON u.member_tier_id = mt.id AND u.role = 'customer'
    GROUP BY mt.id
    ORDER BY mt.min_points ASC
");
$tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê thành viên theo hạng
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_members,
        SUM(member_points) as total_points,
        AVG(member_points) as avg_points
    FROM users 
    WHERE role = 'customer'
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Top thành viên
$stmt = $pdo->query("
    SELECT u.*, mt.name as tier_name, mt.color as tier_color,
           COUNT(b.id) as total_bookings,
           SUM(b.final_price) as total_spent
    FROM users u
    LEFT JOIN member_tiers mt ON u.member_tier_id = mt.id
    LEFT JOIN bookings b ON u.id = b.user_id AND b.status IN ('paid', 'confirmed')
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.member_points DESC
    LIMIT 10
");
$top_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin hạng để edit
$edit_tier = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM member_tiers WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_tier = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Quản lý Thành viên - Admin CGV</title>
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
            background: linear-gradient(45deg, #4834d4, #686de0);
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

        .table th {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-card">
            <h1><i class="bi bi-trophy"></i> Quản lý Hạng Thành Viên</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin.php">Trang chủ Admin</a></li>
                    <li class="breadcrumb-item active">Quản lý Thành viên</li>
                </ol>
            </nav>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?php echo $stats['total_members']; ?></h3>
                    <p><i class="bi bi-people"></i> Tổng thành viên</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?php echo number_format($stats['total_points']); ?></h3>
                    <p><i class="bi bi-star"></i> Tổng điểm</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?php echo number_format($stats['avg_points'], 0); ?></h3>
                    <p><i class="bi bi-graph-up"></i> Điểm TB/người</p>
                </div>
            </div>
        </div>

        <!-- Danh sách hạng thành viên -->
        <div class="admin-card">
            <h3>Cấu hình Hạng Thành Viên</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Hạng</th>
                            <th>Điểm tối thiểu</th>
                            <th>% Giảm giá</th>
                            <th>Số thành viên</th>
                            <th>Quyền lợi</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tiers as $tier): ?>
                            <tr>
                                <td>
                                    <span class="tier-badge" style="background-color: <?php echo $tier['color']; ?>">
                                        <i class="bi bi-award"></i> <?php echo $tier['name']; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo number_format($tier['min_points']); ?></strong> điểm</td>
                                <td><strong><?php echo $tier['discount_percent']; ?>%</strong></td>
                                <td><span class="badge bg-info"><?php echo $tier['member_count']; ?> người</span></td>
                                <td><small><?php echo $tier['benefits']; ?></small></td>
                                <td>
                                    <a href="admin_members.php?edit_id=<?php echo $tier['id']; ?>"
                                        class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Sửa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Form sửa hạng thành viên -->
        <?php if ($edit_tier): ?>
            <div class="admin-card">
                <h3>Sửa Hạng: <?php echo $edit_tier['name']; ?></h3>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="update_tier">
                    <input type="hidden" name="tier_id" value="<?php echo $edit_tier['id']; ?>">

                    <div class="col-md-3">
                        <label class="form-label">Tên hạng</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $edit_tier['name']; ?>"
                            required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Điểm tối thiểu</label>
                        <input type="number" name="min_points" class="form-control"
                            value="<?php echo $edit_tier['min_points']; ?>" min="0" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">% Giảm giá</label>
                        <input type="number" name="discount_percent" class="form-control"
                            value="<?php echo $edit_tier['discount_percent']; ?>" min="0" max="100" step="0.01" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Màu hiển thị</label>
                        <input type="color" name="color" class="form-control" value="<?php echo $edit_tier['color']; ?>"
                            required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Quyền lợi</label>
                        <textarea name="benefits" class="form-control" rows="2"
                            required><?php echo $edit_tier['benefits']; ?></textarea>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Cập nhật
                        </button>
                        <a href="admin_members.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Top thành viên -->
        <div class="admin-card">
            <h3>Top 10 Thành Viên</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Xếp hạng</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Hạng</th>
                            <th>Điểm</th>
                            <th>Số vé đã mua</th>
                            <th>Tổng chi tiêu</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1;
                        foreach ($top_members as $member): ?>
                            <tr>
                                <td>
                                    <?php if ($rank <= 3): ?>
                                        <span class="badge bg-warning">
                                            <i class="bi bi-trophy-fill"></i> #<?php echo $rank; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">#<?php echo $rank; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $member['username']; ?></strong></td>
                                <td><?php echo $member['email']; ?></td>
                                <td>
                                    <span class="tier-badge" style="background-color: <?php echo $member['tier_color']; ?>">
                                        <?php echo $member['tier_name']; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo number_format($member['member_points']); ?></strong></td>
                                <td><?php echo $member['total_bookings']; ?> vé</td>
                                <td><?php echo number_format($member['total_spent'] ?? 0); ?>đ</td>
                                <td>
                                    <a href="admin_points.php?user_id=<?php echo $member['id']; ?>"
                                        class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i> Chi tiết
                                    </a>
                                </td>
                            </tr>
                        <?php $rank++;
                        endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card text-center">
            <a href="admin.php" class="btn btn-secondary me-2">
                <i class="bi bi-house"></i> Về trang chủ Admin
            </a>
            <a href="admin_vouchers.php" class="btn btn-danger me-2">
                <i class="bi bi-tag"></i> Quản lý Voucher
            </a>
            <a href="admin_points.php" class="btn btn-success me-2">
                <i class="bi bi-star"></i> Quản lý Điểm
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="bi bi-power"></i> Đăng xuất
            </a>
        </div>
    </div>
</body>

</html>