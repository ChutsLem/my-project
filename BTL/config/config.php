<?php
/**
 * Cấu hình chung cho hệ thống
 * Hệ thống quản lý dịch vụ đặt lịch làm đẹp
 */

// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Thiết lập múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Hằng số cấu hình
define('BASE_URL', 'http://localhost/BTL/');
define('SITE_NAME', 'Beauty Salon');
define('APP_NAME', 'Beauty Salon');
define('SITE_DESCRIPTION', 'Hệ thống quản lý dịch vụ đặt lịch làm đẹp');

// Đường dẫn thư mục
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('UPLOADS_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('UPLOADS_URL', BASE_URL . 'uploads/');

// Include cấu hình database
require_once __DIR__ . '/database.php';

// =====================================================
// HÀM XÁC THỰC & PHÂN QUYỀN
// =====================================================

/**
 * Kiểm tra người dùng đã đăng nhập chưa
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Kiểm tra người dùng có phải admin không
 * @return bool
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Kiểm tra người dùng có phải nhân viên không
 * @return bool
 */
function isStaff() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'staff';
}

/**
 * Kiểm tra người dùng có phải khách hàng không
 * @return bool
 */
function isCustomer() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer';
}

/**
 * Yêu cầu đăng nhập, nếu chưa đăng nhập thì chuyển hướng
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('login.php');
    }
}

/**
 * Yêu cầu quyền admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('error', 'Bạn không có quyền truy cập trang này.');
        redirect('index.php');
    }
}

/**
 * Yêu cầu quyền nhân viên hoặc admin
 */
function requireStaff() {
    requireLogin();
    if (!isStaff() && !isAdmin()) {
        setFlashMessage('error', 'Bạn không có quyền truy cập trang này.');
        redirect('index.php');
    }
}

/**
 * Yêu cầu quyền khách hàng
 */
function requireCustomer() {
    requireLogin();
    if (!isCustomer()) {
        setFlashMessage('error', 'Bạn không có quyền truy cập trang này.');
        redirect('index.php');
    }
}

// =====================================================
// HÀM CHUYỂN HƯỚNG
// =====================================================

/**
 * Chuyển hướng đến URL
 * @param string $url URL đích (tương đối hoặc tuyệt đối)
 */
function redirect($url) {
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        $url = rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
    }
    header("Location: $url");
    exit();
}

// =====================================================
// HÀM FLASH MESSAGE
// =====================================================

/**
 * Đặt flash message vào session (hỗ trợ cả 2 chuẩn gọi: setFlash($message, $type) và setFlashMessage($type, $message))
 */
function setFlash($message, $type = 'info') {
    $alertType = match($type) {
        'error' => 'danger',
        default => $type
    };
    $_SESSION['flash_message'] = [
        'type' => $alertType,
        'message' => $message
    ];
    $_SESSION['flash_messages'][] = [
        'type' => $alertType,
        'message' => $message
    ];
}

function setFlashMessage($type, $message) {
    setFlash($message, $type);
}

/**
 * Lấy 1 flash message đơn lẻ (dành cho template hiển thị 1 thông báo)
 */
function getFlash() {
    $flash = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return $flash;
}

/**
 * Lấy và xóa tất cả flash messages
 * @return array Mảng các flash messages
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    unset($_SESSION['flash_message']);
    return $messages;
}

/**
 * Kiểm tra có flash message không
 * @return bool
 */
function hasFlashMessages() {
    return !empty($_SESSION['flash_messages']);
}

/**
 * Hiển thị flash messages dưới dạng HTML Bootstrap alerts
 * @return string HTML của các thông báo
 */
function displayFlashMessages() {
    $messages = getFlashMessages();
    $html = '';
    
    foreach ($messages as $msg) {
        $alertClass = match($msg['type']) {
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'warning' => 'alert-warning',
            'info'    => 'alert-info',
            default   => 'alert-secondary'
        };
        
        $icon = match($msg['type']) {
            'success' => '<i class="bi bi-check-circle-fill me-2"></i>',
            'error'   => '<i class="bi bi-exclamation-triangle-fill me-2"></i>',
            'warning' => '<i class="bi bi-exclamation-circle-fill me-2"></i>',
            'info'    => '<i class="bi bi-info-circle-fill me-2"></i>',
            default   => ''
        };
        
        $html .= '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        $html .= $icon . htmlspecialchars($msg['message']);
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>';
        $html .= '</div>';
    }
    
    return $html;
}

// =====================================================
// HÀM CSRF TOKEN
// =====================================================

/**
 * Tạo CSRF token và lưu vào session
 * @return string Token đã tạo
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function generateCSRF() {
    return generateCsrfToken();
}

/**
 * Lấy HTML input hidden chứa CSRF token
 * @return string HTML input hidden
 */
