<?php
session_start();
require_once 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

// Thêm khách hàng mới
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'customer';

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");

    if ($stmt->execute([$username, $email, $password, $role])) {
        echo "<script>alert('Thêm khách hàng thành công!');</script>";
    } else {
        echo "<script>alert('Lỗi: Email hoặc username đã tồn tại!');</script>";
    }
}

// Cập nhật thông tin khách hàng
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_user') {
    $id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
        $result = $stmt->execute([$username, $email, $password, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $result = $stmt->execute([$username, $email, $id]);
    }

    if ($result) {
        echo "<script>alert('Cập nhật thông tin thành công!');</script>";
    } else {
        echo "<script>alert('Lỗi: Email hoặc username đã tồn tại!');</script>";
    }
}

// Xóa khách hàng
if ($_GET && isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // Kiểm tra xem khách hàng có đặt vé nào không
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
    $stmt->execute([$id]);
    $booking_count = $stmt->fetchColumn();

    if ($booking_count > 0) {
        echo "<script>alert('Không thể xóa khách hàng này vì đã có lịch sử đặt vé!');</script>";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
        if ($stmt->execute([$id])) {
            echo "<script>alert('Xóa khách hàng thành công!');</script>";
        }
    }
}

// Lấy danh sách người dùng
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(b.id) as total_bookings,
           SUM(b.total_price) as total_spent
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê người dùng
$stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users WHERE role = 'customer'");
$stmt->execute();
$total_users = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as active_users FROM users WHERE role = 'customer' AND id IN (SELECT DISTINCT user_id FROM bookings)");
$stmt->execute();
$active_users = $stmt->fetchColumn();

// Lấy thông tin khách hàng để edit
$edit_user = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$_GET['edit_id']]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Quản lý Khách Hàng - Admin CGV</title>
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
            margin-bottom: 20px;
        }

        .table th {
            background-color: #f8f9fa;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-card">
            <h1><i class="bi bi-people"></i> Quản lý Khách Hàng</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin.php">Trang chủ Admin</a></li>
                    <li class="breadcrumb-item active">Quản lý Khách Hàng</li>
                </ol>
            </nav>
        </div>

        <!-- Thống kê người dùng -->
        <div class="row">
            <div class="col-md-6">
                <div class="stats-card">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Tổng số khách hàng</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <h3><?php echo $active_users; ?></h3>
                    <p>Khách hàng đã đặt vé</p>
                </div>
            </div>
        </div>

        <!-- Form thêm/sửa khách hàng -->
        <div class="admin-card">
            <h3><?php echo $edit_user ? 'Chỉnh sửa khách hàng' : 'Thêm khách hàng mới'; ?></h3>
            <form method="POST" class="mb-4">
                <input type="hidden" name="action" value="<?php echo $edit_user ? 'update_user' : 'add_user'; ?>">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Username:</label>
                        <input type="text" name="username" class="form-control"
                            value="<?php echo $edit_user ? $edit_user['username'] : ''; ?>"
                            placeholder="Username" required />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email:</label>
                        <input type="email" name="email" class="form-control"
                            value="<?php echo $edit_user ? $edit_user['email'] : ''; ?>"
                            placeholder="Email" required />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mật khẩu <?php echo $edit_user ? '(để trống nếu không đổi)' : ''; ?>:</label>
                        <input type="password" name="password" class="form-control"
                            placeholder="Mật khẩu" <?php echo $edit_user ? '' : 'required'; ?> />
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-<?php echo $edit_user ? 'pencil' : 'plus-circle'; ?>"></i>
                            <?php echo $edit_user ? 'Cập nhật' : 'Thêm Khách Hàng'; ?>
                        </button>
                        <?php if ($edit_user): ?>
                            <a href="admin_users.php" class="btn btn-secondary ms-2">Hủy</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Danh sách khách hàng -->
        <div class="admin-card">
            <h3>Danh sách khách hàng</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Thông tin</th>
                            <th>Email</th>
                            <th>Ngày đăng ký</th>
                            <th>Thống kê đặt vé</th>
                            <th>Tổng chi tiêu</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                    </div>
                                </td>
                                <td>
                                    <strong>@<?php echo $user['username']; ?></strong><br>
                                    <small class="text-muted">ID: <?php echo $user['id']; ?></small>
                                </td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $user['total_bookings']; ?> lượt</span>
                                </td>
                                <td>
                                    <strong><?php echo number_format($user['total_spent'] ?: 0); ?> VNĐ</strong>
                                </td>
                                <td>
                                    <a href="admin_users.php?edit_id=<?php echo $user['id']; ?>"
                                        class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Sửa
                                    </a>
                                    <a href="admin_users.php?delete_id=<?php echo $user['id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Bạn có chắc muốn xóa khách hàng này?')">
                                        <i class="bi bi-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card text-center">
            <a href="admin.php" class="btn btn-secondary me-2">
                <i class="bi bi-house"></i> Về trang chủ Admin
            </a>
            <a href="admin_tickets.php" class="btn btn-info me-2">
                <i class="bi bi-ticket-perforated"></i> Quản lý vé
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="bi bi-power"></i> Đăng xuất
            </a>
        </div>
    </div>
</body>

</html>