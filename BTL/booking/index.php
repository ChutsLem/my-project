<?php
/**
 * Trang Đặt Lịch - Beauty Salon & Spa Lounge
 * Wizard đặt lịch chuyên nghiệp, sang trọng
 * Lấy cảm hứng từ Goldust Spa Bali
 */
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    setFlash('Vui lòng đăng nhập để đặt lịch hẹn.', 'warning');
    redirect(BASE_URL . 'auth/login.php');
}

$csrfToken = generateCSRF();
$pageTitle = 'Đặt Lịch Trải Nghiệm Dịch Vụ';
$currentUser = currentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Beauty Salon & Spa</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --gold: #c5a880;
            --gold-light: #f4ede4;
            --gold-dark: #9e7949;
            --gold-hover: #b38f5e;
            --dark: #1c1a17;
            --canvas: #faf8f5;
            --border-light: #e9e4db;
        }
        body {
            background: var(--canvas);
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #2b2723;
        }
        .page-header-luxury {
            background: var(--dark);
            color: #fff;
            padding: 3rem 0 2.5rem;
            border-bottom: 1px solid rgba(197, 168, 128, 0.25);
            margin-bottom: 2.5rem;
        }
        .wizard-steps-luxury {
            display: flex;
            justify-content: center;
            margin-bottom: 2.5rem;
            position: relative;
        }
        .wizard-steps-luxury::before {
            content: '';
            position: absolute;
            top: 24px;
            left: 15%;
            right: 15%;
            height: 2px;
            background: var(--border-light);
            z-index: 0;
        }
        .wizard-step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            flex: 1;
            max-width: 170px;
        }
        .step-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.05rem;
            color: #8c827a;
            transition: all 0.3s ease;
            margin-bottom: 0.6rem;
        }
        .wizard-step-item.active .step-circle {
            border-color: var(--gold);
            background: #ffffff;
            color: var(--gold-dark);
            box-shadow: 0 0 0 4px rgba(197, 168, 128, 0.2);
        }
        .wizard-step-item.completed .step-circle {
            background: var(--gold);
            border-color: var(--gold);
            color: #ffffff;
        }
        .step-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #8c827a;
            font-weight: 500;
            text-align: center;
        }
        .wizard-step-item.active .step-label {
            color: var(--dark);
            font-weight: 700;
        }
        .wizard-step-item.completed .step-label {
            color: var(--gold-dark);
        }

        .step-content { display: none; }
        .step-content.active { display: block; animation: fadeIn 0.35s ease; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Service Selection Card */
        .service-select-card {
            background: #ffffff;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.25s ease;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .service-select-card:hover {
            border-color: var(--gold);
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(197, 168, 128, 0.15);
        }
        .service-select-card.selected {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.3), 0 10px 30px rgba(197, 168, 128, 0.15);
        }
        .service-select-img {
            height: 175px;
            width: 100%;
            object-fit: cover;
        }
        .service-selected-badge {
            display: none;
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--gold);
            color: #ffffff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 5;
        }
        .service-select-card.selected .service-selected-badge {
            display: flex;
        }

        /* Staff Specialist Card */
        .staff-select-card {
            background: #ffffff;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.25s ease;
            text-align: center;
            padding: 1.5rem;
            position: relative;
            height: 100%;
        }
        .staff-select-card:hover {
            border-color: var(--gold);
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(197, 168, 128, 0.15);
        }
        .staff-select-card.selected {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.3);
        }
        .staff-avatar-round {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--border-light);
            margin-bottom: 0.85rem;
            transition: border-color 0.25s;
        }
        .staff-select-card.selected .staff-avatar-round {
            border-color: var(--gold);
        }

        /* Time Slots */
        .slot-btn {
            display: inline-block;
            padding: 11px 20px;
            margin: 6px;
            border: 1px solid var(--border-light);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            background: #ffffff;
            color: var(--dark);
            font-size: 0.92rem;
        }
        .slot-btn:hover {
            border-color: var(--gold);
            color: var(--gold-dark);
            background: #faf5ee;
        }
        .slot-btn.selected {
            background: var(--dark);
            color: var(--gold-light);
            border-color: var(--dark);
            box-shadow: 0 4px 12px rgba(28,26,23,0.15);
        }

        /* Luxury Filter Pills */
        .booking-cat-pill {
            padding: 7px 18px;
            border: 1px solid var(--border-light);
            background: #ffffff;
            color: #78716a;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
            margin: 3px;
        }
        .booking-cat-pill:hover,
        .booking-cat-pill.active {
            background-color: var(--dark);
            border-color: var(--dark);
            color: var(--gold) !important;
        }

        .main-wizard-card {
            background: #ffffff;
            border: 1px solid var(--border-light);
            border-radius: 8px;
            box-shadow: 0 6px 25px rgba(28,26,23,0.04);
            padding: 30px;
        }

        .summary-box {
            background: #faf8f5;
            border: 1px solid var(--border-light);
            border-radius: 8px;
            padding: 24px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed var(--border-light);
        }
        .summary-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>

