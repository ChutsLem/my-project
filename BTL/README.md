# HỆ THỐNG QUẢN LÝ DỊCH VỤ ĐẶT LỊCH LÀM ĐẸP (BEAUTY SALON)
> **BÀI TẬP LỚN LẬP TRÌNH WEB - ĐỀ TÀI 19**  
> Công nghệ: **PHP, MySQL (XAMPP), HTML5, CSS3, JavaScript, Bootstrap 5, Chart.js**

---

## 📌 GIỚI THIỆU ĐỀ TÀI
Hệ thống giải quyết bài toán quản lý hoạt động vận hành của một thẩm mỹ viện/spa/salon làm đẹp: cho phép khách hàng đăng ký tài khoản, tìm kiếm dịch vụ, chủ động chọn chuyên viên và đặt lịch hẹn theo khung giờ làm việc phù hợp; đồng thời hỗ trợ quản trị viên và nhân viên tiếp nhận, xác nhận, thực hiện dịch vụ, lập hóa đơn thanh toán và theo dõi báo cáo doanh thu trực quan.

### 🎯 Tiêu chí nghiệp vụ cốt lõi (Đề tài 19):
1. **Không cho phép một nhân viên nhận hai lịch trùng thời gian**: Thuật toán tự động quét khoảng thời gian `(start_time, end_time)` của dịch vụ và loại trừ mọi khung giờ đã có khách đặt của nhân viên đó.
2. **Chỉ đặt được lịch trong khung giờ làm việc**: Khách hàng chỉ có thể chọn giờ nằm trong ca trực hoạt động (Thứ 2 - CN) của chuyên viên, tự động chặn ngày nghỉ (`is_off = 1`).
3. **Phải lưu lịch sử đặt, hủy và thay đổi lịch**: Bảng `appointment_history` tự động ghi nhận mọi sự kiện (tạo mới, xác nhận, hoàn thành, hủy lịch), lưu dữ liệu cũ/mới dạng JSON, định danh người thực hiện và thời điểm thay đổi.

---

## 🔑 DANH SÁCH TÀI KHOẢN KIỂM THỬ MẪU

