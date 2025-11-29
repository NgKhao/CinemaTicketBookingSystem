-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3306
-- Thời gian đã tạo: Th10 29, 2025 lúc 06:06 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `cinema_booking`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booked_seats`
--

CREATE TABLE `booked_seats` (
  `id` int(11) NOT NULL,
  `showtime_id` int(11) DEFAULT NULL,
  `seat_number` varchar(10) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `booked_seats`
--

INSERT INTO `booked_seats` (`id`, `showtime_id`, `seat_number`, `booking_id`, `created_at`) VALUES
(1, 7, 'B6', 2, '2025-08-04 16:10:27'),
(2, 7, 'C8', 2, '2025-08-04 16:10:27'),
(4, 7, 'D3', 3, '2025-08-05 07:31:15'),
(5, 7, 'D2', 3, '2025-08-05 07:31:15'),
(6, 7, 'C10', 4, '2025-08-05 07:39:15'),
(7, 7, 'C9', 4, '2025-08-05 07:39:15'),
(8, 7, 'C6', 5, '2025-08-05 08:39:29'),
(9, 7, 'C5', 5, '2025-08-05 08:39:29'),
(10, 7, 'A3', 6, '2025-08-05 10:56:32'),
(11, 7, 'A4', 6, '2025-08-05 10:56:33'),
(12, 7, 'D1', 6, '2025-08-05 10:56:33'),
(13, 7, 'C1', 7, '2025-08-05 10:57:49'),
(14, 7, 'C2', 7, '2025-08-05 10:57:49'),
(15, 7, 'A8', 8, '2025-08-05 10:59:27'),
(16, 7, 'A7', 8, '2025-08-05 10:59:27'),
(17, 6, 'B3', 9, '2025-08-05 12:19:26'),
(18, 6, 'C3', 9, '2025-08-05 12:19:26');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `showtime_id` int(11) DEFAULT NULL,
  `seats` varchar(255) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('pending','paid','confirmed','cancelled') DEFAULT 'pending',
  `payment_method` enum('counter','vnpay') DEFAULT 'counter',
  `voucher_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `points_earned` int(11) DEFAULT 0,
  `final_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `customer_name`, `phone`, `showtime_id`, `seats`, `total_price`, `booking_date`, `updated_at`, `status`, `payment_method`, `voucher_id`, `discount_amount`, `points_earned`, `final_price`) VALUES
(1, 3, NULL, NULL, 6, 'B11,D12,', 200000.00, '2025-08-04 15:44:07', '2025-08-05 08:33:58', 'pending', 'counter', NULL, 0.00, 0, NULL),
(2, 3, 'Hữu', '0000', 7, 'B6,C8,', 160000.00, '2025-08-04 16:10:27', '2025-08-05 08:33:58', 'paid', 'counter', NULL, 0.00, 0, NULL),
(3, 3, 'Kho', '0111111111', 7, 'D3,D2', 240000.00, '2025-08-05 07:31:15', '2025-08-05 08:33:58', 'pending', 'counter', NULL, 0.00, 0, NULL),
(4, 3, 'Phi', '11111111', 7, 'C10,C9', 160000.00, '2025-08-05 07:39:15', '2025-08-05 08:44:38', 'confirmed', 'counter', NULL, 0.00, 0, NULL),
(5, 3, 'aaaa', '0961749623', 7, 'C6,C5', 160000.00, '2025-08-05 08:39:29', '2025-08-05 08:41:37', 'paid', 'vnpay', NULL, 0.00, 0, NULL),
(6, 5, 'Nhu', '0123456789', 7, 'A3,A4,D1', 240000.00, '2025-08-05 10:56:32', '2025-08-05 10:56:32', 'pending', 'vnpay', NULL, 0.00, 0, NULL),
(7, 5, 'pppp', '01', 7, 'C1,C2', 160000.00, '2025-08-05 10:57:49', '2025-08-05 10:57:49', 'pending', 'vnpay', NULL, 0.00, 0, NULL),
(8, 5, 'we', '011111111', 7, 'A8,A7', 120000.00, '2025-08-05 10:59:27', '2025-08-05 11:00:34', 'paid', 'vnpay', NULL, 0.00, 0, NULL),
(9, 5, 'NoNo', '00000000000', 6, 'B3,C3', 160000.00, '2025-08-05 12:19:26', '2025-08-05 12:22:02', 'confirmed', 'vnpay', NULL, 0.00, 0, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `booking_details`
-- (See below for the actual view)
--
CREATE TABLE `booking_details` (
`booking_id` int(11)
,`customer_name` varchar(255)
,`phone` varchar(20)
,`seats` varchar(255)
,`total_price` decimal(10,2)
,`booking_date` timestamp
,`status` enum('pending','paid','confirmed','cancelled')
,`payment_method` enum('counter','vnpay')
,`username` varchar(100)
,`movie_title` varchar(255)
,`movie_poster` varchar(255)
,`cinema_name` varchar(255)
,`show_date` date
,`show_time` time
,`seat_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Hành Động'),
(2, 'Tình Cảm'),
(3, 'Hoạt Hình'),
(4, 'Kinh Dị'),
(5, 'Hài Hước');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cinemas`
--

CREATE TABLE `cinemas` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `cinemas`
--

INSERT INTO `cinemas` (`id`, `name`, `address`, `image`) VALUES
(1, 'CGV Trần Duy Hưng', '123 Trần Duy Hưng, Hà Nội', './img/cgv_cinema.jpg'),
(2, 'CGV Vincom Bà Triệu', '456 Bà Triệu, Hà Nội', './img/cgv_cinema.jpg'),
(3, 'CGV Royal City', '72A Nguyễn Trãi, Thanh Xuân, Hà Nội', './img/cgv_cinema.jpg'),
(4, 'CGV Indochina Plaza', '241 Xuân Thủy, Cầu Giấy, Hà Nội', './img/cgv_cinema.jpg'),
(5, 'CGV Times City', '458 Minh Khai, Hai Bà Trưng, Hà Nội', './img/cgv_cinema.jpg'),
(6, 'CGV AEON Hà Đông', 'Khu dân cư Hoàng Văn Thụ, Dương Nội, Hà Đông, Hà Nội', './img/cgv_cinema.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `member_tiers`
--

CREATE TABLE `member_tiers` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `min_points` int(11) NOT NULL DEFAULT 0,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `color` varchar(20) DEFAULT NULL,
  `benefits` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `member_tiers`
--

INSERT INTO `member_tiers` (`id`, `name`, `min_points`, `discount_percent`, `color`, `benefits`) VALUES
(1, 'Đồng', 0, 0.00, '#CD7F32', 'Tích điểm cơ bản, Thông báo phim mới'),
(2, 'Bạc', 1000, 5.00, '#C0C0C0', 'Giảm 5% giá vé, Ưu đãi bắp nước'),
(3, 'Vàng', 3000, 10.00, '#FFD700', 'Giảm 10% giá vé, Ưu tiên đặt vé, Vé sinh nhật miễn phí');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `movies`
--

INSERT INTO `movies` (`id`, `title`, `genre`, `duration`, `release_date`, `image`, `description`, `category_id`) VALUES
(1, 'SUPERMAN', 'Hành Động, Hài', '2 giờ 34 phút', '2025-07-11', './img/superman.jpg', 'Siêu anh hùng Superman trở lại', 1),
(2, 'THẾ GIỚI KHỦNG LONG', 'Hành động, tâm lý', '2 giờ 34 phút', '2025-07-04', './img/khunglong.jpg', 'Cuộc phiêu lưu trong thế giới khủng long', 1),
(3, 'WOLFOO & CUỘC ĐUA TAM GIỚI', 'Hoạt hình', '2 giờ 34 phút', '2025-07-11', './img/cuocdua.jpg', 'Phim hoạt hình dành cho trẻ em', 3),
(4, 'MỘT NỬA HOÀN HẢO', 'Tình cảm', '2 giờ 34 phút', '2025-06-13', './img/hoanhao.jpg', 'Câu chuyện tình yêu lãng mạn', 2),
(5, 'ĐIỀU ƯỚC CUỐI CÙNG', 'Tình cảm', '2 giờ 34 phút', '2025-07-14', './img/dieuuoc.jpg', 'Một câu chuyện cảm động', 2),
(6, 'BÍ KÍP LUYỆN RỒNG', 'Hành động, phiêu lưu', '2 giờ 34 phút', '2025-06-13', './img/rong.jpg', 'Phim phiêu lưu hành động', 1),
(7, 'F1', 'Hành động', '2 giờ 34 phút', '2025-06-27', './img/f1.jpg', 'Đua xe tốc độ cao', 1),
(8, 'MARACUDA: NHÓC QUẬY RỪNG XANH', 'Hoạt hình, Phiêu lưu', '2 giờ 34 phút', '2025-07-11', './img/nhocquay.jpg', 'Phim hoạt hình phiêu lưu', 3);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `vnp_txn_ref` varchar(100) NOT NULL COMMENT 'Mã đơn hàng gửi đi VNPay',
  `vnp_transaction_no` varchar(100) DEFAULT NULL COMMENT 'Mã giao dịch tại VNPay',
  `vnp_amount` decimal(15,2) NOT NULL COMMENT 'Số tiền thanh toán',
  `vnp_response_code` varchar(10) NOT NULL COMMENT 'Mã kết quả từ VNPay',
  `vnp_bank_code` varchar(20) DEFAULT NULL COMMENT 'Mã ngân hàng',
  `vnp_pay_date` varchar(20) DEFAULT NULL COMMENT 'Thời gian thanh toán tại VNPay',
  `vnp_order_info` text DEFAULT NULL COMMENT 'Thông tin đơn hàng',
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bảng lưu thông tin giao dịch VNPay';

--
-- Đang đổ dữ liệu cho bảng `payment_transactions`
--

INSERT INTO `payment_transactions` (`id`, `booking_id`, `vnp_txn_ref`, `vnp_transaction_no`, `vnp_amount`, `vnp_response_code`, `vnp_bank_code`, `vnp_pay_date`, `vnp_order_info`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 'CGV202508051539295', '15117486', 160000.00, '00', 'NCB', '20250805154232', NULL, 'pending', '2025-08-05 08:41:37', '2025-08-05 08:41:37'),
(2, 8, 'CGV202508051759278', '15117746', 120000.00, '00', 'NCB', '20250805180133', NULL, 'pending', '2025-08-05 11:00:34', '2025-08-05 11:00:34'),
(3, 9, 'CGV202508051919269', '15117801', 160000.00, '00', 'NCB', '20250805192144', NULL, 'pending', '2025-08-05 12:20:42', '2025-08-05 12:20:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `point_history`
--

CREATE TABLE `point_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `points` int(11) NOT NULL,
  `type` enum('earn','spend','bonus','adjust') DEFAULT 'earn',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `movie_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `movie_id`, `rating`, `comment`, `created_at`) VALUES
(1, 3, 6, 4, 'aaassss', '2025-08-05 09:57:09'),
(2, 5, 6, 3, 'qqqqq', '2025-08-05 09:58:07');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `showtimes`
--

CREATE TABLE `showtimes` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) DEFAULT NULL,
  `cinema_id` int(11) DEFAULT NULL,
  `show_date` date DEFAULT NULL,
  `show_time` time DEFAULT NULL,
  `total_seats` int(11) DEFAULT 60,
  `available_seats` int(11) DEFAULT 60
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `showtimes`
--

INSERT INTO `showtimes` (`id`, `movie_id`, `cinema_id`, `show_date`, `show_time`, `total_seats`, `available_seats`) VALUES
(1, 1, 1, '2025-12-05', '10:30:00', 60, 60),
(2, 1, 1, '2025-12-05', '15:00:00', 60, 60),
(3, 1, 1, '2025-12-05', '19:20:00', 60, 60),
(4, 2, 2, '2025-12-06', '17:00:00', 60, 60),
(5, 3, 3, '2025-12-06', '14:30:00', 60, 60),
(6, 7, 6, '2025-12-10', '19:20:00', 60, 58),
(7, 6, 6, '2025-12-15', '10:30:00', 60, 44),
(8, 8, 5, '2025-12-15', '13:00:00', 60, 60),
(9, 3, 5, '2025-12-20', '10:00:00', 60, 60),
(10, 6, 1, '2025-12-12', '11:00:00', 60, 60),
(11, 8, 6, '2025-12-25', '20:30:00', 60, 60),
(12, 6, 1, '2025-11-30', '13:00:00', 60, 60);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `member_points` int(11) DEFAULT 0,
  `member_tier_id` int(11) DEFAULT 1,
  `birthday` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `member_points`, `member_tier_id`, `birthday`, `phone`) VALUES
(2, 'user1', 'user1@gmail.com', 'password123', 'customer', '2025-08-02 13:59:14', 0, 1, NULL, NULL),
(3, 'teonv', 'aaa@gmail.com', '$2y$10$AYIeNdHFj1wgbTwg1BC77Och49ImItaN/Y/zRQlz7mWO9K5GERWM6', 'customer', '2025-08-04 15:28:44', 0, 1, NULL, NULL),
(4, 'admin', 'admin@cgv.com', '$2y$10$1ArV2cY67IspvapL2Ui.iOj.F8fJm9i0jNZmEu54f0rYd5KdohKly', 'admin', '2025-08-04 16:52:26', 0, 1, NULL, NULL),
(5, 'nguyenA', 'nguyenA@gmail.com', '$2y$10$UFXVwIKw43aOa6vTPFj2zOnFSYrQozDZTFL..3nnTFxVlH6RTyezm', 'customer', '2025-08-04 17:54:03', 0, 1, NULL, NULL),
(6, 'hong', 'ttt@gmail.com', '$2y$10$lvUwsaAWqFYjQdRPnob5TeY1PthflmnXX77oJQ7wiMjGtsHfYO8ba', 'customer', '2025-08-06 11:15:53', 0, 1, NULL, NULL),
(7, 'khao', '123@gmail.com', '$2y$10$oLwk8nUVsDdFOK8ibAD7Quo7Ds5tcx2YgN.UO5eSpNv5KHxtZljMy', 'customer', '2025-11-28 08:24:26', 0, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` enum('percent','fixed') DEFAULT 'percent',
  `value` decimal(10,2) NOT NULL,
  `min_order` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `usage_limit` int(11) DEFAULT 1,
  `used_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `type`, `value`, `min_order`, `max_discount`, `start_date`, `end_date`, `usage_limit`, `used_count`, `is_active`, `description`, `created_at`) VALUES
(1, 'WELCOME2025', 'percent', 10.00, 0.00, NULL, '2025-11-29', '2025-12-31', 100, 0, 1, 'Giảm 10% cho thành viên mới', '2025-11-29 17:06:00'),
(2, 'CGV100K', 'fixed', 100000.00, 500000.00, NULL, '2025-11-29', '2025-12-31', 50, 0, 1, 'Giảm 100k cho đơn từ 500k', '2025-11-29 17:06:00'),
(3, 'BIRTHDAY', 'percent', 50.00, 0.00, NULL, '2025-11-29', '2025-12-31', 1, 0, 1, 'Giảm 50% sinh nhật', '2025-11-29 17:06:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `voucher_usage`
--

CREATE TABLE `voucher_usage` (
  `id` int(11) NOT NULL,
  `voucher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `booking_details`
