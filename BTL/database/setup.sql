-- Beauty Salon Booking Management System
-- Database Setup

CREATE DATABASE IF NOT EXISTS beauty_salon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE beauty_salon;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin','staff','customer') DEFAULT 'customer',
    status ENUM('active','locked') DEFAULT 'active',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Services table
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    duration INT NOT NULL DEFAULT 30 COMMENT 'Duration in minutes',
    image VARCHAR(255),
    status ENUM('active','inactive') DEFAULT 'active',
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Staff services (many-to-many)
CREATE TABLE IF NOT EXISTS staff_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    service_id INT NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    UNIQUE KEY unique_staff_service (staff_id, service_id)
) ENGINE=InnoDB;

-- Work schedules
CREATE TABLE IF NOT EXISTS work_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '1=Monday, 7=Sunday',
    start_time TIME,
    end_time TIME,
    is_off TINYINT(1) DEFAULT 0,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_staff_day (staff_id, day_of_week)
) ENGINE=InnoDB;

-- Appointments
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    staff_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Appointment history
CREATE TABLE IF NOT EXISTS appointment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    old_data TEXT,
    new_data TEXT,
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Invoices
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    customer_id INT NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(12,2) DEFAULT 0,
    final_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_method ENUM('cash','card','transfer','momo') DEFAULT 'cash',
    payment_status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản trị viên', 'admin@beautysalon.vn', '0901234567', 'admin', 'active');

-- Sample categories
INSERT INTO categories (name, description) VALUES
('Chăm sóc da', 'Các dịch vụ chăm sóc da mặt'),
('Chăm sóc tóc', 'Các dịch vụ cắt, uốn, nhuộm tóc'),
('Chăm sóc móng', 'Các dịch vụ nail, sơn móng'),
('Massage & Spa', 'Các dịch vụ massage thư giãn');

-- Sample services
INSERT INTO services (category_id, name, description, price, duration) VALUES
(1, 'Chăm sóc da cơ bản', 'Làm sạch, tẩy tế bào chết, đắp mặt nạ', 250000, 60),
(1, 'Trị mụn chuyên sâu', 'Trị mụn viêm, mụn ẩn hiệu quả', 350000, 90),
(2, 'Cắt tóc nữ', 'Cắt tạo kiểu tóc nữ theo xu hướng', 150000, 45),
(2, 'Nhuộm tóc', 'Nhuộm tóc thời trang nhiều màu sắc', 500000, 120),
(2, 'Uốn tóc', 'Uốn tóc xoăn tự nhiên', 450000, 120),
(3, 'Sơn gel', 'Sơn gel bền đẹp lên đến 3 tuần', 200000, 60),
(3, 'Đắp bột móng', 'Đắp bột acrylic tạo hình', 300000, 90),
(4, 'Massage body', 'Massage toàn thân thư giãn', 400000, 90),
(4, 'Massage mặt', 'Massage mặt nâng cơ trẻ hoá', 200000, 45);

-- Sample staff
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Thị Lan', 'lan@beautysalon.vn', '0912345678', 'staff', 'active'),
('staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị Mai', 'mai@beautysalon.vn', '0923456789', 'staff', 'active');

-- Sample customer
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
('customer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lê Văn An', 'an@gmail.com', '0934567890', 'customer', 'active');
