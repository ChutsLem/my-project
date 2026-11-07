<?php
/**
 * Header include - Beauty Salon Booking Management System
 * Luxury Design System inspired by Goldust Spa Bali
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
$currentUser = null;
if (function_exists('isLoggedIn') && isLoggedIn()) {
    $currentUser = function_exists('currentUser') ? currentUser() : null;
}
$flash = getFlash();
$pageTitle = $pageTitle ?? 'Beauty Salon';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Beauty Salon & Spa Lounge</title>
    <!-- Google Fonts: Cormorant Garamond & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Luxury Style -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-collapsed-width: 76px;
            --sidebar-expanded-width: 270px;
            --gold: #c5a880;
            --gold-light: #f4ede4;
            --gold-dark: #9e7949;
            --dark: #171513;
            --dark-elevated: #221f1b;
            --canvas: #faf8f5;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--canvas);
            color: #2b2723;
        }
        h1, h2, h3, h4, h5, .font-serif {
            font-family: 'Cormorant Garamond', Georgia, serif;
            letter-spacing: 0.3px;
        }

        /* ============================================================
           SIDEBAR: LUXURY COLLAPSIBLE ICON DOCK
           ============================================================ */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-collapsed-width);
            height: 100vh;
            background: linear-gradient(180deg, #171513 0%, #1a1714 50%, #141210 100%) !important;
            border-right: 1px solid rgba(197, 168, 128, 0.18);
            color: #ffffff;
            z-index: 1055;
            transition: width 0.32s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.32s ease;
            overflow-x: hidden;
            overflow-y: auto;
            white-space: nowrap;
            display: flex;
            flex-direction: column;
            padding: 0;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.35);
        }

        /* Brand Medallion */
        .sidebar .brand {
            padding: 16px 0;
            margin-bottom: 6px;
            border-bottom: 1px solid rgba(197, 168, 128, 0.15) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            transition: padding 0.3s ease;
        }
        .sidebar .brand-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            text-decoration: none;
        }
        .sidebar .brand-icon-box {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 12px;
            background: radial-gradient(circle at 30% 30%, rgba(220, 190, 140, 0.22), rgba(158, 121, 73, 0.12));
            border: 1px solid rgba(197, 168, 128, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e5c596;
            font-size: 1.45rem;
            margin: 0 auto;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3), inset 0 1px 1px rgba(255, 255, 255, 0.25);
            transition: all 0.25s ease;
        }
        .sidebar .brand-link:hover .brand-icon-box {
            transform: scale(1.06);
            border-color: var(--gold);
            box-shadow: 0 6px 18px rgba(197, 168, 128, 0.4), inset 0 1px 2px rgba(255, 255, 255, 0.4);
        }
        .sidebar .brand-info {
            opacity: 0;
            visibility: hidden;
            width: 0;
            max-width: 0;
            overflow: hidden;
            margin: 0 !important;
            transform: translateX(-8px);
            transition: opacity 0.25s ease, transform 0.25s ease, visibility 0.25s;
        }
        .sidebar .brand-text {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: #fdfaf6;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .sidebar .brand-sub {
            font-size: 0.62rem;
            letter-spacing: 1.8px;
            color: var(--gold);
            text-transform: uppercase;
            font-weight: 500;
        }

        /* Section Headings & Mini Dividers */
        .sidebar .nav-section {
            padding: 0;
            margin: 8px 0 4px 0;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .sidebar .section-text {
            display: none;
            opacity: 0;
            visibility: hidden;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2.2px;
            color: rgba(197, 168, 128, 0.75);
            white-space: nowrap;
            transition: opacity 0.2s ease, visibility 0.2s;
        }
        .sidebar .section-divider-mini {
            display: block;
            width: 24px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(197, 168, 128, 0.4), transparent);
            margin: 4px auto;
        }

        /* Navigation List & Items */
        .sidebar-nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
            width: 100%;
        }
        .sidebar .nav-item {
            width: 100%;
            margin: 3px 0;
            display: flex;
            justify-content: center;
        }
        .sidebar .nav-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 4px 0;
            margin: 0;
            border: none !important;
            background: transparent !important;
            text-decoration: none;
            position: relative;
            transition: all 0.25s ease;
        }

        /* Mathematical Centered Luxury Icon Box */
        .sidebar .nav-icon-box {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(197, 168, 128, 0.22);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d6c6b2;
            font-size: 1.15rem;
            flex-shrink: 0;
            margin: 0 auto;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .sidebar .nav-icon-box i {
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            font-size: 1.18rem;
        }

        /* Hover State on Icon Box */
        .sidebar .nav-link:hover .nav-icon-box {
            background: radial-gradient(circle at 30% 30%, rgba(220, 190, 140, 0.32), rgba(158, 121, 73, 0.22));
            border-color: rgba(197, 168, 128, 0.75);
            color: #ffffff;
            transform: translateY(-2px) scale(1.08);
            box-shadow: 0 6px 18px rgba(197, 168, 128, 0.35), 0 0 10px rgba(197, 168, 128, 0.2);
        }

        /* Active State (Current Page) */
        .sidebar .nav-link.active .nav-icon-box {
            background: linear-gradient(135deg, #e5c596 0%, #c5a880 50%, #9e7949 100%) !important;
            border: 1px solid rgba(255, 248, 230, 0.9) !important;
            color: #171513 !important;
            font-weight: 700;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(197, 168, 128, 0.5), inset 0 1px 2px rgba(255, 255, 255, 0.6) !important;
        }
        .sidebar .nav-link.active .nav-icon-box i {
            color: #171513 !important;
            filter: drop-shadow(0 1px 0 rgba(255, 255, 255, 0.3));
        }

        /* Golden Left Indicator Notch when Active */
        .sidebar .nav-link.active::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3.5px;
            height: 26px;
            background: linear-gradient(180deg, #dfc08f, #9e7949);
            border-radius: 0 4px 4px 0;
            box-shadow: 0 0 10px rgba(197, 168, 128, 0.7);
        }

        /* Logout special styling */
        .sidebar .nav-link-logout .nav-icon-box {
            background: rgba(220, 53, 69, 0.08);
            border-color: rgba(220, 53, 69, 0.25);
            color: #f17a85;
        }
        .sidebar .nav-link-logout:hover .nav-icon-box {
            background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%) !important;
            border-color: #ff6b7b !important;
            color: #ffffff !important;
            box-shadow: 0 4px 16px rgba(220, 53, 69, 0.45);
        }

        /* Text Label in Collapsed State */
        .sidebar .nav-label {
            opacity: 0;
            visibility: hidden;
            width: 0;
            max-width: 0;
            overflow: hidden;
            margin: 0 !important;
            transform: translateX(-10px);
            white-space: nowrap;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
        }

        /* ============================================================
           EXPANDED STATE ON DESKTOP HOVER
           ============================================================ */
        @media (min-width: 992px) {
            .sidebar:hover {
                width: var(--sidebar-expanded-width);
                box-shadow: 16px 0 45px rgba(0, 0, 0, 0.65);
            }
            .sidebar:hover .brand {
                justify-content: flex-start;
                padding: 16px 18px;
            }
            .sidebar:hover .brand-link {
                justify-content: flex-start;
            }
            .sidebar:hover .brand-icon-box {
                margin: 0;
            }
            .sidebar:hover .brand-info {
                opacity: 1;
                visibility: visible;
                width: auto;
                max-width: 200px;
                margin-left: 14px !important;
                transform: translateX(0);
            }
            .sidebar:hover .nav-section {
                justify-content: flex-start;
                padding: 12px 18px 4px 18px;
            }
            .sidebar:hover .section-text {
                display: block;
                opacity: 0.9;
                visibility: visible;
            }
            .sidebar:hover .section-divider-mini {
                display: none;
            }
            .sidebar:hover .nav-link {
                justify-content: flex-start;
                padding: 6px 12px;
                margin: 2px 12px;
                width: calc(100% - 24px);
                border-radius: 12px;
            }
            .sidebar:hover .nav-link:hover {
                background: rgba(197, 168, 128, 0.1) !important;
            }
            .sidebar:hover .nav-link.active {
                background: rgba(197, 168, 128, 0.15) !important;
            }
            .sidebar:hover .nav-icon-box {
                margin: 0;
            }
            .sidebar:hover .nav-label {
                opacity: 1;
                visibility: visible;
                width: auto;
                max-width: 200px;
                margin-left: 12px !important;
                transform: translateX(0);
                font-size: 0.88rem;
                font-weight: 500;
                color: rgba(255, 255, 255, 0.82);
            }
            .sidebar:hover .nav-link.active .nav-label {
                color: #ffffff;
                font-weight: 600;
                text-shadow: 0 0 10px rgba(197, 168, 128, 0.3);
            }
        }

        /* Main content margin matches collapsed sidebar */
        .main-content {
            margin-left: var(--sidebar-collapsed-width);
            min-height: 100vh;
            background-color: var(--canvas);
            transition: margin-left 0.3s ease;
        }
        /* Top navbar */
        .top-navbar {
            background: #ffffff;
            padding: 0.85rem 1.75rem;
            border-bottom: 1px solid rgba(197, 168, 128, 0.2);
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1040;
        }
        .top-navbar .btn-toggle-sidebar {
            display: none;
            border: none;
            background: none;
            font-size: 1.35rem;
            cursor: pointer;
            color: var(--dark);
        }
        .top-navbar h5 {
            color: var(--dark);
            letter-spacing: 0.5px;
        }
        .page-content {
            padding: 2rem;
        }
        /* Cards */
        .stat-card {
            background: #fff;
            border: 1px solid rgba(197, 168, 128, 0.25);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(28,26,23,0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(197, 168, 128, 0.15);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background-color: #faf5ee;
            color: var(--gold-dark);
            border: 1px solid rgba(197, 168, 128, 0.3);
        }
        .table-card {
            background: #fff;
            border: 1px solid rgba(197, 168, 128, 0.2);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(28,26,23,0.04);
        }
        .table-card .card-header {
            background: #faf8f5;
            border-bottom: 1px solid rgba(197, 168, 128, 0.2);
            padding: 1.1rem 1.4rem;
            font-weight: 600;
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.25rem;
            color: var(--dark);
        }
        .btn-primary {
            background-color: var(--gold) !important;
            border-color: var(--gold) !important;
            color: #fff !important;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: var(--dark) !important;
            border-color: var(--dark) !important;
            color: var(--gold-light) !important;
        }
        .btn-outline-primary {
            color: var(--gold-dark) !important;
            border-color: var(--gold) !important;
        }
        .btn-outline-primary:hover {
            background-color: var(--gold) !important;
            color: #fff !important;
        }
        /* Responsive Mobile Drawer */
        @media (max-width: 991.98px) {
            .sidebar {
                width: var(--sidebar-expanded-width) !important;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
            .sidebar .brand {
                justify-content: flex-start !important;
                padding: 16px 18px !important;
            }
            .sidebar .brand-link {
                justify-content: flex-start !important;
            }
            .sidebar .brand-icon-box {
                margin: 0 !important;
            }
            .sidebar .brand-info,
            .sidebar .nav-label {
                opacity: 1 !important;
                visibility: visible !important;
                width: auto !important;
                max-width: none !important;
                transform: none !important;
                margin-left: 12px !important;
            }
            .sidebar .nav-link {
                justify-content: flex-start !important;
                padding: 8px 14px !important;
                margin: 2px 12px !important;
                width: calc(100% - 24px) !important;
                border-radius: 12px !important;
            }
            .sidebar .nav-icon-box {
                margin: 0 !important;
            }
            .sidebar .nav-section {
                justify-content: flex-start !important;
                padding: 12px 18px 4px 18px !important;
            }
            .sidebar .section-text {
                display: block !important;
                opacity: 0.9 !important;
                visibility: visible !important;
            }
            .sidebar .section-divider-mini {
                display: none !important;
            }
            .top-navbar .btn-toggle-sidebar {
                display: block;
            }
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(28,26,23,0.6);
                z-index: 1049;
                display: none;
            }
            .sidebar-overlay.show {
                display: block;
            }
        }
        /* Custom scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(197, 168, 128, 0.25);
            border-radius: 2px;
        }
    </style>
</head>
<body>

<!-- Sidebar overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<?php include __DIR__ . '/sidebar.php'; ?>

<div class="main-content">
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="mb-0 fw-bold"><?= htmlspecialchars($pageTitle) ?></h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="<?= BASE_URL ?>index.php" class="btn btn-sm btn-outline-secondary d-none d-sm-inline-flex align-items-center gap-1">
                <i class="bi bi-shop"></i> Xem trang chủ
            </a>
            <div class="dropdown">
                <a class="d-flex align-items-center gap-2 text-decoration-none text-dark" href="#" role="button" data-bs-toggle="dropdown">
                    <?php if (!empty($currentUser['avatar'])): ?>
                        <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;border:1px solid var(--gold);" alt="Avatar">
                    <?php else: ?>
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:var(--gold);">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    <?php endif; ?>
                    <span class="d-none d-md-inline fw-medium"><?= htmlspecialchars($currentUser['full_name'] ?? 'Admin') ?></span>
                    <i class="bi bi-chevron-down small text-muted"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <?php if (isLoggedIn()): ?>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php"><i class="bi bi-person me-2 text-muted"></i>Hồ sơ cá nhân</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                    <?php else: ?>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>auth/login.php"><i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>auth/register.php"><i class="bi bi-person-plus me-2"></i>Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="page-content">
        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
            <?= $flash['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
