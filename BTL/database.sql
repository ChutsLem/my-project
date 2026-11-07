-- =====================================================
-- HỆ THỐNG QUẢN LÝ DỊCH VỤ ĐẶT LỊCH LÀM ĐẸP
-- Beauty Salon Booking Management System
-- Database: beauty_salon
-- =====================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = 'utf8mb4_unicode_ci';

DROP DATABASE IF EXISTS beauty_salon;
CREATE DATABASE beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE beauty_salon;

-- =====================================================
-- 1. BẢNG NGƯỜI DÙNG (users)
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'staff', 'customer') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'locked') NOT NULL DEFAULT 'active',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. BẢNG DANH MỤC DỊCH VỤ (categories)
-- =====================================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. BẢNG DỊCH VỤ (services)
-- =====================================================
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    duration INT NOT NULL DEFAULT 30 COMMENT 'Thời gian thực hiện (phút)',
    image VARCHAR(255),
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. BẢNG NHÂN VIÊN - DỊCH VỤ (staff_services)
-- =====================================================
CREATE TABLE staff_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    service_id INT NOT NULL,
    UNIQUE KEY unique_staff_service (staff_id, service_id),
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. BẢNG LỊCH LÀM VIỆC (work_schedules)
-- =====================================================
CREATE TABLE work_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Chủ nhật, 1=Thứ 2, ..., 6=Thứ 7',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_off TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Nghỉ, 0=Làm việc',
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. BẢNG LỊCH HẸN (appointments)
-- =====================================================
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    staff_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. BẢNG LỊCH SỬ LỊCH HẸN (appointment_history)
-- =====================================================
CREATE TABLE appointment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    action ENUM('created', 'confirmed', 'changed', 'cancelled', 'completed') NOT NULL,
    old_data JSON,
    new_data JSON,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. BẢNG HÓA ĐƠN (invoices)
-- =====================================================
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    customer_id INT NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0,
    final_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_method ENUM('cash', 'card', 'transfer') NOT NULL DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'refunded') NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- DỮ LIỆU MẪU (Sample Data)
-- =====================================================

