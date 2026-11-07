<?php
/**
 * Quản lý danh mục dịch vụ - Admin
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();

// Xử lý POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        setFlash('Mã bảo mật không hợp lệ!', 'danger');
        redirect('admin/categories.php');
    }

    $action = $_POST['action'] ?? '';

    // Thêm danh mục mới
    if ($action === 'add') {
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status      = $_POST['status'] ?? 'active';

        if (empty($name)) {
            setFlash('Tên danh mục không được để trống!', 'danger');
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, description, status, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$name, $description, $status]);
            setFlash('Thêm danh mục thành công!', 'success');
        }
        redirect('admin/categories.php');
    }

    // Sửa danh mục
    if ($action === 'edit') {
        $id          = intval($_POST['id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status      = $_POST['status'] ?? 'active';

        if (empty($name) || $id <= 0) {
            setFlash('Dữ liệu cập nhật không hợp lệ!', 'danger');
        } else {
            $stmt = $db->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $description, $status, $id]);
            setFlash('Cập nhật danh mục thành công!', 'success');
        }
        redirect('admin/categories.php');
    }

    // Xóa danh mục
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        // Kiểm tra xem danh mục có dịch vụ nào không
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM services WHERE category_id = ?");
        $stmtCheck->execute([$id]);
        $serviceCount = $stmtCheck->fetchColumn();

        if ($serviceCount > 0) {
            setFlash("Không thể xóa danh mục này vì đang có {$serviceCount} dịch vụ thuộc danh mục! Hãy chuyển hoặc xóa dịch vụ trước.", 'warning');
        } else {
            $stmtDel = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmtDel->execute([$id]);
            setFlash('Xóa danh mục thành công!', 'success');
        }
        redirect('admin/categories.php');
    }

    // Đổi trạng thái nhanh
    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT status FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $cat = $stmt->fetch();
        if ($cat) {
            $newStatus = ($cat['status'] === 'active') ? 'inactive' : 'active';
            $stmtUpdate = $db->prepare("UPDATE categories SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $id]);
            setFlash('Đã cập nhật trạng thái danh mục!', 'success');
        }
        redirect('admin/categories.php');
    }
}

// Tìm kiếm
$search = trim($_GET['search'] ?? '');
$sql = "
    SELECT c.*, COUNT(s.id) AS service_count 
    FROM categories c 
    LEFT JOIN services s ON c.id = s.category_id 
";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE c.name LIKE ? OR c.description LIKE ? ";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY c.id ORDER BY c.id ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

$pageTitle = 'Quản lý danh mục dịch vụ';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-tags me-2 text-primary"></i>Danh mục dịch vụ</h4>
        <p class="text-muted small mb-0">Quản lý và phân loại các dịch vụ làm đẹp tại salon</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-lg me-1"></i>Thêm danh mục
    </button>
</div>

<!-- Bộ lọc & Tìm kiếm -->
<div class="card table-card mb-4">
    <div class="card-body p-3">
        <form action="" method="GET" class="row g-2 align-items-center">
            <div class="col-md-9">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Tìm theo tên hoặc mô tả danh mục..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Tìm kiếm</button>
                <a href="<?= BASE_URL ?>admin/categories.php" class="btn btn-outline-secondary">Đặt lại</a>
            </div>
        </form>
    </div>
</div>

<!-- Bảng danh mục -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 70px;">ID</th>
                    <th>Tên danh mục</th>
                    <th>Mô tả</th>
                    <th class="text-center">Số dịch vụ</th>
                    <th>Trạng thái</th>
                    <th class="text-end pe-3">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Không tìm thấy danh mục nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-muted">#<?= $cat['id'] ?></td>
                        <td>
                            <strong class="text-dark"><?= htmlspecialchars($cat['name']) ?></strong>
                        </td>
                        <td>
                            <span class="text-secondary small text-truncate d-inline-block" style="max-width: 300px;">
                                <?= htmlspecialchars($cat['description'] ?: 'Chưa có mô tả') ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                                <?= $cat['service_count'] ?> dịch vụ
                            </span>
                        </td>
                        <td>
                            <form action="" method="POST" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                <?php if ($cat['status'] === 'active'): ?>
                                    <button type="submit" class="btn btn-sm btn-success py-0 px-2 rounded-pill" title="Click để tạm ẩn">
                                        Hoạt động
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-sm btn-secondary py-0 px-2 rounded-pill" title="Click để kích hoạt">
                                        Tạm ẩn
                                    </button>
                                <?php endif; ?>
                            </form>
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                    onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>', '<?= htmlspecialchars(addslashes($cat['description'])) ?>', '<?= $cat['status'] ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm danh mục -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Thêm danh mục mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">Tên danh mục <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Ví dụ: Chăm sóc da" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Mô tả</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Mô tả các dịch vụ thuộc danh mục..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="active">Hoạt động (Hiển thị)</option>
                        <option value="inactive">Tạm ẩn</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu danh mục</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa danh mục -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Cập nhật danh mục</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-medium">Tên danh mục <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Mô tả</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Trạng thái</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="active">Hoạt động (Hiển thị)</option>
                        <option value="inactive">Tạm ẩn</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(id, name, description, status) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_status').value = status;
    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
