<?php
/**
 * Quản lý lịch hẹn tổng thể - Admin
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();

// Xử lý POST nhanh (Xác nhận, Hoàn thành, Hủy lịch)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        setFlash('Mã bảo mật không hợp lệ!', 'danger');
        redirect('admin/appointments.php');
    }

    $appointmentId = intval($_POST['appointment_id'] ?? 0);
    $action        = $_POST['action'] ?? '';
    $notes         = trim($_POST['note'] ?? '');

    $stmtCheck = $db->prepare("SELECT * FROM appointments WHERE id = ?");
    $stmtCheck->execute([$appointmentId]);
    $appt = $stmtCheck->fetch();

    if ($appt && in_array($action, ['confirmed', 'completed', 'cancelled'])) {
        $stmtUpdate = $db->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$action, $appointmentId]);

        // Ghi lại lịch sử
        $oldData = json_encode(['status' => $appt['status']]);
        $newData = json_encode(['status' => $action, 'note' => $notes]);
        $stmtHist = $db->prepare("
            INSERT INTO appointment_history (appointment_id, action, old_data, new_data, changed_by, changed_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmtHist->execute([$appointmentId, $action, $oldData, $newData, $_SESSION['user_id']]);

        // Tự động tạo hóa đơn nếu hoàn thành mà chưa có hóa đơn
        if ($action === 'completed') {
            $stmtCheckInv = $db->prepare("SELECT id FROM invoices WHERE appointment_id = ?");
            $stmtCheckInv->execute([$appointmentId]);
            if (!$stmtCheckInv->fetch()) {
                $stmtSvc = $db->prepare("SELECT price FROM services WHERE id = ?");
                $stmtSvc->execute([$appt['service_id']]);
                $svc = $stmtSvc->fetch();
                $price = $svc['price'] ?? 0;

                $stmtInv = $db->prepare("
                    INSERT INTO invoices (appointment_id, customer_id, total_amount, discount, final_amount, payment_method, payment_status, created_at)
                    VALUES (?, ?, ?, 0, ?, 'cash', 'pending', NOW())
                ");
                $stmtInv->execute([$appointmentId, $appt['customer_id'], $price, $price]);
            }
        }

        setFlash('Cập nhật trạng thái lịch hẹn thành công!', 'success');
    }
    redirect('admin/appointments.php');
}

// Lấy danh sách nhân viên để lọc
$staffList = $db->query("SELECT id, full_name FROM users WHERE role = 'staff' AND status = 'active' ORDER BY full_name ASC")->fetchAll();

// Bộ lọc
$statusFilter = $_GET['status'] ?? 'all';
$staffFilter  = intval($_GET['staff_id'] ?? 0);
$fromDate     = $_GET['from_date'] ?? '';
$toDate       = $_GET['to_date'] ?? '';
$search       = trim($_GET['search'] ?? '');

$sql = "
    SELECT a.*, s.name AS service_name, s.price AS service_price, s.duration,
           c.full_name AS customer_name, c.phone AS customer_phone,
           st.full_name AS staff_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users c ON a.customer_id = c.id
    JOIN users st ON a.staff_id = st.id
    WHERE 1=1
";
$params = [];

if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'confirmed', 'completed', 'cancelled'])) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}

if ($staffFilter > 0) {
    $sql .= " AND a.staff_id = ?";
    $params[] = $staffFilter;
}

if (!empty($fromDate)) {
    $sql .= " AND a.appointment_date >= ?";
    $params[] = $fromDate;
}

if (!empty($toDate)) {
    $sql .= " AND a.appointment_date <= ?";
    $params[] = $toDate;
}

if (!empty($search)) {
    $sql .= " AND (c.full_name LIKE ? OR c.phone LIKE ? OR s.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle = 'Quản lý lịch hẹn';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-calendar-check me-2 text-primary"></i>Quản lý lịch hẹn salon</h4>
        <p class="text-muted small mb-0">Theo dõi, xác nhận, phân bổ và xử lý toàn bộ các yêu cầu đặt lịch</p>
    </div>
    <a href="<?= BASE_URL ?>booking/index.php" class="btn btn-primary" target="_blank">
        <i class="bi bi-plus-circle me-1"></i>Tạo lịch hẹn mới
    </a>
</div>

<!-- Bộ lọc tìm kiếm nâng cao -->
<div class="card table-card mb-4">
    <div class="card-body p-3">
        <form action="" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Tìm tên khách, SĐT, dịch vụ..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Mọi trạng thái</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                    <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Đã hoàn thành</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="staff_id" class="form-select">
                    <option value="0">Tất cả nhân viên</option>
                    <?php foreach ($staffList as $st): ?>
                        <option value="<?= $st['id'] ?>" <?= $staffFilter == $st['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($st['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <input type="date" name="from_date" class="form-control" title="Từ ngày" value="<?= htmlspecialchars($fromDate) ?>">
                <input type="date" name="to_date" class="form-control" title="Đến ngày" value="<?= htmlspecialchars($toDate) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Lọc dữ liệu</button>
                <a href="<?= BASE_URL ?>admin/appointments.php" class="btn btn-outline-secondary">Xóa</a>
            </div>
        </form>
    </div>
</div>

<!-- Bảng lịch hẹn -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 70px;">Mã #</th>
                    <th>Khách hàng</th>
                    <th>Dịch vụ</th>
                    <th>Chuyên viên</th>
                    <th>Ngày hẹn & Giờ</th>
                    <th>Giá tiền</th>
                    <th>Trạng thái</th>
                    <th class="text-end pe-3">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Không tìm thấy lịch hẹn nào theo điều kiện lọc.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($appointments as $apt): 
                        $statusMeta = getAppointmentStatusLabel($apt['status']);
                    ?>
                    <tr>
                        <td class="ps-3 fw-bold text-muted">#<?= $apt['id'] ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($apt['customer_name']) ?></div>
                            <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($apt['customer_phone'] ?? 'N/A') ?></small>
                        </td>
                        <td>
                            <div class="fw-medium text-dark"><?= htmlspecialchars($apt['service_name']) ?></div>
                            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= $apt['duration'] ?> phút</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-person me-1"></i><?= htmlspecialchars($apt['staff_name']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold text-primary"><?= formatDate($apt['appointment_date']) ?></div>
                            <div class="small text-secondary"><?= formatTime($apt['start_time']) ?> - <?= formatTime($apt['end_time']) ?></div>
                        </td>
                        <td>
                            <span class="fw-bold text-danger"><?= formatCurrency($apt['service_price']) ?></span>
                        </td>
                        <td>
                            <span class="badge <?= $statusMeta['class'] ?>"><?= $statusMeta['label'] ?></span>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>admin/appointment_detail.php?id=<?= $apt['id'] ?>" class="btn btn-outline-info" title="Xem chi tiết & Lịch sử">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <?php if ($apt['status'] === 'pending'): ?>
                                    <form action="" method="POST" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                        <input type="hidden" name="action" value="confirmed">
                                        <button type="submit" class="btn btn-outline-primary" title="Xác nhận lịch">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($apt['status'] === 'confirmed'): ?>
                                    <form action="" method="POST" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                        <input type="hidden" name="action" value="completed">
                                        <button type="submit" class="btn btn-outline-success" title="Hoàn thành & Lập hóa đơn">
                                            <i class="bi bi-check2-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array($apt['status'], ['pending', 'confirmed'])): ?>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn hủy lịch này?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                        <input type="hidden" name="action" value="cancelled">
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