-- -----------------------------------------------------
-- Người dùng: 1 admin, 3 nhân viên, 5 khách hàng
-- Mật khẩu được hash bằng password_hash() với bcrypt
-- admin123 / staff123 / customer123
-- -----------------------------------------------------
INSERT INTO users (username, password, full_name, email, phone, role, status, avatar) VALUES
-- Admin (password: admin123)
('admin', '$2y$10$FgLBVx3EvVfobCyk7xX7Yuhyku6c0MR2MSHD5AQyuKEHdWmG3cE9m', 'Nguyễn Văn Admin', 'admin@beautysalon.vn', '0901000001', 'admin', 'active', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=600&q=80'),
-- Staff (password: staff123)
('nhanvien1', '$2y$10$gYP1aZdx6JYhOO3/swm.5eCk1i1sb5vPAM1.Az37X1SvoLczwKdSC', 'Trần Thị Hương', 'huong@beautysalon.vn', '0901000002', 'staff', 'active', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&q=80'),
('nhanvien2', '$2y$10$gYP1aZdx6JYhOO3/swm.5eCk1i1sb5vPAM1.Az37X1SvoLczwKdSC', 'Lê Thị Mai', 'mai@beautysalon.vn', '0901000003', 'staff', 'active', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=600&q=80'),
('nhanvien3', '$2y$10$gYP1aZdx6JYhOO3/swm.5eCk1i1sb5vPAM1.Az37X1SvoLczwKdSC', 'Phạm Minh Tuấn', 'tuan@beautysalon.vn', '0901000004', 'staff', 'active', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80'),
-- Customers (password: customer123)
('khachhang1', '$2y$10$q74RWkglq8Dz6OislCL8j.6870kJ3eSCvGwIZ6yvtBEo/f2cmswNa', 'Nguyễn Thị Lan', 'lan@gmail.com', '0912000001', 'customer', 'active', 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=400&q=80'),
('khachhang2', '$2y$10$q74RWkglq8Dz6OislCL8j.6870kJ3eSCvGwIZ6yvtBEo/f2cmswNa', 'Trần Văn Nam', 'nam@gmail.com', '0912000002', 'customer', 'active', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=400&q=80'),
('khachhang3', '$2y$10$q74RWkglq8Dz6OislCL8j.6870kJ3eSCvGwIZ6yvtBEo/f2cmswNa', 'Lê Thị Hoa', 'hoa@gmail.com', '0912000003', 'customer', 'active', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=400&q=80'),
('khachhang4', '$2y$10$q74RWkglq8Dz6OislCL8j.6870kJ3eSCvGwIZ6yvtBEo/f2cmswNa', 'Phạm Văn Đức', 'duc@gmail.com', '0912000004', 'customer', 'active', 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=400&q=80'),
('khachhang5', '$2y$10$q74RWkglq8Dz6OislCL8j.6870kJ3eSCvGwIZ6yvtBEo/f2cmswNa', 'Hoàng Thị Yến', 'yen@gmail.com', '0912000005', 'customer', 'active', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=400&q=80');

-- -----------------------------------------------------
-- Danh mục dịch vụ (5 danh mục)
-- -----------------------------------------------------
INSERT INTO categories (name, description, status) VALUES
('Chăm sóc da', 'Các dịch vụ chăm sóc da mặt, trị liệu phục hồi da và liệu pháp dát vàng 24K', 'active'),
('Làm tóc', 'Thiết kế tạo kiểu tóc, nhuộm balayage hữu cơ và phục hồi keratin cao cấp', 'active'),
('Làm móng', 'Sơn gel khoáng chất, vẽ nail art bespoke thủ công và spa móng chân thảo mộc', 'active'),
('Massage', 'Massage thư giãn tinh dầu Bali, trị liệu đá nóng bazan và đả thông kinh lạc', 'active'),
('Trang điểm', 'Trang điểm dự tiệc cao cấp, trang điểm cô dâu và phong cách nude thanh khiết', 'active');

-- -----------------------------------------------------
-- Dịch vụ (16 dịch vụ với hình ảnh cao cấp)
-- -----------------------------------------------------
INSERT INTO services (category_id, name, description, price, duration, image, status) VALUES
-- Chăm sóc da (category_id = 1)
(1, 'Chăm sóc da cơ bản chuyên sâu', 'Liệu trình làm sạch thanh lọc da, xông hơi tinh dầu lavender, tẩy tế bào chết và đắp mặt nạ cấp ẩm hữu cơ.', 250000.00, 60, 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=800&q=80', 'active'),
(1, 'Trị mụn & Phục hồi da chuyên sâu', 'Công nghệ ánh sáng sinh học kết hợp serum kháng viêm tràm trà, gom cồi mụn và phục hồi màng bảo vệ da.', 450000.00, 90, 'https://images.unsplash.com/photo-1512290900672-1f48dd83196a?auto=format&fit=crop&w=800&q=80', 'active'),
(1, 'Liệu trình Hoàng Gia Vàng 24K (Signature 24K Gold)', 'Liệu pháp trứ danh dát vàng 24K nguyên chất từ Bali, kích thích collagen tối đa, nâng cơ và đem lại làn da sáng bừng phú quý.', 1200000.00, 90, 'https://images.prismic.io/goldust/Zzl3ia8jQArT08VU_GOLDDUST-11.jpg?auto=format,compress', 'active'),
-- Làm tóc (category_id = 2)
(2, 'Cắt tóc & Tạo kiểu Haute Couture', 'Thiết kế dáng tóc độc bản dựa trên đường nét khuôn mặt, gội massage bấm huyệt thảo mộc thư giãn.', 180000.00, 45, 'https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=800&q=80', 'active'),
(2, 'Nhuộm Balayage & Phủ bóng Nano hữu cơ', 'Kỹ thuật nhuộm chuyển màu tự nhiên chuẩn Paris với hạt màu hữu cơ cao cấp, không gây xơ rối hay tổn hại da đầu.', 650000.00, 120, 'https://images.unsplash.com/photo-1607779097040-26e80aa78e66?auto=format&fit=crop&w=800&q=80', 'active'),
(2, 'Uốn sóng lơi Hoàng Gia & Giữ nếp lâu', 'Sóng xoăn bồng bềnh tự nhiên công nghệ khóa ẩm thông minh, giúp mái tóc luôn óng ả và vào nếp chuẩn salon.', 850000.00, 150, 'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=800&q=80', 'active'),
(2, 'Phục hồi Keratin & Tơ tằm phục sinh tóc', 'Bổ sung chuỗi amino acid và tinh chất tơ tằm nguyên chất, làm lành các liên kết keratin bị gãy đứt.', 400000.00, 60, 'https://images.unsplash.com/photo-1519699047748-de8e457a634e?auto=format&fit=crop&w=800&q=80', 'active'),
-- Làm móng (category_id = 3)
(3, 'Sơn Gel khoáng chất cao cấp dưỡng móng', 'Bộ sưu tập màu sơn gel thuần chay lành tính, độ bền bóng tráng gương lên tới 4 tuần cùng liệu trình dưỡng viền móng.', 220000.00, 45, 'https://images.unsplash.com/photo-1632345031435-8727f6897d53?auto=format&fit=crop&w=800&q=80', 'active'),
(3, 'Nail Art đính đá & Vẽ tay nghệ thuật', 'Thiết kế hoa văn độc quyền theo phong cách tối giản thanh lịch hoặc kiêu sa đính pha lê Swarovski.', 380000.00, 60, 'https://images.unsplash.com/photo-1604654894610-df63bc536371?auto=format&fit=crop&w=800&q=80', 'active'),
(3, 'Spa chăm sóc móng chân thảo mộc hoa hồng', 'Ngâm chân bồn gỗ thảo dược, tẩy da chết muối biển hoa hồng và massage giảm mệt mỏi cho đôi chân.', 280000.00, 50, 'https://images.unsplash.com/photo-1519014816548-bf5fe059798b?auto=format&fit=crop&w=800&q=80', 'active'),
-- Massage (category_id = 4)
(4, 'Massage thư giãn toàn thân bằng tinh dầu Bali', 'Kỹ thuật vuốt miết truyền thống Bali kết hợp tinh dầu sả chanh, hoa lài đánh tan căng thẳng, lưu thông khí huyết toàn diện.', 450000.00, 90, 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&w=800&q=80', 'active'),
(4, 'Trị liệu Massage đá nóng Bazan núi lửa', 'Sức nóng từ đá bazan tự nhiên giàu khoáng chất giúp xua tan hàn khí, giảm đau nhức xương khớp và ngủ sâu giấc.', 550000.00, 90, 'https://images.unsplash.com/photo-1519823551278-64ac92734fb1?auto=format&fit=crop&w=800&q=80', 'active'),
(4, 'Massage mặt nâng cơ Guasha ngọc bích', 'Đả thông kinh lạc vùng mặt với thanh ngọc bích tự nhiên, thon gọn cằm V-line và giảm bọng mắt tức thì.', 350000.00, 45, 'https://images.unsplash.com/photo-1598256989800-fe5f95da9787?auto=format&fit=crop&w=800&q=80', 'active'),
-- Trang điểm (category_id = 5)
(5, 'Trang điểm dạ tiệc phong cách Glow Glam', 'Lớp nền căng mọng tự nhiên, tôn vinh thần thái rạng rỡ cuốn hút trong mọi ánh đèn dạ tiệc sang trọng.', 550000.00, 60, 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=800&q=80', 'active'),
(5, 'Trang điểm & Làm tóc cô dâu Hoàng Gia', 'Trọn gói makeup & hair ngày cưới, mỹ phẩm chống trôi cao cấp Chanel/Dior, đồng hành cùng nàng dâu trọn ngày hạnh phúc.', 1600000.00, 120, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=800&q=80', 'active'),
(5, 'Trang điểm phong cách Nude thanh khiết', 'Vẻ đẹp trong trẻo như không trang điểm, hoàn hảo cho những buổi hẹn hò, chụp ảnh chân dung hoặc sự kiện ban ngày.', 300000.00, 40, 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&q=80', 'active');

-- -----------------------------------------------------
-- Nhân viên - Dịch vụ (staff_services)
-- Nhân viên 1 (id=2): Chăm sóc da, Massage, Trang điểm
-- Nhân viên 2 (id=3): Làm tóc, Trang điểm
-- Nhân viên 3 (id=4): Làm móng, Massage
-- -----------------------------------------------------
INSERT INTO staff_services (staff_id, service_id) VALUES
-- Trần Thị Hương (id=2) - Chăm sóc da + Massage + Trang điểm
(2, 1), (2, 2), (2, 3),
(2, 11), (2, 12), (2, 13),
(2, 14), (2, 15), (2, 16),
-- Lê Thị Mai (id=3) - Làm tóc + Trang điểm
(3, 4), (3, 5), (3, 6), (3, 7),
(3, 14), (3, 15), (3, 16),
-- Phạm Minh Tuấn (id=4) - Làm móng + Massage
(4, 8), (4, 9), (4, 10),
(4, 11), (4, 12), (4, 13);

-- -----------------------------------------------------
-- Lịch làm việc (work_schedules)
-- Nhân viên làm T2-T7, nghỉ CN
-- -----------------------------------------------------
-- Trần Thị Hương (id=2): 8:00 - 17:00
INSERT INTO work_schedules (staff_id, day_of_week, start_time, end_time, is_off) VALUES
(2, 0, '08:00:00', '17:00:00', 1),  -- Chủ nhật: Nghỉ
(2, 1, '08:00:00', '17:00:00', 0),  -- Thứ 2
(2, 2, '08:00:00', '17:00:00', 0),  -- Thứ 3
(2, 3, '08:00:00', '17:00:00', 0),  -- Thứ 4
(2, 4, '08:00:00', '17:00:00', 0),  -- Thứ 5
(2, 5, '08:00:00', '17:00:00', 0),  -- Thứ 6
(2, 6, '08:00:00', '12:00:00', 0);  -- Thứ 7 (nửa ngày)

-- Lê Thị Mai (id=3): 9:00 - 18:00
INSERT INTO work_schedules (staff_id, day_of_week, start_time, end_time, is_off) VALUES
(3, 0, '09:00:00', '18:00:00', 1),  -- Chủ nhật: Nghỉ
(3, 1, '09:00:00', '18:00:00', 0),  -- Thứ 2
(3, 2, '09:00:00', '18:00:00', 0),  -- Thứ 3
(3, 3, '09:00:00', '18:00:00', 0),  -- Thứ 4
(3, 4, '09:00:00', '18:00:00', 0),  -- Thứ 5
(3, 5, '09:00:00', '18:00:00', 0),  -- Thứ 6
(3, 6, '09:00:00', '15:00:00', 0);  -- Thứ 7 (nửa ngày)

-- Phạm Minh Tuấn (id=4): 8:30 - 17:30
INSERT INTO work_schedules (staff_id, day_of_week, start_time, end_time, is_off) VALUES
(4, 0, '08:30:00', '17:30:00', 1),  -- Chủ nhật: Nghỉ
(4, 1, '08:30:00', '17:30:00', 0),  -- Thứ 2
(4, 2, '08:30:00', '17:30:00', 0),  -- Thứ 3
(4, 3, '08:30:00', '17:30:00', 0),  -- Thứ 4
(4, 4, '08:30:00', '17:30:00', 0),  -- Thứ 5
(4, 5, '08:30:00', '17:30:00', 0),  -- Thứ 6
(4, 6, '08:30:00', '12:30:00', 0);  -- Thứ 7 (nửa ngày)

-- -----------------------------------------------------
-- Lịch hẹn mẫu (12 lịch hẹn với nhiều trạng thái)
-- -----------------------------------------------------
INSERT INTO appointments (customer_id, staff_id, service_id, appointment_date, start_time, end_time, status, notes) VALUES
-- Đã hoàn thành
(5, 2, 1, '2025-01-10', '09:00:00', '10:00:00', 'completed', 'Khách hàng muốn dưỡng ẩm thêm'),
(6, 3, 4, '2025-01-11', '10:00:00', '10:45:00', 'completed', 'Cắt tóc ngắn layer'),
(7, 4, 8, '2025-01-12', '14:00:00', '14:45:00', 'completed', 'Sơn gel màu hồng pastel'),
(8, 2, 11, '2025-01-13', '15:00:00', '16:30:00', 'completed', 'Massage toàn thân kết hợp tinh dầu bạc hà'),
(9, 3, 5, '2025-01-14', '09:00:00', '11:00:00', 'completed', 'Nhuộm highlight vàng'),
-- Đã xác nhận
(5, 2, 2, '2025-02-15', '09:00:00', '10:30:00', 'confirmed', 'Trị mụn vùng chữ T'),
(6, 4, 9, '2025-02-16', '13:00:00', '14:00:00', 'confirmed', 'Nail art hoa nhí theo mẫu'),
-- Đang chờ
(7, 3, 6, '2025-02-20', '10:00:00', '12:30:00', 'pending', 'Uốn xoăn lọn to'),
(8, 2, 14, '2025-02-21', '14:00:00', '15:00:00', 'pending', 'Trang điểm dự sinh nhật'),
(9, 4, 12, '2025-02-22', '09:00:00', '10:30:00', 'pending', 'Massage đá nóng vùng lưng'),
-- Đã hủy
(5, 3, 15, '2025-01-20', '10:00:00', '12:00:00', 'cancelled', 'Khách hẹn lại lần sau'),
(7, 2, 3, '2025-01-25', '11:00:00', '12:15:00', 'cancelled', 'Khách bận đột xuất');

-- -----------------------------------------------------
-- Lịch sử lịch hẹn (appointment_history)
-- -----------------------------------------------------
INSERT INTO appointment_history (appointment_id, action, old_data, new_data, changed_by) VALUES
-- Lịch hẹn 1: Tạo -> Xác nhận -> Hoàn thành
(1, 'created', NULL, '{"status":"pending","service":"Chăm sóc da cơ bản","date":"2025-01-10"}', 5),
(1, 'confirmed', '{"status":"pending"}', '{"status":"confirmed"}', 1),
(1, 'completed', '{"status":"confirmed"}', '{"status":"completed"}', 2),
-- Lịch hẹn 2: Tạo -> Xác nhận -> Hoàn thành
(2, 'created', NULL, '{"status":"pending","service":"Cắt tóc tạo kiểu","date":"2025-01-11"}', 6),
(2, 'confirmed', '{"status":"pending"}', '{"status":"confirmed"}', 1),
(2, 'completed', '{"status":"confirmed"}', '{"status":"completed"}', 3),
-- Lịch hẹn 3: Tạo -> Xác nhận -> Hoàn thành
(3, 'created', NULL, '{"status":"pending","service":"Sơn gel móng tay","date":"2025-01-12"}', 7),
(3, 'confirmed', '{"status":"pending"}', '{"status":"confirmed"}', 1),
(3, 'completed', '{"status":"confirmed"}', '{"status":"completed"}', 4),
-- Lịch hẹn 4: Tạo -> Xác nhận -> Hoàn thành
(4, 'created', NULL, '{"status":"pending","service":"Massage thư giãn toàn thân","date":"2025-01-13"}', 8),
(4, 'confirmed', '{"status":"pending"}', '{"status":"confirmed"}', 1),
(4, 'completed', '{"status":"confirmed"}', '{"status":"completed"}', 2),
-- Lịch hẹn 5: Tạo -> Xác nhận -> Hoàn thành
(5, 'created', NULL, '{"status":"pending","service":"Nhuộm tóc thời trang","date":"2025-01-14"}', 9),
(5, 'confirmed', '{"status":"pending"}', '{"status":"confirmed"}', 1),
(5, 'completed', '{"status":"confirmed"}', '{"status":"completed"}', 3),
-- Lịch hẹn 6: Tạo -> Xác nhận
(6, 'created', NULL, '{"status":"pending","service":"Trị mụn chuyên sâu","date":"2025-02-15"}', 5),
(6, 'confirmed', '{"status":"pending"}', '{"status":"confirmed"}', 1),
-- Lịch hẹn 7: Tạo -> Xác nhận
(7, 'created', NULL, '{"status":"pending","service":"Nail art thiết kế","date":"2025-02-16"}', 6),
(7, 'confirmed', '{"status":"pending"}', '{"status":"confirmed"}', 1),
-- Lịch hẹn 8: Tạo (pending)
(8, 'created', NULL, '{"status":"pending","service":"Uốn tóc cao cấp","date":"2025-02-20"}', 7),
-- Lịch hẹn 9: Tạo (pending)
(9, 'created', NULL, '{"status":"pending","service":"Trang điểm dự tiệc","date":"2025-02-21"}', 8),
-- Lịch hẹn 10: Tạo (pending)
(10, 'created', NULL, '{"status":"pending","service":"Massage đá nóng","date":"2025-02-22"}', 9),
-- Lịch hẹn 11: Tạo -> Hủy
(11, 'created', NULL, '{"status":"pending","service":"Trang điểm cô dâu","date":"2025-01-20"}', 5),
(11, 'cancelled', '{"status":"pending"}', '{"status":"cancelled","reason":"Khách hẹn lại lần sau"}', 5),
-- Lịch hẹn 12: Tạo -> Hủy
(12, 'created', NULL, '{"status":"pending","service":"Peel da sinh học","date":"2025-01-25"}', 7),
(12, 'cancelled', '{"status":"pending"}', '{"status":"cancelled","reason":"Khách bận đột xuất"}', 7);

-- -----------------------------------------------------
-- Hóa đơn (invoices) - Cho các lịch hẹn đã hoàn thành
-- -----------------------------------------------------
INSERT INTO invoices (appointment_id, customer_id, total_amount, discount, final_amount, payment_method, payment_status, notes, paid_at) VALUES
(1, 5, 250000.00, 0.00, 250000.00, 'cash', 'paid', 'Thanh toán tiền mặt', '2025-01-10 10:15:00'),
(2, 6, 150000.00, 0.00, 150000.00, 'transfer', 'paid', 'Chuyển khoản qua MBBank', '2025-01-11 11:00:00'),
(3, 7, 200000.00, 20000.00, 180000.00, 'cash', 'paid', 'Giảm 20K cho khách mới', '2025-01-12 15:00:00'),
(4, 8, 400000.00, 0.00, 400000.00, 'card', 'paid', 'Thanh toán thẻ Visa', '2025-01-13 16:45:00'),
(5, 9, 600000.00, 50000.00, 550000.00, 'transfer', 'paid', 'Giảm 50K khách VIP, chuyển khoản', '2025-01-14 11:30:00'),
-- Hóa đơn chờ thanh toán cho lịch hẹn đã xác nhận
(6, 5, 450000.00, 0.00, 450000.00, 'cash', 'pending', NULL, NULL),
(7, 6, 350000.00, 0.00, 350000.00, 'cash', 'pending', NULL, NULL);
