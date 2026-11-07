<?php
/**
 * Thêm / Chỉnh sửa dịch vụ - Admin
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();

$id = intval($_GET['id'] ?? 0);
$isEdit = ($id > 0);

// Lấy danh sách danh mục
$categories = $db->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

$service = [
    'id'          => 0,
    'category_id' => '',
    'name'        => '',
    'description' => '',
    'price'       => '',
    'duration'    => 30,
    'status'      => 'active'
];

if ($isEdit) {
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        setFlash('Không tìm thấy dịch vụ!', 'danger');
        redirect('admin/services.php');
    }
    $service = $existing;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        $errors[] = 'Mã bảo mật không hợp lệ!';
    } else {
        $category_id = intval($_POST['category_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = floatval($_POST['price'] ?? 0);
        $duration    = intval($_POST['duration'] ?? 0);
        $status      = $_POST['status'] ?? 'active';

        // Validation
        if (empty($name)) {
            $errors[] = 'Tên dịch vụ không được để trống.';
        }
        if ($category_id <= 0) {
            $errors[] = 'Vui lòng chọn danh mục dịch vụ.';
        }
        // Tiêu chí cụ thể đề tài: Giá bán phải lớn hơn 0
        if ($price <= 0) {
            $errors[] = 'Giá bán dịch vụ phải lớn hơn 0.';
        }
        if ($duration < 5) {
            $errors[] = 'Thời lượng thực hiện phải tối thiểu 5 phút.';
        }

        if (empty($errors)) {
            if ($isEdit) {
                $stmtUpdate = $db->prepare("
                    UPDATE services 
                    SET category_id = ?, name = ?, description = ?, price = ?, duration = ?, status = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$category_id, $name, $description, $price, $duration, $status, $id]);
                setFlash('Cập nhật thông tin dịch vụ thành công!', 'success');
            } else {
                $stmtInsert = $db->prepare("
                    INSERT INTO services (category_id, name, description, price, duration, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmtInsert->execute([$category_id, $name, $description, $price, $duration, $status]);
                setFlash('Thêm dịch vụ mới thành công!', 'success');
            }
            redirect('admin/services.php');
        } else {
            // Giữ lại dữ liệu vừa nhập
            $service['category_id'] = $category_id;
            $service['name']        = $name;
            $service['description'] = $description;
            $service['price']       = $price;
            $service['duration']    = $duration;
            $service['status']      = $status;
        }
    }
}

$pageTitle = $isEdit ? 'Chỉnh sửa dịch vụ #' . $id : 'Thêm dịch vụ mới';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>admin/services.php" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách dịch vụ
    </a>
    <h4 class="fw-bold mt-2 mb-1">
        <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-2 text-primary"></i>
        <?= $pageTitle ?>
    </h4>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Vui lòng kiểm tra lại thông tin:</div>
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card table-card mb-4">
            <div class="card-body p-4">
                <form action="" method="POST" id="serviceForm" novalidate>
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Tên dịch vụ làm đẹp <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($service['name']) ?>" placeholder="Ví dụ: Chăm sóc da cơ bản" required>
                        <div class="invalid-feedback">Vui lòng nhập tên dịch vụ.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Danh mục dịch vụ <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $service['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn danh mục.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $service['status'] === 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                                <option value="inactive" <?= $service['status'] === 'inactive' ? 'selected' : '' ?>>Tạm ngưng</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Đơn giá (VNĐ) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="price" id="servicePrice" class="form-control" 
                                       value="<?= htmlspecialchars($service['price']) ?>" min="1000" step="1000" placeholder="250000" required>
                                <span class="input-group-text">đ</span>
                            </div>
                            <div class="form-text">Tiêu chí bắt buộc: Giá bán phải lớn hơn 0.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Thời lượng thực hiện (Phút) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="duration" class="form-control" 
                                       value="<?= htmlspecialchars($service['duration']) ?>" min="5" step="5" placeholder="60" required>
                                <span class="input-group-text">phút</span>
                            </div>
                            <div class="form-text">Thời gian làm dịch vụ để tự động chia khung giờ.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Mô tả chi tiết liệu trình</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Mô tả các bước thực hiện, công dụng và sản phẩm sử dụng..."><?= htmlspecialchars($service['description']) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= BASE_URL ?>admin/services.php" class="btn btn-light px-4">Hủy bỏ</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i><?= $isEdit ? 'Lưu thay đổi' : 'Thêm dịch vụ' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-lightbulb me-2 text-warning"></i>Gợi ý quy chuẩn</h6>
            </div>
            <div class="card-body p-3 small text-secondary">
                <p><strong>1. Quy chuẩn giá:</strong> Giá dịch vụ phải lớn hơn 0 và tương ứng với thời lượng thực hiện của kỹ thuật viên.</p>
                <p><strong>2. Thời lượng:</strong> Được tính bằng phút, hệ thống đặt lịch tự động dùng chỉ số này để khóa khung giờ của chuyên viên, đảm bảo tiêu chí không nhận trùng 2 lịch cùng lúc.</p>
                <p class="mb-0"><strong>3. Tạm ngưng dịch vụ:</strong> Khi không còn cung cấp, hãy chuyển sang "Tạm ngưng" thay vì xóa để bảo toàn các lịch hẹn và hóa đơn cũ.</p>
            </div>
        </div>
    </div>
</div>

<script>
// Client-side validation
document.getElementById('serviceForm').addEventListener('submit', function(e) {
    const price = parseFloat(document.getElementById('servicePrice').value);
    if (isNaN(price) || price <= 0) {
        e.preventDefault();
        alert('Giá bán dịch vụ phải lớn hơn 0!');
        document.getElementById('servicePrice').focus();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
