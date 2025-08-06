<?php
session_start();
require_once 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

// Thêm rạp mới
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_cinema') {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $image = './img/cgv_cinema.jpg'; // Default image

    // Xử lý upload ảnh
    if (isset($_FILES['cinema_image']) && $_FILES['cinema_image']['error'] == 0) {
        $uploadDir = './img/';
        $fileName = time() . '_' . $_FILES['cinema_image']['name'];
        $uploadPath = $uploadDir . $fileName;

        // Kiểm tra định dạng file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (in_array($_FILES['cinema_image']['type'], $allowedTypes)) {
            if (move_uploaded_file($_FILES['cinema_image']['tmp_name'], $uploadPath)) {
                $image = $uploadPath;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO cinemas (name, address, image) VALUES (?, ?, ?)");

    if ($stmt->execute([$name, $address, $image])) {
        echo "<script>alert('Thêm rạp thành công!');</script>";
    }
}

// Cập nhật thông tin rạp
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_cinema') {
    $id = $_POST['cinema_id'];
    $name = $_POST['name'];
    $address = $_POST['address'];

    // Lấy ảnh hiện tại
    $stmt = $pdo->prepare("SELECT image FROM cinemas WHERE id = ?");
    $stmt->execute([$id]);
    $current_cinema = $stmt->fetch(PDO::FETCH_ASSOC);
    $image = $current_cinema['image']; // Giữ ảnh cũ

    // Xử lý upload ảnh mới
    if (isset($_FILES['cinema_image']) && $_FILES['cinema_image']['error'] == 0) {
        $uploadDir = './img/';
        $fileName = time() . '_' . $_FILES['cinema_image']['name'];
        $uploadPath = $uploadDir . $fileName;

        // Kiểm tra định dạng file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (in_array($_FILES['cinema_image']['type'], $allowedTypes)) {
            if (move_uploaded_file($_FILES['cinema_image']['tmp_name'], $uploadPath)) {
                // Xóa ảnh cũ (trừ ảnh mặc định)
                if ($image && $image != './img/cgv_cinema.jpg' && file_exists($image)) {
                    unlink($image);
                }
                $image = $uploadPath;
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE cinemas SET name = ?, address = ?, image = ? WHERE id = ?");

    if ($stmt->execute([$name, $address, $image, $id])) {
        echo "<script>alert('Cập nhật rạp thành công!');</script>";
    }
}

// Xóa rạp
if ($_GET && isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // Kiểm tra xem rạp có suất chiếu nào không
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM showtimes WHERE cinema_id = ?");
    $stmt->execute([$id]);
    $showtime_count = $stmt->fetchColumn();

    if ($showtime_count > 0) {
        echo "<script>alert('Không thể xóa rạp này vì đang có suất chiếu!');</script>";
    } else {
        $stmt = $pdo->prepare("DELETE FROM cinemas WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo "<script>alert('Xóa rạp thành công!');</script>";
        }
    }
}

// Lấy danh sách rạp
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(s.id) as total_showtimes,
           COUNT(DISTINCT s.movie_id) as total_movies
    FROM cinemas c
    LEFT JOIN showtimes s ON c.id = s.cinema_id
    GROUP BY c.id
    ORDER BY c.id ASC
");
$stmt->execute();
$cinemas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin rạp để edit
$edit_cinema = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM cinemas WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_cinema = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Quản lý Rạp - Admin CGV</title>
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

        .table th {
            background-color: #f8f9fa;
        }

        .cinema-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-card">
            <h1><i class="bi bi-building"></i> Quản lý Rạp CGV</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin.php">Trang chủ Admin</a></li>
                    <li class="breadcrumb-item active">Quản lý Rạp</li>
                </ol>
            </nav>
        </div>

        <div class="admin-card">
            <h3><?php echo $edit_cinema ? 'Chỉnh sửa rạp' : 'Thêm rạp mới'; ?></h3>
            <form method="POST" enctype="multipart/form-data" class="mb-4">
                <input type="hidden" name="action" value="<?php echo $edit_cinema ? 'update_cinema' : 'add_cinema'; ?>">
                <?php if ($edit_cinema): ?>
                    <input type="hidden" name="cinema_id" value="<?php echo $edit_cinema['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Tên rạp:</label>
                        <input type="text" name="name" class="form-control"
                            value="<?php echo $edit_cinema ? $edit_cinema['name'] : ''; ?>"
                            placeholder="Tên rạp" required />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ảnh rạp:</label>
                        <input type="file" name="cinema_image" class="form-control" accept="image/*" />
                        <?php if ($edit_cinema && $edit_cinema['image']): ?>
                            <small class="text-muted">Ảnh hiện tại: <?php echo basename($edit_cinema['image']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <label class="form-label">Địa chỉ:</label>
                        <textarea name="address" class="form-control" placeholder="Địa chỉ rạp" required><?php echo $edit_cinema ? $edit_cinema['address'] : ''; ?></textarea>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-<?php echo $edit_cinema ? 'pencil' : 'plus-circle'; ?>"></i>
                            <?php echo $edit_cinema ? 'Cập nhật' : 'Thêm Rạp'; ?>
                        </button>
                        <?php if ($edit_cinema): ?>
                            <a href="admin_cinemas.php" class="btn btn-secondary ms-2">Hủy</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <h3>Danh sách rạp</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ảnh</th>
                            <th>Tên rạp</th>
                            <th>Địa chỉ</th>
                            <th>Số suất chiếu</th>
                            <th>Số phim</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cinemas as $cinema): ?>
                            <tr>
                                <td><?php echo $cinema['id']; ?></td>
                                <td>
                                    <img src="<?php echo $cinema['image']; ?>"
                                        alt="<?php echo $cinema['name']; ?>"
                                        class="cinema-image">
                                </td>
                                <td><strong><?php echo $cinema['name']; ?></strong></td>
                                <td><?php echo $cinema['address']; ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $cinema['total_showtimes']; ?> suất</span>
                                </td>
                                <td>
                                    <span class="badge bg-success"><?php echo $cinema['total_movies']; ?> phim</span>
                                </td>
                                <td>
                                    <a href="admin_cinemas.php?edit_id=<?php echo $cinema['id']; ?>"
                                        class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Sửa
                                    </a>
                                    <a href="admin_cinemas.php?delete_id=<?php echo $cinema['id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Bạn có chắc muốn xóa rạp này?')">
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
            <a href="admin_showtimes.php" class="btn btn-primary me-2">
                <i class="bi bi-calendar-event"></i> Quản lý suất chiếu
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="bi bi-power"></i> Đăng xuất
            </a>
        </div>
    </div>
</body>

</html>