<!-- Header Banner -->
<div class="page-header-luxury">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <a href="<?= BASE_URL ?>index.php" class="text-gold text-decoration-none small text-uppercase mb-2 d-inline-block" style="letter-spacing: 2px;">
                    <i class="bi bi-arrow-left me-1"></i> Trở về Trang Chủ
                </a>
                <h1 class="display-6 font-serif mb-1 text-white"><?= $pageTitle ?></h1>
                <p class="text-white-50 mb-0 font-sans small">Chuẩn mực dịch vụ trị liệu thư giãn & làm đẹp hoàng gia</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= BASE_URL ?>booking/my_appointments.php" class="btn btn-outline-gold text-white border-white btn-sm">
                    <i class="bi bi-calendar-event me-1"></i> Lịch Hẹn Của Tôi
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <!-- Wizard Steps Indicator -->
    <div class="wizard-steps-luxury">
        <div class="wizard-step-item active" data-step="1">
            <div class="step-circle">1</div>
            <span class="step-label">Chọn Dịch Vụ</span>
        </div>
        <div class="wizard-step-item" data-step="2">
            <div class="step-circle">2</div>
            <span class="step-label">Chọn Chuyên Viên</span>
        </div>
        <div class="wizard-step-item" data-step="3">
            <div class="step-circle">3</div>
            <span class="step-label">Ngày & Giờ</span>
        </div>
        <div class="wizard-step-item" data-step="4">
            <div class="step-circle">4</div>
            <span class="step-label">Xác Nhận</span>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer"></div>

    <div class="main-wizard-card">
        <!-- Step 1: Chọn dịch vụ -->
        <div class="step-content active" id="step1">
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
                <div>
                    <span class="section-subtitle">Bước 1 trên 4</span>
                    <h3 class="font-serif mb-1">Lựa Chọn Liệu Trình Trải Nghiệm</h3>
                    <p class="text-muted small mb-0">Tìm kiếm và chọn dịch vụ phù hợp với nhu cầu chăm sóc của bạn</p>
                </div>
            </div>

            <!-- Search and Category Filter -->
            <div class="p-3 bg-light rounded border border-light mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <div class="position-relative">
                            <input type="text" id="svcKeyword" class="form-control search-input-luxury w-100 ps-5" 
                                   placeholder="Tìm kiếm dịch vụ theo tên, từ khóa...">
                            <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-wrap align-items-center" id="bookingCategoryFilter">
                            <span class="booking-cat-pill active" data-category="0">Tất cả danh mục</span>
                            <!-- Categories dynamically injected -->
                        </div>
                    </div>
                </div>
            </div>

            <div id="servicesLoading" class="text-center py-5">
                <div class="spinner-border text-gold" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="small text-muted mt-2">Đang tải danh sách dịch vụ...</p>
            </div>

            <div class="row g-3" id="servicesList"></div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-light">
                <span class="text-muted small" id="step1SelectionText">Chưa có dịch vụ nào được chọn</span>
                <button class="btn btn-gold px-4" id="btnStep1Next" disabled>
                    Tiếp theo: Chọn Chuyên Viên <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
        </div>

        <!-- Step 2: Chọn chuyên viên -->
        <div class="step-content" id="step2">
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
                <div>
                    <span class="section-subtitle">Bước 2 trên 4</span>
                    <h3 class="font-serif mb-1">Chọn Chuyên Viên Trị Liệu</h3>
                    <p class="text-muted small mb-0">Chọn kỹ thuật viên phụ trách dịch vụ <strong id="step2ServiceName" class="text-dark"></strong></p>
                </div>
            </div>

            <div id="staffLoading" class="text-center py-5" style="display:none;">
                <div class="spinner-border text-gold" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="small text-muted mt-2">Đang tải danh sách chuyên viên...</p>
            </div>

            <div class="row g-3" id="staffList"></div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-light">
                <button class="btn btn-outline-gold" id="btnStep2Prev">
                    <i class="bi bi-arrow-left me-2"></i> Quay lại Dịch Vụ
                </button>
                <button class="btn btn-gold px-4" id="btnStep2Next" disabled>
                    Tiếp theo: Chọn Ngày & Giờ <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
        </div>

        <!-- Step 3: Chọn ngày & giờ -->
        <div class="step-content" id="step3">
            <div class="mb-4">
                <span class="section-subtitle">Bước 3 trên 4</span>
                <h3 class="font-serif mb-1">Chọn Ngày & Khung Giờ Trống</h3>
                <p class="text-muted small mb-0">Hệ thống tự động kiểm tra lịch trực của chuyên viên để hiển thị các khung giờ còn trống</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-5 col-md-6">
                    <div class="p-4 bg-light rounded border border-light">
                        <label class="form-label fw-bold mb-3 font-serif fs-5">
                            <i class="bi bi-calendar3 me-2 text-gold"></i>Chọn Ngày Hẹn
                        </label>
                        <input type="text" class="form-control search-input-luxury form-control-lg bg-white" id="appointmentDate" 
                               placeholder="Bấm để chọn ngày..." readonly>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-info-circle me-1"></i> Salon phục vụ từ 08:30 - 20:30 tất cả các ngày trong tuần.
                        </small>
                    </div>
                </div>
                <div class="col-lg-7 col-md-6">
                    <div class="p-4 bg-light rounded border border-light h-100">
                        <label class="form-label fw-bold mb-3 font-serif fs-5">
                            <i class="bi bi-clock me-2 text-gold"></i>Khung Giờ Khả Dụng
                        </label>
                        <div id="slotsLoading" class="text-center py-4" style="display:none;">
                            <div class="spinner-border text-gold spinner-border-sm" role="status"></div>
                            <span class="ms-2 small text-muted">Đang tìm các khung giờ trống...</span>
                        </div>
                        <div id="timeSlots">
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-calendar-check text-gold" style="font-size: 2.5rem;"></i>
                                <p class="mt-2 mb-0 small">Vui lòng chọn ngày để tra cứu các khung giờ còn trống của chuyên viên.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-light">
                <button class="btn btn-outline-gold" id="btnStep3Prev">
                    <i class="bi bi-arrow-left me-2"></i> Quay lại Chuyên Viên
                </button>
                <button class="btn btn-gold px-4" id="btnStep3Next" disabled>
                    Tiếp theo: Xác Nhận Đặt Lịch <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </div>
        </div>

        <!-- Step 4: Xác nhận -->
        <div class="step-content" id="step4">
            <div class="mb-4">
                <span class="section-subtitle">Bước 4 trên 4</span>
                <h3 class="font-serif mb-1">Xác Nhận Thông Tin Đặt Lịch</h3>
                <p class="text-muted small mb-0">Vui lòng kiểm tra lại chi tiết cuộc hẹn trước khi gửi yêu cầu</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="summary-box">
                        <h5 class="font-serif mb-3 text-dark">
                            <i class="bi bi-receipt me-2 text-gold"></i>Phiếu Tóm Tắt Lịch Hẹn
                        </h5>
                        <div class="summary-row">
                            <span class="text-muted">Khách hàng</span>
                            <strong class="text-dark"><?= htmlspecialchars($currentUser['full_name']) ?></strong>
                        </div>
                        <div class="summary-row">
                            <span class="text-muted">Số điện thoại</span>
                            <span class="text-dark"><?= htmlspecialchars($currentUser['phone'] ?: 'Chưa cập nhật') ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="text-muted">Liệu trình đã chọn</span>
                            <strong class="text-dark" id="summaryService">-</strong>
                        </div>
                        <div class="summary-row">
                            <span class="text-muted">Thời lượng liệu trình</span>
                            <span class="text-dark" id="summaryDuration">-</span>
                        </div>
                        <div class="summary-row">
                            <span class="text-muted">Chuyên viên phụ trách</span>
                            <strong class="text-dark" id="summaryStaff">-</strong>
                        </div>
                        <div class="summary-row">
                            <span class="text-muted">Ngày & Khung giờ</span>
                            <span class="text-gold fw-bold" id="summaryDateTime">-</span>
                        </div>
                        <div class="summary-row pt-3">
                            <span class="fs-5 font-serif text-dark fw-bold">Tổng chi phí dự kiến</span>
                            <span class="fs-5 font-serif fw-bold text-gold" id="summaryPrice">-</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="summary-box h-100">
                        <h5 class="font-serif mb-3 text-dark">
                            <i class="bi bi-chat-quote me-2 text-gold"></i>Ghi Chú Đặc Biệt (Tùy chọn)
                        </h5>
                        <textarea class="form-control search-input-luxury" id="bookingNotes" rows="6" 
                                  placeholder="Ví dụ: Da nhạy cảm, yêu cầu phòng riêng, lực massage nhẹ nhàng..."></textarea>
                        <small class="text-muted d-block mt-2">
                            <i class="bi bi-shield-check text-gold me-1"></i> Salon cam kết bảo mật toàn bộ thông tin cá nhân và yêu cầu của quý khách.
                        </small>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-light">
                <button class="btn btn-outline-gold" id="btnStep4Prev">
                    <i class="bi bi-arrow-left me-2"></i> Quay lại Chọn Giờ
                </button>
                <button class="btn btn-gold btn-lg px-4" id="btnSubmitBooking">
                    <i class="bi bi-check-circle me-2"></i> Xác Nhận Đặt Lịch Ngay
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Success -->
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <div class="modal-body py-4">
                <div class="stat-icon mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2.2rem; background: #faf5ee;">
                    <i class="bi bi-check2-circle text-gold"></i>
                </div>
                <h3 class="font-serif mb-2">Đặt Lịch Thành Công!</h3>
                <p class="text-muted small mb-4">
                    Yêu cầu lịch hẹn của bạn đã được ghi nhận vào hệ thống. Chuyên viên sẽ chuẩn bị chu đáo để đón tiếp bạn.
                </p>
                <div class="d-flex flex-column gap-2">
                    <a href="<?= BASE_URL ?>booking/my_appointments.php" class="btn btn-gold">
                        <i class="bi bi-calendar-check me-2"></i> Xem Danh Sách Lịch Hẹn Của Tôi
                    </a>
                    <button class="btn btn-outline-gold" onclick="location.reload()">
                        <i class="bi bi-plus-circle me-2"></i> Đặt Thêm Lịch Hẹn Khác
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ambient Audio Player -->
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

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>

