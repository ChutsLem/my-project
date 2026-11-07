<?php
/**
 * Lịch sử sử dụng dịch vụ - Khách hàng
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$customerId = $_SESSION['user_id'];

// Thống kê tổng quan cá nhân
$stmtStats = $db->prepare("
    SELECT 
        COUNT(CASE WHEN a.status = 'completed' THEN 1 END) AS total_completed,
        COUNT(CASE WHEN a.status = 'cancelled' THEN 1 END) AS total_cancelled,
        COALESCE(SUM(i.final_amount), 0) AS total_spent
    FROM appointments a
    LEFT JOIN invoices i ON a.id = i.appointment_id AND i.payment_status = 'paid'
    WHERE a.customer_id = ?
");
$stmtStats->execute([$customerId]);
$userStats = $stmtStats->fetch();

// Bộ lọc và tìm kiếm lịch sử
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

$sql = "
    SELECT a.*, s.name AS service_name, s.duration, 
           u.full_name AS staff_name,
           i.id AS invoice_id, i.final_amount, i.payment_status, i.payment_method, i.paid_at
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.staff_id = u.id
    LEFT JOIN invoices i ON a.id = i.appointment_id
    WHERE a.customer_id = ? AND a.status IN ('completed', 'cancelled')
";
$params = [$customerId];

if ($statusFilter !== 'all' && in_array($statusFilter, ['completed', 'cancelled'])) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (s.name LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";
$stmtHistory = $db->prepare($sql);
$stmtHistory->execute($params);
$historyList = $stmtHistory->fetchAll();

$pageTitle = 'Lịch sử sử dụng dịch vụ';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1"><i class="bi bi-clock-history me-2 text-primary"></i>Lịch sử sử dụng dịch vụ</h4>
    <p class="text-muted small mb-0">Theo dõi toàn bộ nhật ký làm đẹp và chi tiêu của bạn tại salon</p>
</div>

<!-- Thẻ tóm tắt chi tiêu -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Dịch vụ đã hoàn thành</p>
                        <h3 class="fw-bold mb-0 text-success"><?= $userStats['total_completed'] ?? 0 ?></h3>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check2-all"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Tổng tiền đã chi tiêu</p>
                        <h3 class="fw-bold mb-0 text-primary"><?= formatCurrency($userStats['total_spent'] ?? 0) ?></h3>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Lịch hẹn đã hủy</p>
                        <h3 class="fw-bold mb-0 text-danger"><?= $userStats['total_cancelled'] ?? 0 ?></h3>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-x-octagon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bộ lọc và tìm kiếm lịch sử -->
<div class="card table-card mb-4">
    <div class="card-body p-3">
        <form action="" method="GET" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Tìm theo tên dịch vụ, chuyên viên..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Đã hoàn thành</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Lọc dữ liệu</button>
                <a href="<?= BASE_URL ?>booking/history.php" class="btn btn-outline-secondary">Đặt lại</a>
            </div>
        </form>
    </div>
</div>

<!-- Danh sách chi tiết lịch sử -->
<div class="card table-card">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Chi tiết lịch sử dịch vụ</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Thời gian</th>
                    <th>Dịch vụ</th>
                    <th>Chuyên viên phục vụ</th>
                    <th>Trạng thái</th>
                    <th>Số tiền</th>
                    <th>Thanh toán</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historyList)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Chưa có lịch sử dịch vụ nào được ghi nhận.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historyList as $item): 
                        $statusMeta = getAppointmentStatusLabel($item['status']);
                    ?>
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold"><?= formatDate($item['appointment_date']) ?></div>
                            <small class="text-muted"><?= formatTime($item['start_time']) ?> - <?= formatTime($item['end_time']) ?></small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($item['service_name']) ?></div>
                            <small class="text-muted"><?= $item['duration'] ?> phút</small>
                        </td>
                        <td>
                            <span class="fw-medium"><?= htmlspecialchars($item['staff_name']) ?></span>
                        </td>
                        <td>
                            <span class="badge <?= $statusMeta['class'] ?>"><?= $statusMeta['label'] ?></span>
                        </td>
                        <td>
                            <?php if ($item['final_amount']): ?>
                                <strong class="text-danger"><?= formatCurrency($item['final_amount']) ?></strong>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['payment_status'] === 'paid'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check-circle me-1"></i>Đã thanh toán (<?= getPaymentMethodLabel($item['payment_method']) ?>)
                                </span>
                            <?php elseif ($item['status'] === 'completed'): ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <i class="bi bi-clock me-1"></i>Chờ quyết toán
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">Không áp dụng</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