| Vai trò | Tên đăng nhập | Mật khẩu | Họ và tên | Chức năng chính |
| :--- | :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin` | `admin123` | Nguyễn Văn Admin | Toàn quyền quản trị hệ thống, dịch vụ, nhân sự, lịch hẹn, doanh thu |
| **Nhân viên (Staff 1)** | `nhanvien1` | `staff123` | Trần Thị Hương | Tiếp nhận lịch, xác nhận, hoàn tất dịch vụ, xem ca trực |
| **Nhân viên (Staff 2)** | `nhanvien2` | `staff123` | Lê Thị Mai | Quản lý lịch hẹn được phân công cá nhân |
| **Nhân viên (Staff 3)** | `nhanvien3` | `staff123` | Phạm Minh Tuấn | Quản lý lịch hẹn được phân công cá nhân |
| **Khách hàng 1** | `khachhang1` | `customer123` | Nguyễn Thị Lan | Đặt lịch mới, theo dõi lịch hẹn, hủy lịch, xem lịch sử |
| **Khách hàng 2** | `khachhang2` | `customer123` | Trần Văn Nam | Đặt lịch mới, kiểm tra dịch vụ |

*(Giao diện đăng nhập đã tích hợp sẵn các nút bấm điền nhanh tài khoản để giảng viên và ban giám khảo kiểm thử thuận tiện nhất)*

---

## 🚀 HƯỚNG DẪN CÀI ĐẶT & CHẠY ỨNG DỤNG (XAMPP)

### Bước 1: Sao chép mã nguồn vào thư mục XAMPP
- Copy toàn bộ thư mục `BTL` vào thư mục `htdocs` của XAMPP:
  ```text
  C:\xampp\htdocs\BTL\
  ```

### Bước 2: Khởi động dịch vụ trong XAMPP Control Panel
- Mở **XAMPP Control Panel**.
- Nhấn **Start** tại 2 dịch vụ:
  - **Apache** (Cổng 80/443)
  - **MySQL** (Cổng 3306)

### Bước 3: Tạo cơ sở dữ liệu và nạp dữ liệu mẫu
- Mở trình duyệt web và truy cập phpMyAdmin: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
- Chọn tab **Import** (Nhập).
- Nhấn **Choose File** (Chọn tệp) và chọn file `database.sql` trong thư mục dự án (`C:\xampp\htdocs\BTL\database.sql`).
- Nhấn **Import** ở cuối trang để tự động tạo CSDL `beauty_salon`, 8 bảng quan hệ và nạp toàn bộ dữ liệu mẫu (dịch vụ, nhân viên, lịch làm việc, lịch hẹn, hóa đơn).

### Bước 4: Kiểm tra cấu hình kết nối (Nếu cần)
- File cấu hình CSDL nằm tại `config/database.php`:
  ```php
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'beauty_salon');
  define('DB_USER', 'root');
  define('DB_PASS', ''); // Để trống nếu XAMPP mặc định
  ```

### Bước 5: Truy cập ứng dụng
- **Trang chủ khách hàng**: [http://localhost/BTL/index.php](http://localhost/BTL/index.php)
- **Trang đăng nhập**: [http://localhost/BTL/auth/login.php](http://localhost/BTL/auth/login.php)
- **Trang đăng ký**: [http://localhost/BTL/auth/register.php](http://localhost/BTL/auth/register.php)
- **Trang đặt lịch hẹn**: [http://localhost/BTL/booking/index.php](http://localhost/BTL/booking/index.php)
- **Trang quản trị (Admin)**: [http://localhost/BTL/admin/index.php](http://localhost/BTL/admin/index.php)

---

## 📁 CẤU TRÚC THƯ MỤC DỰ ÁN

```text
BTL/
├── admin/                     # Module dành riêng cho Quản trị viên
│   ├── index.php              # Dashboard tổng quan & biểu đồ
│   ├── categories.php         # Quản lý danh mục dịch vụ (CRUD)
│   ├── services.php           # Quản lý dịch vụ & giá (CRUD)
│   ├── service_form.php       # Form thêm / sửa dịch vụ
│   ├── users.php              # Quản lý người dùng, phân quyền, khóa tài khoản
│   ├── staff.php              # Quản lý nhân viên & gán dịch vụ phụ trách
│   ├── staff_schedule.php     # Cài đặt lịch ca trực tuần của nhân viên
│   ├── appointments.php       # Quản lý lịch hẹn toàn salon & bộ lọc
│   ├── appointment_detail.php # Chi tiết lịch hẹn & nhật ký thay đổi
│   ├── invoices.php           # Quản lý hóa đơn & thanh toán
│   ├── invoice_detail.php     # Chi tiết & in hóa đơn thanh toán
│   └── statistics.php         # Báo cáo doanh thu & biểu đồ Chart.js
├── api/
│   └── booking.php            # AJAX API kiểm tra lịch trống, tránh trùng giờ
├── assets/
│   ├── css/style.css          # CSS giao diện Beauty Salon cao cấp
│   └── js/main.js             # JavaScript xử lý form, validate, AJAX
├── auth/
│   ├── login.php              # Đăng nhập, mã hóa bcrypt, kiểm tra trạng thái
│   ├── register.php           # Đăng ký tài khoản khách hàng
│   └── logout.php             # Đăng xuất an toàn, xóa session
├── booking/
│   ├── index.php              # Wizard đặt lịch 4 bước trực quan
│   ├── my_appointments.php    # Quản lý và hủy lịch hẹn của khách
│   └── history.php            # Lịch sử dịch vụ và tổng tiền chi tiêu
├── config/
│   ├── database.php           # Kết nối PDO MySQL chuẩn Singleton
│   └── config.php             # Biến toàn cục, CSRF, Session, Formatters
├── includes/
│   ├── header.php             # Header chung, navbar, font & icon
│   ├── footer.php             # Footer, scripts Bootstrap & Chart.js
│   └── sidebar.php            # Sidebar điều hướng phân quyền theo Role
├── staff/
│   ├── index.php              # Dashboard làm việc của nhân viên
│   ├── appointments.php       # Danh sách lịch hẹn được giao của nhân viên
│   └── schedule.php           # Lịch làm việc cá nhân của nhân viên
├── database.sql               # File SQL hoàn chỉnh CSDL & Dữ liệu mẫu
├── profile.php                # Cập nhật thông tin cá nhân & đổi mật khẩu
├── index.php                  # Trang chủ giới thiệu dịch vụ & salon
├── BAO_CAO_BTL_DE_TAI_19.md   # Báo cáo BTL đầy đủ (Phân tích, ERD, Use Case, Test)
└── README.md                  # Hướng dẫn sử dụng dự án
```

---

## 🛡️ TÍNH NĂNG BẢO MẬT & KỸ THUẬT
- **Chống SQL Injection**: 100% câu truy vấn sử dụng **PDO Prepared Statements** với tham số binding (`execute([$param])`).
- **Mã hóa mật khẩu**: Sử dụng thuật toán `password_hash()` với chuẩn **BCRYPT** và kiểm tra bằng `password_verify()`.
- **Chống CSRF**: Tự động sinh `csrf_token` trong form và kiểm tra với `validateCSRF()`.
- **Chống XSS**: Sử dụng hàm `htmlspecialchars()` / `e()` trên mọi dữ liệu đầu ra từ người dùng.
- **Phân quyền chặt chẽ (RBAC)**: Các middleware `requireAdmin()`, `requireStaff()`, `requireCustomer()`, `requireLogin()` bảo vệ từng URL.
- **Kiểm tra trạng thái tài khoản**: Tài khoản bị khóa (`status = 'locked'`) không thể đăng nhập vào hệ thống.