<script>
const API_URL = '<?= BASE_URL ?>api/booking.php';
const CSRF_TOKEN = '<?= $csrfToken ?>';

// State
let selectedService = null;
let selectedStaff = null;
let selectedDate = null;
let selectedSlot = null;
let currentStep = 1;
let allServices = [];

// URL Parameters
const urlParams = new URLSearchParams(window.location.search);
const preServiceId = parseInt(urlParams.get('service_id')) || 0;
const preStaffId = parseInt(urlParams.get('staff_id')) || 0;

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
    loadServices(0);
    initDatePicker();
    bindEvents();
});

// Wizard Navigation
function goToStep(step) {
    document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
    document.getElementById('step' + step).classList.add('active');

    document.querySelectorAll('.wizard-step-item').forEach(el => {
        const s = parseInt(el.dataset.step);
        el.classList.remove('active', 'completed');
        if (s === step) el.classList.add('active');
        else if (s < step) el.classList.add('completed');
    });

    currentStep = step;
    window.scrollTo({ top: 120, behavior: 'smooth' });
}

// Load Categories
function loadCategories() {
    fetch(API_URL + '?action=get_categories')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                const container = document.getElementById('bookingCategoryFilter');
                data.data.forEach(cat => {
                    const tab = document.createElement('span');
                    tab.className = 'booking-cat-pill';
                    tab.dataset.category = cat.id;
                    tab.textContent = cat.name;
                    tab.onclick = function() {
                        document.querySelectorAll('.booking-cat-pill').forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                        filterServices();
                    };
                    container.appendChild(tab);
                });
            }
        })
        .catch(err => console.error('Error loading categories:', err));
}