function csrfTokenField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function csrfField() {
    return csrfTokenField();
}

/**
 * Xác thực CSRF token từ request
 * @param string|null $token Token cần kiểm tra (nếu null sẽ lấy từ $_POST)
 * @return bool Token hợp lệ hay không
 */
function validateCsrfToken($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

function validateCSRF($token = null) {
    return validateCsrfToken($token);
}

// =====================================================
// HÀM TIỆN ÍCH
// =====================================================

/**
 * Escape HTML để chống XSS
 * @param string|null $string Chuỗi cần escape
 * @return string Chuỗi đã escape
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Alias cho e()
 */
function sanitize($string) {
    return e($string);
}

/**
 * Định dạng tiền tệ VND
 * @param float $amount Số tiền
 * @return string Chuỗi tiền tệ đã định dạng
 */
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' đ';
}

/**
 * Định dạng ngày tháng theo kiểu Việt Nam
 * @param string $date Ngày (Y-m-d)
 * @return string Ngày đã định dạng (d/m/Y)
 */
function formatDate($date) {
    if (empty($date)) return '';
    return date('d/m/Y', strtotime($date));
}

/**
 * Định dạng ngày giờ theo kiểu Việt Nam
 * @param string $datetime Ngày giờ
 * @return string Ngày giờ đã định dạng (d/m/Y H:i)
 */
function formatDateTime($datetime) {
    if (empty($datetime)) return '';
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Định dạng thời gian (giờ:phút)
 * @param string $time Thời gian (H:i:s)
 * @return string Thời gian đã định dạng (H:i)
 */
function formatTime($time) {
    if (empty($time)) return '';
    return date('H:i', strtotime($time));
}

/**
 * Lấy tên thứ trong tuần bằng tiếng Việt
 * @param int $dayOfWeek 0=Chủ nhật, 1-6=Thứ 2 đến Thứ 7
 * @return string Tên thứ
 */
function getDayName($dayOfWeek) {
    $days = [
        0 => 'Chủ nhật',
        1 => 'Thứ 2',
        2 => 'Thứ 3',
        3 => 'Thứ 4',
        4 => 'Thứ 5',
        5 => 'Thứ 6',
        6 => 'Thứ 7'
    ];
    return $days[$dayOfWeek] ?? '';
}

/**
 * Lấy nhãn trạng thái lịch hẹn bằng tiếng Việt với class Bootstrap
 * @param string $status Trạng thái
 * @return array ['label' => string, 'class' => string]
 */
function getAppointmentStatusLabel($status) {
    return match($status) {
        'pending'   => ['label' => 'Chờ xác nhận', 'class' => 'bg-warning text-dark'],
        'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'bg-primary'],
        'completed' => ['label' => 'Hoàn thành', 'class' => 'bg-success'],
        'cancelled' => ['label' => 'Đã hủy', 'class' => 'bg-danger'],
        default     => ['label' => $status, 'class' => 'bg-secondary']
    };
}

/**
 * Lấy nhãn trạng thái thanh toán bằng tiếng Việt
 * @param string $status Trạng thái thanh toán
 * @return array ['label' => string, 'class' => string]
 */
function getPaymentStatusLabel($status) {
    return match($status) {
        'pending'  => ['label' => 'Chờ thanh toán', 'class' => 'bg-warning text-dark'],
        'paid'     => ['label' => 'Đã thanh toán', 'class' => 'bg-success'],
        'refunded' => ['label' => 'Đã hoàn tiền', 'class' => 'bg-info'],
        default    => ['label' => $status, 'class' => 'bg-secondary']
    };
}

/**
 * Lấy nhãn phương thức thanh toán bằng tiếng Việt
 * @param string $method Phương thức
 * @return string Nhãn tiếng Việt
 */
function getPaymentMethodLabel($method) {
    return match($method) {
        'cash'     => 'Tiền mặt',
        'card'     => 'Thẻ ngân hàng',
        'transfer' => 'Chuyển khoản',
        default    => $method
    };
}

/**
 * Tạo chuỗi ngẫu nhiên
 * @param int $length Độ dài chuỗi
 * @return string Chuỗi ngẫu nhiên
 */
function generateRandomString($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Lấy thông tin người dùng hiện tại từ session
 * @param string|null $key Key cần lấy, null để lấy tất cả
 * @return mixed
 */
function currentUser($key = null) {
    if (!isLoggedIn()) return null;
    
    $user = [
        'id'        => $_SESSION['user_id'] ?? null,
        'username'  => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role'      => $_SESSION['user_role'] ?? null,
        'avatar'    => $_SESSION['avatar'] ?? null,
    ];
    
    if ($key !== null) {
        return $user[$key] ?? null;
    }
    
    return $user;
}

/**
 * Lấy ID người dùng hiện tại từ session
 * @return int|null
 */
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}
