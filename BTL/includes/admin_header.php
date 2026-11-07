<?php
/**
 * Admin Sidebar Include
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="bg-dark text-white" id="sidebar-wrapper" style="min-width: 250px; max-width: 250px; min-height: 100vh;">
        <div class="sidebar-heading text-center py-4 fs-4 fw-bold border-bottom border-secondary">
            <i class="bi bi-scissors"></i> <?= APP_NAME ?>
        </div>
        <div class="list-group list-group-flush">
            <a href="<?= BASE_URL ?>/admin/index.php" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 <?= $currentPage == 'index.php' ? 'active bg-primary' : '' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 <?= $currentPage == 'users.php' ? 'active bg-primary' : '' ?>">
                <i class="bi bi-people me-2"></i> Quản lý người dùng
            </a>
            <a href="<?= BASE_URL ?>/admin/staff.php" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 <?= $currentPage == 'staff.php' ? 'active bg-primary' : '' ?>">
                <i class="bi bi-person-badge me-2"></i> Quản lý nhân viên
            </a>
            <a href="<?= BASE_URL ?>/admin/invoices.php" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 <?= $currentPage == 'invoices.php' ? 'active bg-primary' : '' ?>">
                <i class="bi bi-receipt me-2"></i> Quản lý hóa đơn
            </a>
            <a href="<?= BASE_URL ?>/admin/statistics.php" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3 <?= $currentPage == 'statistics.php' ? 'active bg-primary' : '' ?>">
                <i class="bi bi-bar-chart me-2"></i> Thống kê báo cáo
            </a>
            <hr class="text-secondary mx-3">
            <a href="<?= BASE_URL ?>/profile.php" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3">
                <i class="bi bi-person-circle me-2"></i> Hồ sơ cá nhân
            </a>
            <a href="<?= BASE_URL ?>/index.php" class="list-group-item list-group-item-action bg-dark text-white border-0 py-3">
                <i class="bi bi-house me-2"></i> Trang chủ
            </a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="list-group-item list-group-item-action bg-dark text-danger border-0 py-3">
                <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
            </a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper" class="w-100">
        <!-- Top Nav -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
            <button class="btn btn-outline-dark" id="menu-toggle">
                <i class="bi bi-list"></i>
            </button>
            <div class="ms-auto d-flex align-items-center">
                <span class="me-3 text-muted">
                    <i class="bi bi-person-circle me-1"></i>
                    <?= sanitize($_SESSION['full_name'] ?? 'Admin') ?>
                </span>
            </div>
        </nav>

        <div class="container-fluid p-4">
            <?php
            $flash = getFlash();
            if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $flash['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
