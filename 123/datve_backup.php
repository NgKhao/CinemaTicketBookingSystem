<?php
require_once 'config.php';

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

// Lấy danh sách phim từ database
$stmt = $pdo->prepare("SELECT id, title FROM movies ORDER BY title");
$stmt->execute();
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách rạp từ database  
$stmt = $pdo->prepare("SELECT id, name FROM cinemas ORDER BY name");
$stmt->execute();
$cinemas = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CGV - Đặt Vé</title>

    <!-- CSS liên kết -->
    <link rel="stylesheet" href="ASST1.css" />

    <!-- Icon của trang -->
    <link rel="icon" href="./img/4.png" type="image/png" />

    <!-- Font Awesome để dùng icon dropdown -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>

<body>
    <div class="container">
        <!-- Thanh thông báo chạy -->
        <marquee behavior="scroll" direction="left">
            CGV Trần Duy Hưng 1-6-2025 Kỉ niệm 22 năm thành lập - tặng free bắp rang
            bơ và nước khi mua từ 2 vé trở lên
        </marquee>

        <!-- Phần đầu -->
        <header></header>

        <!-- Menu điều hướng -->
        <nav>
            <ul>
                <li><a href="./index.php">Trang chủ</a></li>
                <li class="dropdown">
                    <a href="#">Phim <i class="fas fa-chevron-down"></i></a>
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

        <!-- Form đăng kí mua vé -->
        <div class="booking-container">
            <h3>Đặt Vé Xem Phim</h3>

            <?php if ($selected_showtime): ?>
            <div class="selected-movie-info">
                <h4>🎬 Phim đã chọn: <?php echo $selected_showtime['movie_title']; ?></h4>
                <p><strong>Thể loại:</strong> <?php echo $selected_showtime['genre']; ?></p>
                <p><strong>Thời lượng:</strong> <?php echo $selected_showtime['duration']; ?></p>
            </div>
            <?php elseif ($selected_movie): ?>
            <div class="selected-movie-info">
                <h4>🎬 Phim đã chọn: <?php echo $selected_movie['title']; ?></h4>
                <p><strong>Thể loại:</strong> <?php echo $selected_movie['genre']; ?></p>
                <p><strong>Thời lượng:</strong> <?php echo $selected_movie['duration']; ?></p>
            </div>
            <?php endif; ?>

            <form action="datve_process.php" method="POST" id="bookingForm">
                <div>
                    <input type="text" name="customer_name" placeholder="Tên Khách hàng" required />
                </div>
                <br />
                <div>
                    <input type="text" name="phone" placeholder="Số Điện Thoại" required />
                </div>
                <br />

                <?php if ($selected_movie_id): ?>
                <input type="hidden" name="movie_id" value="<?php echo $selected_movie_id; ?>" />
                <?php else: ?>
                <div>
                    <select name="movie_id" required>
                        <option value="">Chọn phim</option>
                        <?php foreach ($movies as $movie): ?>
                        <option value="<?php echo $movie['id']; ?>"><?php echo $movie['title']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <br />
                <?php endif; ?>

                <!-- Hiển thị suất chiếu -->
                <?php if (!empty($available_showtimes)): ?>
                <div class="showtime-selection">
                    <h4>📅 Chọn suất chiếu có sẵn:</h4>
                    <div class="showtime-grid">
                        <?php
                            $current_date = '';
                            foreach ($available_showtimes as $showtime):
                                if ($current_date != $showtime['show_date']):
                                    $current_date = $showtime['show_date'];
                                    echo "<h5>" . date('d/m/Y - l', strtotime($showtime['show_date'])) . "</h5>";
                                endif;

                                // Kiểm tra xem có phải suất chiếu được chọn không
                                $isSelected = ($selected_showtime_id && $showtime['id'] == $selected_showtime_id);
                            ?>
                        <label class="showtime-option">
                            <input type="radio" name="showtime_id" value="<?php echo $showtime['id']; ?>"
                                <?php echo $isSelected ? 'checked' : ''; ?> required />
                            <span>
                                <strong><?php echo $showtime['cinema_name']; ?></strong><br>
                                ⏰ <?php echo date('H:i', strtotime($showtime['show_time'])); ?>
                                (Còn <?php echo $showtime['available_seats']; ?>/<?php echo $showtime['total_seats']; ?>
                                ghế)
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="no-showtime">
                    <h4>😔 Hiện tại chưa có suất chiếu nào cho phim này</h4>
                    <p>Vui lòng chọn phim khác hoặc quay lại sau.</p>
                    <button type="button" onclick="window.location.href='index.php'" class="btn-back">
                        🏠 Về trang chủ
                    </button>
                </div>
                <?php endif; ?>

                <input type="hidden" name="seats" id="selectedSeats" />

                <div class="screen">MÀN HÌNH</div>

                <div class="seats">
                    <!-- Hàng A - Ghế thường -->
                    <div class="row" data-row="A">
                        <span class="row-label">A</span>
                        <div class="side">
                            <button type="button" class="seat">A1</button>
                            <button type="button" class="seat">A2</button>
                            <button type="button" class="seat">A3</button>
                            <button type="button" class="seat">A4</button>
                            <button type="button" class="seat">A5</button>
                            <button type="button" class="seat">A6</button>
                            <button type="button" class="seat">A7</button>
                        </div>
                        <div class="gap"></div>
                        <div class="side">
                            <button type="button" class="seat">A8</button>
                            <button type="button" class="seat">A9</button>
                            <button type="button" class="seat">A10</button>
                            <button type="button" class="seat">A11</button>
                            <button type="button" class="seat">A12</button>
                            <button type="button" class="seat">A13</button>
                            <button type="button" class="seat">A14</button>
                            <button type="button" class="seat">A15</button>
                        </div>
                    </div>

                    <!-- Hàng B - Ghế VIP -->
                    <div class="row" data-row="B">
                        <span class="row-label">B</span>
                        <div class="side">
                            <button type="button" class="seat vip">B1</button>
                            <button type="button" class="seat vip">B2</button>
                            <button type="button" class="seat vip">B3</button>
                            <button type="button" class="seat vip">B4</button>
                            <button type="button" class="seat vip">B5</button>
                            <button type="button" class="seat vip">B6</button>
                            <button type="button" class="seat vip">B7</button>
                        </div>
                        <div class="gap"></div>
                        <div class="side">
                            <button type="button" class="seat vip">B8</button>
                            <button type="button" class="seat vip">B9</button>
                            <button type="button" class="seat vip">B10</button>
                            <button type="button" class="seat vip">B11</button>
                            <button type="button" class="seat vip">B12</button>
                            <button type="button" class="seat vip">B13</button>
                            <button type="button" class="seat vip">B14</button>
                            <button type="button" class="seat vip">B15</button>
                        </div>
                    </div>

                    <!-- Hàng C - Ghế VIP -->
                    <div class="row" data-row="C">
                        <span class="row-label">C</span>
                        <div class="side">
                            <button type="button" class="seat vip">C1</button>
                            <button type="button" class="seat vip">C2</button>
                            <button type="button" class="seat vip">C3</button>
                            <button type="button" class="seat vip">C4</button>
                            <button type="button" class="seat vip">C5</button>
                            <button type="button" class="seat vip">C6</button>
                            <button type="button" class="seat vip">C7</button>
                        </div>
                        <div class="gap"></div>
                        <div class="side">
                            <button type="button" class="seat vip">C8</button>
                            <button type="button" class="seat vip">C9</button>
                            <button type="button" class="seat vip">C10</button>
                            <button type="button" class="seat vip">C11</button>
                            <button type="button" class="seat vip">C12</button>
                            <button type="button" class="seat vip">C13</button>
                            <button type="button" class="seat vip">C14</button>
                            <button type="button" class="seat vip">C15</button>
                        </div>
                    </div>

                    <!-- Hàng D - Ghế Sweetbox -->
                    <div class="row" data-row="D">
                        <span class="row-label">D</span>
                        <div class="side">
                            <button type="button" class="seat sweetbox">D1</button>
                            <button type="button" class="seat sweetbox">D2</button>
                            <button type="button" class="seat sweetbox">D3</button>
                            <button type="button" class="seat sweetbox">D4</button>
                            <button type="button" class="seat sweetbox">D5</button>
                            <button type="button" class="seat sweetbox">D6</button>
                            <button type="button" class="seat sweetbox">D7</button>
                        </div>
                        <div class="gap"></div>
                        <div class="side">
                            <button type="button" class="seat sweetbox">D8</button>
                            <button type="button" class="seat sweetbox">D9</button>
                            <button type="button" class="seat sweetbox">D10</button>
                            <button type="button" class="seat sweetbox">D11</button>
                            <button type="button" class="seat sweetbox">D12</button>
                            <button type="button" class="seat sweetbox">D13</button>
                            <button type="button" class="seat sweetbox">D14</button>
                            <button type="button" class="seat sweetbox">D15</button>
                        </div>
                    </div>
                </div>

                <div class="legend">
                    <span><span class="seat"></span> Ghế thường (60,000đ)</span>
                    <span><span class="seat vip"></span> Ghế VIP (80,000đ)</span>
                    <span><span class="seat sweetbox"></span> Ghế Sweetbox (120,000đ)</span>
                    <span><span class="seat booked"></span> Đã đặt</span>
                    <span><span class="seat selected"></span> Đang chọn</span>
                </div>

                <div class="exit">🚪 Cửa ra/vào</div>
            </form>
        </div>

        <!-- Hiển thị ghế đã chọn và tổng tiền -->
        <div class="booking-summary">
            <h4>Thông tin đặt vé:</h4>
            <p id="selectedSeatsDisplay">Ghế đã chọn: Chưa chọn</p>
            <p id="totalPrice">Tổng tiền: 0đ</p>
        </div>

        <!-- Khối chứa nút và thông báo -->
        <div class="center-wrapper">
            <button type="button" onclick="xacNhanDatVe()" class="confirm-button">
                Xác nhận đặt vé
            </button>
            <p id="thongbao" class="hidden success-message">
                Bạn đã đặt vé thành công! Hãy đến rạp để lấy vé và thanh toán.
            </p>
        </div>

        <!-- Footer -->
        <footer>
            <span>Chăm sóc khách hàng</span>
        </footer>
    </div>

    <script>
    // Biến lưu giá vé theo loại ghế
    const seatPrices = {
        'seat': 60000, // Ghế thường
        'seat vip': 80000, // Ghế VIP  
        'seat sweetbox': 120000 // Ghế Sweetbox
    };

    // Ghế đã đặt từ database (sẽ được load qua AJAX)
    let bookedSeats = [];

    // Load ghế đã đặt khi chọn suất chiếu
    function loadBookedSeats(showtimeId) {
        if (!showtimeId) return;

        fetch(`get_booked_seats.php?showtime_id=${showtimeId}`)
            .then(response => response.json())
            .then(data => {
                bookedSeats = data;
                updateSeatDisplay();
            })
            .catch(error => console.error('Error:', error));
    }

    // Cập nhật hiển thị ghế
    function updateSeatDisplay() {
        // Reset tất cả ghế
        document.querySelectorAll('.seat').forEach(seat => {
            seat.classList.remove('booked');
        });

        // Đánh dấu ghế đã đặt
        bookedSeats.forEach(seatNumber => {
            const seatElement = document.querySelector(`.seat:contains('${seatNumber}')`);
            if (seatElement) {
                seatElement.classList.add('booked');
            }
        });

        // Tìm element theo text content
        document.querySelectorAll('.seat').forEach(seat => {
            if (bookedSeats.includes(seat.textContent.trim())) {
                seat.classList.add('booked');
            }
        });
    }

    // Lắng nghe sự kiện chọn suất chiếu
    document.addEventListener('change', function(e) {
        if (e.target.name === 'showtime_id') {
            loadBookedSeats(e.target.value);
        }
    });

    // Cập nhật thông tin đặt vé
    function updateBookingSummary() {
        const selectedSeats = [];
        let totalPrice = 0;

        document.querySelectorAll('.seat.selected').forEach((seat) => {
            selectedSeats.push(seat.textContent);

            // Tính giá dựa trên loại ghế
            if (seat.classList.contains('sweetbox')) {
                totalPrice += seatPrices['seat sweetbox'];
            } else if (seat.classList.contains('vip')) {
                totalPrice += seatPrices['seat vip'];
            } else {
                totalPrice += seatPrices['seat'];
            }
        });

        // Cập nhật hiển thị
        document.getElementById('selectedSeatsDisplay').textContent =
            'Ghế đã chọn: ' + (selectedSeats.length > 0 ? selectedSeats.join(', ') : 'Chưa chọn');
        document.getElementById('totalPrice').textContent =
            'Tổng tiền: ' + totalPrice.toLocaleString('vi-VN') + 'đ';
    }

    function xacNhanDatVe() {
        // Thu thập ghế đã chọn
        const selectedSeats = [];
        document.querySelectorAll('.seat.selected').forEach((seat) => {
            selectedSeats.push(seat.textContent);
        });

        if (selectedSeats.length === 0) {
            alert('Vui lòng chọn ít nhất 1 ghế!');
            return;
        }

        // Kiểm tra các field bắt buộc
        const customerName = document.querySelector('input[name="customer_name"]').value;
        const phone = document.querySelector('input[name="phone"]').value;
        const movieId = document.querySelector('input[name="movie_id"], select[name="movie_id"]').value;

        if (!customerName || !phone || !movieId) {
            alert('Vui lòng điền đầy đủ thông tin!');
            return;
        }

        // Kiểm tra suất chiếu đã được chọn
        const showtimeId = document.querySelector('input[name="showtime_id"]:checked');
        if (!showtimeId) {
            alert('Vui lòng chọn suất chiếu!');
            return;
        }

        // Cập nhật input hidden
        document.getElementById('selectedSeats').value = selectedSeats.join(',');

        // Submit form
        document.getElementById('bookingForm').submit();
    }

    // Load ghế đã đặt khi trang được tải
    document.addEventListener('DOMContentLoaded', function() {
        // Kiểm tra nếu có suất chiếu được chọn (từ URL hoặc mặc định)
        const selectedShowtime = document.querySelector('input[name="showtime_id"]:checked');
        if (selectedShowtime) {
            loadBookedSeats(selectedShowtime.value);
        }

        // Thêm event listener cho việc cập nhật booking summary khi chọn ghế
        document.querySelectorAll('.seat').forEach((seat) => {
            seat.addEventListener('click', () => {
                if (!seat.classList.contains('booked')) {
                    seat.classList.toggle('selected');
                    updateBookingSummary();
                }
            });
        });
    });
    </script>
</body>

</html>