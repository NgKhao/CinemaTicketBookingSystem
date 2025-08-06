-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost:3308
-- Thời gian đã tạo: Th8 05, 2025 lúc 11:17 AM
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
(9, 7, 'C5', 5, '2025-08-05 08:39:29');

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
  `payment_method` enum('counter','vnpay') DEFAULT 'counter'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `customer_name`, `phone`, `showtime_id`, `seats`, `total_price`, `booking_date`, `updated_at`, `status`, `payment_method`) VALUES
(1, 3, NULL, NULL, 6, 'B11,D12,', 200000.00, '2025-08-04 15:44:07', '2025-08-05 08:33:58', 'pending', 'counter'),
(2, 3, 'Hữu', '0000', 7, 'B6,C8,', 160000.00, '2025-08-04 16:10:27', '2025-08-05 08:33:58', 'paid', 'counter'),
(3, 3, 'Kho', '0111111111', 7, 'D3,D2', 240000.00, '2025-08-05 07:31:15', '2025-08-05 08:33:58', 'pending', 'counter'),
(4, 3, 'Phi', '11111111', 7, 'C10,C9', 160000.00, '2025-08-05 07:39:15', '2025-08-05 08:44:38', 'confirmed', 'counter'),
(5, 3, 'aaaa', '0961749623', 7, 'C6,C5', 160000.00, '2025-08-05 08:39:29', '2025-08-05 08:41:37', 'paid', 'vnpay');

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
(1, 5, 'CGV202508051539295', '15117486', 160000.00, '00', 'NCB', '20250805154232', NULL, 'pending', '2025-08-05 08:41:37', '2025-08-05 08:41:37');

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
(1, 1, 1, '2025-08-03', '10:30:00', 60, 60),
(2, 1, 1, '2025-08-03', '15:00:00', 60, 60),
(3, 1, 1, '2025-08-03', '19:20:00', 60, 60),
(4, 2, 2, '2025-08-03', '17:00:00', 60, 60),
(5, 3, 3, '2025-08-03', '14:30:00', 60, 60),
(6, 7, 6, '2025-08-05', '19:20:00', 60, 60),
(7, 6, 6, '2025-08-14', '10:30:00', 60, 51),
(8, 8, 5, '2025-08-14', '13:00:00', 60, 60),
(9, 3, 5, '2025-08-19', '10:00:00', 60, 60),
(10, 6, 1, '2025-08-10', '11:00:00', 60, 60);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(2, 'user1', 'user1@gmail.com', 'password123', 'customer', '2025-08-02 13:59:14'),
(3, 'teonv', 'aaa@gmail.com', '$2y$10$AYIeNdHFj1wgbTwg1BC77Och49ImItaN/Y/zRQlz7mWO9K5GERWM6', 'customer', '2025-08-04 15:28:44'),
(4, 'admin', 'admin@cgv.com', '$2y$10$1ArV2cY67IspvapL2Ui.iOj.F8fJm9i0jNZmEu54f0rYd5KdohKly', 'admin', '2025-08-04 16:52:26'),
(5, 'nguyenA', 'nguyenA@gmail.com', '$2y$10$UFXVwIKw43aOa6vTPFj2zOnFSYrQozDZTFL..3nnTFxVlH6RTyezm', 'customer', '2025-08-04 17:54:03');

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
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `booked_seats`
--
ALTER TABLE `booked_seats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT cho bảng `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `showtimes`
--
ALTER TABLE `showtimes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
