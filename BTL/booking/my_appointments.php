<?php
/**
 * Lịch hẹn của tôi - Khách hàng
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$customerId = $_SESSION['user_id'];

// Xử lý khách hủy lịch hẹn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_appointment') {
    if (!validateCSRF()) {
        setFlash('Mã bảo mật không hợp lệ!', 'danger');
        redirect('booking/my_appointments.php');
    }

    $appointmentId = intval($_POST['appointment_id'] ?? 0);
    $cancelReason  = trim($_POST['cancel_reason'] ?? 'Khách hàng hủy');

    // Kiểm tra lịch hẹn thuộc về khách hàng và có trạng thái pending hoặc confirmed
    $stmtCheck = $db->prepare("SELECT * FROM appointments WHERE id = ? AND customer_id = ?");
    $stmtCheck->execute([$appointmentId, $customerId]);
    $appt = $stmtCheck->fetch();

    if (!$appt) {
        setFlash('Không tìm thấy lịch hẹn hoặc bạn không có quyền!', 'danger');
    } elseif ($appt['status'] === 'completed') {
        setFlash('Lịch hẹn đã hoàn thành, không thể hủy!', 'danger');
    } elseif ($appt['status'] === 'cancelled') {
        setFlash('Lịch hẹn này đã bị hủy trước đó!', 'warning');
    } else {
        // Cập nhật trạng thái sang cancelled
        $stmtCancel = $db->prepare("UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
        $stmtCancel->execute([$appointmentId]);

        // Ghi lại lịch sử thay đổi vào appointment_history (Tiêu chí cụ thể đề tài 19: Phải lưu lịch sử đặt, hủy và thay đổi lịch)
        $oldData = json_encode(['status' => $appt['status'], 'notes' => $appt['notes']]);
        $newData = json_encode(['status' => 'cancelled', 'reason' => $cancelReason]);
        $stmtHist = $db->prepare("
            INSERT INTO appointment_history (appointment_id, action, old_data, new_data, changed_by, changed_at) 
            VALUES (?, 'cancelled', ?, ?, ?, NOW())
        ");
        $stmtHist->execute([$appointmentId, $oldData, $newData, $customerId]);

        setFlash('Hủy lịch hẹn thành công!', 'success');
    }
    redirect('booking/my_appointments.php');
}

// Lọc theo trạng thái và tìm kiếm
$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT a.*, s.name AS service_name, s.price AS service_price, s.duration AS service_duration,
           u.full_name AS staff_name, u.phone AS staff_phone
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.staff_id = u.id
    WHERE a.customer_id = ?
";
$params = [$customerId];

if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'confirmed', 'completed', 'cancelled'])) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (s.name LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle = 'Lịch hẹn của tôi';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-calendar-event me-2 text-primary"></i>Lịch hẹn của tôi</h4>
        <p class="text-muted small mb-0">Theo dõi, quản lý và hủy các cuộc hẹn làm đẹp đã đăng ký</p>
    </div>
    <a href="<?= BASE_URL ?>booking/index.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Đặt lịch mới
    </a>
</div>

<!-- Bộ lọc và tìm kiếm -->
<div class="card table-card mb-4">
    <div class="card-body p-3">
        <form action="" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Tìm theo tên dịch vụ, chuyên viên..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                    <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Đã hoàn thành</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Lọc dữ liệu</button>
                <a href="<?= BASE_URL ?>booking/my_appointments.php" class="btn btn-outline-secondary">Đặt lại</a>
            </div>
        </form>
    </div>
</div>

<!-- Danh sách lịch hẹn -->
<?php if (empty($appointments)): ?>
    <div class="card table-card p-5 text-center">
        <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
        <h5 class="fw-bold">Bạn chưa có lịch hẹn nào phù hợp</h5>
        <p class="text-muted small mb-3">Hãy đặt lịch ngay để được trải nghiệm các dịch vụ làm đẹp cao cấp tại salon!</p>
        <div>
            <a href="<?= BASE_URL ?>booking/index.php" class="btn btn-primary px-4">
                <i class="bi bi-calendar-plus me-1"></i>Đặt lịch ngay
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Mã #</th>
                        <th>Dịch vụ</th>
                        <th>Chuyên viên</th>
                        <th>Ngày hẹn & Giờ</th>
                        <th>Giá tiền</th>
                        <th>Trạng thái</th>
                        <th class="text-end pe-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $apt): 
                        $statusMeta = getAppointmentStatusLabel($apt['status']);
                    ?>
                    <tr>
                        <td class="ps-3 fw-bold text-muted">#<?= $apt['id'] ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($apt['service_name']) ?></div>
                            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= $apt['service_duration'] ?> phút</small>
                        </td>
                        <td>
                            <div class="fw-medium"><?= htmlspecialchars($apt['staff_name']) ?></div>
                            <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($apt['staff_phone'] ?? 'N/A') ?></small>
                        </td>
                        <td>
                            <div class="fw-semibold text-primary"><?= formatDate($apt['appointment_date']) ?></div>
                            <div class="small text-secondary"><?= formatTime($apt['start_time']) ?> - <?= formatTime($apt['end_time']) ?></div>
                        </td>
                        <td class="fw-bold text-danger"><?= formatCurrency($apt['service_price']) ?></td>
                        <td>
                            <span class="badge <?= $statusMeta['class'] ?> px-2 py-1"><?= $statusMeta['label'] ?></span>
                        </td>
                        <td class="text-end pe-3">
                            <?php if (in_array($apt['status'], ['pending', 'confirmed'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="openCancelModal(<?= $apt['id'] ?>, '<?= htmlspecialchars($apt['service_name']) ?>', '<?= formatDate($apt['appointment_date']) ?> <?= formatTime($apt['start_time']) ?>')">
                                    <i class="bi bi-x-circle me-1"></i>Hủy lịch
                                </button>
                            <?php else: ?>
                                <span class="text-muted small">Không khả dụng</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Xác nhận Hủy Lịch -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="cancel_appointment">
            <input type="hidden" name="appointment_id" id="modalApptId">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Xác nhận hủy cuộc hẹn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-2">Bạn có chắc chắn muốn hủy lịch hẹn cho dịch vụ <strong id="modalServiceName" class="text-primary"></strong> vào lúc <strong id="modalApptTime"></strong>?</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">LÝ DO HỦY LỊCH (TÙY CHỌN):</label>
                    <textarea name="cancel_reason" class="form-control" rows="2" placeholder="Ví dụ: Bận việc đột xuất, đổi lịch sau..."></textarea>
                </div>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-info-circle me-1"></i>Sau khi hủy, thông tin sẽ được lưu vào lịch sử hệ thống. Bạn có thể đặt lại lịch khác bất cứ lúc nào.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-danger">Xác nhận hủy</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCancelModal(id, serviceName, timeStr) {
    document.getElementById('modalApptId').value = id;
    document.getElementById('modalServiceName').innerText = serviceName;
    document.getElementById('modalApptTime').innerText = timeStr;
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
