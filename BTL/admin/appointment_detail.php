<?php
/**
 * Chi tiết lịch hẹn & Nhật ký thay đổi - Admin
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);

// Lấy thông tin lịch hẹn
$stmt = $db->prepare("
    SELECT a.*, 
           s.name AS service_name, s.price AS service_price, s.duration, c.name AS category_name,
           u.full_name AS customer_name, u.phone AS customer_phone, u.email AS customer_email,
           st.full_name AS staff_name, st.phone AS staff_phone
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    LEFT JOIN categories c ON s.category_id = c.id
    JOIN users u ON a.customer_id = u.id
    JOIN users st ON a.staff_id = st.id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash('Không tìm thấy lịch hẹn!', 'danger');
    redirect('admin/appointments.php');
}

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!validateCSRF()) {
        setFlash('Mã bảo mật không hợp lệ!', 'danger');
        redirect("admin/appointment_detail.php?id={$id}");
    }

    $newStatus = $_POST['status'] ?? '';
    $note      = trim($_POST['status_note'] ?? '');

    if (in_array($newStatus, ['pending', 'confirmed', 'completed', 'cancelled'])) {
        $stmtUpdate = $db->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$newStatus, $id]);

        // Ghi lịch sử
        $oldData = json_encode(['status' => $appointment['status']]);
        $newData = json_encode(['status' => $newStatus, 'note' => $note]);
        $stmtHist = $db->prepare("
            INSERT INTO appointment_history (appointment_id, action, old_data, new_data, changed_by, changed_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmtHist->execute([$id, $newStatus, $oldData, $newData, $_SESSION['user_id']]);

        // Tạo hóa đơn nếu hoàn thành
        if ($newStatus === 'completed') {
            $stmtCheckInv = $db->prepare("SELECT id FROM invoices WHERE appointment_id = ?");
            $stmtCheckInv->execute([$id]);
            if (!$stmtCheckInv->fetch()) {
                $price = $appointment['service_price'];
                $stmtInv = $db->prepare("
                    INSERT INTO invoices (appointment_id, customer_id, total_amount, discount, final_amount, payment_method, payment_status, created_at)
                    VALUES (?, ?, ?, 0, ?, 'cash', 'pending', NOW())
                ");
                $stmtInv->execute([$id, $appointment['customer_id'], $price, $price]);
            }
        }

        setFlash('Cập nhật trạng thái thành công!', 'success');
        redirect("admin/appointment_detail.php?id={$id}");
    }
}

// Lấy thông tin hóa đơn nếu có
$stmtInv = $db->prepare("SELECT * FROM invoices WHERE appointment_id = ?");
$stmtInv->execute([$id]);
$invoice = $stmtInv->fetch();

// Lấy lịch sử thay đổi của cuộc hẹn này
$stmtHistory = $db->prepare("
    SELECT h.*, u.full_name AS changer_name, u.role AS changer_role
    FROM appointment_history h
    JOIN users u ON h.changed_by = u.id
    WHERE h.appointment_id = ?
    ORDER BY h.changed_at DESC
");
$stmtHistory->execute([$id]);
$historyLogs = $stmtHistory->fetchAll();

$pageTitle = 'Chi tiết lịch hẹn #' . $id;
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>admin/appointments.php" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách lịch hẹn
    </a>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h4 class="fw-bold mb-0">
            <i class="bi bi-calendar-event me-2 text-primary"></i>Lịch hẹn #<?= $appointment['id'] ?>
        </h4>
        <?php $statusMeta = getAppointmentStatusLabel($appointment['status']); ?>
        <span class="badge <?= $statusMeta['class'] ?> fs-6 px-3 py-2">
            <?= $statusMeta['label'] ?>
        </span>
    </div>
</div>

<div class="row g-4">
    <!-- Cột trái: Thông tin chính -->
    <div class="col-lg-8">
        <!-- Thẻ thông tin dịch vụ & lịch trình -->
        <div class="card table-card mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-stars me-2 text-primary"></i>Thông tin dịch vụ & thời gian</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">DỊCH VỤ</label>
                        <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($appointment['service_name']) ?></h5>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($appointment['category_name'] ?? 'Dịch vụ') ?></span>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">GIÁ DỊCH VỤ</label>
                        <h4 class="fw-bold text-danger mb-0"><?= formatCurrency($appointment['service_price']) ?></h4>
                        <small class="text-muted">Thời lượng: <?= $appointment['duration'] ?> phút</small>
                    </div>
                    <div class="col-12"><hr class="my-2"></div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">NGÀY HẸN</label>
                        <div class="fw-bold text-primary fs-5">
                            <i class="bi bi-calendar3 me-1"></i><?= formatDate($appointment['appointment_date']) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">KHUNG GIỜ</label>
                        <div class="fw-bold text-dark fs-5">
                            <i class="bi bi-clock me-1"></i><?= formatTime($appointment['start_time']) ?> - <?= formatTime($appointment['end_time']) ?>
                        </div>
                    </div>
                    <?php if (!empty($appointment['notes'])): ?>
                    <div class="col-12 mt-3">
                        <label class="text-muted small fw-bold">GHI CHÚ CỦA KHÁCH</label>
                        <div class="p-3 bg-light rounded text-secondary small">
                            <?= nl2br(htmlspecialchars($appointment['notes'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Thẻ đối tác (Khách hàng & Chuyên viên) -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card table-card h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-person me-2 text-primary"></i>Thông tin khách hàng</h6>
                    </div>
                    <div class="card-body p-3 small">
                        <p class="mb-1 fw-bold text-dark fs-6"><?= htmlspecialchars($appointment['customer_name']) ?></p>
                        <p class="mb-1 text-muted"><i class="bi bi-telephone me-2"></i><?= htmlspecialchars($appointment['customer_phone'] ?? 'Chưa có SĐT') ?></p>
                        <p class="mb-0 text-muted"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($appointment['customer_email'] ?? 'Chưa có email') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card table-card h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Chuyên viên phụ trách</h6>
                    </div>
                    <div class="card-body p-3 small">
                        <p class="mb-1 fw-bold text-dark fs-6"><?= htmlspecialchars($appointment['staff_name']) ?></p>
                        <p class="mb-1 text-muted"><i class="bi bi-telephone me-2"></i><?= htmlspecialchars($appointment['staff_phone'] ?? 'N/A') ?></p>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Kỹ thuật viên</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nhật ký lịch sử thay đổi (Criteria: Phải lưu lịch sử đặt, hủy và thay đổi lịch) -->
        <div class="card table-card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Nhật ký lịch sử thay đổi</h6>
            </div>
            <div class="card-body p-4">
                <?php if (empty($historyLogs)): ?>
                    <p class="text-muted small mb-0">Chưa có nhật ký thay đổi nào cho lịch hẹn này.</p>
                <?php else: ?>
                    <ul class="timeline list-unstyled mb-0 position-relative ps-4">
                        <?php foreach ($historyLogs as $log): ?>
                            <li class="mb-3 position-relative pb-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-secondary-subtle text-dark text-uppercase"><?= $log['action'] ?></span>
                                    <small class="text-muted"><?= formatDateTime($log['changed_at']) ?></small>
                                </div>
                                <div class="small">
                                    Thực hiện bởi: <strong><?= htmlspecialchars($log['changer_name']) ?></strong> 
                                    <span class="text-muted">(<?= $log['changer_role'] ?>)</span>
                                </div>
                                <?php if ($log['new_data']): ?>
                                    <div class="bg-light p-2 rounded small mt-1 text-muted font-monospace" style="font-size: 0.78rem;">
                                        <?= htmlspecialchars($log['new_data']) ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cột phải: Thao tác & Hóa đơn -->
    <div class="col-lg-4">
        <!-- Đổi trạng thái -->
        <div class="card table-card mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-arrow-repeat me-2 text-primary"></i>Cập nhật trạng thái</h6>
            </div>
            <div class="card-body p-3">
                <form action="" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_status">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">CHỌN TRẠNG THÁI</label>
                        <select name="status" class="form-select">
                            <option value="pending" <?= $appointment['status'] === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                            <option value="confirmed" <?= $appointment['status'] === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                            <option value="completed" <?= $appointment['status'] === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                            <option value="cancelled" <?= $appointment['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">GHI CHÚ THAY ĐỔI</label>
                        <textarea name="status_note" class="form-control" rows="2" placeholder="Lý do hoặc ghi chú nội bộ..."></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Lưu trạng thái</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Hóa đơn liên kết -->
        <div class="card table-card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-primary"></i>Hóa đơn thanh toán</h6>
            </div>
            <div class="card-body p-3">
                <?php if ($invoice): 
                    $invMeta = getPaymentStatusLabel($invoice['payment_status']);
                ?>
                    <div class="text-center mb-3">
                        <span class="badge <?= $invMeta['class'] ?> fs-6 px-3 py-2"><?= $invMeta['label'] ?></span>
                    </div>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Mã hóa đơn:</span>
                            <strong>#INV-<?= $invoice['id'] ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Tổng tiền:</span>
                            <span><?= formatCurrency($invoice['total_amount']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Giảm giá:</span>
                            <span class="text-success">-<?= formatCurrency($invoice['discount']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 border-top pt-1">
                            <span class="fw-bold">Thành tiền:</span>
                            <strong class="text-danger fs-6"><?= formatCurrency($invoice['final_amount']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Phương thức:</span>
                            <span><?= getPaymentMethodLabel($invoice['payment_method']) ?></span>
                        </div>
                    </div>
                    <div class="d-grid">
                        <a href="<?= BASE_URL ?>admin/invoice_detail.php?id=<?= $invoice['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-printer me-1"></i>Xem & In hóa đơn
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-muted small text-center mb-3">Chưa có hóa đơn nào được tạo cho lịch hẹn này.</p>
                    <?php if ($appointment['status'] === 'completed'): ?>
                        <div class="d-grid">
                            <a href="<?= BASE_URL ?>admin/invoices.php?action=create&appointment_id=<?= $appointment['id'] ?>" class="btn btn-success btn-sm">
                                <i class="bi bi-receipt me-1"></i>Lập hóa đơn ngay
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
