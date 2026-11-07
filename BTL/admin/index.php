<?php
/**
 * Admin Dashboard - Beauty Salon Booking Management System
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$pageTitle = 'Dashboard';

// ============================================================
// 1. SUMMARY CARDS DATA
// ============================================================

// Tổng lịch hẹn hôm nay
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
$stmt->execute();
$todayAppointments = $stmt->fetchColumn();

// Lịch hẹn chờ xác nhận
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
$stmt->execute();
$pendingAppointments = $stmt->fetchColumn();

// Doanh thu tháng này
$stmt = $db->prepare("
    SELECT COALESCE(SUM(i.final_amount), 0) 
    FROM invoices i 
    WHERE i.payment_status = 'paid' 
    AND MONTH(i.paid_at) = MONTH(CURDATE()) 
    AND YEAR(i.paid_at) = YEAR(CURDATE())
");
$stmt->execute();
$monthRevenue = $stmt->fetchColumn();

// Tổng khách hàng
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'customer' AND status = 'active'");
$stmt->execute();
$totalCustomers = $stmt->fetchColumn();

// ============================================================
// 2. CHART DATA - Doanh thu 6 tháng gần nhất
// ============================================================
$revenueData = [];
$revenueLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('m/Y', strtotime("-$i months"));
    $revenueLabels[] = $monthLabel;
    
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(final_amount), 0) 
        FROM invoices 
        WHERE payment_status = 'paid' 
        AND DATE_FORMAT(paid_at, '%Y-%m') = ?
    ");
    $stmt->execute([$month]);
    $revenueData[] = (float) $stmt->fetchColumn();
}

// ============================================================
// 3. CHART DATA - Tỷ lệ dịch vụ được đặt
// ============================================================
$stmt = $db->prepare("
    SELECT s.name, COUNT(a.id) as total 
    FROM appointments a 
    JOIN services s ON a.service_id = s.id 
    GROUP BY a.service_id, s.name 
    ORDER BY total DESC 
    LIMIT 6
");
$stmt->execute();
$serviceStats = $stmt->fetchAll();
$serviceLabels = array_column($serviceStats, 'name');
$serviceCounts = array_column($serviceStats, 'total');

// ============================================================
// 4. RECENT APPOINTMENTS (5 latest)
// ============================================================
$stmt = $db->prepare("
    SELECT a.*, 
           c.full_name AS customer_name, 
           st.full_name AS staff_name,
           s.name AS service_name,
           s.price AS service_price
    FROM appointments a
    JOIN users c ON a.customer_id = c.id
    JOIN users st ON a.staff_id = st.id
    JOIN services s ON a.service_id = s.id
    ORDER BY a.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentAppointments = $stmt->fetchAll();

// ============================================================
// 5. TOP STAFF BY REVENUE THIS MONTH
// ============================================================
$stmt = $db->prepare("
    SELECT u.full_name, u.avatar, 
           COUNT(a.id) AS total_appointments,
           COALESCE(SUM(i.final_amount), 0) AS total_revenue
    FROM users u
    JOIN appointments a ON u.id = a.staff_id AND a.status = 'completed'
    LEFT JOIN invoices i ON a.id = i.appointment_id AND i.payment_status = 'paid'
    WHERE u.role = 'staff'
    GROUP BY u.id, u.full_name, u.avatar
    ORDER BY total_revenue DESC
    LIMIT 5
");
$stmt->execute();
$topStaff = $stmt->fetchAll();

// Include header
include __DIR__ . '/../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Lịch hẹn hôm nay -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Lịch hẹn hôm nay</p>
                        <h3 class="fw-bold mb-0"><?= $todayAppointments ?></h3>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-calendar-day"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d/m/Y') ?></small>
                </div>
            </div>
        </div>
    </div>
    <!-- Chờ xác nhận -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Chờ xác nhận</p>
                        <h3 class="fw-bold mb-0"><?= $pendingAppointments ?></h3>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="<?= BASE_URL ?>/admin/appointments.php?status=pending" class="text-decoration-none small">
                        Xem chi tiết <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Doanh thu tháng -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Doanh thu tháng này</p>
                        <h3 class="fw-bold mb-0"><?= formatCurrency($monthRevenue) ?></h3>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i>Tháng <?= date('m/Y') ?></small>
                </div>
            </div>
        </div>
    </div>
    <!-- Tổng khách hàng -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1 small">Tổng khách hàng</p>
                        <h3 class="fw-bold mb-0"><?= $totalCustomers ?></h3>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="<?= BASE_URL ?>/admin/customers.php" class="text-decoration-none small">
                        Xem tất cả <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-3 mb-4">
    <!-- Biểu đồ doanh thu 6 tháng -->
    <div class="col-lg-8">
        <div class="card table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart me-2"></i>Doanh thu 6 tháng gần nhất</span>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="280"></canvas>
            </div>
        </div>
    </div>
    <!-- Biểu đồ tỷ lệ dịch vụ -->
    <div class="col-lg-4">
        <div class="card table-card h-100">
            <div class="card-header">
                <i class="bi bi-pie-chart me-2"></i>Tỷ lệ dịch vụ được đặt
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="serviceChart" height="280"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Appointments & Top Staff -->
<div class="row g-3">
    <!-- Lịch hẹn gần đây -->
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-check me-2"></i>Lịch hẹn gần đây</span>
                <a href="<?= BASE_URL ?>/admin/appointments.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Khách hàng</th>
                                <th>Dịch vụ</th>
                                <th>Nhân viên</th>
                                <th>Ngày hẹn</th>
                                <th>Giờ</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentAppointments)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Chưa có lịch hẹn nào</td></tr>
                            <?php else: ?>
                            <?php foreach ($recentAppointments as $apt): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:0.75rem;">
                                            <?= mb_substr($apt['customer_name'], 0, 1) ?>
                                        </div>
                                        <span class="fw-medium"><?= htmlspecialchars($apt['customer_name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($apt['service_name']) ?></td>
                                <td><?= htmlspecialchars($apt['staff_name']) ?></td>
                                <td><?= date('d/m/Y', strtotime($apt['appointment_date'])) ?></td>
                                <td><?= date('H:i', strtotime($apt['start_time'])) ?></td>
                                <td>
                                    <?php
                                    $statusMap = [
                                        'pending'   => ['Chờ xác nhận', 'bg-warning text-dark'],
                                        'confirmed' => ['Đã xác nhận', 'bg-info'],
                                        'completed' => ['Hoàn thành', 'bg-success'],
                                        'cancelled' => ['Đã hủy', 'bg-danger'],
                                    ];
                                    $s = $statusMap[$apt['status']] ?? ['Không rõ', 'bg-secondary'];
                                    ?>
                                    <span class="badge <?= $s[1] ?> badge-status"><?= $s[0] ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top nhân viên -->
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header">
                <i class="bi bi-trophy me-2"></i>Top nhân viên theo doanh thu
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($topStaff)): ?>
                    <div class="list-group-item text-center text-muted py-4">Chưa có dữ liệu</div>
                    <?php else: ?>
                    <?php foreach ($topStaff as $idx => $staff): ?>
                    <div class="list-group-item d-flex align-items-center gap-3 py-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold 
                            <?= $idx === 0 ? 'bg-warning text-dark' : ($idx === 1 ? 'bg-secondary text-white' : 'bg-light text-dark') ?>" 
                            style="width:36px;height:36px;font-size:0.85rem;">
                            <?= $idx + 1 ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-medium"><?= htmlspecialchars($staff['full_name']) ?></div>
                            <small class="text-muted"><?= $staff['total_appointments'] ?> lịch hẹn</small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success"><?= formatCurrency($staff['total_revenue']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // === Revenue Chart (Bar) ===
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($revenueLabels) ?>,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: <?= json_encode($revenueData) ?>,
                backgroundColor: 'rgba(233, 30, 140, 0.15)',
                borderColor: 'rgba(233, 30, 140, 1)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' đ';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                            return value;
                        }
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // === Service Distribution Chart (Doughnut) ===
    const serviceCtx = document.getElementById('serviceChart').getContext('2d');
    const serviceColors = [
        '#e91e8c', '#6f42c1', '#0dcaf0', '#198754', '#ffc107', '#fd7e14'
    ];
    new Chart(serviceCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($serviceLabels) ?>,
            datasets: [{
                data: <?= json_encode($serviceCounts) ?>,
                backgroundColor: serviceColors.slice(0, <?= count($serviceLabels) ?>),
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 12,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 11 }
                    }
                }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
