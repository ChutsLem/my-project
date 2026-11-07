<?php
/**
 * Chi tiết & In hóa đơn thanh toán - Admin
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);

// Lấy thông tin hóa đơn
$stmt = $db->prepare("
    SELECT i.*, 
           u.full_name AS customer_name, u.phone AS customer_phone, u.email AS customer_email,
           a.appointment_date, a.start_time, a.end_time,
           s.name AS service_name, s.price AS service_price, s.duration,
           st.full_name AS staff_name
    FROM invoices i
    JOIN users u ON i.customer_id = u.id
    JOIN appointments a ON i.appointment_id = a.id
    JOIN services s ON a.service_id = s.id
    JOIN users st ON a.staff_id = st.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    setFlash('Không tìm thấy hóa đơn!', 'danger');
    redirect('admin/invoices.php');
}

// Xử lý cập nhật thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payment') {
    if (!validateCSRF()) {
        setFlash('Mã bảo mật không hợp lệ!', 'danger');
        redirect("admin/invoice_detail.php?id={$id}");
    }

    $status  = $_POST['payment_status'] ?? 'pending';
    $method  = $_POST['payment_method'] ?? 'cash';
    $discount = floatval($_POST['discount'] ?? 0);
    $notes   = trim($_POST['notes'] ?? '');

    $total = $invoice['total_amount'];
    if ($discount < 0) $discount = 0;
    if ($discount > $total) $discount = $total;
    $final = $total - $discount;

    $paidAt = ($status === 'paid') ? date('Y-m-d H:i:s') : null;

    $stmtUpdate = $db->prepare("
        UPDATE invoices 
        SET payment_status = ?, payment_method = ?, discount = ?, final_amount = ?, notes = ?, 
            paid_at = IF(? = 'paid', COALESCE(paid_at, NOW()), NULL)
        WHERE id = ?
    ");
    $stmtUpdate->execute([$status, $method, $discount, $final, $notes, $status, $id]);

    setFlash('Cập nhật trạng thái thanh toán thành công!', 'success');
    redirect("admin/invoice_detail.php?id={$id}");
}

$pageTitle = 'Hóa đơn #INV-' . $id;
include __DIR__ . '/../includes/header.php';
?>

<style>
@media print {
    .sidebar, .top-navbar, .no-print, .btn, form {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .page-content {
        padding: 0 !important;
    }
    .invoice-box {
        box-shadow: none !important;
        border: none !important;
    }
}
.invoice-box {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    padding: 40px;
}
</style>

<div class="mb-4 no-print d-flex justify-content-between align-items-center">
    <div>
        <a href="<?= BASE_URL ?>admin/invoices.php" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách hóa đơn
        </a>
        <h4 class="fw-bold mt-2 mb-0">Hóa đơn thanh toán #INV-<?= $invoice['id'] ?></h4>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>In hóa đơn
        </button>
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPaymentModal">
            <i class="bi bi-pencil me-1"></i>Cập nhật thanh toán
        </button>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="invoice-box mb-4">
            <!-- Header Hóa đơn -->
            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
                <div>
                    <h3 class="fw-bold text-primary mb-1"><i class="bi bi-flower1 me-2"></i>Beauty Salon</h3>
                    <p class="text-muted small mb-0">123 Đường Sắc Đẹp, Quận 1, TP. Hồ Chí Minh</p>
                    <p class="text-muted small mb-0">Hotline: 0901 000 001 | Email: contact@beautysalon.vn</p>
                </div>
                <div class="text-end">
                    <h4 class="fw-bold text-dark mb-1">PHIẾU THANH TOÁN</h4>
                    <div class="text-muted small">Mã HĐ: <strong class="text-dark">#INV-<?= $invoice['id'] ?></strong></div>
                    <div class="text-muted small">Ngày lập: <?= formatDateTime($invoice['created_at']) ?></div>
                    <div class="mt-2">
                        <?php $meta = getPaymentStatusLabel($invoice['payment_status']); ?>
                        <span class="badge <?= $meta['class'] ?> fs-6 px-3 py-1"><?= $meta['label'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Khách hàng & Lịch hẹn -->
            <div class="row mb-4">
                <div class="col-sm-6">
                    <h6 class="fw-bold text-uppercase small text-muted">Khách hàng</h6>
                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($invoice['customer_name']) ?></h5>
                    <p class="text-muted small mb-1"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($invoice['customer_phone'] ?? 'Chưa cập nhật') ?></p>
                    <p class="text-muted small mb-0"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($invoice['customer_email'] ?? 'Chưa cập nhật') ?></p>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <h6 class="fw-bold text-uppercase small text-muted">Chi tiết lịch hẹn</h6>
                    <p class="mb-1"><strong>Lịch hẹn số:</strong> #<?= $invoice['appointment_id'] ?></p>
                    <p class="mb-1 text-muted small"><strong>Ngày thực hiện:</strong> <?= formatDate($invoice['appointment_date']) ?></p>
                    <p class="mb-0 text-muted small"><strong>Chuyên viên phục vụ:</strong> <?= htmlspecialchars($invoice['staff_name']) ?></p>
                </div>
            </div>

            <!-- Bảng dịch vụ thanh toán -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 50px;">STT</th>
                            <th>Mô tả dịch vụ làm đẹp</th>
                            <th class="text-center" style="width: 140px;">Thời lượng</th>
                            <th class="text-end pe-3" style="width: 180px;">Đơn giá</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="ps-3 text-center">1</td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($invoice['service_name']) ?></strong>
                            </td>
                            <td class="text-center"><?= $invoice['duration'] ?> phút</td>
                            <td class="text-end pe-3 fw-bold"><?= formatCurrency($invoice['total_amount']) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tổng kết tiền -->
            <div class="row justify-content-end mb-4">
                <div class="col-md-5">
                    <div class="p-3 bg-light rounded-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tổng phụ:</span>
                            <strong><?= formatCurrency($invoice['total_amount']) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Chiết khấu / Giảm giá:</span>
                            <span class="text-success fw-bold">-<?= formatCurrency($invoice['discount']) ?></span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-0">
                            <span class="fw-bold fs-6">TỔNG CỘNG:</span>
                            <strong class="text-danger fs-5"><?= formatCurrency($invoice['final_amount']) ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ghi chú & Chữ ký -->
            <div class="row pt-4 border-top">
                <div class="col-md-7">
                    <p class="small text-muted mb-1"><strong>Phương thức thanh toán:</strong> <?= getPaymentMethodLabel($invoice['payment_method']) ?></p>
                    <?php if (!empty($invoice['paid_at'])): ?>
                        <p class="small text-muted mb-1"><strong>Thời gian thanh toán:</strong> <?= formatDateTime($invoice['paid_at']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($invoice['notes'])): ?>
                        <p class="small text-muted mb-0"><strong>Ghi chú:</strong> <?= htmlspecialchars($invoice['notes']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-5 text-center mt-3 mt-md-0">
                    <p class="small fw-bold text-dark mb-5">Người thu tiền / Đại diện salon</p>
                    <p class="small text-muted mb-0"><?= htmlspecialchars($_SESSION['full_name']) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal cập nhật thanh toán -->
<div class="modal fade no-print" id="editPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_payment">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Cập nhật thanh toán #INV-<?= $invoice['id'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">Trạng thái thanh toán <span class="text-danger">*</span></label>
                    <select name="payment_status" class="form-select">
                        <option value="pending" <?= $invoice['payment_status'] === 'pending' ? 'selected' : '' ?>>Chờ thanh toán</option>
                        <option value="paid" <?= $invoice['payment_status'] === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                        <option value="refunded" <?= $invoice['payment_status'] === 'refunded' ? 'selected' : '' ?>>Đã hoàn tiền</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Phương thức thanh toán</label>
                    <select name="payment_method" class="form-select">
                        <option value="cash" <?= $invoice['payment_method'] === 'cash' ? 'selected' : '' ?>>Tiền mặt</option>
                        <option value="card" <?= $invoice['payment_method'] === 'card' ? 'selected' : '' ?>>Thẻ ngân hàng</option>
                        <option value="transfer" <?= $invoice['payment_method'] === 'transfer' ? 'selected' : '' ?>>Chuyển khoản</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Giảm giá / Ưu đãi (VNĐ)</label>
                    <input type="number" name="discount" class="form-control" value="<?= $invoice['discount'] ?>" min="0" step="1000">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Ghi chú</label>
                    <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($invoice['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
