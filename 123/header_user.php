<?php
// File header chung cho các trang user
// Yêu cầu: session_start() và các biến $isLoggedIn, $username, $userRole phải được khai báo trước khi include file này
?>
<div class="container">
    <marquee>CGV Trần Duy Hưng 1-6-2025 Kỉ niệm 22 năm thành lập tặng free bắp rang bơ và nước khi mua từ 2 vé trở lên</marquee>

    <!-- User Status Bar -->
    <div class="user-status-bar">
        <?php if ($isLoggedIn): ?>
            <div class="user-info">
                <i class="fas fa-user"></i> Xin chào, <strong><?php echo htmlspecialchars($username); ?></strong>
                <?php if ($userRole === 'admin'): ?>
                    <a href="admin.php" class="admin-link"><i class="fas fa-cog"></i> Quản trị</a>
                <?php endif; ?>
            </div>
            <!-- Search Bar -->
            <div class="search-container">
                <form method="GET" action="search_movies.php" class="search-form">
                    <input type="text" name="q" placeholder="Tìm kiếm phim..." class="search-input"
                        value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            <div class="user-actions">
                <a href="member_profile.php" class="member-btn"><i class="fas fa-award"></i> Thành viên</a>
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
            <li><a href="./list_user.php">Thành viên</a></li>
            <li><a href="./ap.php">Tuyển dụng</a></li>
        </ul>
    </nav>