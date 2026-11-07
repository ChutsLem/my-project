<?php
/**
 * Cài đặt lịch làm việc nhân viên - Admin
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$staffId = intval($_GET['staff_id'] ?? 0);

// Kiểm tra nhân viên tồn tại
$stmtStaff = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'staff'");
$stmtStaff->execute([$staffId]);
$staff = $stmtStaff->fetch();

if (!$staff) {
    setFlash('Không tìm thấy nhân viên!', 'danger');
    redirect('admin/staff.php');
}

// Xử lý lưu lịch làm việc
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        setFlash('Mã bảo mật không hợp lệ!', 'danger');
        redirect("admin/staff_schedule.php?staff_id={$staffId}");
    }

    $days = $_POST['days'] ?? []; // mảng 0..6

    // Bắt đầu transaction
    $db->beginTransaction();
    try {
        // Xóa lịch cũ
        $stmtDel = $db->prepare("DELETE FROM work_schedules WHERE staff_id = ?");
        $stmtDel->execute([$staffId]);

        // Thêm lịch mới cho 7 ngày
        $stmtInsert = $db->prepare("
            INSERT INTO work_schedules (staff_id, day_of_week, start_time, end_time, is_off) 
            VALUES (?, ?, ?, ?, ?)
        ");

        for ($d = 0; $d <= 6; $d++) {
            $isOff = isset($days[$d]['is_off']) ? 1 : 0;
            $start = $days[$d]['start_time'] ?? '08:00:00';
            $end   = $days[$d]['end_time'] ?? '17:00:00';

            // Format HH:MM:00
            if (strlen($start) == 5) $start .= ':00';
            if (strlen($end) == 5) $end .= ':00';

            $stmtInsert->execute([$staffId, $d, $start, $end, $isOff]);
        }

        $db->commit();
        setFlash('Cập nhật lịch làm việc tuần thành công!', 'success');
        redirect("admin/staff_schedule.php?staff_id={$staffId}");
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('Có lỗi xảy ra: ' . $e->getMessage(), 'danger');
    }
}

// Lấy lịch hiện tại của nhân viên
$stmtSched = $db->prepare("SELECT * FROM work_schedules WHERE staff_id = ? ORDER BY day_of_week ASC");
$stmtSched->execute([$staffId]);
$existingSchedules = [];
while ($row = $stmtSched->fetch()) {
    $existingSchedules[$row['day_of_week']] = $row;
}

// Mặc định nếu chưa có
$defaultSchedule = [
    0 => ['start_time' => '08:00:00', 'end_time' => '17:00:00', 'is_off' => 1], // Chủ nhật nghỉ
    1 => ['start_time' => '08:00:00', 'end_time' => '17:00:00', 'is_off' => 0],
    2 => ['start_time' => '08:00:00', 'end_time' => '17:00:00', 'is_off' => 0],
    3 => ['start_time' => '08:00:00', 'end_time' => '17:00:00', 'is_off' => 0],
    4 => ['start_time' => '08:00:00', 'end_time' => '17:00:00', 'is_off' => 0],
    5 => ['start_time' => '08:00:00', 'end_time' => '17:00:00', 'is_off' => 0],
    6 => ['start_time' => '08:00:00', 'end_time' => '12:00:00', 'is_off' => 0],
];

$pageTitle = 'Cài đặt ca trực - ' . $staff['full_name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>admin/staff.php" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách nhân viên
    </a>
    <h4 class="fw-bold mt-2 mb-1">
        <i class="bi bi-clock me-2 text-primary"></i>Cài đặt lịch ca trực tuần
    </h4>
    <p class="text-muted small mb-0">Thiết lập khung giờ tiếp nhận khách hàng cho nhân viên: <strong><?= htmlspecialchars($staff['full_name']) ?></strong></p>
</div>

<div class="row">
    <div class="col-lg-9">
        <div class="card table-card mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Thời gian biểu từ Thứ Hai đến Chủ Nhật</h6>
            </div>
            <div class="card-body p-4">
                <form action="" method="POST" id="scheduleForm">
                    <?= csrfField() ?>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th style="width: 160px;">Thứ trong tuần</th>
                                    <th>Giờ bắt đầu làm việc</th>
                                    <th>Giờ kết thúc ca</th>
                                    <th style="width: 140px;">Đánh dấu nghỉ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Hiển thị thứ 2 -> thứ 7 -> chủ nhật
                                $dayOrder = [1, 2, 3, 4, 5, 6, 0];
                                foreach ($dayOrder as $d): 
                                    $sched = $existingSchedules[$d] ?? $defaultSchedule[$d];
                                    $isOff = $sched['is_off'];
                                    $startTime = substr($sched['start_time'], 0, 5);
                                    $endTime   = substr($sched['end_time'], 0, 5);
                                ?>
                                <tr class="<?= $isOff ? 'table-light' : '' ?>" id="row_<?= $d ?>">
                                    <td class="fw-bold ps-3 text-dark">
                                        <?= getDayName($d) ?>
                                    </td>
                                    <td>
                                        <input type="time" name="days[<?= $d ?>][start_time]" id="start_<?= $d ?>" 
                                               class="form-control" value="<?= $startTime ?>" <?= $isOff ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="time" name="days[<?= $d ?>][end_time]" id="end_<?= $d ?>" 
                                               class="form-control" value="<?= $endTime ?>" <?= $isOff ? 'disabled' : '' ?>>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input off-toggle" type="checkbox" name="days[<?= $d ?>][is_off]" 
                                                   value="1" id="off_<?= $d ?>" data-day="<?= $d ?>" <?= $isOff ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="off_<?= $d ?>">Nghỉ ca</label>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= BASE_URL ?>admin/staff.php" class="btn btn-light px-4">Hủy bỏ</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i>Lưu ca trực
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card table-card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-shield-check me-2 text-success"></i>Quy tắc hệ thống</h6>
            </div>
            <div class="card-body p-3 small text-secondary">
                <p><strong>Khung giờ hợp lệ:</strong> Khách hàng chỉ có thể đặt lịch hẹn trong khoảng thời gian giữa <em>Giờ bắt đầu</em> và <em>Giờ kết thúc</em> của ngày làm việc.</p>
                <p><strong>Tránh trùng lịch:</strong> Khi chuyên viên đang thực hiện dịch vụ cho một khách, hệ thống sẽ tự động vô hiệu hóa slot giờ đó với các khách khác.</p>
                <p class="mb-0"><strong>Ngày nghỉ:</strong> Những ngày được đánh dấu "Nghỉ ca" sẽ không hiển thị bất kỳ khung giờ trống nào trên giao diện đặt lịch.</p>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.off-toggle').forEach(function(toggle) {
    toggle.addEventListener('change', function() {
        const d = this.getAttribute('data-day');
        const startInput = document.getElementById('start_' + d);
        const endInput = document.getElementById('end_' + d);
        const row = document.getElementById('row_' + d);

        if (this.checked) {
            startInput.disabled = true;
            endInput.disabled = true;
            row.classList.add('table-light');
        } else {
            startInput.disabled = false;
            endInput.disabled = false;
            row.classList.remove('table-light');
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
