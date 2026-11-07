<?php
/**
 * Thống kê & Báo cáo - Admin
 * Đề tài 19: Thống kê doanh thu theo dịch vụ và nhân viên
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();

// Lọc thời gian (Mặc định năm nay)
$year = intval($_GET['year'] ?? date('Y'));
$month = $_GET['month'] ?? 'all';

// ============================================================
// 1. TỔNG QUAN DOANH SỐ
// ============================================================
$stmtTotalRev = $db->prepare("
    SELECT COALESCE(SUM(final_amount), 0) FROM invoices 
    WHERE payment_status = 'paid' AND YEAR(paid_at) = ?
");
$stmtTotalRev->execute([$year]);
$totalYearRevenue = $stmtTotalRev->fetchColumn();

// Tổng số lịch hẹn trong năm
$stmtTotalAppt = $db->prepare("
    SELECT COUNT(*) FROM appointments WHERE YEAR(appointment_date) = ?
");
$stmtTotalAppt->execute([$year]);
$totalYearAppointments = $stmtTotalAppt->fetchColumn();

// Lịch hẹn hoàn thành
$stmtCompletedAppt = $db->prepare("
    SELECT COUNT(*) FROM appointments WHERE status = 'completed' AND YEAR(appointment_date) = ?
");
$stmtCompletedAppt->execute([$year]);
$completedYearAppointments = $stmtCompletedAppt->fetchColumn();

$completionRate = ($totalYearAppointments > 0) ? round(($completedYearAppointments / $totalYearAppointments) * 100, 1) : 0;

// ============================================================
// 2. BIỂU ĐỒ DOANH THU 12 THÁNG TRONG NĂM
// ============================================================
$monthlyRevenues = array_fill(1, 12, 0);
$stmtMonthly = $db->prepare("
    SELECT MONTH(paid_at) AS m, SUM(final_amount) AS revenue 
    FROM invoices 
    WHERE payment_status = 'paid' AND YEAR(paid_at) = ?
    GROUP BY MONTH(paid_at)
");
$stmtMonthly->execute([$year]);
while ($row = $stmtMonthly->fetch()) {
    $monthlyRevenues[intval($row['m'])] = floatval($row['revenue']);
}

// ============================================================
// 3. THỐNG KÊ DOANH THU THEO DỊCH VỤ
// ============================================================
$stmtServiceStats = $db->prepare("
    SELECT s.id, s.name, c.name AS category_name,
           COUNT(a.id) AS total_bookings,
           COUNT(CASE WHEN a.status = 'completed' THEN 1 END) AS completed_bookings,
           COALESCE(SUM(CASE WHEN i.payment_status = 'paid' THEN i.final_amount ELSE 0 END), 0) AS total_revenue
    FROM services s
    LEFT JOIN categories c ON s.category_id = c.id
    LEFT JOIN appointments a ON s.id = a.service_id AND YEAR(a.appointment_date) = ?
    LEFT JOIN invoices i ON a.id = i.appointment_id
    GROUP BY s.id, s.name, c.name
    ORDER BY total_revenue DESC
");
$stmtServiceStats->execute([$year]);
$serviceStats = $stmtServiceStats->fetchAll();

// Chuẩn bị dữ liệu biểu đồ dịch vụ (Top 6)
$topServiceNames = [];
$topServiceRevenues = [];
foreach (array_slice($serviceStats, 0, 6) as $st) {
    $topServiceNames[] = $st['name'];
    $topServiceRevenues[] = floatval($st['total_revenue']);
}

// ============================================================
// 4. THỐNG KÊ DOANH THU & HIỆU SUẤT THEO NHÂN VIÊN
// ============================================================
$stmtStaffStats = $db->prepare("
    SELECT u.id, u.full_name, u.phone,
           COUNT(a.id) AS total_assigned,
           COUNT(CASE WHEN a.status = 'completed' THEN 1 END) AS total_completed,
           COUNT(CASE WHEN a.status = 'cancelled' THEN 1 END) AS total_cancelled,
           COALESCE(SUM(CASE WHEN i.payment_status = 'paid' THEN i.final_amount ELSE 0 END), 0) AS total_revenue
    FROM users u
    LEFT JOIN appointments a ON u.id = a.staff_id AND YEAR(a.appointment_date) = ?
    LEFT JOIN invoices i ON a.id = i.appointment_id
    WHERE u.role = 'staff'
    GROUP BY u.id, u.full_name, u.phone
    ORDER BY total_revenue DESC
");
$stmtStaffStats->execute([$year]);
$staffStats = $stmtStaffStats->fetchAll();

$staffNames = [];
$staffRevenues = [];
foreach ($staffStats as $st) {
    $staffNames[] = $st['full_name'];
    $staffRevenues[] = floatval($st['total_revenue']);
}

// ============================================================
// 5. CƠ CẤU TRẠNG THÁI LỊCH HẸN
// ============================================================
$stmtApptStatus = $db->prepare("
    SELECT status, COUNT(*) AS cnt 
    FROM appointments 
    WHERE YEAR(appointment_date) = ? 
    GROUP BY status
");
$stmtApptStatus->execute([$year]);
$statusMap = ['pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
while ($row = $stmtApptStatus->fetch()) {
    $statusMap[$row['status']] = intval($row['cnt']);
}

$pageTitle = 'Thống kê & Báo cáo doanh thu';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Báo cáo & Thống kê kinh doanh</h4>
        <p class="text-muted small mb-0">Tổng hợp doanh thu theo dịch vụ, hiệu suất nhân viên và phân tích lịch hẹn</p>
    </div>
    <!-- Chọn năm -->
    <form action="" method="GET" class="d-flex align-items-center gap-2">
        <label class="text-muted small fw-bold mb-0">Năm:</label>
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Tổng doanh thu năm <?= $year ?></p>
                        <h4 class="fw-bold mb-0 text-primary"><?= formatCurrency($totalYearRevenue) ?></h4>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-cash-stack"></i>
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
                        <p class="text-muted small mb-1">Tổng lượt đặt lịch</p>
                        <h4 class="fw-bold mb-0 text-dark"><?= $totalYearAppointments ?> lượt</h4>
                    </div>
                    <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-calendar-event"></i>
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
                        <p class="text-muted small mb-1">Đã hoàn thành phục vụ</p>
                        <h4 class="fw-bold mb-0 text-success"><?= $completedYearAppointments ?> lượt</h4>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check-circle"></i>
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
                        <p class="text-muted small mb-1">Tỷ lệ hoàn thành</p>
                        <h4 class="fw-bold mb-0 text-info"><?= $completionRate ?>%</h4>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-pie-chart"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-4 mb-4">
    <!-- Biểu đồ 1: Doanh thu 12 tháng -->
    <div class="col-lg-8">
        <div class="card table-card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up me-2 text-primary"></i>Biến động doanh thu theo tháng (Năm <?= $year ?>)</h6>
            </div>
            <div class="card-body p-3">
                <div style="height: 320px;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ 2: Cơ cấu trạng thái lịch hẹn -->
    <div class="col-lg-4">
        <div class="card table-card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart me-2 text-primary"></i>Tỷ lệ trạng thái lịch hẹn</h6>
            </div>
            <div class="card-body p-3">
                <div style="height: 320px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Biểu đồ 3: Doanh thu theo chuyên viên -->
    <div class="col-lg-6">
        <div class="card table-card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-people me-2 text-primary"></i>So sánh doanh thu giữa các chuyên viên</h6>
            </div>
            <div class="card-body p-3">
                <div style="height: 300px;">
                    <canvas id="staffChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ 4: Top dịch vụ tạo doanh thu cao nhất -->
    <div class="col-lg-6">
        <div class="card table-card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-stars me-2 text-primary"></i>Top dịch vụ tạo doanh thu cao nhất</h6>
            </div>
            <div class="card-body p-3">
                <div style="height: 300px;">
                    <canvas id="serviceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bảng chi tiết: Thống kê doanh thu theo Dịch vụ -->
<div class="card table-card mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Bảng tổng hợp chi tiết theo dịch vụ làm đẹp</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Tên dịch vụ</th>
                    <th>Danh mục</th>
                    <th class="text-center">Tổng lượt đặt</th>
                    <th class="text-center">Đã hoàn thành</th>
                    <th class="text-end">Doanh thu tích lũy</th>
                    <th class="text-end pe-3">Tỷ trọng</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($serviceStats as $st): 
                    $share = ($totalYearRevenue > 0) ? round(($st['total_revenue'] / $totalYearRevenue) * 100, 1) : 0;
                ?>
                <tr>
                    <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($st['name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($st['category_name'] ?? 'Khác') ?></span></td>
                    <td class="text-center"><?= $st['total_bookings'] ?></td>
                    <td class="text-center text-success fw-bold"><?= $st['completed_bookings'] ?></td>
                    <td class="text-end fw-bold text-danger"><?= formatCurrency($st['total_revenue']) ?></td>
                    <td class="text-end pe-3">
                        <span class="badge bg-secondary-subtle text-dark"><?= $share ?>%</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bảng chi tiết: Thống kê hiệu suất & Doanh thu theo Nhân viên -->
<div class="card table-card">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-award me-2 text-primary"></i>Bảng tổng hợp hiệu suất theo từng nhân viên</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Họ và tên nhân viên</th>
                    <th>Số điện thoại</th>
                    <th class="text-center">Số lịch được giao</th>
                    <th class="text-center">Đã hoàn thành</th>
                    <th class="text-center">Tỷ lệ hoàn thành</th>
                    <th class="text-end pe-3">Doanh thu mang lại</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staffStats as $st): 
                    $staffRate = ($st['total_assigned'] > 0) ? round(($st['total_completed'] / $st['total_assigned']) * 100, 1) : 0;
                ?>
                <tr>
                    <td class="ps-3 fw-bold text-dark">
                        <i class="bi bi-person-circle me-1 text-primary"></i><?= htmlspecialchars($st['full_name']) ?>
                    </td>
                    <td><?= htmlspecialchars($st['phone'] ?? 'N/A') ?></td>
                    <td class="text-center"><?= $st['total_assigned'] ?></td>
                    <td class="text-center text-success fw-bold"><?= $st['total_completed'] ?></td>
                    <td class="text-center">
                        <div class="progress" style="height: 6px; width: 80px; margin: 0 auto;">
                            <div class="progress-bar bg-success" style="width: <?= $staffRate ?>%"></div>
                        </div>
                        <small class="text-muted"><?= $staffRate ?>%</small>
                    </td>
                    <td class="text-end pe-3 fw-bold text-danger"><?= formatCurrency($st['total_revenue']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Monthly Revenue Chart
    const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctxMonthly, {
        type: 'bar',
        data: {
            labels: ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'],
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: <?= json_encode(array_values($monthlyRevenues)) ?>,
                backgroundColor: 'rgba(233, 30, 140, 0.7)',
                borderColor: '#e91e8c',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return (value / 1000000).toFixed(1) + ' tr';
                        }
                    }
                }
            }
        }
    });

    // 2. Appointment Status Chart
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Chờ xác nhận', 'Đã xác nhận', 'Hoàn thành', 'Đã hủy'],
            datasets: [{
                data: [
                    <?= $statusMap['pending'] ?>,
                    <?= $statusMap['confirmed'] ?>,
                    <?= $statusMap['completed'] ?>,
                    <?= $statusMap['cancelled'] ?>
                ],
                backgroundColor: ['#ffc107', '#0d6efd', '#198754', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // 3. Staff Comparison Chart
    const ctxStaff = document.getElementById('staffChart').getContext('2d');
    new Chart(ctxStaff, {
        type: 'bar',
        data: {
            labels: <?= json_encode($staffNames) ?>,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: <?= json_encode($staffRevenues) ?>,
                backgroundColor: 'rgba(111, 66, 193, 0.7)',
                borderColor: '#6f42c1',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        callback: function(value) {
                            return (value / 1000000).toFixed(1) + ' tr';
                        }
                    }
                }
            }
        }
    });

    // 4. Top Services Chart
    const ctxService = document.getElementById('serviceChart').getContext('2d');
    new Chart(ctxService, {
        type: 'pie',
        data: {
            labels: <?= json_encode($topServiceNames) ?>,
            datasets: [{
                data: <?= json_encode($topServiceRevenues) ?>,
                backgroundColor: ['#e91e8c', '#9c27b0', '#3f51b5', '#009688', '#ff9800', '#795548']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
