<?php
/**
 * Trang chủ - Beauty Salon & Spa Lounge
 * Lấy cảm hứng từ Goldust Spa Bali (https://goldustspa.com/)
 * Phong cách: Đơn giản, Sang trọng, Tĩnh lặng, Tinh tế
 */
require_once __DIR__ . '/config/config.php';

$db = getDB();

// Lấy danh mục dịch vụ đang hoạt động
$stmtCats = $db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY id ASC");
$categories = $stmtCats->fetchAll();

// Lấy toàn bộ dịch vụ đang hoạt động kèm tên danh mục
$stmtServices = $db->query("
    SELECT s.*, c.name AS category_name 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.id 
    WHERE s.status = 'active' 
    ORDER BY s.category_id ASC, s.id ASC
");
$services = $stmtServices->fetchAll();

// Lấy danh sách chuyên viên trị liệu kèm dịch vụ phụ trách
$stmtStaff = $db->query("
    SELECT u.id, u.full_name, u.avatar, u.phone, u.email,
           (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') 
            FROM staff_services ss 
            JOIN services s ON ss.service_id = s.id 
            JOIN categories c ON s.category_id = c.id 
            WHERE ss.staff_id = u.id) AS specialties
    FROM users u 
    WHERE u.role = 'staff' AND u.status = 'active' 
    ORDER BY u.id ASC
");
$staffMembers = $stmtStaff->fetchAll();

$currentUser = isLoggedIn() ? currentUser() : null;
$pageTitle = 'Trang chủ - Beauty Salon & Spa Lounge | Chuẩn Mực Nghệ Thuật Làm Đẹp';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <!-- Google Fonts: Cormorant Garamond (Editorial Serif) & Plus Jakarta Sans (Clean Modern) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Luxury Goldust Design System -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <style>
        /* Page & Navbar Styling */
        html, body {
            overflow-x: hidden !important;
            width: 100%;
            max-width: 100vw;
        }
        .luxury-navbar {
            background: rgba(28, 26, 23, 0.98);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(197, 168, 128, 0.3);
            padding: 10px 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.25);
            width: 100%;
        }
        .luxury-navbar .brand-text {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #ffffff;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .luxury-navbar .brand-text span {
            color: var(--gold);
        }
        .luxury-navbar .nav-link {
            color: #eae5de !important;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.75px;
            font-weight: 500;
            padding: 6px 10px !important;
            white-space: nowrap !important;
            transition: all 0.25s ease;
            position: relative;
        }
        .luxury-navbar .nav-link:hover,
        .luxury-navbar .nav-link.active {
            color: var(--gold) !important;
        }
        .luxury-navbar .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 10px;
            right: 10px;
            height: 2px;
            background: var(--gold);
        }
        .luxury-navbar .btn-outline-gold {
            color: #ffffff !important;
            border: 1px solid var(--gold) !important;
            background: transparent !important;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 7px 16px !important;
            border-radius: var(--radius-sm);
            white-space: nowrap !important;
            transition: all 0.25s ease;
        }
        .luxury-navbar .btn-outline-gold:hover {
            background-color: var(--gold) !important;
            color: #1c1a17 !important;
            border-color: var(--gold) !important;
            box-shadow: 0 4px 15px rgba(197, 168, 128, 0.35);
        }
        .luxury-navbar .btn-gold {
            background-color: var(--gold) !important;
            color: #1c1a17 !important;
            border: 1px solid var(--gold) !important;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 7px 18px !important;
            border-radius: var(--radius-sm);
            white-space: nowrap !important;
            transition: all 0.25s ease;
            box-shadow: 0 4px 15px rgba(197, 168, 128, 0.3);
        }
        .luxury-navbar .btn-gold:hover {
            background-color: #ffffff !important;
            color: #1c1a17 !important;
            border-color: #ffffff !important;
            transform: translateY(-1px);
        }
        .luxury-navbar .dropdown-menu {
            background-color: #24211d;
            border: 1px solid rgba(197, 168, 128, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }
        .luxury-navbar .dropdown-item {
            color: #e5ded5;
            font-size: 0.88rem;
            padding: 10px 18px;
        }
        .luxury-navbar .dropdown-item:hover {
            background-color: rgba(197, 168, 128, 0.15);
            color: var(--gold);
        }
        .luxury-navbar .dropdown-divider {
            border-color: rgba(197, 168, 128, 0.2);
        }

        /* Hero Video Section */
        .hero-banner {
            position: relative;
            min-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #1c1a17;
        }
        .hero-banner-video {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            object-fit: cover;
            z-index: 1;
            opacity: 0.42;
        }
        .hero-banner-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(28,26,23,0.55) 0%, rgba(28,26,23,0.78) 100%);
            z-index: 2;
        }
        .hero-banner-content {
            position: relative;
            z-index: 3;
            max-width: 960px;
            text-align: center;
            padding: 80px 20px;
        }

        /* Signature 24K Gold Banner */
        .signature-gold-section {
            background-color: var(--dark);
            color: #faf8f5;
            padding: 90px 0;
            position: relative;
            overflow: hidden;
        }
        .signature-gold-section::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(197, 168, 128, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        /* Filter Controls */
        .filter-section-wrapper {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(28,26,23,0.04);
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg luxury-navbar sticky-top">
    <div class="container-fluid px-3 px-lg-4 px-xl-5">
        <a class="navbar-brand brand-text d-flex align-items-center gap-2 me-3 me-lg-4" href="<?= BASE_URL ?>index.php">
            <i class="bi bi-flower1" style="color: var(--gold); font-size: 1.5rem;"></i>
            <span>BEAUTY</span> SALON
        </a>
        <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-2" style="color: var(--gold);"></i>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ps-lg-2">
                <li class="nav-item">
                    <a class="nav-link active" href="<?= BASE_URL ?>index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#servicesSection">Dịch vụ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#signatureGold">Liệu trình 24K</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#specialistsSection">Chuyên viên</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#aboutSection">Về chúng tôi</a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto me-1 me-lg-2">
                <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-gold dropdown-toggle d-flex align-items-center gap-2 py-1.5 px-3" type="button" data-bs-toggle="dropdown">
                            <?php if (!empty($currentUser['avatar'])): ?>
                                <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" class="rounded-circle" style="width:24px;height:24px;object-fit:cover;border:1px solid var(--gold);" alt="Avatar">
                            <?php else: ?>
                                <i class="bi bi-person-circle text-gold"></i>
                            <?php endif; ?>
                            <span class="text-white fw-medium"><?= htmlspecialchars($currentUser['full_name']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <?php if (isAdmin()): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/index.php"><i class="bi bi-speedometer2 me-2 text-gold"></i>Trang Quản Trị</a></li>
                            <?php elseif (isStaff()): ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>staff/index.php"><i class="bi bi-person-workspace me-2 text-gold"></i>Bàn Làm Việc</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>booking/my_appointments.php"><i class="bi bi-calendar-event me-2 text-gold"></i>Lịch Hẹn Của Tôi</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php"><i class="bi bi-person me-2 text-gold"></i>Hồ Sơ Cá Nhân</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng Xuất</a></li>
                        </ul>
                    </div>
                    <a href="<?= BASE_URL ?>booking/index.php" class="btn btn-gold py-1.5 px-3">
                        <i class="bi bi-calendar-plus me-1"></i> Đặt Lịch Hẹn
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-outline-gold py-1.5 px-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Đăng Nhập
                    </a>
                    <a href="<?= BASE_URL ?>auth/register.php" class="btn btn-outline-gold py-1.5 px-3 d-none d-xxl-inline-flex">
                        <i class="bi bi-person-plus me-1"></i> Đăng Ký
                    </a>
                    <a href="<?= BASE_URL ?>booking/index.php" class="btn btn-gold py-1.5 px-3">
                        <i class="bi bi-calendar-plus me-1"></i> Đặt Lịch Hẹn
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Video Section (Goldust Spa Inspiration) -->
<section class="hero-banner">
    <!-- Real Video extracted from Goldust Spa Bali -->
    <video class="hero-banner-video" autoplay muted loop playsinline poster="https://images.prismic.io/goldust/Z3b2XJbqstJ99AEQ_GOLDDUST-08--1---2-.jpg?auto=format,compress">
        <source src="https://goldust.cdn.prismic.io/goldust/Z37waJbqstJ99Mg0_SGASPA.mp4" type="video/mp4">
        <source src="https://goldust.cdn.prismic.io/goldust/Z3lYLJbqstJ99Cv2_SGASPA.webm" type="video/webm">
    </video>
    <div class="hero-banner-overlay"></div>
    <div class="hero-banner-content">
        <span class="hero-badge">Goldust Sanctuary & Beauty Lounge</span>
        <h1 class="display-3 text-white fw-light mb-4 font-serif">
            Nơi Tinh Hoa Làm Đẹp & Thư Giãn Tối Thượng Giao Hòa
        </h1>
        <p class="lead text-light opacity-90 mb-5 mx-auto" style="max-width: 720px; font-weight: 300; font-size: 1.15rem; line-height: 1.8;">
            Trải nghiệm không gian trị liệu chuẩn phong cách Goldust Bali — Nuôi dưỡng làn da và tâm hồn với liệu pháp vàng 24K hoàng gia, tinh dầu hữu cơ tinh khiết và nghệ thuật chăm sóc độc bản.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="<?= BASE_URL ?>booking/index.php" class="btn btn-gold btn-lg px-4 py-3">
                <i class="bi bi-calendar-check me-2"></i> Đặt Lịch Trải Nghiệm Ngay
            </a>
            <a href="#servicesSection" class="btn btn-outline-gold btn-lg px-4 py-3 text-white border-white">
                <i class="bi bi-compass me-2"></i> Khám Phá Dịch Vụ & Liệu Trình
            </a>
        </div>
    </div>
</section>

<!-- Main Container with Integrated Search & Filters -->
<main class="container my-5">

    <!-- Control Center: Live Search & Multi-Filters (Theo Yêu Cầu Chung) -->
    <div class="filter-section-wrapper" id="servicesSection">
        <div class="text-center mb-4">
            <span class="section-subtitle">Chăm sóc & Trị liệu cao cấp</span>
            <h2 class="font-serif">Tìm Kiếm & Lọc Dịch Vụ Spa</h2>
            <div class="gold-divider"></div>
            <p class="text-muted mx-auto" style="max-width: 650px;">
                Chọn lựa liệu trình hoàn hảo phù hợp với nhu cầu thư giãn, tái tạo làn da và phục hồi năng lượng của bạn.
            </p>
        </div>

        <!-- Filter Row 1: Keyword Search + Dropdown Filters -->
        <div class="row g-3 align-items-center mb-3">
            <!-- Keyword Search Input -->
            <div class="col-lg-5 col-md-12">
                <div class="position-relative">
                    <input type="text" id="liveServiceSearch" class="form-control search-input-luxury w-100 ps-5" 
                           placeholder="Tìm kiếm liệu trình (tên dịch vụ, mô tả, từ khóa)...">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                    <button class="btn btn-sm btn-link text-muted position-absolute top-50 end-0 translate-middle-y me-2 d-none" 
                            id="clearSearchBtn" type="button" style="text-decoration:none;">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </div>
            </div>

            <!-- Price Range Dropdown -->
            <div class="col-lg-3 col-md-6 col-sm-6">
                <select id="priceFilter" class="form-select search-input-luxury">
                    <option value="all">Tất cả mức giá</option>
                    <option value="under_300">Dưới 300.000 đ</option>
                    <option value="300_600">300.000 đ - 600.000 đ</option>
                    <option value="above_600">Trên 600.000 đ</option>
                </select>
            </div>

            <!-- Duration Dropdown -->
            <div class="col-lg-2 col-md-6 col-sm-6">
                <select id="durationFilter" class="form-select search-input-luxury">
                    <option value="all">Tất cả thời lượng</option>
                    <option value="under_45">Dưới 45 phút</option>
                    <option value="45_75">45 - 75 phút</option>
                    <option value="above_75">Trên 75 phút</option>
                </select>
            </div>

            <!-- Sort Dropdown -->
            <div class="col-lg-2 col-md-12">
                <select id="sortBy" class="form-select search-input-luxury">
                    <option value="default">Sắp xếp mặc định</option>
                    <option value="price_asc">Giá: Thấp đến Cao</option>
                    <option value="price_desc">Giá: Cao đến Thấp</option>
                    <option value="duration_asc">Thời lượng: Ngắn đến Dài</option>
                </select>
            </div>
        </div>

        <!-- Filter Row 2: Category Pills -->
        <div class="d-flex flex-wrap align-items-center justify-content-between pt-2 border-top border-light mt-3">
            <div class="d-flex flex-wrap align-items-center" id="categoryPillContainer">
                <span class="filter-pill active" data-category="all">
                    <i class="bi bi-grid-fill me-1"></i> Tất cả danh mục
                </span>
                <?php foreach ($categories as $cat): ?>
                    <span class="filter-pill" data-category="<?= $cat['id'] ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                <span class="small text-muted">
                    Hiển thị <strong id="filterCounter" class="text-dark"><?= count($services) ?></strong> / <?= count($services) ?> dịch vụ
                </span>
                <button type="button" class="btn btn-sm btn-link text-muted" id="resetFiltersBtn" style="font-size: 0.78rem;">
                    <i class="bi bi-arrow-counterclockwise"></i> Đặt lại
                </button>
            </div>
        </div>
    </div>

    <!-- Services Grid Display (With Real Photos & Dynamic Filtering) -->
    <div class="row g-4 mt-2" id="servicesGrid">
        <?php foreach ($services as $svc): ?>
            <div class="col-xl-3 col-lg-4 col-md-6 service-item" 
                 data-id="<?= $svc['id'] ?>"
                 data-name="<?= htmlspecialchars(mb_strtolower($svc['name'])) ?>"
                 data-desc="<?= htmlspecialchars(mb_strtolower($svc['description'] ?? '')) ?>"
                 data-category="<?= $svc['category_id'] ?>"
                 data-price="<?= $svc['price'] ?>"
                 data-duration="<?= $svc['duration'] ?>">
                <div class="service-card-luxury">
                    <!-- Real High-Res Photo Illustration -->
                    <div class="service-img-wrapper">
                        <img src="<?= htmlspecialchars($svc['image'] ?: 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=800&q=80') ?>" 
                             alt="<?= htmlspecialchars($svc['name']) ?>" 
                             loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=800&q=80'">
                        <span class="service-category-tag">
                            <?= htmlspecialchars($svc['category_name']) ?>
                        </span>
                    </div>
                    <div class="service-body">
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <span class="service-duration-tag">
                                <i class="bi bi-clock me-1 text-gold"></i><?= $svc['duration'] ?> phút
                            </span>
                            <span class="service-price-tag">
                                <?= formatCurrency($svc['price']) ?>
                            </span>
                        </div>
                        <h4 class="service-title"><?= htmlspecialchars($svc['name']) ?></h4>
                        <p class="small text-muted flex-grow-1 mb-3">
                            <?= htmlspecialchars($svc['description'] ?: 'Liệu trình thư giãn và chăm sóc chuyên sâu mang lại sự tươi mới và rạng rỡ.') ?>
                        </p>
                        <div class="pt-3 border-top border-light mt-auto">
                            <a href="<?= BASE_URL ?>booking/index.php?service_id=<?= $svc['id'] ?>" class="btn btn-gold w-100">
                                <i class="bi bi-calendar-check me-2"></i> Đặt Lịch Liệu Trình
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty State when filter yields 0 results -->
    <div id="noServicesFound" class="text-center py-5 d-none">
        <div class="p-5 bg-white rounded border border-light shadow-sm max-w-md mx-auto" style="max-width: 500px;">
            <i class="bi bi-search text-gold" style="font-size: 3rem;"></i>
            <h4 class="font-serif mt-3">Không tìm thấy liệu trình phù hợp</h4>
            <p class="text-muted small">Hãy thử điều chỉnh từ khóa tìm kiếm hoặc bỏ chọn bớt bộ lọc giá, thời lượng.</p>
            <button class="btn btn-gold btn-sm mt-2" onclick="resetAllFilters()">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Khôi phục bộ lọc mặc định
            </button>
        </div>
    </div>

</main>

<!-- Signature 24K Gold Facial Banner Section (Inspired by Goldust Spa Bali) -->
<section class="signature-gold-section my-5" id="signatureGold">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="position-relative">
                    <img src="https://images.prismic.io/goldust/Zzl3ia8jQArT08VU_GOLDDUST-11.jpg?auto=format,compress" 
                         alt="Goldust 24K Gold Facial" 
                         class="img-fluid rounded shadow-lg" 
                         style="border: 1px solid rgba(197, 168, 128, 0.4); width: 100%; object-fit: cover; max-height: 480px;">
                    <div class="position-absolute bottom-0 start-0 m-4 p-3 rounded" style="background: rgba(28,26,23,0.85); backdrop-filter: blur(8px); border: 1px solid rgba(197, 168, 128, 0.3);">
                        <span class="d-block text-gold text-uppercase small" style="letter-spacing: 2px;">Signature Treatment</span>
                        <strong class="text-white font-serif fs-5">24K Pure Gold Leaf Ritual</strong>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <span class="section-subtitle">Độc Bản & Đẳng Cấp Hoàng Gia</span>
                <h2 class="display-5 font-serif text-white mb-4">Liệu Trình Vàng 24K Nguyên Chất</h2>
                <div class="gold-divider ms-0 mb-4"></div>
                <p class="text-light opacity-90 mb-4" style="line-height: 1.8;">
                    Được kế thừa từ bí quyết làm đẹp hoàng tộc và chuẩn mực từ Goldust Bali, liệu trình đắp mặt nạ lá vàng 24K nguyên chất kích thích tăng sinh collagen, tăng cường tuần hoàn vi mạch máu và thải độc tầng sâu, mang lại làn da căng bóng ngọc ngà, tỏa sáng rạng ngời.
                </p>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-check2-circle text-gold fs-5"></i>
                            <div>
                                <strong class="text-white d-block">Trẻ hóa & Chống lão hóa</strong>
                                <small class="text-white-50">Tái sinh độ đàn hồi tự nhiên</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-check2-circle text-gold fs-5"></i>
                            <div>
                                <strong class="text-white d-block">Thải độc & Căng bóng</strong>
                                <small class="text-white-50">Đào thải độc tố kim loại nặng</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <a href="<?= BASE_URL ?>booking/index.php?service_id=1" class="btn btn-gold btn-lg">
                        <i class="bi bi-stars me-2"></i> Trải Nghiệm 24K Gold Facial
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Specialist Team Section with Real Photos -->
<section class="container my-5 py-4" id="specialistsSection">
    <div class="text-center mb-5">
        <span class="section-subtitle">Đội ngũ nghệ nhân & chuyên viên trị liệu</span>
        <h2 class="font-serif">Bậc Thầy Chăm Sóc & Trị Liệu</h2>
        <div class="gold-divider"></div>
        <p class="text-muted mx-auto" style="max-width: 600px;">
            Mỗi liệu trình tại Beauty Salon được thực hiện bởi đội ngũ kỹ thuật viên giàu kinh nghiệm, đạt chứng chỉ chuyên môn quốc tế và tận tâm với từng khách hàng.
        </p>
    </div>

    <div class="row g-4 justify-content-center">
        <?php foreach ($staffMembers as $staff): ?>
            <div class="col-lg-4 col-md-6">
                <div class="staff-card-luxury">
                    <div class="staff-photo-wrapper">
                        <img src="<?= htmlspecialchars($staff['avatar'] ?: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&q=80') ?>" 
                             alt="<?= htmlspecialchars($staff['full_name']) ?>"
                             loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&q=80'">
                    </div>
                    <div class="staff-info">
                        <h4 class="staff-name"><?= htmlspecialchars($staff['full_name']) ?></h4>
                        <span class="staff-role d-block mb-2">Master Therapist & Specialist</span>
                        <p class="small text-muted mb-3" style="min-height: 40px;">
                            <i class="bi bi-flower2 text-gold me-1"></i> Chuyên môn: 
                            <span class="text-dark fw-medium">
                                <?= htmlspecialchars($staff['specialties'] ?: 'Chăm sóc da chuyên sâu & Massage Thư giãn') ?>
                            </span>
                        </p>
                        <div class="d-flex justify-content-center gap-2 pt-2 border-top border-light">
                            <a href="<?= BASE_URL ?>booking/index.php?staff_id=<?= $staff['id'] ?>" class="btn btn-outline-gold btn-sm w-100">
                                <i class="bi bi-calendar-check me-1"></i> Đặt Lịch Với Chuyên Viên
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Why Choose Us / Philosophy Section -->
<section class="container my-5 py-4" id="aboutSection">
    <div class="p-5 rounded bg-white border border-light shadow-sm">
        <div class="row g-4 align-items-center">
            <div class="col-lg-5">
                <span class="section-subtitle">Triết lý dịch vụ</span>
                <h2 class="font-serif mb-3">Tôn Vinh Vẻ Đẹp Thuần Khiết</h2>
                <p class="text-muted mb-4" style="line-height: 1.8;">
                    Chúng tôi tin rằng làm đẹp không chỉ là diện mạo bên ngoài mà còn là hành trình phục hồi năng lượng tinh thần, giúp bạn tìm lại sự cân bằng sau những bộn bề cuộc sống.
                </p>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon flex-shrink-0" style="width: 42px; height: 42px; font-size: 1.1rem;">
                            <i class="bi bi-gem"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 font-serif fs-5">Mỹ Phẩm Hữu Cơ & Vàng 24K</h6>
                            <p class="small text-muted mb-0">100% chiết xuất thiên nhiên, an toàn tuyệt đối cho mọi làn da nhạy cảm.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon flex-shrink-0" style="width: 42px; height: 42px; font-size: 1.1rem;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 font-serif fs-5">Quy Chuẩn Vệ Sinh Y Tế</h6>
                            <p class="small text-muted mb-0">Dụng cụ tiệt trùng khép kín, khăn và phụ kiện liệu trình dùng một lần riêng biệt.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon flex-shrink-0" style="width: 42px; height: 42px; font-size: 1.1rem;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 font-serif fs-5">Hệ Thống Đặt Lịch Chuẩn Xác</h6>
                            <p class="small text-muted mb-0">Không cần chờ đợi, quản lý thời gian cá nhân hóa với công nghệ đặt hẹn thông minh.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-6">
                        <img src="https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=700&q=80" 
                             class="img-fluid rounded shadow-sm mb-3" alt="Spa Ambience" style="height: 240px; width: 100%; object-fit: cover;">
                        <img src="https://images.unsplash.com/photo-1519699047748-de8e457a634e?auto=format&fit=crop&w=700&q=80" 
                             class="img-fluid rounded shadow-sm" alt="Facial treatment" style="height: 180px; width: 100%; object-fit: cover;">
                    </div>
                    <div class="col-6">
                        <img src="https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=700&q=80" 
                             class="img-fluid rounded shadow-sm mb-3" alt="Hair salon" style="height: 180px; width: 100%; object-fit: cover;">
                        <img src="https://images.unsplash.com/photo-1600334089648-b0d9d3028eb2?auto=format&fit=crop&w=700&q=80" 
                             class="img-fluid rounded shadow-sm" alt="Massage room" style="height: 240px; width: 100%; object-fit: cover;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Customer Reviews Section -->
<section class="container my-5 py-4">
    <div class="text-center mb-5">
        <span class="section-subtitle">Đánh giá từ khách hàng</span>
        <h2 class="font-serif">Cảm Nhận Trải Nghiệm</h2>
        <div class="gold-divider"></div>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="p-4 bg-white rounded border border-light h-100 shadow-sm d-flex flex-column">
                <div class="text-gold mb-3">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p class="text-muted small fst-italic flex-grow-1" style="line-height: 1.7;">
                    "Liệu trình Vàng 24K ở đây thực sự vượt ngoài mong đợi! Da mình sau khi chăm sóc căng mịn rõ rệt, không gian tĩnh lặng và tiếng nhạc nền rất êm dịu."
                </p>
                <div class="pt-3 border-top border-light d-flex align-items-center gap-3">
                    <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=100&q=80" class="rounded-circle" style="width:42px;height:42px;object-fit:cover;" alt="Reviewer">
                    <div>
                        <strong class="text-dark d-block small">Chị Hoàng Yến</strong>
                        <small class="text-muted">Khách hàng VIP</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4 bg-white rounded border border-light h-100 shadow-sm d-flex flex-column">
                <div class="text-gold mb-3">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p class="text-muted small fst-italic flex-grow-1" style="line-height: 1.7;">
                    "Massage Thụy Điển với tinh dầu hữu cơ của chuyên viên Hương giải tỏa hết cơn đau vai gáy do ngồi văn phòng cả tuần. Đặt lịch trên web rất nhanh chóng."
                </p>
                <div class="pt-3 border-top border-light d-flex align-items-center gap-3">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=100&q=80" class="rounded-circle" style="width:42px;height:42px;object-fit:cover;" alt="Reviewer">
                    <div>
                        <strong class="text-dark d-block small">Anh Quốc Đạt</strong>
                        <small class="text-muted">Doanh nhân</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4 bg-white rounded border border-light h-100 shadow-sm d-flex flex-column">
                <div class="text-gold mb-3">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p class="text-muted small fst-italic flex-grow-1" style="line-height: 1.7;">
                    "Dịch vụ làm móng gel và phục hồi tóc collagen rất chu đáo, nhân viên nhẹ nhàng, trà hoa cúc và không gian ngập tràn hương thảo mộc rất thư giãn."
                </p>
                <div class="pt-3 border-top border-light d-flex align-items-center gap-3">
                    <img src="https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=100&q=80" class="rounded-circle" style="width:42px;height:42px;object-fit:cover;" alt="Reviewer">
                    <div>
                        <strong class="text-dark d-block small">Chị Minh Trang</strong>
                        <small class="text-muted">Khách hàng thân thiết</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Luxury Footer -->
<footer class="luxury-footer py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <h4 class="mb-3 text-gold font-serif">BEAUTY SALON & SPA</h4>
                <p class="small text-light opacity-75 mb-4" style="line-height: 1.8;">
                    Hệ thống quản lý và trải nghiệm dịch vụ làm đẹp cao cấp. Lấy cảm hứng từ nghệ thuật thư giãn đỉnh cao, mang đến sự chăm sóc hoàn mỹ cho quý khách.
                </p>
                <div class="d-flex gap-3 text-gold">
                    <a href="#" class="text-gold fs-5"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-gold fs-5"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-gold fs-5"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="text-gold fs-5"><i class="bi bi-tiktok"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <h6 class="text-white text-uppercase font-sans mb-3" style="letter-spacing: 2px; font-size: 0.8rem;">Dịch vụ</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="#servicesSection">Chăm sóc da & Trị liệu</a></li>
                    <li class="mb-2"><a href="#servicesSection">Massage Body Thụy Điển</a></li>
                    <li class="mb-2"><a href="#signatureGold">Liệu trình 24K Gold</a></li>
                    <li class="mb-2"><a href="#servicesSection">Chăm sóc móng & Tóc</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white text-uppercase font-sans mb-3" style="letter-spacing: 2px; font-size: 0.8rem;">Giờ mở cửa</h6>
                <ul class="list-unstyled small text-light opacity-75">
                    <li class="mb-2"><i class="bi bi-clock me-2 text-gold"></i>Thứ Hai - Thứ Sáu: 08:30 - 20:30</li>
                    <li class="mb-2"><i class="bi bi-clock me-2 text-gold"></i>Thứ Bảy - Chủ Nhật: 08:00 - 21:00</li>
                    <li class="mb-2"><i class="bi bi-check2-circle me-2 text-gold"></i>Lễ & Tết: Mở cửa theo lịch hẹn</li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white text-uppercase font-sans mb-3" style="letter-spacing: 2px; font-size: 0.8rem;">Liên hệ & Đặt lịch</h6>
                <ul class="list-unstyled small text-light opacity-75">
                    <li class="mb-2"><i class="bi bi-geo-alt me-2 text-gold"></i>123 Đường Nguyễn Huệ, Quận 1, TP. HCM</li>
                    <li class="mb-2"><i class="bi bi-telephone me-2 text-gold"></i>Hotline: 0901 000 001</li>
                    <li class="mb-2"><i class="bi bi-envelope me-2 text-gold"></i>booking@beautysalon.vn</li>
                </ul>
            </div>
        </div>
        <div class="pt-4 mt-4 border-top border-secondary text-center small text-light opacity-50">
            <p class="mb-0">&copy; <?= date('Y') ?> Beauty Salon & Spa Lounge. Thiết kế & Phát triển theo tiêu chuẩn BTL Đề tài 19.</p>
        </div>
    </div>
</footer>

<!-- Floating Ambient Spa Audio Player (Extracted from Goldust Spa Bali) -->
<div class="spa-audio-player shadow-lg" id="spaAudioContainer" title="Âm nhạc thư giãn - Goldust Spa Lounge Soundtrack">
    <button class="spa-audio-btn" id="spaAudioBtn" type="button" aria-label="Phát / Dừng nhạc nền Spa">
        <i class="bi bi-play-fill" id="spaAudioIcon"></i>
    </button>
    <div class="d-none d-sm-flex flex-column">
        <span class="spa-audio-label">Goldust Spa Ambient</span>
        <small class="text-white-50" style="font-size: 0.65rem;">Thư giãn & Tĩnh lặng</small>
    </div>
    <div class="sound-waves" id="soundWaves">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
    <audio id="spaAudioTrack" loop preload="metadata">
        <source src="https://goldust.cdn.prismic.io/goldust/Z4E0LJbqstJ99Uif_Sequence01.mp3" type="audio/mpeg">
    </audio>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Real-time Filter & Search Engine Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('liveServiceSearch');
    const clearBtn = document.getElementById('clearSearchBtn');
    const priceFilter = document.getElementById('priceFilter');
    const durationFilter = document.getElementById('durationFilter');
    const sortBy = document.getElementById('sortBy');
    const categoryPills = document.querySelectorAll('#categoryPillContainer .filter-pill');
    const resetBtn = document.getElementById('resetFiltersBtn');
    const serviceItems = Array.from(document.querySelectorAll('.service-item'));
    const counterEl = document.getElementById('filterCounter');
    const noResultsEl = document.getElementById('noServicesFound');
    const gridContainer = document.getElementById('servicesGrid');

    let activeCategory = 'all';

    function applyFilters() {
        const query = searchInput.value.trim().toLowerCase();
        const priceVal = priceFilter.value;
        const durationVal = durationFilter.value;

        // Toggle clear search button
        if (query.length > 0) {
            clearBtn.classList.remove('d-none');
        } else {
            clearBtn.classList.add('d-none');
        }

        let visibleCount = 0;

        serviceItems.forEach(item => {
            const name = item.dataset.name || '';
            const desc = item.dataset.desc || '';
            const cat = item.dataset.category;
            const price = parseFloat(item.dataset.price) || 0;
            const duration = parseInt(item.dataset.duration) || 0;

            // 1. Keyword check
            const matchQuery = !query || name.includes(query) || desc.includes(query);

            // 2. Category check
            const matchCat = (activeCategory === 'all') || (cat === activeCategory);

            // 3. Price check
            let matchPrice = true;
            if (priceVal === 'under_300') {
                matchPrice = price < 300000;
            } else if (priceVal === '300_600') {
                matchPrice = (price >= 300000 && price <= 600000);
            } else if (priceVal === 'above_600') {
                matchPrice = price > 600000;
            }

            // 4. Duration check
            let matchDuration = true;
            if (durationVal === 'under_45') {
                matchDuration = duration < 45;
            } else if (durationVal === '45_75') {
                matchDuration = (duration >= 45 && duration <= 75);
            } else if (durationVal === 'above_75') {
                matchDuration = duration > 75;
            }

            if (matchQuery && matchCat && matchPrice && matchDuration) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Sắp xếp
        const sortVal = sortBy.value;
        if (sortVal !== 'default') {
            const sorted = serviceItems.slice().sort((a, b) => {
                const priceA = parseFloat(a.dataset.price) || 0;
                const priceB = parseFloat(b.dataset.price) || 0;
                const durA = parseInt(a.dataset.duration) || 0;
                const durB = parseInt(b.dataset.duration) || 0;

                if (sortVal === 'price_asc') return priceA - priceB;
                if (sortVal === 'price_desc') return priceB - priceA;
                if (sortVal === 'duration_asc') return durA - durB;
                return 0;
            });
            sorted.forEach(el => gridContainer.appendChild(el));
        }

        // Update counter
        counterEl.textContent = visibleCount;
        if (visibleCount === 0) {
            noResultsEl.classList.remove('d-none');
        } else {
            noResultsEl.classList.add('d-none');
        }
    }

    // Category pills click handler
    categoryPills.forEach(pill => {
        pill.addEventListener('click', function() {
            categoryPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            activeCategory = this.dataset.category;
            applyFilters();
        });
    });

    // Inputs events
    searchInput.addEventListener('input', applyFilters);
    priceFilter.addEventListener('change', applyFilters);
    durationFilter.addEventListener('change', applyFilters);
    sortBy.addEventListener('change', applyFilters);

    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        applyFilters();
        searchInput.focus();
    });

    window.resetAllFilters = function() {
        searchInput.value = '';
        priceFilter.value = 'all';
        durationFilter.value = 'all';
        sortBy.value = 'default';
        activeCategory = 'all';
        categoryPills.forEach(p => {
            if (p.dataset.category === 'all') p.classList.add('active');
            else p.classList.remove('active');
        });
        applyFilters();
    };

    resetBtn.addEventListener('click', resetAllFilters);

    // Initial check
    applyFilters();
});

// Ambient Audio Controller
(function() {
    const audio = document.getElementById('spaAudioTrack');
    const btn = document.getElementById('spaAudioBtn');
    const icon = document.getElementById('spaAudioIcon');
    const waves = document.getElementById('soundWaves');

    if (!audio || !btn) return;

    audio.volume = 0.4;

    function updateAudioUI(isPlaying) {
        if (isPlaying) {
            icon.className = 'bi bi-pause-fill';
            waves.classList.add('playing');
        } else {
            icon.className = 'bi bi-play-fill';
            waves.classList.remove('playing');
        }
    }

    btn.addEventListener('click', function() {
        if (audio.paused) {
            audio.play().then(function() {
                updateAudioUI(true);
                sessionStorage.setItem('spa_audio_playing', 'true');
            }).catch(function(e) {
                console.log('Autoplay prevented:', e);
            });
        } else {
            audio.pause();
            updateAudioUI(false);
            sessionStorage.setItem('spa_audio_playing', 'false');
        }
    });

    if (sessionStorage.getItem('spa_audio_playing') === 'true') {
        audio.play().then(function() {
            updateAudioUI(true);
        }).catch(function() {
            updateAudioUI(false);
        });
    }
})();
</script>
</body>
</html>