--
DROP TABLE IF EXISTS `booking_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `booking_details`  AS SELECT `b`.`id` AS `booking_id`, `b`.`customer_name` AS `customer_name`, `b`.`phone` AS `phone`, `b`.`seats` AS `seats`, `b`.`total_price` AS `total_price`, `b`.`booking_date` AS `booking_date`, `b`.`status` AS `status`, `b`.`payment_method` AS `payment_method`, `u`.`username` AS `username`, `m`.`title` AS `movie_title`, `m`.`image` AS `movie_poster`, `c`.`name` AS `cinema_name`, `s`.`show_date` AS `show_date`, `s`.`show_time` AS `show_time`, (select count(0) from `booked_seats` `bs` where `bs`.`booking_id` = `b`.`id`) AS `seat_count` FROM ((((`bookings` `b` join `users` `u` on(`b`.`user_id` = `u`.`id`)) join `showtimes` `s` on(`b`.`showtime_id` = `s`.`id`)) join `movies` `m` on(`s`.`movie_id` = `m`.`id`)) join `cinemas` `c` on(`s`.`cinema_id` = `c`.`id`)) ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `booked_seats`
--
ALTER TABLE `booked_seats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_seat_showtime` (`showtime_id`,`seat_number`),
  ADD KEY `idx_showtime_seat` (`showtime_id`,`seat_number`),
  ADD KEY `fk_booked_seats_booking` (`booking_id`);

--
-- Chỉ mục cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_showtime_status` (`showtime_id`,`status`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `cinemas`
--
ALTER TABLE `cinemas`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `member_tiers`
--
ALTER TABLE `member_tiers`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Chỉ mục cho bảng `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_vnp_txn_ref` (`vnp_txn_ref`),
  ADD KEY `idx_vnp_transaction_no` (`vnp_transaction_no`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Chỉ mục cho bảng `point_history`
--
ALTER TABLE `point_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Chỉ mục cho bảng `showtimes`
--
ALTER TABLE `showtimes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`),
  ADD KEY `cinema_id` (`cinema_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `voucher_usage`
--
ALTER TABLE `voucher_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher_id` (`voucher_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `booked_seats`
--
ALTER TABLE `booked_seats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `cinemas`
--
ALTER TABLE `cinemas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `member_tiers`
--
ALTER TABLE `member_tiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `point_history`
--
ALTER TABLE `point_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `showtimes`
--
ALTER TABLE `showtimes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `voucher_usage`
--
ALTER TABLE `voucher_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `booked_seats`
--
ALTER TABLE `booked_seats`
  ADD CONSTRAINT `booked_seats_ibfk_1` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`id`),
  ADD CONSTRAINT `booked_seats_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `fk_booked_seats_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booked_seats_showtime` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`id`),
  ADD CONSTRAINT `fk_bookings_showtime` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `movies`
--
ALTER TABLE `movies`
  ADD CONSTRAINT `movies_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Các ràng buộc cho bảng `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `point_history`
--
ALTER TABLE `point_history`
  ADD CONSTRAINT `point_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `point_history_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`);

--
-- Các ràng buộc cho bảng `showtimes`
--
ALTER TABLE `showtimes`
  ADD CONSTRAINT `showtimes_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`),
  ADD CONSTRAINT `showtimes_ibfk_2` FOREIGN KEY (`cinema_id`) REFERENCES `cinemas` (`id`);

--
-- Các ràng buộc cho bảng `voucher_usage`
--
ALTER TABLE `voucher_usage`
  ADD CONSTRAINT `voucher_usage_ibfk_1` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`),
  ADD CONSTRAINT `voucher_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `voucher_usage_ibfk_3` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
