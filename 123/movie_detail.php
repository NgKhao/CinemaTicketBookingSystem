<?php
session_start();
require_once 'config.php';

$movie_id = $_GET['id'] ?? 1;

// Lấy thông tin phim
$stmt = $pdo->prepare("SELECT m.*, c.name as category_name FROM movies m 
                       LEFT JOIN categories c ON m.category_id = c.id 
                       WHERE m.id = ?");
$stmt->execute([$movie_id]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    header('Location: index.php');
    exit();
}

// Lấy danh sách đánh giá
$stmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r 
                       LEFT JOIN users u ON r.user_id = u.id 
                       WHERE r.movie_id = ? 
                       ORDER BY r.created_at DESC");
$stmt->execute([$movie_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính rating trung bình
$stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE movie_id = ?");
$stmt->execute([$movie_id]);
$rating_data = $stmt->fetch(PDO::FETCH_ASSOC);
$avg_rating = round($rating_data['avg_rating'], 1);
$total_reviews = $rating_data['total_reviews'];

$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$userRole = $isLoggedIn ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CGV - <?php echo $movie['title']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="ASST1.css" />
    <link rel="icon" href="./img/4.png" />
    <style>
    .movie-detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .movie-info {
        display: flex;
        gap: 30px;
        margin-bottom: 40px;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .movie-poster {
        flex: 0 0 300px;
    }

    .movie-poster img {
        width: 100%;
        height: auto;
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }

    .movie-details {
        flex: 1;
    }

    .movie-title {
        font-size: 2.5em;
        color: #e50914;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .movie-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }

    .meta-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #e50914;
    }

    .meta-item strong {
        color: #e50914;
        display: block;
        margin-bottom: 5px;
    }

    .movie-description {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
        line-height: 1.6;
    }

    .rating-display {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 20px 0;
    }

    .stars {
        color: #ffc107;
        font-size: 1.2em;
    }

    .showtimes-section {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .showtimes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .showtime-card {
        border: 2px solid #e50914;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
        transition: transform 0.3s ease;
    }

    .showtime-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(229, 9, 20, 0.2);
    }

    .review-section {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .review-form {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .rating-input {
        display: flex;
        gap: 5px;
        margin: 15px 0;
    }

    .star-input {
        font-size: 2em;
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
    }

    .star-input:hover,
    .star-input.active {
        color: #ffc107;
    }

    .review-item {
        border-bottom: 1px solid #eee;
        padding: 20px 0;
    }

    .review-item:last-child {
        border-bottom: none;
    }

    .review-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 10px;
    }

    .reviewer-name {
        font-weight: bold;
        color: #e50914;
    }

    .review-date {
        color: #666;
        font-size: 0.9em;
    }

    .btn-primary {
        background: #e50914;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1.1em;
        transition: background 0.3s;
    }

    .btn-primary:hover {
        background: #c8070f;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
        padding: 10px 25px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: background 0.3s;
    }

    .btn-secondary:hover {
        background: #545b62;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #ddd;
        border-radius: 5px;
        font-size: 1em;
    }

    .form-group textarea {
        height: 120px;
        resize: vertical;
    }

    @media (max-width: 768px) {
        .movie-info {
            flex-direction: column;
        }

        .movie-poster {
            flex: none;
            max-width: 300px;
            margin: 0 auto;
        }

        .movie-title {
            font-size: 2em;
            text-align: center;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <marquee>CGV Trần Duy Hưng 1-6-2025 Kỉ niệm 22 năm thành lập tặng free bắp rang bơ và nước khi mua từ 2 vé trở
            lên</marquee>

        <!-- User Status Bar -->
        <div class="user-status-bar">
            <?php if ($isLoggedIn): ?>
            <div class="user-info">
                <i class="fas fa-user"></i> Xin chào, <strong><?php echo $username; ?></strong>
                <?php if ($userRole === 'admin'): ?>
                <a href="admin.php" class="admin-link"><i class="fas fa-cog"></i> Quản trị</a>
                <?php endif; ?>
            </div>
            <!-- Search Bar -->
            <div class="search-container">
                <form method="GET" action="search_movies.php" class="search-form">
                    <input type="text" name="q" placeholder="Tìm kiếm phim..." class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            <div class="user-actions">
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
            <?php else: ?>
            <div class="user-actions">
                <a href="login.html" class="login-btn"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                <a href="register.html" class="register-btn"><i class="fas fa-user-plus"></i> Đăng ký</a>
            </div>
            <?php endif; ?>
        </div>

        <header></header>
        <nav>
            <ul>
                <li><a href="./index.php">Trang chủ</a></li>
                <li class="dropdown">
                    <a href="#"> Phim <i class="fas fa-chevron-down"></i> </a>
                    <ul class="dropdown-content">
                        <li><a href="./movies.php?category=1">Phim Hành Động</a></li>
                        <li><a href="./movies.php?category=2">Phim Tình Cảm</a></li>
                        <li><a href="./movies.php?category=3">Phim Hoạt Hình</a></li>
                    </ul>
                </li>
                <li><a href="./list_cgv.php">Rạp CGV</a></li>
                <li><a href="./list_user.html">Thành viên</a></li>
                <li><a href="./ap.html">Tuyển dụng</a></li>
            </ul>


        </nav>

        <article>
            <div class="movie-detail-container">

                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success'];
                                                            unset($_SESSION['success']); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error'];
                                                                    unset($_SESSION['error']); ?>
                </div>
                <?php endif; ?>

                <!-- Thông tin phim -->
                <div class="movie-info">
                    <div class="movie-poster">
                        <img src="<?php echo $movie['image']; ?>" alt="<?php echo $movie['title']; ?>" />
                    </div>
                    <div class="movie-details">
                        <h1 class="movie-title"><?php echo $movie['title']; ?></h1>

                        <div class="rating-display">
                            <div class="stars">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= floor($avg_rating)) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i <= $avg_rating) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <span><strong><?php echo $avg_rating; ?>/5</strong> (<?php echo $total_reviews; ?> đánh
                                giá)</span>
                        </div>

                        <div class="movie-meta">
                            <div class="meta-item">
                                <strong>Thể loại:</strong>
                                <?php echo $movie['genre']; ?>
                            </div>
                            <div class="meta-item">
                                <strong>Thời lượng:</strong>
                                <?php echo $movie['duration']; ?>
                            </div>
                            <div class="meta-item">
                                <strong>Ngày khởi chiếu:</strong>
                                <?php echo date('d/m/Y', strtotime($movie['release_date'])); ?>
                            </div>
                            <div class="meta-item">
                                <strong>Danh mục:</strong>
                                <?php echo $movie['category_name']; ?>
                            </div>
                        </div>

                        <div class="movie-description">
                            <strong>Nội dung phim:</strong><br>
                            <?php echo nl2br($movie['description']); ?>
                        </div>
                    </div>
                </div>

                <!-- Đánh giá -->
                <div class="review-section">
                    <h2><i class="fas fa-comments"></i> Đánh Giá Phim</h2>

                    <?php if ($isLoggedIn): ?>
                    <!-- Form đánh giá -->
                    <div class="review-form">
                        <h3>Viết đánh giá của bạn</h3>
                        <form id="reviewForm" action="submit_review.php" method="POST">
                            <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">

                            <div class="form-group">
                                <label>Đánh giá sao:</label>
                                <div class="rating-input" id="starRating">
                                    <span class="star-input" data-rating="1">★</span>
                                    <span class="star-input" data-rating="2">★</span>
                                    <span class="star-input" data-rating="3">★</span>
                                    <span class="star-input" data-rating="4">★</span>
                                    <span class="star-input" data-rating="5">★</span>
                                </div>
                                <input type="hidden" name="rating" id="ratingValue" required>
                            </div>

                            <div class="form-group">
                                <label for="comment">Nhận xét:</label>
                                <textarea name="comment" id="comment"
                                    placeholder="Chia sẻ cảm nhận của bạn về bộ phim..." required></textarea>
                            </div>

                            <button type="submit" class="btn-primary">
                                <i class="fas fa-paper-plane"></i> Gửi đánh giá
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="review-form">
                        <p><a href="login.html">Đăng nhập</a> để viết đánh giá về bộ phim này.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Danh sách đánh giá -->
                    <div class="reviews-list">
                        <h3>Các đánh giá khác (<?php echo $total_reviews; ?>)</h3>

                        <?php if (empty($reviews)): ?>
                        <p>Chưa có đánh giá nào cho bộ phim này. Hãy là người đầu tiên!</p>
                        <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <span class="reviewer-name">
                                    <i class="fas fa-user-circle"></i> <?php echo $review['username']; ?>
                                </span>
                                <span class="review-date">
                                    <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                                </span>
                            </div>
                            <div class="rating-display">
                                <div class="stars">
                                    <?php
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $review['rating']) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            ?>
                                </div>
                                <span><?php echo $review['rating']; ?>/5 sao</span>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>

        <footer>
            <span>chăm sóc khách hàng</span>
        </footer>
    </div>

    <script>
    // Xử lý đánh giá sao
    document.addEventListener('DOMContentLoaded', function() {
        const stars = document.querySelectorAll('.star-input');
        const ratingValue = document.getElementById('ratingValue');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingValue.value = rating;

                // Cập nhật hiển thị sao
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });

            star.addEventListener('mouseover', function() {
                const rating = this.getAttribute('data-rating');
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#ffc107';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });

        document.getElementById('starRating').addEventListener('mouseleave', function() {
            const currentRating = ratingValue.value;
            stars.forEach((s, index) => {
                if (index < currentRating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });
    </script>
</body>

</html>