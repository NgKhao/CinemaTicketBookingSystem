<?php
session_start();
require_once 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

// Thêm suất chiếu mới
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_showtime') {
    $movie_id = $_POST['movie_id'];
    $cinema_id = $_POST['cinema_id'];
    $show_date = $_POST['show_date'];
    $show_time = $_POST['show_time'];

    $stmt = $pdo->prepare("INSERT INTO showtimes (movie_id, cinema_id, show_date, show_time, total_seats, available_seats) VALUES (?, ?, ?, ?, 60, 60)");

    if ($stmt->execute([$movie_id, $cinema_id, $show_date, $show_time])) {
        echo "<script>alert('Thêm suất chiếu thành công!');</script>";
    }
}

// Lấy danh sách phim và rạp
$stmt = $pdo->prepare("SELECT id, title FROM movies ORDER BY title");
$stmt->execute();
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, name FROM cinemas ORDER BY name");
$stmt->execute();
$cinemas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách suất chiếu
$stmt = $pdo->prepare("
    SELECT s.*, m.title as movie_title, c.name as cinema_name 
    FROM showtimes s 
    JOIN movies m ON s.movie_id = m.id 
    JOIN cinemas c ON s.cinema_id = c.id 
    WHERE s.show_date >= CURDATE()
    ORDER BY s.show_date, s.show_time
");
$stmt->execute();
$showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Quản lý suất chiếu - Admin CGV</title>
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
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-card">
            <h1><i class="bi bi-calendar-event"></i> Quản lý Suất Chiếu CGV</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin.php">Trang chủ Admin</a></li>
                    <li class="breadcrumb-item active">Quản lý Suất Chiếu</li>
                </ol>
            </nav>
        </div>

        <div class="admin-card">
            <div class="row">
                <div class="col-md-6">
                    <h3>Thêm suất chiếu mới</h3>
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="action" value="add_showtime">
                        <div class="mb-3">
                            <label>Phim:</label>
                            <select name="movie_id" class="form-control" required>
                                <option value="">Chọn phim</option>
                                <?php foreach ($movies as $movie): ?>
                                    <option value="<?php echo $movie['id']; ?>"><?php echo $movie['title']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Rạp:</label>
                            <select name="cinema_id" class="form-control" required>
                                <option value="">Chọn rạp</option>
                                <?php foreach ($cinemas as $cinema): ?>
                                    <option value="<?php echo $cinema['id']; ?>"><?php echo $cinema['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Ngày chiếu:</label>
                            <input type="date" name="show_date" class="form-control" required />
                        </div>
                        <div class="mb-3">
                            <label>Giờ chiếu:</label>
                            <input type="time" name="show_time" class="form-control" required />
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Thêm suất chiếu
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <h3>Danh sách suất chiếu</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Phim</th>
                            <th>Rạp</th>
                            <th>Ngày</th>
                            <th>Giờ</th>
                            <th>Ghế còn lại</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($showtimes as $showtime): ?>
                            <tr>
                                <td><?php echo $showtime['movie_title']; ?></td>
                                <td><?php echo $showtime['cinema_name']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($showtime['show_date'])); ?></td>
                                <td><?php echo date('H:i', strtotime($showtime['show_time'])); ?></td>
                                <td><?php echo $showtime['available_seats']; ?>/<?php echo $showtime['total_seats']; ?></td>
                                <td>
                                    <?php if ($showtime['available_seats'] > 0): ?>
                                        <span class="badge bg-success">Còn vé</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Hết vé</span>
                                    <?php endif; ?>
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
            <a href="admin_movies.php" class="btn btn-primary me-2">
                <i class="bi bi-film"></i> Quản lý phim
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="bi bi-power"></i> Đăng xuất
            </a>
        </div>
    </div>
</body>

</html>