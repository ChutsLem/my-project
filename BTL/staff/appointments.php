<?php
/**
 * Quản lý lịch hẹn được giao - Nhân viên
 */
require_once __DIR__ . '/../config/config.php';
requireStaff();

$db = getDB();
$staffId = $_SESSION['user_id'];

// Xử lý cập nhật trạng thái lịch hẹn
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        setFlash('Mã bảo mật không hợp lệ!', 'danger');
        redirect('staff/appointments.php');
    }

    $appointmentId = intval($_POST['appointment_id'] ?? 0);
    $newStatus     = $_POST['status'] ?? '';
    $actionNote    = trim($_POST['note'] ?? '');

    // Kiểm tra lịch hẹn thuộc về nhân viên này
    $stmtCheck = $db->prepare("SELECT * FROM appointments WHERE id = ? AND staff_id = ?");
    $stmtCheck->execute([$appointmentId, $staffId]);
    $appt = $stmtCheck->fetch();

    if (!$appt) {
        setFlash('Không tìm thấy lịch hẹn hoặc bạn không được phân công!', 'danger');
    } elseif (!in_array($newStatus, ['confirmed', 'completed', 'cancelled'])) {
        setFlash('Trạng thái chuyển đổi không hợp lệ!', 'danger');
    } else {
        // Cập nhật trạng thái
        $stmtUpdate = $db->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$newStatus, $appointmentId]);

        // Ghi nhận vào appointment_history
        $oldData = json_encode(['status' => $appt['status']]);
        $newData = json_encode(['status' => $newStatus, 'note' => $actionNote]);
        $actionType = match($newStatus) {
            'confirmed' => 'confirmed',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            default     => 'changed'
        };

        $stmtHist = $db->prepare("
            INSERT INTO appointment_history (appointment_id, action, old_data, new_data, changed_by, changed_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmtHist->execute([$appointmentId, $actionType, $oldData, $newData, $staffId]);

        // Nếu trạng thái là completed, kiểm tra và tự động tạo bản ghi hóa đơn nếu chưa có
        if ($newStatus === 'completed') {
            $stmtCheckInv = $db->prepare("SELECT id FROM invoices WHERE appointment_id = ?");
            $stmtCheckInv->execute([$appointmentId]);
            if (!$stmtCheckInv->fetch()) {
                // Lấy giá dịch vụ
                $stmtSvc = $db->prepare("SELECT price FROM services WHERE id = ?");
                $stmtSvc->execute([$appt['service_id']]);
                $svc = $stmtSvc->fetch();
                $amount = $svc['price'] ?? 0;

                $stmtCreateInv = $db->prepare("
                    INSERT INTO invoices (appointment_id, customer_id, total_amount, discount, final_amount, payment_method, payment_status, created_at)
                    VALUES (?, ?, ?, 0, ?, 'cash', 'pending', NOW())
                ");
                $stmtCreateInv->execute([$appointmentId, $appt['customer_id'], $amount, $amount]);
            }
        }

        setFlash('Đã cập nhật trạng thái lịch hẹn thành công!', 'success');
    }
    redirect('staff/appointments.php');
}

// Bộ lọc
$statusFilter = $_GET['status'] ?? 'all';
$dateFilter   = $_GET['date'] ?? '';
$search       = trim($_GET['search'] ?? '');

$sql = "
    SELECT a.*, s.name AS service_name, s.price AS service_price, s.duration,
           u.full_name AS customer_name, u.phone AS customer_phone, u.email AS customer_email
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.customer_id = u.id
    WHERE a.staff_id = ?
";
$params = [$staffId];

if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'confirmed', 'completed', 'cancelled'])) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}

if (!empty($dateFilter)) {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $dateFilter;
}

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR u.phone LIKE ? OR s.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle = 'Lịch hẹn được giao';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-calendar-check me-2 text-primary"></i>Lịch hẹn được phân công</h4>
        <p class="text-muted small mb-0">Tiếp nhận, xác nhận và hoàn tất dịch vụ cho khách hàng</p>
    </div>
</div>

<!-- Bộ lọc -->
<div class="card table-card mb-4">
    <div class="card-body p-3">
        <form action="" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Tìm tên khách, SĐT, dịch vụ..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                    <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Đã hoàn thành</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Lọc</button>
                <a href="<?= BASE_URL ?>staff/appointments.php" class="btn btn-outline-secondary">Xóa</a>
            </div>
        </form>
    </div>
</div>

<!-- Bảng danh sách lịch hẹn -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Mã #</th>
                    <th>Khách hàng</th>
                    <th>Dịch vụ</th>
                    <th>Thời gian hẹn</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú</th>
                    <th class="text-end pe-3">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Không tìm thấy lịch hẹn nào theo tiêu chí.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($appointments as $apt): 
                        $statusMeta = getAppointmentStatusLabel($apt['status']);
                    ?>
                    <tr>
                        <td class="ps-3 fw-bold text-muted">#<?= $apt['id'] ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($apt['customer_name']) ?></div>
                            <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($apt['customer_phone'] ?? 'N/A') ?></small>
                        </td>
                        <td>
                            <div class="fw-medium text-dark"><?= htmlspecialchars($apt['service_name']) ?></div>
                            <small class="text-muted"><?= $apt['duration'] ?> phút | <?= formatCurrency($apt['service_price']) ?></small>
                        </td>
                        <td>
                            <div class="fw-bold text-primary"><?= formatDate($apt['appointment_date']) ?></div>
                            <div class="small text-secondary"><?= formatTime($apt['start_time']) ?> - <?= formatTime($apt['end_time']) ?></div>
                        </td>
                        <td>
                            <span class="badge <?= $statusMeta['class'] ?>"><?= $statusMeta['label'] ?></span>
                        </td>
                        <td>
                            <span class="small text-muted text-truncate d-inline-block" style="max-width: 150px;">
                                <?= htmlspecialchars($apt['notes'] ?: 'Không có') ?>
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <?php if ($apt['status'] === 'pending'): ?>
                                    <form action="" method="POST" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" class="btn btn-outline-primary" title="Xác nhận lịch">
                                            <i class="bi bi-check-lg"></i> Nhận lịch
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($apt['status'] === 'confirmed'): ?>
                                    <form action="" method="POST" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn btn-success" title="Xác nhận hoàn thành">
                                            <i class="bi bi-check-circle me-1"></i>Hoàn thành
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array($apt['status'], ['pending', 'confirmed'])): ?>
                                    <form action="" method="POST" class="d-inline ms-1" onsubmit="return confirm('Bạn có chắc chắn muốn hủy lịch hẹn này?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="btn btn-outline-danger" title="Hủy lịch">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
