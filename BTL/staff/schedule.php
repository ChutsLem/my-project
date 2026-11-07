<?php
/**
 * Lịch làm việc cá nhân - Nhân viên
 */
require_once __DIR__ . '/../config/config.php';
requireStaff();

$db = getDB();
$staffId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM work_schedules WHERE staff_id = ? ORDER BY day_of_week ASC");
$stmt->execute([$staffId]);
$schedules = $stmt->fetchAll();

// Lấy danh sách dịch vụ mà nhân viên này phụ trách
$stmtServices = $db->prepare("
    SELECT s.*, c.name AS category_name
    FROM staff_services ss
    JOIN services s ON ss.service_id = s.id
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE ss.staff_id = ?
");
$stmtServices->execute([$staffId]);
$myServices = $stmtServices->fetchAll();

$pageTitle = 'Lịch làm việc cá nhân';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1"><i class="bi bi-clock me-2 text-primary"></i>Lịch làm việc cá nhân</h4>
    <p class="text-muted small mb-0">Thời gian biểu làm việc cố định và các dịch vụ bạn được phân công thực hiện</p>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card table-card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-calendar3 me-2 text-primary"></i>Khung giờ làm việc theo tuần</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Thứ trong tuần</th>
                                <th>Giờ bắt đầu</th>
                                <th>Giờ kết thúc</th>
                                <th>Trạng thái ca</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $item): ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark"><?= getDayName($item['day_of_week']) ?></td>
                                <td>
                                    <?= $item['is_off'] ? '-' : formatTime($item['start_time']) ?>
                                </td>
                                <td>
                                    <?= $item['is_off'] ? '-' : formatTime($item['end_time']) ?>
                                </td>
                                <td>
                                    <?php if ($item['is_off']): ?>
                                        <span class="badge bg-secondary">Nghỉ định kỳ</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Làm việc</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light p-3 small text-muted">
                <i class="bi bi-info-circle me-1"></i>Để thay đổi ca làm việc hoặc xin nghỉ phép, vui lòng liên hệ trực tiếp với người quản trị (Admin).
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card table-card h-100">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-stars me-2 text-primary"></i>Dịch vụ bạn phụ trách (<?= count($myServices) ?>)</h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($myServices)): ?>
                    <p class="text-muted small mb-0">Chưa được gán dịch vụ phụ trách nào.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($myServices as $s): ?>
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold small text-dark"><?= htmlspecialchars($s['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($s['category_name'] ?? 'Dịch vụ') ?> | <?= $s['duration'] ?> phút</small>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <?= formatCurrency($s['price']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