// Load Services
function loadServices(categoryId) {
    document.getElementById('servicesLoading').style.display = 'block';
    document.getElementById('servicesList').innerHTML = '';

    fetch(API_URL + '?action=get_services&category_id=' + categoryId)
        .then(r => r.json())
        .then(data => {
            document.getElementById('servicesLoading').style.display = 'none';
            if (data.success) {
                allServices = data.data;
                renderServices(allServices);

                // Auto-select if URL pre-service was passed
                if (preServiceId > 0) {
                    const target = allServices.find(s => parseInt(s.id) === preServiceId);
                    if (target) {
                        selectService(preServiceId);
                    }
                }
            }
        })
        .catch(err => {
            document.getElementById('servicesLoading').style.display = 'none';
            console.error('Error loading services:', err);
        });
}

function filterServices() {
    const activePill = document.querySelector('.booking-cat-pill.active');
    const catId = activePill ? parseInt(activePill.dataset.category) : 0;
    const kw = document.getElementById('svcKeyword').value.trim().toLowerCase();

    const filtered = allServices.filter(s => {
        const matchCat = (catId === 0) || (parseInt(s.category_id) === catId);
        const name = (s.name || '').toLowerCase();
        const desc = (s.description || '').toLowerCase();
        const matchKw = !kw || name.includes(kw) || desc.includes(kw);
        return matchCat && matchKw;
    });

    renderServices(filtered);
}

