<?php
session_start();
require_once 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.html");
    exit;
}

// Thêm voucher mới
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_voucher') {
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['type'];
    $value = $_POST['value'];
    $min_order = $_POST['min_order'] ?? 0;
    $max_discount = $_POST['max_discount'] ?? null;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $usage_limit = $_POST['usage_limit'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO vouchers (code, type, value, min_order, max_discount, start_date, end_date, usage_limit, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$code, $type, $value, $min_order, $max_discount, $start_date, $end_date, $usage_limit, $description])) {
        echo "<script>alert('Thêm voucher thành công!');</script>";
    } else {
        echo "<script>alert('Lỗi: Mã voucher đã tồn tại!');</script>";
    }
}

// Cập nhật voucher
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_voucher') {
    $id = $_POST['voucher_id'];
    $type = $_POST['type'];
    $value = $_POST['value'];
    $min_order = $_POST['min_order'] ?? 0;
    $max_discount = $_POST['max_discount'] ?? null;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $usage_limit = $_POST['usage_limit'];
    $is_active = $_POST['is_active'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("UPDATE vouchers SET type=?, value=?, min_order=?, max_discount=?, start_date=?, end_date=?, usage_limit=?, is_active=?, description=? WHERE id=?");

    if ($stmt->execute([$type, $value, $min_order, $max_discount, $start_date, $end_date, $usage_limit, $is_active, $description, $id])) {
        echo "<script>alert('Cập nhật voucher thành công!');</script>";
    }
}

// Xóa voucher
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // Kiểm tra voucher đã được sử dụng chưa
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM voucher_usage WHERE voucher_id = ?");
    $stmt->execute([$id]);
    $usage_count = $stmt->fetchColumn();

    if ($usage_count > 0) {
        echo "<script>alert('Không thể xóa voucher đã được sử dụng! Hãy tắt trạng thái hoạt động thay vì xóa.');</script>";
    } else {
        $stmt = $pdo->prepare("DELETE FROM vouchers WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo "<script>alert('Xóa voucher thành công!');</script>";
        }
    }
}

