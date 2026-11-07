<?php
/**
 * API Đặt lịch - Beauty Salon Booking Management System
 * Xử lý các request AJAX cho đặt lịch
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // ===== Lấy danh sách danh mục dịch vụ =====
        case 'get_categories':
            $stmt = $db->query("SELECT DISTINCT sc.id, sc.name 
                                FROM categories sc 
                                INNER JOIN services s ON s.category_id = sc.id 
                                WHERE s.status = 'active' 
                                ORDER BY sc.name");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $categories]);
            break;

        // ===== Lấy dịch vụ theo danh mục =====
        case 'get_services':
            $categoryId = intval($_GET['category_id'] ?? 0);
            if ($categoryId <= 0) {
                // Lấy tất cả dịch vụ active
                $stmt = $db->query("SELECT id, category_id, name, description, price, duration, image 
                                    FROM services WHERE status = 'active' ORDER BY name");
            } else {
                $stmt = $db->prepare("SELECT id, category_id, name, description, price, duration, image 
                                      FROM services WHERE category_id = ? AND status = 'active' ORDER BY name");
                $stmt->execute([$categoryId]);
            }
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $services]);
            break;

        // ===== Lấy nhân viên theo dịch vụ =====
        case 'get_staff':
            $serviceId = intval($_GET['service_id'] ?? 0);
            if ($serviceId <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID dịch vụ không hợp lệ']);
                break;
            }

            // Kiểm tra dịch vụ tồn tại và active
            $stmtSvc = $db->prepare("SELECT id FROM services WHERE id = ? AND status = 'active'");
            $stmtSvc->execute([$serviceId]);
            if (!$stmtSvc->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Dịch vụ không tồn tại hoặc không khả dụng']);
                break;
            }

            $stmt = $db->prepare("
                SELECT u.id, u.full_name, u.avatar, u.phone
                FROM users u
                INNER JOIN staff_services ss ON ss.staff_id = u.id
                WHERE ss.service_id = ? 
                  AND u.role = 'staff' 
                  AND u.status = 'active'
                ORDER BY u.full_name
            ");
            $stmt->execute([$serviceId]);
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $staff]);
            break;

        // ===== Lấy khung giờ trống =====
        case 'get_slots':
            $staffId = intval($_GET['staff_id'] ?? 0);
            $serviceId = intval($_GET['service_id'] ?? 0);
            $date = $_GET['date'] ?? '';

            if ($staffId <= 0 || $serviceId <= 0 || empty($date)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
                break;
            }

            // Validate date format
            $dateObj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
                echo json_encode(['success' => false, 'message' => 'Ngày không hợp lệ']);
                break;
            }

            // Không cho đặt ngày trong quá khứ
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            if ($dateObj < $today) {
                echo json_encode(['success' => false, 'message' => 'Không thể đặt lịch ngày trong quá khứ']);
                break;
            }

            // Lấy thời lượng dịch vụ
            $stmtSvc = $db->prepare("SELECT duration FROM services WHERE id = ? AND status = 'active'");
            $stmtSvc->execute([$serviceId]);
            $service = $stmtSvc->fetch(PDO::FETCH_ASSOC);
            if (!$service) {
                echo json_encode(['success' => false, 'message' => 'Dịch vụ không tồn tại']);
                break;
            }
            $duration = intval($service['duration']); // phút

            // Lấy lịch làm việc của nhân viên cho ngày đó
            $dayOfWeek = intval($dateObj->format('w')); // 0=Sunday, 1=Monday, ..., 6=Saturday
            $stmtSchedule = $db->prepare("
                SELECT start_time, end_time, is_off 
                FROM work_schedules 
                WHERE staff_id = ? AND day_of_week = ?
            ");
            $stmtSchedule->execute([$staffId, $dayOfWeek]);
            $schedule = $stmtSchedule->fetch(PDO::FETCH_ASSOC);

            if (!$schedule || $schedule['is_off']) {
                echo json_encode(['success' => true, 'data' => [], 'message' => 'Nhân viên nghỉ vào ngày này']);
                break;
            }

            $workStart = $schedule['start_time'];
            $workEnd = $schedule['end_time'];

            // Lấy các lịch hẹn đã đặt của nhân viên trong ngày đó
            $stmtAppts = $db->prepare("
                SELECT start_time, end_time 
                FROM appointments 
                WHERE staff_id = ? 
                  AND appointment_date = ? 
                  AND status IN ('pending', 'confirmed')
                ORDER BY start_time
            ");
            $stmtAppts->execute([$staffId, $date]);
            $bookedSlots = $stmtAppts->fetchAll(PDO::FETCH_ASSOC);

            // Tạo danh sách khung giờ trống
            $slots = [];
            $slotStart = new DateTime($date . ' ' . $workStart);
            $slotEndLimit = new DateTime($date . ' ' . $workEnd);

            // Nếu là ngày hôm nay, bắt đầu từ giờ hiện tại (làm tròn lên 30 phút)
            $now = new DateTime();
            if ($dateObj->format('Y-m-d') === $now->format('Y-m-d')) {
                $minutes = intval($now->format('i'));
                $roundedMinutes = ceil($minutes / 30) * 30;
                $now->setTime(intval($now->format('H')), $roundedMinutes, 0);
                if ($now > $slotStart) {
                    $slotStart = $now;
                }
            }

            while ($slotStart < $slotEndLimit) {
                $slotEnd = clone $slotStart;
                $slotEnd->modify("+{$duration} minutes");

                // Kiểm tra slot kết thúc trước giờ nghỉ
                if ($slotEnd > $slotEndLimit) {
                    break;
                }

                // Kiểm tra trùng với lịch đã đặt
                $isAvailable = true;
                foreach ($bookedSlots as $booked) {
                    $bookedStart = new DateTime($date . ' ' . $booked['start_time']);
                    $bookedEnd = new DateTime($date . ' ' . $booked['end_time']);

                    // Kiểm tra overlap
                    if ($slotStart < $bookedEnd && $slotEnd > $bookedStart) {
                        $isAvailable = false;
                        break;
                    }
                }

                if ($isAvailable) {
                    $slots[] = [
                        'start_time' => $slotStart->format('H:i'),
                        'end_time' => $slotEnd->format('H:i')
                    ];
                }

                // Di chuyển đến slot tiếp theo (mỗi 30 phút)
                $slotStart->modify('+30 minutes');
            }

            echo json_encode(['success' => true, 'data' => $slots]);
            break;

        // ===== Tạo lịch hẹn mới =====
        case 'create_booking':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
                break;
            }

            if (!isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đặt lịch', 'redirect' => BASE_URL . '/auth/login.php']);
                break;
            }

            // CSRF validation
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!validateCSRF($csrfToken)) {
                echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn, vui lòng tải lại trang']);
                break;
            }

            $customerId = $_SESSION['user_id'];
            $serviceId = intval($_POST['service_id'] ?? 0);
            $staffId = intval($_POST['staff_id'] ?? 0);
            $date = trim($_POST['appointment_date'] ?? '');
            $startTime = trim($_POST['start_time'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            // Validate inputs
            if ($serviceId <= 0 || $staffId <= 0 || empty($date) || empty($startTime)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
                break;
            }

            // Validate date
            $dateObj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
                echo json_encode(['success' => false, 'message' => 'Ngày không hợp lệ']);
                break;
            }

            $today = new DateTime();
            $today->setTime(0, 0, 0);
            if ($dateObj < $today) {
                echo json_encode(['success' => false, 'message' => 'Không thể đặt lịch ngày trong quá khứ']);
                break;
            }

            // Validate time format
            if (!preg_match('/^\d{2}:\d{2}$/', $startTime)) {
                echo json_encode(['success' => false, 'message' => 'Giờ không hợp lệ']);
                break;
            }

            // Lấy thông tin dịch vụ
            $stmtSvc = $db->prepare("SELECT id, duration, price FROM services WHERE id = ? AND status = 'active'");
            $stmtSvc->execute([$serviceId]);
            $service = $stmtSvc->fetch(PDO::FETCH_ASSOC);
            if (!$service) {
                echo json_encode(['success' => false, 'message' => 'Dịch vụ không tồn tại hoặc không khả dụng']);
                break;
            }

            // Kiểm tra nhân viên phục vụ dịch vụ này
            $stmtStaffSvc = $db->prepare("
                SELECT ss.id FROM staff_services ss 
                INNER JOIN users u ON u.id = ss.staff_id 
                WHERE ss.staff_id = ? AND ss.service_id = ? AND u.status = 'active' AND u.role = 'staff'
            ");
            $stmtStaffSvc->execute([$staffId, $serviceId]);
            if (!$stmtStaffSvc->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Nhân viên không phục vụ dịch vụ này']);
                break;
            }

            // Tính end_time
            $duration = intval($service['duration']);
            $startDateTime = new DateTime($date . ' ' . $startTime . ':00');
            $endDateTime = clone $startDateTime;
            $endDateTime->modify("+{$duration} minutes");
            $endTime = $endDateTime->format('H:i:s');
            $startTimeFull = $startDateTime->format('H:i:s');

            // Kiểm tra trong giờ làm việc
            $dayOfWeek = intval($dateObj->format('w')); // 0=Sunday, 1=Monday, ..., 6=Saturday
            $stmtSchedule = $db->prepare("
                SELECT start_time, end_time, is_off 
                FROM work_schedules 
                WHERE staff_id = ? AND day_of_week = ?
            ");
            $stmtSchedule->execute([$staffId, $dayOfWeek]);
            $schedule = $stmtSchedule->fetch(PDO::FETCH_ASSOC);

            if (!$schedule || $schedule['is_off']) {
                echo json_encode(['success' => false, 'message' => 'Nhân viên nghỉ vào ngày này']);
                break;
            }

            if ($startTimeFull < $schedule['start_time'] || $endTime > $schedule['end_time']) {
                echo json_encode(['success' => false, 'message' => 'Khung giờ nằm ngoài giờ làm việc']);
                break;
            }

            // Kiểm tra trùng lịch (overlap)
            $stmtOverlap = $db->prepare("
                SELECT id FROM appointments 
                WHERE staff_id = ? 
                  AND appointment_date = ? 
                  AND status IN ('pending', 'confirmed')
                  AND (
                      (start_time < ? AND end_time > ?)
                  )
            ");
            $stmtOverlap->execute([$staffId, $date, $endTime, $startTimeFull]);
            if ($stmtOverlap->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Khung giờ này đã có người đặt, vui lòng chọn giờ khác']);
                break;
            }

            // Tạo lịch hẹn
            $db->beginTransaction();
            try {
                $stmtInsert = $db->prepare("
                    INSERT INTO appointments (customer_id, staff_id, service_id, appointment_date, start_time, end_time, status, notes, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())
                ");
                $stmtInsert->execute([$customerId, $staffId, $serviceId, $date, $startTimeFull, $endTime, $notes]);
                $appointmentId = $db->lastInsertId();

                // Ghi lịch sử
                $stmtHistory = $db->prepare("
                    INSERT INTO appointment_history (appointment_id, action, old_data, new_data, changed_by, changed_at)
                    VALUES (?, 'created', NULL, ?, ?, NOW())
                ");
                $newData = json_encode([
                    'customer_id' => $customerId,
                    'staff_id' => $staffId,
                    'service_id' => $serviceId,
                    'date' => $date,
                    'start_time' => $startTimeFull,
                    'end_time' => $endTime,
                    'status' => 'pending'
                ], JSON_UNESCAPED_UNICODE);
                $stmtHistory->execute([$appointmentId, $newData, $customerId]);

                $db->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Đặt lịch thành công! Lịch hẹn đang chờ xác nhận.',
                    'appointment_id' => $appointmentId
                ]);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống, vui lòng thử lại']);
            }
            break;

        // ===== Hủy lịch hẹn =====
        case 'cancel_appointment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
                break;
            }

            if (!isLoggedIn()) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
                break;
            }

            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!validateCSRF($csrfToken)) {
                echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn']);
                break;
            }

            $appointmentId = intval($_POST['appointment_id'] ?? 0);
            $userId = $_SESSION['user_id'];

            // Lấy thông tin lịch hẹn
            $stmtAppt = $db->prepare("SELECT * FROM appointments WHERE id = ?");
            $stmtAppt->execute([$appointmentId]);
            $appointment = $stmtAppt->fetch(PDO::FETCH_ASSOC);

            if (!$appointment) {
                echo json_encode(['success' => false, 'message' => 'Lịch hẹn không tồn tại']);
                break;
            }

            // Kiểm tra quyền: customer chỉ hủy của mình, admin/staff có thể hủy bất kỳ
            if (isCustomer() && $appointment['customer_id'] != $userId) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền hủy lịch hẹn này']);
                break;
            }

            if (!in_array($appointment['status'], ['pending', 'confirmed'])) {
                echo json_encode(['success' => false, 'message' => 'Chỉ có thể hủy lịch hẹn đang chờ hoặc đã xác nhận']);
                break;
            }

            $db->beginTransaction();
            try {
                $oldStatus = $appointment['status'];
                $stmtUpdate = $db->prepare("UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
                $stmtUpdate->execute([$appointmentId]);

                $stmtHistory = $db->prepare("
                    INSERT INTO appointment_history (appointment_id, action, old_data, new_data, changed_by, changed_at)
                    VALUES (?, 'cancelled', ?, ?, ?, NOW())
                ");
                $oldData = json_encode(['status' => $oldStatus], JSON_UNESCAPED_UNICODE);
                $newData = json_encode(['status' => 'cancelled'], JSON_UNESCAPED_UNICODE);
                $stmtHistory->execute([$appointmentId, $oldData, $newData, $userId]);

                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Đã hủy lịch hẹn thành công']);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
            }
            break;

        // ===== Xác nhận lịch hẹn (admin/staff) =====
        case 'confirm_appointment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
                break;
            }

            if (!isLoggedIn() || (!isAdmin() && !isStaff())) {
                echo json_encode(['success' => false, 'message' => 'Không có quyền thực hiện']);
                break;
            }

            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!validateCSRF($csrfToken)) {
                echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn']);
                break;
            }

            $appointmentId = intval($_POST['appointment_id'] ?? 0);
            $userId = $_SESSION['user_id'];

            $stmtAppt = $db->prepare("SELECT * FROM appointments WHERE id = ?");
            $stmtAppt->execute([$appointmentId]);
            $appointment = $stmtAppt->fetch(PDO::FETCH_ASSOC);

            if (!$appointment) {
                echo json_encode(['success' => false, 'message' => 'Lịch hẹn không tồn tại']);
                break;
            }

            if ($appointment['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Chỉ có thể xác nhận lịch hẹn đang chờ']);
                break;
            }

            // Staff chỉ xác nhận lịch của mình
            if (isStaff() && $appointment['staff_id'] != $userId) {
                echo json_encode(['success' => false, 'message' => 'Bạn chỉ có thể xác nhận lịch hẹn của mình']);
                break;
            }

            $db->beginTransaction();
            try {
                $stmtUpdate = $db->prepare("UPDATE appointments SET status = 'confirmed', updated_at = NOW() WHERE id = ?");
                $stmtUpdate->execute([$appointmentId]);

                $stmtHistory = $db->prepare("
                    INSERT INTO appointment_history (appointment_id, action, old_data, new_data, changed_by, changed_at)
                    VALUES (?, 'confirmed', ?, ?, ?, NOW())
                ");
                $oldData = json_encode(['status' => 'pending'], JSON_UNESCAPED_UNICODE);
                $newData = json_encode(['status' => 'confirmed'], JSON_UNESCAPED_UNICODE);
                $stmtHistory->execute([$appointmentId, $oldData, $newData, $userId]);

                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Đã xác nhận lịch hẹn']);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
            }
            break;

        // ===== Hoàn thành lịch hẹn (admin/staff) =====
        case 'complete_appointment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
                break;
            }

            if (!isLoggedIn() || (!isAdmin() && !isStaff())) {
                echo json_encode(['success' => false, 'message' => 'Không có quyền thực hiện']);
                break;
            }

            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!validateCSRF($csrfToken)) {
                echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn']);
                break;
            }

            $appointmentId = intval($_POST['appointment_id'] ?? 0);
            $userId = $_SESSION['user_id'];

            $stmtAppt = $db->prepare("SELECT * FROM appointments WHERE id = ?");
            $stmtAppt->execute([$appointmentId]);
            $appointment = $stmtAppt->fetch(PDO::FETCH_ASSOC);

            if (!$appointment) {
                echo json_encode(['success' => false, 'message' => 'Lịch hẹn không tồn tại']);
                break;
            }

            if ($appointment['status'] !== 'confirmed') {
                echo json_encode(['success' => false, 'message' => 'Chỉ có thể hoàn thành lịch hẹn đã xác nhận']);
                break;
            }

            if (isStaff() && $appointment['staff_id'] != $userId) {
                echo json_encode(['success' => false, 'message' => 'Bạn chỉ có thể hoàn thành lịch hẹn của mình']);
                break;
            }

            $db->beginTransaction();
            try {
                $stmtUpdate = $db->prepare("UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE id = ?");
                $stmtUpdate->execute([$appointmentId]);

                $stmtHistory = $db->prepare("
                    INSERT INTO appointment_history (appointment_id, action, old_data, new_data, changed_by, changed_at)
                    VALUES (?, 'completed', ?, ?, ?, NOW())
                ");
                $oldData = json_encode(['status' => 'confirmed'], JSON_UNESCAPED_UNICODE);
                $newData = json_encode(['status' => 'completed'], JSON_UNESCAPED_UNICODE);
                $stmtHistory->execute([$appointmentId, $oldData, $newData, $userId]);

                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Đã hoàn thành lịch hẹn']);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu']);
}
