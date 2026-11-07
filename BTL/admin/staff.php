<?php
/**
 * Quản lý nhân viên & Phân công dịch vụ - Admin
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();

// Xử lý POST gán dịch vụ cho nhân viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_services') {
    if (!validateCSRF()) {
        setFlash('Mã bảo mật không hợp lệ!', 'danger');
        redirect('admin/staff.php');
    }

    $staffId = intval($_POST['staff_id'] ?? 0);
    $selectedServices = $_POST['services'] ?? [];

    if ($staffId > 0) {
        // Xóa các dịch vụ cũ
        $stmtDel = $db->prepare("DELETE FROM staff_services WHERE staff_id = ?");
        $stmtDel->execute([$staffId]);

        // Thêm các dịch vụ mới được chọn
        if (!empty($selectedServices)) {
            $stmtInsert = $db->prepare("INSERT INTO staff_services (staff_id, service_id) VALUES (?, ?)");
            foreach ($selectedServices as $svcId) {
                $stmtInsert->execute([$staffId, intval($svcId)]);
            }
        }

        setFlash('Cập nhật danh sách dịch vụ phụ trách cho nhân viên thành công!', 'success');
    }
    redirect('admin/staff.php');
}

// Lấy danh sách nhân viên (role = 'staff')
$stmtStaff = $db->query("
    SELECT u.*,
           (SELECT COUNT(*) FROM staff_services ss WHERE ss.staff_id = u.id) AS assigned_services_count,
           (SELECT COUNT(*) FROM appointments a WHERE a.staff_id = u.id AND a.status = 'completed') AS completed_count,
           (SELECT COUNT(*) FROM appointments a WHERE a.staff_id = u.id AND a.appointment_date = CURDATE() AND a.status != 'cancelled') AS today_appts
    FROM users u
    WHERE u.role = 'staff'
    ORDER BY u.id ASC
");
$staffList = $stmtStaff->fetchAll();

// Lấy toàn bộ dịch vụ để hiển thị trong modal phân công
$allServices = $db->query("
    SELECT s.id, s.name, s.price, c.name AS category_name
    FROM services s
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE s.status = 'active'
    ORDER BY c.name ASC, s.name ASC
")->fetchAll();

// Lấy mapping nhân viên - dịch vụ
$staffServicesMap = [];
$stmtSS = $db->query("SELECT staff_id, service_id FROM staff_services");
while ($row = $stmtSS->fetch()) {
    $staffServicesMap[$row['staff_id']][] = $row['service_id'];
}

$pageTitle = 'Quản lý nhân viên & Phân công';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-badge me-2 text-primary"></i>Quản lý nhân viên</h4>
        <p class="text-muted small mb-0">Phân công dịch vụ chuyên môn và quản lý lịch ca trực cho nhân viên salon</p>
    </div>
    <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-primary">
        <i class="bi bi-person-plus me-1"></i>Thêm tài khoản nhân viên
    </a>
</div>

<!-- Danh sách thẻ nhân viên -->
<div class="row g-4 mb-4">
    <?php foreach ($staffList as $staff): 
        $assignedIds = $staffServicesMap[$staff['id']] ?? [];
    ?>
    <div class="col-lg-4 col-md-6">
        <div class="card table-card h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <?php if (!empty($staff['avatar'])): ?>
                        <img src="<?= htmlspecialchars($staff['avatar']) ?>" class="rounded-circle me-3 shadow-sm" style="width: 58px; height: 58px; object-fit: cover; border: 2px solid var(--gold);" alt="<?= htmlspecialchars($staff['full_name']) ?>">
                    <?php else: ?>
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 58px; height: 58px; font-size: 1.5rem; background: #faf5ee; color: var(--gold-dark); border: 2px solid var(--gold);">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($staff['full_name']) ?></h5>
                        <small class="text-muted">@<?= htmlspecialchars($staff['username']) ?></small>
                        <div>
                            <span class="badge <?= $staff['status'] === 'active' ? 'bg-success' : 'bg-danger' ?> small">
                                <?= $staff['status'] === 'active' ? 'Đang làm việc' : 'Đã khóa' ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-light p-3 rounded-3 mb-3 small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted"><i class="bi bi-telephone me-1"></i>Số điện thoại:</span>
                        <strong class="text-dark"><?= htmlspecialchars($staff['phone'] ?? 'Chưa cập nhật') ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted"><i class="bi bi-envelope me-1"></i>Email:</span>
                        <span class="text-dark"><?= htmlspecialchars($staff['email'] ?? 'N/A') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted"><i class="bi bi-stars me-1"></i>Dịch vụ phụ trách:</span>
                        <span class="badge bg-primary"><?= $staff['assigned_services_count'] ?> dịch vụ</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-check2-all me-1"></i>Đã phục vụ:</span>
                        <strong class="text-success"><?= $staff['completed_count'] ?> lượt</strong>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1" 
                            onclick='openAssignModal(<?= $staff['id'] ?>, "<?= htmlspecialchars(addslashes($staff['full_name'])) ?>", <?= json_encode($assignedIds) ?>)'>
                        <i class="bi bi-stars me-1"></i>Gán dịch vụ
                    </button>
                    <a href="<?= BASE_URL ?>admin/staff_schedule.php?staff_id=<?= $staff['id'] ?>" class="btn btn-outline-dark btn-sm flex-grow-1">
                        <i class="bi bi-clock me-1"></i>Cài ca trực
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Phân công dịch vụ -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="" method="POST" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="assign_services">
            <input type="hidden" name="staff_id" id="assign_staff_id">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-stars me-2 text-primary"></i>Phân công dịch vụ: <span id="assign_staff_name" class="text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Tích chọn các dịch vụ mà nhân viên này có đủ chuyên môn và tay nghề thực hiện:</p>
                <div class="row g-2">
                    <?php 
                    $currCategory = '';
                    foreach ($allServices as $svc): 
                        if ($currCategory !== $svc['category_name']):
                            $currCategory = $svc['category_name'];
                    ?>
                        <div class="col-12 mt-3 mb-1">
                            <span class="badge bg-secondary-subtle text-secondary fw-bold text-uppercase"><?= htmlspecialchars($currCategory ?? 'Khác') ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <div class="form-check p-2 border rounded hover-bg-light">
                            <input class="form-check-input ms-0 me-2 service-checkbox" type="checkbox" name="services[]" value="<?= $svc['id'] ?>" id="svc_<?= $svc['id'] ?>">
                            <label class="form-check-label w-100" for="svc_<?= $svc['id'] ?>">
                                <div class="fw-medium text-dark"><?= htmlspecialchars($svc['name']) ?></div>
                                <small class="text-muted"><?= formatCurrency($svc['price']) ?></small>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu phân công</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAssignModal(staffId, staffName, assignedServiceIds) {
    document.getElementById('assign_staff_id').value = staffId;
    document.getElementById('assign_staff_name').innerText = staffName;

    // Reset all checkboxes
    document.querySelectorAll('.service-checkbox').forEach(cb => {
        cb.checked = assignedServiceIds.includes(parseInt(cb.value));
    });

    const modal = new bootstrap.Modal(document.getElementById('assignModal'));
    modal.show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
