<?php
session_start();
require_once 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

// Lấy các tham số lọc từ GET request
$filter_type = $_GET['filter_type'] ?? 'daily';
$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-7 days'));
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$selected_month = $_GET['month'] ?? date('Y-m');
$selected_year = $_GET['year'] ?? date('Y');

// Thống kê tổng quan
$stats = [];

// Thống kê phim
$stmt = $pdo->prepare("
    SELECT m.title, COUNT(b.id) as bookings, COUNT(bs.id) as tickets_sold
    FROM movies m
    LEFT JOIN showtimes s ON m.id = s.movie_id
    LEFT JOIN bookings b ON s.id = b.showtime_id
    LEFT JOIN booked_seats bs ON b.id = bs.booking_id
    GROUP BY m.id
    ORDER BY tickets_sold DESC
    LIMIT 10
");
$stmt->execute();
$popular_movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê doanh thu theo ngày (tùy chọn khoảng thời gian)
if ($filter_type == 'daily') {
    $stmt = $pdo->prepare("
        SELECT DATE(booking_date) as date,
               COUNT(*) as total_bookings,
               COUNT(bs.id) as total_tickets,
               SUM(COALESCE(b.final_price, b.total_price)) as revenue
        FROM bookings b
        LEFT JOIN booked_seats bs ON b.id = bs.booking_id
        WHERE b.status IN ('paid', 'confirmed')
        AND DATE(booking_date) BETWEEN :from_date AND :to_date
        GROUP BY DATE(booking_date)
        ORDER BY date ASC
    ");
    $stmt->execute(['from_date' => $from_date, 'to_date' => $to_date]);
    $daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $daily_stats = [];
}

// Thống kê doanh thu theo tháng (theo năm được chọn)
if ($filter_type == 'monthly') {
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(booking_date, '%Y-%m') as month,
               COUNT(*) as total_bookings,
               COUNT(bs.id) as total_tickets,
               SUM(COALESCE(b.final_price, b.total_price)) as revenue
        FROM bookings b
        LEFT JOIN booked_seats bs ON b.id = bs.booking_id
        WHERE b.status IN ('paid', 'confirmed')
        AND YEAR(booking_date) = :year
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->execute(['year' => $selected_year]);
    $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $monthly_stats = [];
}

// Thống kê doanh thu theo năm (5 năm gần nhất)
if ($filter_type == 'yearly') {
    $stmt = $pdo->prepare("
        SELECT YEAR(booking_date) as year,
               COUNT(*) as total_bookings,
               COUNT(bs.id) as total_tickets,
               SUM(COALESCE(b.final_price, b.total_price)) as revenue
        FROM bookings b
        LEFT JOIN booked_seats bs ON b.id = bs.booking_id
        WHERE b.status IN ('paid', 'confirmed')
        GROUP BY YEAR(booking_date)
        ORDER BY year ASC
        LIMIT 5
    ");
    $stmt->execute();
    $yearly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $yearly_stats = [];
}

// Thống kê rạp
$stmt = $pdo->prepare("
    SELECT c.name, COUNT(b.id) as bookings, COUNT(bs.id) as tickets_sold
    FROM cinemas c
    LEFT JOIN showtimes s ON c.id = s.cinema_id
    LEFT JOIN bookings b ON s.id = b.showtime_id
    LEFT JOIN booked_seats bs ON b.id = bs.booking_id
    GROUP BY c.id
    ORDER BY tickets_sold DESC
");
$stmt->execute();
$cinema_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tổng quan hệ thống
$stmt = $pdo->prepare("SELECT COUNT(*) FROM movies");
$stmt->execute();
$total_movies = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$stmt->execute();
$total_customers = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status IN ('paid', 'confirmed')");
$stmt->execute();
$total_bookings = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(COALESCE(final_price, total_price)) FROM bookings WHERE status IN ('paid', 'confirmed')");
$stmt->execute();
$total_revenue = $stmt->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html>

<head>
    <title>Thống Kê - Admin CGV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            padding: 20px;
            max-width: 1400px;
        }

        .admin-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: white;
        }

        .stats-movies {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
        }

        .stats-customers {
            background: linear-gradient(45deg, #4834d4, #686de0);
        }

        .stats-bookings {
            background: linear-gradient(45deg, #00d2d3, #01a3a4);
        }

        .stats-revenue {
            background: linear-gradient(45deg, #feca57, #ff9ff3);
        }

        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }

        .filter-section label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            display: block;
        }

        .btn-filter {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-reset {
            background: linear-gradient(45deg, #95a5a6, #7f8c8d);
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin: 30px 0;
            padding: 20px;
            background: white;
            border-radius: 10px;
        }

        .stats-table {
            margin-top: 20px;
        }

        .stats-table th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
            border: none;
            padding: 12px;
        }

        .stats-table td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }

        .stats-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .revenue-highlight {
            color: #feca57;
            font-weight: bold;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid;
            border-image: linear-gradient(to right, #667eea, #764ba2) 1;
        }

        .filter-type-active {
            background: linear-gradient(45deg, #667eea, #764ba2) !important;
            color: white !important;
            border-color: transparent !important;
        }

        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-card">
            <h1><i class="bi bi-graph-up"></i> Thống Kê Hệ Thống CGV</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin.php">Trang chủ Admin</a></li>
                    <li class="breadcrumb-item active">Thống Kê</li>
                </ol>
            </nav>
        </div>

        <!-- Thống kê tổng quan -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card stats-movies">
                    <i class="bi bi-film" style="font-size: 2.5rem;"></i>
                    <h3><?php echo $total_movies; ?></h3>
                    <p>Tổng số phim</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-customers">
                    <i class="bi bi-people" style="font-size: 2.5rem;"></i>
                    <h3><?php echo $total_customers; ?></h3>
                    <p>Khách hàng</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-bookings">
                    <i class="bi bi-ticket-perforated" style="font-size: 2.5rem;"></i>
                    <h3><?php echo $total_bookings; ?></h3>
                    <p>Vé đã đặt</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card stats-revenue">
                    <i class="bi bi-currency-dollar" style="font-size: 2.5rem;"></i>
                    <h3><?php echo number_format($total_revenue / 1000); ?>K</h3>
                    <p>Doanh thu (VNĐ)</p>
                </div>
            </div>
        </div>



        <!-- Bộ lọc thống kê -->
        <div class="admin-card">
            <div class="section-title">
                <i class="bi bi-funnel"></i> Bộ Lọc Thống Kê Doanh Thu
            </div>

            <form method="GET" action="admin_stats.php" id="filterForm">
                <div class="filter-section">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label>Loại thống kê:</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button"
                                    class="btn btn-outline-primary filter-type-btn <?php echo $filter_type == 'daily' ? 'filter-type-active' : ''; ?>"
                                    data-type="daily">
                                    <i class="bi bi-calendar-day"></i> Theo Ngày
                                </button>
                                <button type="button"
                                    class="btn btn-outline-primary filter-type-btn <?php echo $filter_type == 'monthly' ? 'filter-type-active' : ''; ?>"
                                    data-type="monthly">
                                    <i class="bi bi-calendar-month"></i> Theo Tháng
                                </button>
                                <button type="button"
                                    class="btn btn-outline-primary filter-type-btn <?php echo $filter_type == 'yearly' ? 'filter-type-active' : ''; ?>"
                                    data-type="yearly">
                                    <i class="bi bi-calendar3"></i> Theo Năm
                                </button>
                            </div>
                            <input type="hidden" name="filter_type" id="filter_type"
                                value="<?php echo $filter_type; ?>">
                        </div>
                    </div>

                    <!-- Bộ lọc theo ngày -->
                    <div id="daily-filter" class="filter-options"
                        style="display: <?php echo $filter_type == 'daily' ? 'block' : 'none'; ?>;">
                        <div class="row">
                            <div class="col-md-5">
                                <label>Từ ngày:</label>
                                <input type="text" class="form-control" name="from_date" id="from_date"
                                    value="<?php echo $from_date; ?>" placeholder="Chọn ngày bắt đầu">
                            </div>
                            <div class="col-md-5">
                                <label>Đến ngày:</label>
                                <input type="text" class="form-control" name="to_date" id="to_date"
                                    value="<?php echo $to_date; ?>" placeholder="Chọn ngày kết thúc">
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-filter w-100">
                                    <i class="bi bi-search"></i> Lọc
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Bộ lọc theo tháng -->
                    <div id="monthly-filter" class="filter-options"
                        style="display: <?php echo $filter_type == 'monthly' ? 'block' : 'none'; ?>;">
                        <div class="row">
                            <div class="col-md-8">
                                <label>Chọn năm:</label>
                                <select class="form-select" name="year" id="year_select">
                                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                        <option value="<?php echo $y; ?>"
                                            <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                                            Năm <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-filter w-100">
                                    <i class="bi bi-search"></i> Lọc
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Bộ lọc theo năm -->
                    <div id="yearly-filter" class="filter-options"
                        style="display: <?php echo $filter_type == 'yearly' ? 'block' : 'none'; ?>;">
                        <div class="row">
                            <div class="col-md-12">
                                <p class="text-muted mb-0">
                                    <i class="bi bi-info-circle"></i> Hiển thị thống kê doanh thu theo năm (tối đa 5 năm
                                    gần nhất)
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12 text-end">
                            <a href="admin_stats.php" class="btn btn-reset">
                                <i class="bi bi-arrow-counterclockwise"></i> Đặt lại
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Thống kê doanh thu -->
        <div class="admin-card">
            <div class="section-title">
                <i class="bi bi-graph-up-arrow"></i> Biểu Đồ Doanh Thu
            </div>

            <!-- Theo Ngày -->
            <?php if ($filter_type == 'daily' && count($daily_stats) > 0): ?>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="table-responsive stats-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Số đơn</th>
                                <th>Vé bán</th>
                                <th>Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_bookings_sum = 0;
                            $total_tickets_sum = 0;
                            $total_revenue_sum = 0;
                            foreach ($daily_stats as $stat):
                                $total_bookings_sum += $stat['total_bookings'];
                                $total_tickets_sum += $stat['total_tickets'] ?: 0;
                                $total_revenue_sum += $stat['revenue'] ?: 0;
                            ?>
                                <tr>
                                    <td><?php echo date('d/m/Y (l)', strtotime($stat['date'])); ?></td>
                                    <td><?php echo $stat['total_bookings']; ?></td>
                                    <td><?php echo $stat['total_tickets'] ?: 0; ?></td>
                                    <td class="revenue-highlight"><?php echo number_format($stat['revenue'] ?: 0); ?>đ</td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                <td>TỔNG CỘNG</td>
                                <td><?php echo $total_bookings_sum; ?></td>
                                <td><?php echo $total_tickets_sum; ?></td>
                                <td class="revenue-highlight"><?php echo number_format($total_revenue_sum); ?>đ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($filter_type == 'daily'): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Không có dữ liệu trong khoảng thời gian đã chọn.
                </div>
            <?php endif; ?>

            <!-- Theo Tháng -->
            <?php if ($filter_type == 'monthly' && count($monthly_stats) > 0): ?>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="table-responsive stats-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Tháng</th>
                                <th>Số đơn</th>
                                <th>Vé bán</th>
                                <th>Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_bookings_sum = 0;
                            $total_tickets_sum = 0;
                            $total_revenue_sum = 0;
                            foreach ($monthly_stats as $stat):
                                $total_bookings_sum += $stat['total_bookings'];
                                $total_tickets_sum += $stat['total_tickets'] ?: 0;
                                $total_revenue_sum += $stat['revenue'] ?: 0;
                            ?>
                                <tr>
                                    <td>Tháng <?php echo date('m/Y', strtotime($stat['month'] . '-01')); ?></td>
                                    <td><?php echo $stat['total_bookings']; ?></td>
                                    <td><?php echo $stat['total_tickets'] ?: 0; ?></td>
                                    <td class="revenue-highlight"><?php echo number_format($stat['revenue'] ?: 0); ?>đ</td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                <td>TỔNG NĂM <?php echo $selected_year; ?></td>
                                <td><?php echo $total_bookings_sum; ?></td>
                                <td><?php echo $total_tickets_sum; ?></td>
                                <td class="revenue-highlight"><?php echo number_format($total_revenue_sum); ?>đ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($filter_type == 'monthly'): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Không có dữ liệu năm <?php echo $selected_year; ?>.
                </div>
            <?php endif; ?>

            <!-- Theo Năm -->
            <?php if ($filter_type == 'yearly' && count($yearly_stats) > 0): ?>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="table-responsive stats-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Năm</th>
                                <th>Số đơn</th>
                                <th>Vé bán</th>
                                <th>Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($yearly_stats as $stat): ?>
                                <tr>
                                    <td>Năm <?php echo $stat['year']; ?></td>
                                    <td><?php echo $stat['total_bookings']; ?></td>
                                    <td><?php echo $stat['total_tickets'] ?: 0; ?></td>
                                    <td class="revenue-highlight"><?php echo number_format($stat['revenue'] ?: 0); ?>đ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($filter_type == 'yearly'): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Chưa có dữ liệu thống kê theo năm.
                </div>
            <?php endif; ?>
        </div>

        <!-- Top phim bán chạy -->
        <div class="admin-card">
            <h3><i class="bi bi-trophy"></i> Top Phim Bán Chạy</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Hạng</th>
                            <th>Tên Phim</th>
                            <th>Số lượt đặt</th>
                            <th>Vé đã bán</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_movies as $index => $movie): ?>
                            <tr>
                                <td>
                                    <?php if ($index < 3): ?>
                                        <span class="badge bg-warning">#{<?php echo $index + 1; ?>}</span>
                                    <?php else: ?>
                                        #{<?php echo $index + 1; ?>}
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $movie['title']; ?></td>
                                <td><?php echo $movie['bookings'] ?: 0; ?> lượt</td>
                                <td><?php echo $movie['tickets_sold'] ?: 0; ?> vé</td>
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
            <a href="admin_users.php" class="btn btn-primary me-2">
                <i class="bi bi-people"></i> Quản lý khách hàng
            </a>
            <a href="admin_vouchers.php" class="btn btn-success me-2">
                <i class="bi bi-ticket-detailed"></i> Quản lý voucher
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="bi bi-power"></i> Đăng xuất
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Khởi tạo Flatpickr cho date picker
        flatpickr("#from_date", {
            dateFormat: "Y-m-d",
            maxDate: "today"
        });

        flatpickr("#to_date", {
            dateFormat: "Y-m-d",
            maxDate: "today"
        });

        // Xử lý chuyển đổi loại thống kê
        document.querySelectorAll('.filter-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.dataset.type;

                // Cập nhật active class
                document.querySelectorAll('.filter-type-btn').forEach(b => b.classList.remove(
                    'filter-type-active'));
                this.classList.add('filter-type-active');

                // Cập nhật hidden input
                document.getElementById('filter_type').value = type;

                // Hiển thị/ẩn bộ lọc tương ứng
                document.querySelectorAll('.filter-options').forEach(opt => opt.style.display = 'none');
                document.getElementById(type + '-filter').style.display = 'block';

                // Nếu chọn "Theo Năm" thì submit luôn
                if (type === 'yearly') {
                    document.getElementById('filterForm').submit();
                }
            });
        });

        // Vẽ biểu đồ
        <?php if (($filter_type == 'daily' && count($daily_stats) > 0) || ($filter_type == 'monthly' && count($monthly_stats) > 0) || ($filter_type == 'yearly' && count($yearly_stats) > 0)): ?>
                (function() {
                    // Dữ liệu cho biểu đồ
                    let chartData, chartLabels, chartTitle, chartType, chartColor, chartBorderColor;

                    <?php if ($filter_type == 'daily' && count($daily_stats) > 0): ?>
                        chartData = <?php echo json_encode($daily_stats); ?>;
                        chartLabels = chartData.map(d => {
                            const date = new Date(d.date);
                            return date.toLocaleDateString('vi-VN', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric'
                            });
                        });
                        chartTitle =
                            'Doanh thu từ <?php echo date("d/m/Y", strtotime($from_date)); ?> đến <?php echo date("d/m/Y", strtotime($to_date)); ?>';
                        chartType = 'bar';
                        chartColor = 'rgba(52, 152, 219, 0.8)';
                        chartBorderColor = 'rgba(52, 152, 219, 1)';
                    <?php elseif ($filter_type == 'monthly' && count($monthly_stats) > 0): ?>
                        chartData = <?php echo json_encode($monthly_stats); ?>;
                        chartLabels = chartData.map(d => {
                            const [year, month] = d.month.split('-');
                            return `Tháng ${month}/${year}`;
                        });
                        chartTitle = 'Doanh thu từng tháng năm <?php echo $selected_year; ?>';
                        chartType = 'line';
                        chartColor = 'rgba(46, 204, 113, 0.3)';
                        chartBorderColor = 'rgba(46, 204, 113, 1)';
                    <?php elseif ($filter_type == 'yearly' && count($yearly_stats) > 0): ?>
                        chartData = <?php echo json_encode($yearly_stats); ?>;
                        chartLabels = chartData.map(d => `Năm ${d.year}`);
                        chartTitle = 'Doanh thu theo năm';
                        chartType = 'bar';
                        chartColor = 'rgba(155, 89, 182, 0.8)';
                        chartBorderColor = 'rgba(155, 89, 182, 1)';
                    <?php endif; ?>

                    const ctx = document.getElementById('revenueChart').getContext('2d');
                    new Chart(ctx, {
                        type: chartType,
                        data: {
                            labels: chartLabels,
                            datasets: [{
                                label: 'Doanh thu (VNĐ)',
                                data: chartData.map(d => d.revenue || 0),
                                backgroundColor: chartColor,
                                borderColor: chartBorderColor,
                                borderWidth: 2,
                                fill: chartType === 'line',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        font: {
                                            size: 14
                                        },
                                        color: '#2c3e50'
                                    }
                                },
                                title: {
                                    display: true,
                                    text: chartTitle,
                                    font: {
                                        size: 18,
                                        weight: 'bold'
                                    },
                                    color: '#2c3e50',
                                    padding: 20
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                                        },
                                        color: '#7f8c8d'
                                    },
                                    grid: {
                                        color: '#ecf0f1'
                                    }
                                },
                                x: {
                                    ticks: {
                                        color: '#7f8c8d'
                                    },
                                    grid: {
                                        color: '#ecf0f1'
                                    }
                                }
                            }
                        }
                    });
                })();
        <?php endif; ?>
    </script>
</body>

</html>