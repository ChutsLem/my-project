<?php
/**
 * Trang làm việc của Nhân viên (Staff Dashboard)
 */
require_once __DIR__ . '/../config/config.php';
requireStaff();

$db = getDB();
$staffId = $_SESSION['user_id'];

// Thống kê cá nhân nhân viên
// Lịch hẹn hôm nay
$stmtToday = $db->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE staff_id = ? AND appointment_date = CURDATE() AND status != 'cancelled'
");
$stmtToday->execute([$staffId]);
$todayCount = $stmtToday->fetchColumn();

// Lịch hẹn chờ xác nhận
$stmtPending = $db->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE staff_id = ? AND status = 'pending'
");
$stmtPending->execute([$staffId]);
$pendingCount = $stmtPending->fetchColumn();

// Đã hoàn thành trong tháng này
$stmtMonthCompleted = $db->prepare("
    SELECT COUNT(*) FROM appointments 
    WHERE staff_id = ? AND status = 'completed'
      AND MONTH(appointment_date) = MONTH(CURDATE()) 
      AND YEAR(appointment_date) = YEAR(CURDATE())
");
$stmtMonthCompleted->execute([$staffId]);
$monthCompleted = $stmtMonthCompleted->fetchColumn();

// Doanh thu mang lại tháng này
$stmtRevenue = $db->prepare("
    SELECT COALESCE(SUM(i.final_amount), 0)
    FROM appointments a
    JOIN invoices i ON a.id = i.appointment_id AND i.payment_status = 'paid'
    WHERE a.staff_id = ?
      AND MONTH(i.paid_at) = MONTH(CURDATE())
      AND YEAR(i.paid_at) = YEAR(CURDATE())
");
$stmtRevenue->execute([$staffId]);
$staffRevenue = $stmtRevenue->fetchColumn();

// Lịch hẹn hôm nay cần phục vụ
$stmtTodayAppts = $db->prepare("
    SELECT a.*, s.name AS service_name, s.duration, u.full_name AS customer_name, u.phone AS customer_phone
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.customer_id = u.id
    WHERE a.staff_id = ? AND a.appointment_date = CURDATE()
    ORDER BY a.start_time ASC
");
$stmtTodayAppts->execute([$staffId]);
$todayAppts = $stmtTodayAppts->fetchAll();

// Lịch làm việc tuần này
$stmtSchedule = $db->prepare("
    SELECT * FROM work_schedules 
    WHERE staff_id = ? 
    ORDER BY day_of_week ASC
");
$stmtSchedule->execute([$staffId]);
$schedules = $stmtSchedule->fetchAll();

$pageTitle = 'Trang làm việc nhân viên';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1"><i class="bi bi-speedometer2 me-2 text-primary"></i>Khu vực làm việc chuyên viên</h4>
    <p class="text-muted small mb-0">Xin chào <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>! Theo dõi lịch hẹn và ca trực hôm nay.</p>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Lịch hẹn hôm nay</p>
                        <h3 class="fw-bold mb-0 text-primary"><?= $todayCount ?></h3>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Lịch cần xác nhận</p>
                        <h3 class="fw-bold mb-0 text-warning"><?= $pendingCount ?></h3>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Đã xong trong tháng</p>
                        <h3 class="fw-bold mb-0 text-success"><?= $monthCompleted ?></h3>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-award"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Doanh số tạo ra tháng</p>
                        <h4 class="fw-bold mb-0 text-danger"><?= formatCurrency($staffRevenue) ?></h4>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Danh sách lịch hẹn hôm nay -->
    <div class="col-lg-8">
        <div class="card table-card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-calendar-day me-2 text-primary"></i>Lịch hẹn phục vụ hôm nay (<?= date('d/m/Y') ?>)</h6>
                <a href="<?= BASE_URL ?>staff/appointments.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($todayAppts)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-calendar2-check fs-2 d-block mb-2"></i>
                        Hôm nay bạn không có lịch hẹn nào. Hãy nghỉ ngơi hoặc chuẩn bị đồ nghề!
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Khung giờ</th>
                                    <th>Khách hàng</th>
                                    <th>Dịch vụ</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end pe-3">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todayAppts as $apt): 
                                    $meta = getAppointmentStatusLabel($apt['status']);
                                ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-primary">
                                        <?= formatTime($apt['start_time']) ?> - <?= formatTime($apt['end_time']) ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($apt['customer_name']) ?></div>
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($apt['customer_phone'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($apt['service_name']) ?></div>
                                        <small class="text-muted"><?= $apt['duration'] ?> phút</small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $meta['class'] ?>"><?= $meta['label'] ?></span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="<?= BASE_URL ?>staff/appointments.php" class="btn btn-sm btn-outline-secondary">
                                            Chi tiết
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Lịch làm việc trong tuần -->
    <div class="col-lg-4">
        <div class="card table-card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Lịch ca trực cố định</h6>
            </div>
            <div class="card-body p-3">
                <ul class="list-group list-group-flush">
                    <?php 
                    $todayW = intval(date('w'));
                    foreach ($schedules as $sc): 
                        $isToday = ($sc['day_of_week'] == $todayW);
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-2 <?= $isToday ? 'bg-light rounded' : '' ?>">
                        <span class="fw-medium <?= $isToday ? 'text-primary fw-bold' : '' ?>">
                            <?= getDayName($sc['day_of_week']) ?>
                            <?php if ($isToday): ?><span class="badge bg-primary ms-1">Hôm nay</span><?php endif; ?>
                        </span>
                        <?php if ($sc['is_off']): ?>
                            <span class="badge bg-secondary">Nghỉ ca</span>
                        <?php else: ?>
                            <span class="small fw-bold text-dark">
                                <?= formatTime($sc['start_time']) ?> - <?= formatTime($sc['end_time']) ?>
                            </span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
