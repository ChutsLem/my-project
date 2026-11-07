<?php
/**
 * Sidebar navigation - Beauty Salon & Spa Lounge
 * Luxury Collapsible Icon Dock with Smooth Hover Expansion
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<nav class="sidebar" id="sidebar">
    <!-- Brand Medallion -->
    <div class="brand">
        <a href="<?= BASE_URL ?>index.php" class="brand-link text-decoration-none">
            <div class="brand-icon-box">
                <i class="bi bi-flower1"></i>
            </div>
            <div class="brand-info ms-3">
                <span class="brand-text d-block">BEAUTY SALON</span>
                <span class="brand-sub d-block">Spa & Sanctuary</span>
            </div>
        </a>
    </div>

    <?php if (isAdmin()): ?>
    <div class="nav-section">
        <span class="section-text">Tổng quan</span>
        <div class="section-divider-mini"></div>
    </div>
    <ul class="nav flex-column sidebar-nav-list">
        <li class="nav-item">
            <a class="nav-link <?= ($currentDir === 'admin' && $currentPage === 'index.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>admin/index.php" title="Dashboard">
                <div class="nav-icon-box">
                    <i class="bi bi-grid-fill"></i>
                </div>
                <span class="nav-label">Dashboard</span>
            </a>
        </li>
    </ul>

    <div class="nav-section">
        <span class="section-text">Quản lý dịch vụ</span>
        <div class="section-divider-mini"></div>
    </div>
    <ul class="nav flex-column sidebar-nav-list">
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'categories.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>admin/categories.php" title="Danh mục dịch vụ">
                <div class="nav-icon-box">
                    <i class="bi bi-tags-fill"></i>
                </div>
                <span class="nav-label">Danh mục dịch vụ</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'services.php' || $currentPage === 'service_form.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>admin/services.php" title="Danh sách dịch vụ">
                <div class="nav-icon-box">
                    <i class="bi bi-stars"></i>
                </div>
                <span class="nav-label">Danh sách dịch vụ</span>
            </a>
        </li>
    </ul>

    <div class="nav-section">
        <span class="section-text">Lịch hẹn & Khách</span>
        <div class="section-divider-mini"></div>
    </div>
    <ul class="nav flex-column sidebar-nav-list">
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'appointments.php' || $currentPage === 'appointment_detail.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>admin/appointments.php" title="Quản lý lịch hẹn">
                <div class="nav-icon-box">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <span class="nav-label">Quản lý lịch hẹn</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'users.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>admin/users.php" title="Quản lý người dùng">
                <div class="nav-icon-box">
                    <i class="bi bi-people-fill"></i>
                </div>
                <span class="nav-label">Quản lý người dùng</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'staff.php' || $currentPage === 'staff_schedule.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>admin/staff.php" title="Nhân viên & Ca trực">
                <div class="nav-icon-box">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <span class="nav-label">Nhân viên & Ca trực</span>
            </a>
        </li>
    </ul>

    <div class="nav-section">
        <span class="section-text">Tài chính & Báo cáo</span>
        <div class="section-divider-mini"></div>
    </div>
    <ul class="nav flex-column sidebar-nav-list">
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'invoices.php' || $currentPage === 'invoice_detail.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>admin/invoices.php" title="Hóa đơn & Thanh toán">
                <div class="nav-icon-box">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
                <span class="nav-label">Hóa đơn & Thanh toán</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'statistics.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>admin/statistics.php" title="Thống kê & Báo cáo">
                <div class="nav-icon-box">
                    <i class="bi bi-bar-chart-fill"></i>
                </div>
                <span class="nav-label">Thống kê & Báo cáo</span>
            </a>
        </li>
    </ul>
    <?php endif; ?>

    <?php if (isStaff()): ?>
    <div class="nav-section">
        <span class="section-text">Nhân viên</span>
        <div class="section-divider-mini"></div>
    </div>
    <ul class="nav flex-column sidebar-nav-list">
        <li class="nav-item">
            <a class="nav-link <?= ($currentDir === 'staff' && $currentPage === 'index.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>staff/index.php" title="Trang làm việc">
                <div class="nav-icon-box">
                    <i class="bi bi-grid-fill"></i>
                </div>
                <span class="nav-label">Trang làm việc</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'appointments.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>staff/appointments.php" title="Lịch hẹn được giao">
                <div class="nav-icon-box">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <span class="nav-label">Lịch hẹn được giao</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'schedule.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>staff/schedule.php" title="Lịch làm việc cá nhân">
                <div class="nav-icon-box">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <span class="nav-label">Lịch làm việc cá nhân</span>
            </a>
        </li>
    </ul>
    <?php endif; ?>

    <?php if (isCustomer()): ?>
    <div class="nav-section">
        <span class="section-text">Khách hàng</span>
        <div class="section-divider-mini"></div>
    </div>
    <ul class="nav flex-column sidebar-nav-list">
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'index.php' && $currentDir !== 'admin' && $currentDir !== 'staff') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>index.php" title="Trang chủ">
                <div class="nav-icon-box">
                    <i class="bi bi-house-door-fill"></i>
                </div>
                <span class="nav-label">Trang chủ</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentDir === 'booking' && $currentPage === 'index.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>booking/index.php" title="Đặt lịch hẹn mới">
                <div class="nav-icon-box">
                    <i class="bi bi-calendar-plus-fill"></i>
                </div>
                <span class="nav-label">Đặt lịch hẹn mới</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'my_appointments.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>booking/my_appointments.php" title="Lịch hẹn của tôi">
                <div class="nav-icon-box">
                    <i class="bi bi-calendar-event-fill"></i>
                </div>
                <span class="nav-label">Lịch hẹn của tôi</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'history.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>booking/history.php" title="Lịch sử dịch vụ">
                <div class="nav-icon-box">
                    <i class="bi bi-clock-history"></i>
                </div>
                <span class="nav-label">Lịch sử dịch vụ</span>
            </a>
        </li>
    </ul>
    <?php endif; ?>

    <div class="nav-section">
        <span class="section-text">Tài khoản</span>
        <div class="section-divider-mini"></div>
    </div>
    <ul class="nav flex-column sidebar-nav-list mb-4">
        <?php if (isLoggedIn()): ?>
        <li class="nav-item">
            <a class="nav-link <?= ($currentPage === 'profile.php') ? 'active' : '' ?>" 
               href="<?= BASE_URL ?>profile.php" title="Hồ sơ cá nhân">
                <div class="nav-icon-box">
                    <i class="bi bi-person-circle"></i>
                </div>
                <span class="nav-label">Hồ sơ cá nhân</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link nav-link-logout" href="<?= BASE_URL ?>auth/logout.php" title="Đăng xuất">
                <div class="nav-icon-box text-danger">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <span class="nav-label text-danger">Đăng xuất</span>
            </a>
        </li>
        <?php else: ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>auth/login.php" title="Đăng nhập">
                <div class="nav-icon-box">
                    <i class="bi bi-box-arrow-in-right"></i>
                </div>
                <span class="nav-label">Đăng nhập</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>auth/register.php" title="Đăng ký">
                <div class="nav-icon-box">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <span class="nav-label">Đăng ký</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
