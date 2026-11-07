<?php
/**
 * Quản lý hóa đơn & Thanh toán - Admin
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();

// Xử lý tạo hóa đơn mới từ appointment nếu có request
if (isset($_GET['action']) && $_GET['action'] === 'create' && isset($_GET['appointment_id'])) {
    $apptId = intval($_GET['appointment_id']);
    
    // Kiểm tra xem đã có hóa đơn chưa
    $stmtCheck = $db->prepare("SELECT id FROM invoices WHERE appointment_id = ?");
    $stmtCheck->execute([$apptId]);
    $inv = $stmtCheck->fetch();

    if ($inv) {
        redirect("admin/invoice_detail.php?id={$inv['id']}");
    }

    $stmtAppt = $db->prepare("
        SELECT a.*, s.price 
        FROM appointments a 
        JOIN services s ON a.service_id = s.id 
        WHERE a.id = ?
    ");
    $stmtAppt->execute([$apptId]);
    $appt = $stmtAppt->fetch();

    if ($appt) {
        $price = $appt['price'];
        $stmtCreate = $db->prepare("
            INSERT INTO invoices (appointment_id, customer_id, total_amount, discount, final_amount, payment_method, payment_status, created_at)
            VALUES (?, ?, ?, 0, ?, 'cash', 'pending', NOW())
        ");
        $stmtCreate->execute([$apptId, $appt['customer_id'], $price, $price]);
        $newInvId = $db->lastInsertId();
        setFlash('Đã tạo hóa đơn thành công!', 'success');
        redirect("admin/invoice_detail.php?id={$newInvId}");
    }
}

// Xử lý POST thanh toán nhanh
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        setFlash('Mã bảo mật không hợp lệ!', 'danger');
        redirect('admin/invoices.php');
    }

    $action    = $_POST['action'] ?? '';
    $invoiceId = intval($_POST['invoice_id'] ?? 0);

    if ($action === 'mark_paid') {
        $method = $_POST['payment_method'] ?? 'cash';
        $stmtPaid = $db->prepare("
            UPDATE invoices 
            SET payment_status = 'paid', payment_method = ?, paid_at = NOW() 
            WHERE id = ?
        ");
        $stmtPaid->execute([$method, $invoiceId]);
        setFlash('Đã ghi nhận thanh toán thành công!', 'success');
        redirect('admin/invoices.php');
    }
}

// Bộ lọc & Tìm kiếm
$statusFilter = $_GET['status'] ?? 'all';
$methodFilter = $_GET['method'] ?? 'all';
$search       = trim($_GET['search'] ?? '');
$fromDate     = $_GET['from_date'] ?? '';
$toDate       = $_GET['to_date'] ?? '';

$sql = "
    SELECT i.*, 
           u.full_name AS customer_name, u.phone AS customer_phone,
           s.name AS service_name,
           a.appointment_date,
           st.full_name AS staff_name
    FROM invoices i
    JOIN users u ON i.customer_id = u.id
    JOIN appointments a ON i.appointment_id = a.id
    JOIN services s ON a.service_id = s.id
    JOIN users st ON a.staff_id = st.id
    WHERE 1=1
";
$params = [];

if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'paid', 'refunded'])) {
    $sql .= " AND i.payment_status = ?";
    $params[] = $statusFilter;
}

if ($methodFilter !== 'all' && in_array($methodFilter, ['cash', 'card', 'transfer'])) {
    $sql .= " AND i.payment_method = ?";
    $params[] = $methodFilter;
}

if (!empty($fromDate)) {
    $sql .= " AND DATE(i.created_at) >= ?";
    $params[] = $fromDate;
}

if (!empty($toDate)) {
    $sql .= " AND DATE(i.created_at) <= ?";
    $params[] = $toDate;
}

if (!empty($search)) {
    $sql .= " AND (u.full_name LIKE ? OR u.phone LIKE ? OR s.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY i.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Tổng số liệu
$totalAmountSum = 0;
$paidAmountSum = 0;
$pendingAmountSum = 0;

foreach ($invoices as $inv) {
    $totalAmountSum += $inv['final_amount'];
    if ($inv['payment_status'] === 'paid') {
        $paidAmountSum += $inv['final_amount'];
    } elseif ($inv['payment_status'] === 'pending') {
        $pendingAmountSum += $inv['final_amount'];
    }
}

$pageTitle = 'Quản lý hóa đơn & Thanh toán';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-receipt me-2 text-primary"></i>Hóa đơn & Doanh thu</h4>
        <p class="text-muted small mb-0">Theo dõi dòng tiền, quyết toán lịch hẹn và in phiếu thanh toán cho khách</p>
    </div>
</div>

<!-- Thống kê nhanh theo bộ lọc -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Tổng tiền hóa đơn hiển thị</p>
                <h4 class="fw-bold mb-0 text-dark"><?= formatCurrency($totalAmountSum) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Đã thực thu (Đã thanh toán)</p>
                <h4 class="fw-bold mb-0 text-success"><?= formatCurrency($paidAmountSum) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Chờ thu (Chưa thanh toán)</p>
                <h4 class="fw-bold mb-0 text-warning"><?= formatCurrency($pendingAmountSum) ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Bộ lọc -->
<div class="card table-card mb-4">
    <div class="card-body p-3">
        <form action="" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Tìm tên khách, SĐT, dịch vụ..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Mọi trạng thái</option>
                    <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Chờ thanh toán</option>
                    <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Đã hoàn tiền</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="method" class="form-select">
                    <option value="all" <?= $methodFilter === 'all' ? 'selected' : '' ?>>Mọi phương thức</option>
                    <option value="cash" <?= $methodFilter === 'cash' ? 'selected' : '' ?>>Tiền mặt</option>
                    <option value="card" <?= $methodFilter === 'card' ? 'selected' : '' ?>>Thẻ ngân hàng</option>
                    <option value="transfer" <?= $methodFilter === 'transfer' ? 'selected' : '' ?>>Chuyển khoản</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
                <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Lọc</button>
                <a href="<?= BASE_URL ?>admin/invoices.php" class="btn btn-outline-secondary">Xóa</a>
            </div>
        </form>
    </div>
</div>

<!-- Bảng hóa đơn -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 100px;">Hóa đơn</th>
                    <th>Khách hàng</th>
                    <th>Dịch vụ & Ngày</th>
                    <th>Chuyên viên</th>
                    <th>Thành tiền</th>
                    <th>Phương thức</th>
                    <th>Trạng thái</th>
                    <th class="text-end pe-3">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Không tìm thấy hóa đơn nào theo tiêu chí.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): 
                        $statusMeta = getPaymentStatusLabel($inv['payment_status']);
                    ?>
                    <tr>
                        <td class="ps-3 fw-bold text-dark">#INV-<?= $inv['id'] ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($inv['customer_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($inv['customer_phone'] ?? 'N/A') ?></small>
                        </td>
                        <td>
                            <div class="fw-medium text-dark"><?= htmlspecialchars($inv['service_name']) ?></div>
                            <small class="text-muted">Ngày: <?= formatDate($inv['appointment_date']) ?></small>
                        </td>
                        <td>
                            <span class="small text-secondary"><?= htmlspecialchars($inv['staff_name']) ?></span>
                        </td>
                        <td>
                            <span class="fw-bold text-danger"><?= formatCurrency($inv['final_amount']) ?></span>
                            <?php if ($inv['discount'] > 0): ?>
                                <small class="text-muted d-block">Giảm: <?= formatCurrency($inv['discount']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= getPaymentMethodLabel($inv['payment_method']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $statusMeta['class'] ?>"><?= $statusMeta['label'] ?></span>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>admin/invoice_detail.php?id=<?= $inv['id'] ?>" class="btn btn-outline-primary" title="Xem chi tiết & In">
                                    <i class="bi bi-printer me-1"></i>Xem
                                </a>
                                <?php if ($inv['payment_status'] === 'pending'): ?>
                                    <button type="button" class="btn btn-outline-success" 
                                            onclick="openPayModal(<?= $inv['id'] ?>, '<?= formatCurrency($inv['final_amount']) ?>', '<?= htmlspecialchars($inv['customer_name']) ?>')" title="Thu tiền">
                                        <i class="bi bi-cash-coin me-1"></i>Thu tiền
                                    </button>
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

<!-- Modal xác nhận thu tiền nhanh -->
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="mark_paid">
            <input type="hidden" name="invoice_id" id="modalInvId">

            <div class="modal-header">
                <h5 class="modal-title fw-bold text-success"><i class="bi bi-cash-stack me-2"></i>Xác nhận thu tiền</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Khách hàng: <strong id="modalCustName"></strong></p>
                <p class="fs-5">Số tiền cần thanh toán: <strong class="text-danger" id="modalAmount"></strong></p>
                
                <div class="mb-3">
                    <label class="form-label fw-medium">Phương thức thanh toán <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select">
                        <option value="cash">Tiền mặt</option>
                        <option value="card">Quẹt thẻ ngân hàng (POS)</option>
                        <option value="transfer">Chuyển khoản ngân hàng (QR)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-success">Xác nhận đã nhận tiền</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPayModal(invId, amountStr, customerName) {
    document.getElementById('modalInvId').value = invId;
    document.getElementById('modalCustName').innerText = customerName;
    document.getElementById('modalAmount').innerText = amountStr;
    const modal = new bootstrap.Modal(document.getElementById('payModal'));
    modal.show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