document.getElementById('svcKeyword').addEventListener('input', filterServices);

function renderServices(services) {
    const container = document.getElementById('servicesList');
    if (services.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="bi bi-search text-gold fs-1 d-block mb-2"></i>Không tìm thấy dịch vụ nào phù hợp</div>';
        return;
    }

    container.innerHTML = services.map(svc => `
        <div class="col-lg-4 col-md-6">
            <div class="service-select-card ${selectedService && selectedService.id == svc.id ? 'selected' : ''}" 
                 onclick="selectService(${svc.id})" data-service-id="${svc.id}">
                <div class="service-selected-badge"><i class="bi bi-check-lg"></i></div>
                <img src="${svc.image || 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=700&q=80'}" 
                     class="service-select-img" alt="${svc.name}"
                     onerror="this.src='https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=700&q=80'">
                <div class="p-3 d-flex flex-column flex-grow-1">
                    <h5 class="font-serif mb-1 text-dark">${svc.name}</h5>
                    <p class="text-muted small mb-3 flex-grow-1">${svc.description ? svc.description.substring(0, 95) + '...' : ''}</p>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light mt-auto">
                        <span class="fw-bold text-gold font-serif fs-5">${formatCurrency(svc.price)}</span>
                        <span class="small text-muted"><i class="bi bi-clock me-1 text-gold"></i>${svc.duration} phút</span>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function selectService(serviceId) {
    selectedService = allServices.find(s => parseInt(s.id) === serviceId);
    document.querySelectorAll('.service-select-card').forEach(c => c.classList.remove('selected'));
    const chosen = document.querySelector(`.service-select-card[data-service-id="${serviceId}"]`);
    if (chosen) chosen.classList.add('selected');

    document.getElementById('btnStep1Next').disabled = false;
    document.getElementById('step1SelectionText').innerHTML = `Đã chọn: <strong class="text-dark">${selectedService.name}</strong> (${formatCurrency(selectedService.price)})`;

    // Reset subsequent selections
    selectedStaff = null;
    selectedDate = null;
    selectedSlot = null;
}

// Load Staff
function loadStaff(serviceId) {
    document.getElementById('staffLoading').style.display = 'block';
    document.getElementById('staffList').innerHTML = '';
    document.getElementById('step2ServiceName').textContent = selectedService.name;

    fetch(API_URL + '?action=get_staff&service_id=' + serviceId)
        .then(r => r.json())
        .then(data => {
            document.getElementById('staffLoading').style.display = 'none';
            if (data.success) {
                renderStaff(data.data);

                // Auto-select preStaffId if set
                if (preStaffId > 0) {
                    const target = data.data.find(s => parseInt(s.id) === preStaffId);
                    if (target) {
                        selectStaff(target.id, target.full_name);
                    }
                }
            } else {
                document.getElementById('staffList').innerHTML = 
                    '<div class="col-12 text-center text-muted py-5">' + data.message + '</div>';
            }
        })
        .catch(err => {
            document.getElementById('staffLoading').style.display = 'none';
            console.error('Error loading staff:', err);
        });
}

function renderStaff(staffList) {
    const container = document.getElementById('staffList');
    if (staffList.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="bi bi-person-x fs-1 d-block mb-2 text-gold"></i>Chưa có nhân viên nào phụ trách dịch vụ này</div>';
        return;
    }

    container.innerHTML = staffList.map(staff => `
        <div class="col-lg-4 col-md-6">
            <div class="staff-select-card ${selectedStaff && selectedStaff.id == staff.id ? 'selected' : ''}" 
                 onclick="selectStaff(${staff.id}, '${staff.full_name}')" 
                 data-staff-id="${staff.id}">
                <div class="service-selected-badge"><i class="bi bi-check-lg"></i></div>
                <img src="${staff.avatar || 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=300&q=80'}" 
                     class="staff-avatar-round mx-auto" alt="${staff.full_name}"
                     onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=300&q=80'">
                <h5 class="font-serif text-dark mb-1">${staff.full_name}</h5>
                <span class="small text-gold text-uppercase d-block mb-2" style="letter-spacing: 1.5px; font-size: 0.75rem;">Chuyên viên trị liệu</span>
                ${staff.phone ? '<p class="small text-muted mb-0"><i class="bi bi-telephone me-1"></i>' + staff.phone + '</p>' : ''}
            </div>
        </div>
    `).join('');
}

function selectStaff(staffId, staffName) {
    selectedStaff = { id: staffId, name: staffName };
    document.querySelectorAll('.staff-select-card').forEach(c => c.classList.remove('selected'));
    const card = document.querySelector(`.staff-select-card[data-staff-id="${staffId}"]`);
    if (card) card.classList.add('selected');

    document.getElementById('btnStep2Next').disabled = false;
    selectedDate = null;
    selectedSlot = null;
}

// Date Picker
function initDatePicker() {
    flatpickr('#appointmentDate', {
        locale: 'vn',
        dateFormat: 'Y-m-d',
        minDate: 'today',
        maxDate: new Date().fp_incr(60),
        disableMobile: true,
        onChange: function(selectedDates, dateStr) {
            selectedDate = dateStr;
            selectedSlot = null;
            document.getElementById('btnStep3Next').disabled = true;
            loadTimeSlots();
        }
    });
}

// Load Time Slots
function loadTimeSlots() {
    if (!selectedStaff || !selectedService || !selectedDate) return;

    document.getElementById('slotsLoading').style.display = 'block';
    document.getElementById('timeSlots').innerHTML = '';

    const url = `${API_URL}?action=get_slots&staff_id=${selectedStaff.id}&service_id=${selectedService.id}&date=${selectedDate}`;

    fetch(url)
        .then(r => r.json())
        .then(data => {
            document.getElementById('slotsLoading').style.display = 'none';
            if (data.success) {
                renderSlots(data.data.slots, data.data.break_time);
            } else {
                document.getElementById('timeSlots').innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-calendar-x text-gold fs-2 d-block mb-2"></i>
                        ${data.message}
                    </div>
                `;
            }
        })
        .catch(err => {
            document.getElementById('slotsLoading').style.display = 'none';
            console.error('Error loading slots:', err);
        });
}