// Lấy danh sách voucher
$stmt = $pdo->prepare("
    SELECT v.*, 
           COUNT(vu.id) as times_used,
           CASE 
               WHEN v.end_date < CURDATE() THEN 'Hết hạn'
               WHEN v.used_count >= v.usage_limit THEN 'Hết lượt'
               WHEN v.is_active = 0 THEN 'Tắt'
               ELSE 'Hoạt động'
           END as status_text
    FROM vouchers v
    LEFT JOIN voucher_usage vu ON v.id = vu.voucher_id
    GROUP BY v.id
    ORDER BY v.created_at DESC
");
$stmt->execute();
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin voucher để edit
$edit_voucher = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_voucher = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Thống kê
$stmt = $pdo->query("SELECT COUNT(*) FROM vouchers WHERE is_active = 1 AND end_date >= CURDATE()");
$active_vouchers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM voucher_usage");
$total_usage = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(discount_amount) FROM voucher_usage");
$total_discount = $stmt->fetchColumn() ?? 0;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Quản lý Voucher - Admin CGV</title>
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
            background: linear-gradient(45deg, #e50914, #b8070f);
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
        }

        .table th {
            background-color: #f8f9fa;
        }

        .badge-active {
            background-color: #28a745;
        }

        .badge-expired {
            background-color: #dc3545;
        }

        .badge-disabled {
            background-color: #6c757d;
        }

        .voucher-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #e50914;
            font-size: 1.1em;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-card">
            <h1><i class="bi bi-tag-fill"></i> Quản lý Voucher & Khuyến Mãi</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin.php">Trang chủ Admin</a></li>
                    <li class="breadcrumb-item active">Quản lý Voucher</li>
                </ol>
            </nav>
        </div>

        <!-- Thống kê -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?php echo $active_vouchers; ?></h3>
                    <p><i class="bi bi-check-circle"></i> Voucher đang hoạt động</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?php echo $total_usage; ?></h3>
                    <p><i class="bi bi-graph-up"></i> Lượt sử dụng</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?php echo number_format($total_discount); ?>đ</h3>
                    <p><i class="bi bi-cash-coin"></i> Tổng giảm giá</p>
                </div>
            </div>
        </div>

        <!-- Form thêm/sửa voucher -->
        <div class="admin-card">
            <h3><?php echo $edit_voucher ? 'Sửa Voucher' : 'Thêm Voucher Mới'; ?></h3>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action"
                    value="<?php echo $edit_voucher ? 'update_voucher' : 'add_voucher'; ?>">
                <?php if ($edit_voucher): ?>
                    <input type="hidden" name="voucher_id" value="<?php echo $edit_voucher['id']; ?>">
                <?php endif; ?>

                <div class="col-md-4">
                    <label class="form-label">Mã Voucher <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control text-uppercase"
                        value="<?php echo $edit_voucher['code'] ?? ''; ?>"
                        <?php echo $edit_voucher ? 'readonly' : 'required'; ?> placeholder="VD: WELCOME2025">
                    <small class="text-muted">Chữ in hoa, không dấu</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Loại giảm giá <span class="text-danger">*</span></label>
                    <select name="type" class="form-control" required>
                        <option value="percent"
                            <?php echo ($edit_voucher['type'] ?? '') == 'percent' ? 'selected' : ''; ?>>% Phần trăm
                        </option>
                        <option value="fixed" <?php echo ($edit_voucher['type'] ?? '') == 'fixed' ? 'selected' : ''; ?>>
                            Số tiền cố định</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Giá trị <span class="text-danger">*</span></label>
                    <input type="number" name="value" class="form-control"
                        value="<?php echo $edit_voucher['value'] ?? ''; ?>" step="0.01" min="0" required
                        placeholder="VD: 10 (%) hoặc 100000 (VND)">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Đơn hàng tối thiểu (VND)</label>
                    <input type="number" name="min_order" class="form-control"
                        value="<?php echo $edit_voucher['min_order'] ?? 0; ?>" min="0" placeholder="0 = không giới hạn">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Giảm tối đa (VND - chỉ cho %)</label>
                    <input type="number" name="max_discount" class="form-control"
                        value="<?php echo $edit_voucher['max_discount'] ?? ''; ?>" min="0"
                        placeholder="Để trống nếu không giới hạn">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Số lần sử dụng tối đa <span class="text-danger">*</span></label>
                    <input type="number" name="usage_limit" class="form-control"
                        value="<?php echo $edit_voucher['usage_limit'] ?? 100; ?>" min="1" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control"
                        value="<?php echo $edit_voucher['start_date'] ?? date('Y-m-d'); ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" class="form-control"
                        value="<?php echo $edit_voucher['end_date'] ?? ''; ?>" required>
                </div>

                <?php if ($edit_voucher): ?>
                    <div class="col-md-4">
                        <label class="form-label">Trạng thái</label>
                        <select name="is_active" class="form-control">
                            <option value="1" <?php echo $edit_voucher['is_active'] == 1 ? 'selected' : ''; ?>>Hoạt động
                            </option>
                            <option value="0" <?php echo $edit_voucher['is_active'] == 0 ? 'selected' : ''; ?>>Tắt</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="2"
                        placeholder="VD: Giảm 10% cho thành viên mới"><?php echo $edit_voucher['description'] ?? ''; ?></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> <?php echo $edit_voucher ? 'Cập nhật' : 'Thêm voucher'; ?>
                    </button>
                    <?php if ($edit_voucher): ?>
                        <a href="admin_vouchers.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Hủy
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Danh sách voucher -->
        <div class="admin-card">
            <h3>Danh sách Voucher</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Mã Voucher</th>
                            <th>Loại</th>
                            <th>Giá trị</th>
                            <th>Điều kiện</th>
                            <th>Thời hạn</th>
                            <th>Sử dụng</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vouchers as $voucher): ?>
                            <tr>
                                <td class="voucher-code"><?php echo $voucher['code']; ?></td>
                                <td>
                                    <?php if ($voucher['type'] == 'percent'): ?>
                                        <span class="badge bg-info">% Phần trăm</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Cố định</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong>
                                        <?php
                                        if ($voucher['type'] == 'percent') {
                                            echo $voucher['value'] . '%';
                                        } else {
                                            echo number_format($voucher['value']) . 'đ';
                                        }
                                        ?>
                                    </strong>
                                </td>
                                <td>
                                    <small>
                                        Min: <?php echo number_format($voucher['min_order']); ?>đ<br>
                                        <?php if ($voucher['max_discount']): ?>
                                            Max: <?php echo number_format($voucher['max_discount']); ?>đ
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        <?php echo date('d/m/Y', strtotime($voucher['start_date'])); ?><br>
                                        → <?php echo date('d/m/Y', strtotime($voucher['end_date'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo $voucher['used_count']; ?>/<?php echo $voucher['usage_limit']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-disabled';
                                    if ($voucher['status_text'] == 'Hoạt động') $badge_class = 'badge-active';
                                    if ($voucher['status_text'] == 'Hết hạn' || $voucher['status_text'] == 'Hết lượt') $badge_class = 'badge-expired';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $voucher['status_text']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="admin_vouchers.php?edit_id=<?php echo $voucher['id']; ?>"
                                        class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="admin_vouchers.php?delete_id=<?php echo $voucher['id']; ?>"
                                        class="btn btn-sm btn-danger" onclick="return confirm('Xác nhận xóa voucher này?')">
                                        <i class="bi bi-trash"></i>
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
            <a href="admin_members.php" class="btn btn-info me-2">
                <i class="bi bi-people"></i> Quản lý thành viên
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="bi bi-power"></i> Đăng xuất
            </a>
        </div>
    </div>
</body>

</html>