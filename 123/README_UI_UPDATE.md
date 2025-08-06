# Hệ Thống Đặt Vé Xem Phim CGV - Phiên bản mới

## Những thay đổi chính

### 1. Thiết kế UI mới

- **Loại bỏ các phần đặt vé trực tiếp** trong thanh navigation và sidebar
- **Thiết kế đơn giản**, phù hợp với sinh viên mới học web
- **Giữ nguyên tông màu đỏ CGV** (#e50914) đặc trưng
- **Responsive design** hoạt động tốt trên mobile và desktop

### 2. Luồng đặt vé mới

Thay vì đặt vé trực tiếp, người dùng phải:

1. **Chọn phim** từ trang chủ hoặc danh mục
2. **Xem chi tiết phim** với thông tin đầy đủ
3. **Chọn suất chiếu** phù hợp
4. **Đặt vé** cho suất chiếu đã chọn

### 3. Trang chi tiết phim mới

- **Hiển thị đầy đủ thông tin**: poster, mô tả, thể loại, thời lượng
- **Hệ thống đánh giá**: 5 sao với bình luận
- **Lịch chiếu tích hợp**: xem và đặt vé trực tiếp
- **Giao diện chuyên nghiệp**: gradient, shadow, animation

### 4. Các trang được thiết kế lại

#### Trang chủ (index.php)

- Loại bỏ form đặt vé trong sidebar
- Tập trung vào giới thiệu CGV
- Grid phim với nút "Xem chi tiết"

#### Trang danh mục phim (movies.php)

- Navigation sạch sẽ, không có link đặt vé
- Danh sách phim theo thể loại
- Chuyển hướng đến chi tiết phim

#### Trang thành viên (list_user.html)

- **Chương trình thành viên CGV** với các hạng Bronze, Silver, Gold
- **Quyền lợi thành viên**: tích điểm, ưu đãi, quà sinh nhật
- **Hướng dẫn đăng ký** rõ ràng với 3 bước

#### Trang tuyển dụng (ap.html)

- **Danh sách vị trí tuyển dụng**: Marketing, Bán vé, Kỹ thuật
- **Thông tin chi tiết**: mô tả công việc, yêu cầu, mức lương
- **Quy trình ứng tuyển** 3 bước
- **Thông tin liên hệ** đầy đủ

#### Trang tìm kiếm (search_movies.php)

- **Tìm kiếm thông minh** theo tên, thể loại, mô tả
- **Kết quả trực quan** với grid layout
- **Thông báo rõ ràng** khi không có kết quả

### 5. Chức năng đánh giá phim

- **Form đánh giá** với rating 5 sao và bình luận
- **Hiển thị rating trung bình** và tổng số đánh giá
- **Danh sách đánh giá** của người dùng khác
- **Xác thực đăng nhập** để viết đánh giá

### 6. Cải thiện CSS

- **Grid layout** responsive cho tất cả danh sách
- **Card design** với shadow và hover effects
- **Color scheme** nhất quán với màu CGV
- **Typography** rõ ràng và dễ đọc
- **Animation** mượt mà cho user experience

## Cách sử dụng

### Cho người dùng thông thường:

1. Truy cập `index.php` để xem phim đang chiếu
2. Click "Xem chi tiết" trên phim muốn xem
3. Đọc thông tin phim và xem đánh giá
4. Chọn suất chiếu phù hợp
5. Đăng nhập và đặt vé

### Cho admin:

- Truy cập `admin.php` để quản lý hệ thống
- Quản lý phim, rạp, suất chiếu
- Xem thống kê đặt vé

## Files chính được cập nhật:

- `index.php` - Trang chủ mới
- `movies.php` - Danh sách phim theo thể loại
- `movie_detail.php` - Chi tiết phim với đánh giá (MỚI)
- `submit_review.php` - Xử lý đánh giá (MỚI)
- `list_user.html` - Trang thành viên mới
- `ap.html` - Trang tuyển dụng mới
- `search_movies.php` - Tìm kiếm cải tiến
- `list_cgv.php` - Danh sách rạp
- `get_movies.php` - API lấy danh sách phim
- `ASST1.css` - CSS mới với responsive design

## Công nghệ sử dụng:

- **PHP 8+** với PDO
- **MySQL** database
- **HTML5** semantic
- **CSS3** với Grid và Flexbox
- **JavaScript** vanilla cho interaction
- **Font Awesome** cho icons
- **VNPay** payment integration

## Tính năng nổi bật:

- ✅ Luồng đặt vé hợp lý: Phim → Chi tiết → Suất chiếu → Đặt vé
- ✅ Hệ thống đánh giá phim 5 sao
- ✅ Responsive design cho mọi thiết bị
- ✅ UI/UX đơn giản, thân thiện
- ✅ Navigation nhất quán trên tất cả trang
- ✅ Tích hợp thanh toán VNPay
- ✅ Quản lý session và authentication
- ✅ Database schema tối ưu với foreign keys