function renderSlots(slots, breakTime) {
    const container = document.getElementById('timeSlots');
    if (!slots || slots.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-clock-history text-gold fs-2 d-block mb-2"></i>
                Không có khung giờ nào còn trống cho ngày này. Vui lòng chọn ngày khác.
            </div>
        `;
        return;
    }

    let html = '<div class="d-flex flex-wrap">';
    slots.forEach(slot => {
        html += `
            <button type="button" class="slot-btn" onclick="selectSlot(this, '${slot.start}', '${slot.end}')">
                <i class="bi bi-clock me-1 text-gold"></i>${slot.start} - ${slot.end}
            </button>
        `;
    });
    html += '</div>';

    if (breakTime) {
        html += `<small class="text-muted d-block mt-3"><i class="bi bi-cup-hot me-1 text-gold"></i>Nghỉ trưa: ${breakTime.start} - ${breakTime.end}</small>`;
    }

    container.innerHTML = html;
}

function selectSlot(btn, start, end) {
    selectedSlot = { start, end };
    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('btnStep3Next').disabled = false;
}

// Update Summary
function updateSummary() {
    document.getElementById('summaryService').textContent = selectedService ? selectedService.name : '-';
    document.getElementById('summaryPrice').textContent = selectedService ? formatCurrency(selectedService.price) : '-';
    document.getElementById('summaryDuration').textContent = selectedService ? selectedService.duration + ' phút' : '-';
    document.getElementById('summaryStaff').textContent = selectedStaff ? selectedStaff.name : '-';
    document.getElementById('summaryDateTime').textContent = selectedDate ? (formatDate(selectedDate) + ' lúc ' + selectedSlot.start) : '-';
}

// Submit Booking
function submitBooking() {
    const btn = document.getElementById('btnSubmitBooking');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xác nhận đặt lịch...';

    const formData = new FormData();
    formData.append('action', 'create_booking');
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('service_id', selectedService.id);
    formData.append('staff_id', selectedStaff.id);
    formData.append('appointment_date', selectedDate);
    formData.append('start_time', selectedSlot.start);
    formData.append('notes', document.getElementById('bookingNotes').value);

    fetch(API_URL + '?action=create_booking', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Xác Nhận Đặt Lịch Ngay';

        if (data.success) {
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
        } else {
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            showAlert(data.message, 'danger');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Xác Nhận Đặt Lịch Ngay';
        showAlert('Có lỗi xảy ra, vui lòng thử lại.', 'danger');
        console.error('Error submitting booking:', err);
    });
}

// Bind Button Events
function bindEvents() {
    document.getElementById('btnStep1Next').onclick = () => {
        if (selectedService) { loadStaff(selectedService.id); goToStep(2); }
    };
    document.getElementById('btnStep2Prev').onclick = () => goToStep(1);
    document.getElementById('btnStep2Next').onclick = () => {
        if (selectedStaff) goToStep(3);
    };
    document.getElementById('btnStep3Prev').onclick = () => goToStep(2);
    document.getElementById('btnStep3Next').onclick = () => {
        if (selectedSlot) { updateSummary(); goToStep(4); }
    };
    document.getElementById('btnStep4Prev').onclick = () => goToStep(3);
    document.getElementById('btnSubmitBooking').onclick = submitBooking;
}

// Utilities
function formatCurrency(amount) {
    return parseInt(amount).toLocaleString('vi-VN') + ' ₫';
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    const days = ['Chủ nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
    return days[d.getDay()] + ', ' + d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function showAlert(message, type) {
    const container = document.getElementById('alertContainer');
    container.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    window.scrollTo({ top: 100, behavior: 'smooth' });
}

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
