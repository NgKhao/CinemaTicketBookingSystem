<?php
session_start();
require_once 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

// Thêm phim mới
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'add_movie') {
    $title = $_POST['title'];
    $genre = $_POST['genre'];
    $duration = $_POST['duration'];
    $release_date = $_POST['release_date'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $image = './img/default_movie.jpg'; // Default image

    // Xử lý upload ảnh
    if (isset($_FILES['movie_image']) && $_FILES['movie_image']['error'] == 0) {
        $uploadDir = './img/';
        $fileName = time() . '_' . $_FILES['movie_image']['name'];
        $uploadPath = $uploadDir . $fileName;

        // Kiểm tra định dạng file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (in_array($_FILES['movie_image']['type'], $allowedTypes)) {
            if (move_uploaded_file($_FILES['movie_image']['tmp_name'], $uploadPath)) {
                $image = $uploadPath;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO movies (title, genre, duration, release_date, image, description, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$title, $genre, $duration, $release_date, $image, $description, $category_id])) {
        echo "<script>alert('Thêm phim thành công!');</script>";
    }
}

// Cập nhật phim
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_movie') {
    $id = $_POST['movie_id'];
    $title = $_POST['title'];
    $genre = $_POST['genre'];
    $duration = $_POST['duration'];
    $release_date = $_POST['release_date'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];

    // Lấy ảnh hiện tại
    $stmt = $pdo->prepare("SELECT image FROM movies WHERE id = ?");
    $stmt->execute([$id]);
    $current_movie = $stmt->fetch(PDO::FETCH_ASSOC);
    $image = $current_movie['image']; // Giữ ảnh cũ

    // Xử lý upload ảnh mới
    if (isset($_FILES['movie_image']) && $_FILES['movie_image']['error'] == 0) {
        $uploadDir = './img/';
        $fileName = time() . '_' . $_FILES['movie_image']['name'];
        $uploadPath = $uploadDir . $fileName;

        // Kiểm tra định dạng file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (in_array($_FILES['movie_image']['type'], $allowedTypes)) {
            if (move_uploaded_file($_FILES['movie_image']['tmp_name'], $uploadPath)) {
                // Xóa ảnh cũ (trừ ảnh mặc định)
                if ($image && $image != './img/default_movie.jpg' && file_exists($image)) {
                    unlink($image);
                }
                $image = $uploadPath;
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE movies SET title = ?, genre = ?, duration = ?, release_date = ?, image = ?, description = ?, category_id = ? WHERE id = ?");

    if ($stmt->execute([$title, $genre, $duration, $release_date, $image, $description, $category_id, $id])) {
        echo "<script>alert('Cập nhật phim thành công!');</script>";
    }
}

// Xóa phim
if ($_GET && isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // Kiểm tra xem phim có suất chiếu nào không
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM showtimes WHERE movie_id = ?");
    $stmt->execute([$id]);
    $showtime_count = $stmt->fetchColumn();

    if ($showtime_count > 0) {
        echo "<script>alert('Không thể xóa phim này vì đang có suất chiếu!');</script>";
    } else {
        // Lấy thông tin ảnh để xóa file
        $stmt = $pdo->prepare("SELECT image FROM movies WHERE id = ?");
        $stmt->execute([$id]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
        if ($stmt->execute([$id])) {
            // Xóa file ảnh (trừ ảnh mặc định)
            if ($movie['image'] && $movie['image'] != './img/default_movie.jpg' && file_exists($movie['image'])) {
                unlink($movie['image']);
            }
            echo "<script>alert('Xóa phim thành công!');</script>";
        }
    }
}

// Lấy danh sách phim
$stmt = $pdo->prepare("SELECT m.*, c.name as category_name FROM movies m LEFT JOIN categories c ON m.category_id = c.id ORDER BY m.id DESC");
$stmt->execute();
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin phim để edit
$edit_movie = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $edit_movie = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Quản lý phim - Admin CGV</title>
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

        .movie-image {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-card">
            <h1><i class="bi bi-film"></i> Quản lý Phim CGV</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin.php">Trang chủ Admin</a></li>
                    <li class="breadcrumb-item active">Quản lý Phim</li>
                </ol>
            </nav>
        </div>

        <div class="admin-card">
            <h3><?php echo $edit_movie ? 'Chỉnh sửa phim' : 'Thêm phim mới'; ?></h3>
            <form method="POST" enctype="multipart/form-data" class="mb-4">
                <input type="hidden" name="action" value="<?php echo $edit_movie ? 'update_movie' : 'add_movie'; ?>">
                <?php if ($edit_movie): ?>
                    <input type="hidden" name="movie_id" value="<?php echo $edit_movie['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Tên phim:</label>
                        <input type="text" name="title" class="form-control"
                            value="<?php echo $edit_movie ? $edit_movie['title'] : ''; ?>"
                            placeholder="Tên phim" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Thể loại:</label>
                        <input type="text" name="genre" class="form-control"
                            value="<?php echo $edit_movie ? $edit_movie['genre'] : ''; ?>"
                            placeholder="Thể loại" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Thời lượng:</label>
                        <input type="text" name="duration" class="form-control"
                            value="<?php echo $edit_movie ? $edit_movie['duration'] : ''; ?>"
                            placeholder="Thời lượng" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ngày phát hành:</label>
                        <input type="date" name="release_date" class="form-control"
                            value="<?php echo $edit_movie ? $edit_movie['release_date'] : ''; ?>" required />
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Poster phim:</label>
                        <input type="file" name="movie_image" class="form-control" accept="image/*" />
                        <?php if ($edit_movie && $edit_movie['image']): ?>
                            <small class="text-muted">Ảnh hiện tại: <?php echo basename($edit_movie['image']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Danh mục:</label>
                        <select name="category_id" class="form-control" required>
                            <option value="1" <?php echo ($edit_movie && $edit_movie['category_id'] == 1) ? 'selected' : ''; ?>>Hành Động</option>
                            <option value="2" <?php echo ($edit_movie && $edit_movie['category_id'] == 2) ? 'selected' : ''; ?>>Tình Cảm</option>
                            <option value="3" <?php echo ($edit_movie && $edit_movie['category_id'] == 3) ? 'selected' : ''; ?>>Hoạt Hình</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">
                            <i class="bi bi-<?php echo $edit_movie ? 'pencil' : 'plus-circle'; ?>"></i>
                            <?php echo $edit_movie ? 'Cập nhật' : 'Thêm Phim'; ?>
                        </button>
                        <?php if ($edit_movie): ?>
                            <a href="admin_movies.php" class="btn btn-secondary btn-sm mt-1">Hủy</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <label class="form-label">Mô tả phim:</label>
                        <textarea name="description" class="form-control" placeholder="Mô tả phim"><?php echo $edit_movie ? $edit_movie['description'] : ''; ?></textarea>
                    </div>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <h3>Danh sách phim</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Poster</th>
                            <th>Tên phim</th>
                            <th>Thể loại</th>
                            <th>Thời lượng</th>
                            <th>Ngày phát hành</th>
                            <th>Danh mục</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movies as $movie): ?>
                            <tr>
                                <td><?php echo $movie['id']; ?></td>
                                <td>
                                    <img src="<?php echo $movie['image']; ?>"
                                        alt="<?php echo $movie['title']; ?>"
                                        class="movie-image">
                                </td>
                                <td><strong><?php echo $movie['title']; ?></strong></td>
                                <td><?php echo $movie['genre']; ?></td>
                                <td><?php echo $movie['duration']; ?></td>
                                <td><?php echo $movie['release_date']; ?></td>
                                <td><?php echo $movie['category_name']; ?></td>
                                <td>
                                    <a href="admin_movies.php?edit_id=<?php echo $movie['id']; ?>"
                                        class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Sửa
                                    </a>
                                    <a href="admin_movies.php?delete_id=<?php echo $movie['id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Bạn có chắc muốn xóa phim này?')">
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