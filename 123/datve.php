<?php
require_once 'config.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header('Location: login.html');
    exit;
}

// Lấy movie_id và showtime_id từ URL (nếu có)
$selected_movie_id = isset($_GET['movie_id']) ? $_GET['movie_id'] : null;
$selected_showtime_id = isset($_GET['showtime_id']) ? $_GET['showtime_id'] : null;

// Nếu có showtime_id, lấy thông tin từ showtime
$selected_showtime = null;
if ($selected_showtime_id) {
    $stmt = $pdo->prepare("
        SELECT s.*, m.id as movie_id, m.title as movie_title, m.genre, m.duration, 
               c.name as cinema_name 
        FROM showtimes s 
        JOIN movies m ON s.movie_id = m.id 
        JOIN cinemas c ON s.cinema_id = c.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$selected_showtime_id]);
    $selected_showtime = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($selected_showtime) {
        $selected_movie_id = $selected_showtime['movie_id'];
    }
}

// Lấy thông tin phim được chọn (nếu có)
$selected_movie = null;
if ($selected_movie_id) {
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$selected_movie_id]);
    $selected_movie = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy suất chiếu có sẵn cho phim được chọn
$available_showtimes = [];
if ($selected_movie_id) {
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as cinema_name 
        FROM showtimes s 
        JOIN cinemas c ON s.cinema_id = c.id 
        WHERE s.movie_id = ? AND s.show_date >= CURDATE() 
        ORDER BY s.show_date, s.show_time
    ");
    $stmt->execute([$selected_movie_id]);
    $available_showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy danh sách phim từ database
$stmt = $pdo->prepare("SELECT id, title FROM movies ORDER BY title");
$stmt->execute();
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CGV - Đặt Vé</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .header {
        background: #e50914;
        color: white;
        text-align: center;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
    }

    .nav {
        text-align: center;
        margin-bottom: 20px;
    }

    .nav a {
        color: #e50914;
        text-decoration: none;
        margin: 0 15px;
        font-weight: bold;
        transition: all 0.3s;
    }

    .nav a:hover {
        color: #b8070f;
    }

    .booking-section {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .movie-info {
        background: linear-gradient(135deg, #e50914 0%, #b8070f 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .movie-info h3 {
        margin-bottom: 10px;
        font-size: 24px;
    }

    .movie-info p {
        margin: 5px 0;
        font-size: 16px;
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
    .form-group select {
        width: 100%;
        padding: 12px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #e50914;
    }

    .showtimes-container {
        margin-bottom: 20px;
    }

    .showtimes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 15px;
    }

    .showtime-card {
        border: 2px solid #ddd;
        border-radius: 10px;
        padding: 15px;
        transition: all 0.3s;
        cursor: pointer;
    }

    .showtime-card.selected {
        border-color: #e50914;
        background: #fff5f5;
    }

    .showtime-card:hover {
        border-color: #e50914;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(229, 9, 20, 0.2);
    }

    .cinema-section {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .screen {
        background: linear-gradient(135deg, #e50914 0%, #b8070f 100%);
        color: white;
        text-align: center;
        padding: 15px;
        margin: 20px auto 30px;
        width: 80%;
        border-radius: 10px;
        font-weight: bold;
        font-size: 18px;
        box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
    }

    .seats-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .seat-row {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 10px;
        gap: 5px;
    }

    .row-label {
        width: 30px;
        text-align: center;
        font-weight: bold;
        color: #666;
    }

    .seat {
        width: 35px;
        height: 35px;
        border: 2px solid #ddd;
        background: #f8f9fa;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 12px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .seat.regular {
        border-color: #28a745;
    }

    .seat.vip {
        border-color: #ffc107;
        background: #fff8dc;
    }

    .seat.sweetbox {
        border-color: #e50914;
        background: #ffe6e6;
    }

    .seat.booked {
        background: #6c757d;
        color: white;
        cursor: not-allowed;
        border-color: #6c757d;
    }

    .seat.selected {
        background: #e50914;
        color: white;
        border-color: #e50914;
        transform: scale(1.1);
    }

    .seat:not(.booked):hover {
        transform: scale(1.1);
    }

    .gap {
        width: 20px;
    }

    .legend {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin: 20px 0;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .legend-seat {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        border: 2px solid;
    }

    .booking-summary {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 5px solid #e50914;
    }

    .booking-summary h4 {
        color: #e50914;
        margin-bottom: 15px;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        margin: 10px 0;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .total-price {
        font-size: 20px;
        font-weight: bold;
        color: #e50914;
    }

    .confirm-btn {
        background: linear-gradient(135deg, #e50914 0%, #b8070f 100%);
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 8px;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        width: 100%;
        margin-bottom: 20px;
    }

    .confirm-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(229, 9, 20, 0.4);
    }

    .confirm-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
    }

    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #f5c6cb;
    }

    .success-message {
        background: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #c3e6cb;
    }

    .loading {
        text-align: center;
        padding: 20px;
        color: #666;
    }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-film"></i> CGV - Đặt Vé Xem Phim</h1>
            <p>Chào mừng <?php echo $_SESSION['username']; ?> đến với hệ thống đặt vé CGV</p>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
            <a href="movies.php"><i class="fas fa-film"></i> Phim</a>
            <a href="list_cgv.php"><i class="fas fa-building"></i> Rạp CGV</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </div>

        <!-- Movie Information -->
        <?php if ($selected_showtime): ?>
        <div class="movie-info">
            <h3><i class="fas fa-video"></i> <?php echo $selected_showtime['movie_title']; ?></h3>
            <p><i class="fas fa-tags"></i> <strong>Thể loại:</strong> <?php echo $selected_showtime['genre']; ?></p>
            <p><i class="fas fa-clock"></i> <strong>Thời lượng:</strong> <?php echo $selected_showtime['duration']; ?>
            </p>
        </div>
        <?php elseif ($selected_movie): ?>
        <div class="movie-info">
            <h3><i class="fas fa-video"></i> <?php echo $selected_movie['title']; ?></h3>
            <p><i class="fas fa-tags"></i> <strong>Thể loại:</strong> <?php echo $selected_movie['genre']; ?></p>
            <p><i class="fas fa-clock"></i> <strong>Thời lượng:</strong> <?php echo $selected_movie['duration']; ?></p>
        </div>
        <?php endif; ?>

        <!-- Booking Form -->
        <div class="booking-section">
            <h3><i class="fas fa-ticket-alt"></i> Thông tin đặt vé</h3>

            <form id="bookingForm" action="datve_process.php" method="POST">
                <!-- Customer Information -->
                <div class="form-group">
                    <label for="customer_name"><i class="fas fa-user"></i> Tên khách hàng</label>
                    <input type="text" id="customer_name" name="customer_name" required placeholder="Nhập tên đầy đủ">
                </div>

                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Số điện thoại</label>
                    <input type="tel" id="phone" name="phone" required placeholder="Nhập số điện thoại">
                </div>

                <!-- Movie Selection -->
                <?php if ($selected_movie_id): ?>
                <input type="hidden" name="movie_id" value="<?php echo $selected_movie_id; ?>">
                <?php else: ?>
                <div class="form-group">
                    <label for="movie_id"><i class="fas fa-film"></i> Chọn phim</label>
                    <select id="movie_id" name="movie_id" required>
                        <option value="">-- Chọn phim --</option>
                        <?php foreach ($movies as $movie): ?>
                        <option value="<?php echo $movie['id']; ?>"><?php echo $movie['title']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Showtime Selection -->
                <?php if (!empty($available_showtimes)): ?>
                <div class="showtimes-container">
                    <h4><i class="fas fa-calendar-alt"></i> Chọn suất chiếu</h4>
                    <div class="showtimes-grid">
                        <?php foreach ($available_showtimes as $showtime): ?>
                        <div class="showtime-card" data-showtime-id="<?php echo $showtime['id']; ?>">
                            <input type="radio" name="showtime_id" value="<?php echo $showtime['id']; ?>"
                                <?php echo ($selected_showtime_id && $showtime['id'] == $selected_showtime_id) ? 'checked' : ''; ?>
                                style="display: none;" required>
                            <div>
                                <strong><i class="fas fa-building"></i> <?php echo $showtime['cinema_name']; ?></strong>
                            </div>
                            <div>
                                <i class="fas fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($showtime['show_date'])); ?>
                            </div>
                            <div>
                                <i class="fas fa-clock"></i>
                                <?php echo date('H:i', strtotime($showtime['show_time'])); ?>
                            </div>
                            <div>
                                <i class="fas fa-chair"></i> Còn
                                <?php echo $showtime['available_seats']; ?>/<?php echo $showtime['total_seats']; ?> ghế
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Hidden input for selected seats -->
                <input type="hidden" id="selectedSeats" name="seats">
            </form>
        </div>

        <!-- Cinema Section -->
        <div class="cinema-section">
            <h3><i class="fas fa-chair"></i> Chọn ghế ngồi</h3>

            <div class="screen">MÀN HÌNH CHIẾU PHIM</div>

            <div class="seats-container">
                <!-- Row A - Regular seats -->
                <div class="seat-row">
                    <div class="row-label">A</div>
                    <button type="button" class="seat regular" data-seat="A1">A1</button>
                    <button type="button" class="seat regular" data-seat="A2">A2</button>
                    <button type="button" class="seat regular" data-seat="A3">A3</button>
                    <button type="button" class="seat regular" data-seat="A4">A4</button>
                    <button type="button" class="seat regular" data-seat="A5">A5</button>
                    <div class="gap"></div>
                    <button type="button" class="seat regular" data-seat="A6">A6</button>
                    <button type="button" class="seat regular" data-seat="A7">A7</button>
                    <button type="button" class="seat regular" data-seat="A8">A8</button>
                    <button type="button" class="seat regular" data-seat="A9">A9</button>
                    <button type="button" class="seat regular" data-seat="A10">A10</button>
                </div>

                <!-- Row B - VIP seats -->
                <div class="seat-row">
                    <div class="row-label">B</div>
                    <button type="button" class="seat vip" data-seat="B1">B1</button>
                    <button type="button" class="seat vip" data-seat="B2">B2</button>
                    <button type="button" class="seat vip" data-seat="B3">B3</button>
                    <button type="button" class="seat vip" data-seat="B4">B4</button>
                    <button type="button" class="seat vip" data-seat="B5">B5</button>
                    <div class="gap"></div>
                    <button type="button" class="seat vip" data-seat="B6">B6</button>
                    <button type="button" class="seat vip" data-seat="B7">B7</button>
                    <button type="button" class="seat vip" data-seat="B8">B8</button>
                    <button type="button" class="seat vip" data-seat="B9">B9</button>
                    <button type="button" class="seat vip" data-seat="B10">B10</button>
                </div>

                <!-- Row C - VIP seats -->
                <div class="seat-row">
                    <div class="row-label">C</div>
                    <button type="button" class="seat vip" data-seat="C1">C1</button>
                    <button type="button" class="seat vip" data-seat="C2">C2</button>
                    <button type="button" class="seat vip" data-seat="C3">C3</button>
                    <button type="button" class="seat vip" data-seat="C4">C4</button>
                    <button type="button" class="seat vip" data-seat="C5">C5</button>
                    <div class="gap"></div>
                    <button type="button" class="seat vip" data-seat="C6">C6</button>
                    <button type="button" class="seat vip" data-seat="C7">C7</button>
                    <button type="button" class="seat vip" data-seat="C8">C8</button>
                    <button type="button" class="seat vip" data-seat="C9">C9</button>
                    <button type="button" class="seat vip" data-seat="C10">C10</button>
                </div>

                <!-- Row D - Sweetbox seats -->
                <div class="seat-row">
                    <div class="row-label">D</div>
                    <div style="width: 35px;"></div>
                    <div style="width: 35px;"></div>
                    <button type="button" class="seat sweetbox" data-seat="D1">D1</button>
                    <button type="button" class="seat sweetbox" data-seat="D2">D2</button>
                    <button type="button" class="seat sweetbox" data-seat="D3">D3</button>
                    <div class="gap"></div>
                    <button type="button" class="seat sweetbox" data-seat="D4">D4</button>
                    <button type="button" class="seat sweetbox" data-seat="D5">D5</button>
                    <button type="button" class="seat sweetbox" data-seat="D6">D6</button>
                    <div style="width: 35px;"></div>
                    <div style="width: 35px;"></div>
                </div>
            </div>

            <!-- Legend -->
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-seat regular" style="border-color: #28a745; background: #f8f9fa;"></div>
                    <span>Ghế thường (60,000đ)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-seat vip" style="border-color: #ffc107; background: #fff8dc;"></div>
                    <span>Ghế VIP (80,000đ)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-seat sweetbox" style="border-color: #e50914; background: #ffe6e6;"></div>
                    <span>Ghế Sweetbox (120,000đ)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-seat" style="border-color: #6c757d; background: #6c757d;"></div>
                    <span>Đã đặt</span>
                </div>
                <div class="legend-item">
                    <div class="legend-seat" style="border-color: #e50914; background: #e50914;"></div>
                    <span>Đang chọn</span>
                </div>
            </div>
        </div>

        <!-- Booking Summary -->
        <div class="booking-summary">
            <h4><i class="fas fa-receipt"></i> Thông tin đặt vé</h4>
            <div class="summary-item">
                <span>Ghế đã chọn:</span>
                <span id="selectedSeatsDisplay">Chưa chọn</span>
            </div>
            <div class="summary-item">
                <span>Số lượng ghế:</span>
                <span id="seatCount">0</span>
            </div>
            <div class="summary-item total-price">
                <span>Tổng tiền:</span>
                <span id="totalPrice">0đ</span>
            </div>
        </div>

        <!-- Confirm Button -->
        <button type="button" id="confirmBtn" class="confirm-btn" onclick="confirmBooking()">
            <i class="fas fa-credit-card"></i> Xác nhận đặt vé
        </button>

        <!-- Messages -->
        <div id="errorMessage" class="error-message" style="display: none;"></div>
        <div id="successMessage" class="success-message" style="display: none;"></div>
    </div>

    <script>
    // Giá vé theo loại ghế
    const SEAT_PRICES = {
        'regular': 60000,
        'vip': 80000,
        'sweetbox': 120000
    };

    // Biến toàn cục
    let selectedSeats = [];
    let bookedSeats = [];
    let selectedShowtimeId = null;

    // Hàm load ghế đã đặt
    async function loadBookedSeats(showtimeId) {
        if (!showtimeId) return;

        try {
            console.log('Loading booked seats for showtime:', showtimeId);
            const response = await fetch(`get_booked_seats.php?showtime_id=${showtimeId}`);
            const data = await response.json();
            console.log('Booked seats data:', data);

            bookedSeats = data;
            updateSeatDisplay();
        } catch (error) {
            console.error('Error loading booked seats:', error);
        }
    }

    // Hàm cập nhật hiển thị ghế
    function updateSeatDisplay() {
        // Reset tất cả ghế
        document.querySelectorAll('.seat').forEach(seat => {
            seat.classList.remove('booked');
        });

        // Đánh dấu ghế đã đặt
        bookedSeats.forEach(seatNumber => {
            const seat = document.querySelector(`[data-seat="${seatNumber}"]`);
            if (seat) {
                seat.classList.add('booked');
            }
        });

        console.log('Updated seat display, booked seats:', bookedSeats);
    }

    // Hàm cập nhật tổng kết đặt vé
    function updateBookingSummary() {
        const seatCount = selectedSeats.length;
        let totalPrice = 0;

        selectedSeats.forEach(seatId => {
            const seat = document.querySelector(`[data-seat="${seatId}"]`);
            if (seat.classList.contains('vip')) {
                totalPrice += SEAT_PRICES.vip;
            } else if (seat.classList.contains('sweetbox')) {
                totalPrice += SEAT_PRICES.sweetbox;
            } else {
                totalPrice += SEAT_PRICES.regular;
            }
        });

        document.getElementById('selectedSeatsDisplay').textContent =
            selectedSeats.length > 0 ? selectedSeats.join(', ') : 'Chưa chọn';
        document.getElementById('seatCount').textContent = seatCount;
        document.getElementById('totalPrice').textContent = totalPrice.toLocaleString('vi-VN') + 'đ';
        document.getElementById('selectedSeats').value = selectedSeats.join(',');
    }

    // Hàm xử lý chọn ghế
    function handleSeatClick(event) {
        const seat = event.target;
        const seatId = seat.getAttribute('data-seat');

        if (seat.classList.contains('booked')) {
            return; // Không thể chọn ghế đã đặt
        }

        if (seat.classList.contains('selected')) {
            // Bỏ chọn ghế
            seat.classList.remove('selected');
            selectedSeats = selectedSeats.filter(id => id !== seatId);
        } else {
            // Chọn ghế
            seat.classList.add('selected');
            selectedSeats.push(seatId);
        }

        updateBookingSummary();
    }

    // Hàm xử lý chọn suất chiếu
    function handleShowtimeClick(event) {
        const card = event.target.closest('.showtime-card');
        if (!card) return;

        // Bỏ chọn tất cả suất chiếu
        document.querySelectorAll('.showtime-card').forEach(c => c.classList.remove('selected'));
        document.querySelectorAll('input[name="showtime_id"]').forEach(input => input.checked = false);

        // Chọn suất chiếu hiện tại
        card.classList.add('selected');
        const radio = card.querySelector('input[name="showtime_id"]');
        radio.checked = true;

        selectedShowtimeId = radio.value;
        console.log('Selected showtime:', selectedShowtimeId);

        // Load ghế đã đặt cho suất chiếu này
        loadBookedSeats(selectedShowtimeId);
    }

    // Hàm xác nhận đặt vé
    function confirmBooking() {
        // Kiểm tra thông tin khách hàng
        const customerName = document.getElementById('customer_name').value.trim();
        const phone = document.getElementById('phone').value.trim();

        if (!customerName || !phone) {
            showError('Vui lòng điền đầy đủ thông tin khách hàng!');
            return;
        }

        // Kiểm tra suất chiếu
        const showtimeId = document.querySelector('input[name="showtime_id"]:checked');
        if (!showtimeId) {
            showError('Vui lòng chọn suất chiếu!');
            return;
        }

        // Kiểm tra ghế đã chọn
        if (selectedSeats.length === 0) {
            showError('Vui lòng chọn ít nhất 1 ghế!');
            return;
        }

        // Submit form
        document.getElementById('bookingForm').submit();
    }

    // Hàm hiển thị lỗi
    function showError(message) {
        const errorDiv = document.getElementById('errorMessage');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';

        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }

    // Khởi tạo khi trang load
    document.addEventListener('DOMContentLoaded', function() {
        // Thêm event listener cho ghế
        document.querySelectorAll('.seat').forEach(seat => {
            seat.addEventListener('click', handleSeatClick);
        });

        // Thêm event listener cho suất chiếu
        document.querySelectorAll('.showtime-card').forEach(card => {
            card.addEventListener('click', handleShowtimeClick);
        });

        // Nếu có suất chiếu được chọn sẵn, load ghế đã đặt
        const selectedShowtime = document.querySelector('input[name="showtime_id"]:checked');
        if (selectedShowtime) {
            selectedShowtimeId = selectedShowtime.value;
            selectedShowtime.closest('.showtime-card').classList.add('selected');
            loadBookedSeats(selectedShowtimeId);
        }

        // Cập nhật summary ban đầu
        updateBookingSummary();
    });
    </script>
</body>

</html>