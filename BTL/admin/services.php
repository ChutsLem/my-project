<?php
/**
 * Quản lý danh sách dịch vụ - Admin
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();

// Xử lý POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        setFlash('Mã bảo mật không hợp lệ!', 'danger');
        redirect('admin/services.php');
    }

    $action = $_POST['action'] ?? '';

    // Xóa dịch vụ
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        // Kiểm tra xem dịch vụ có lịch hẹn nào không
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM appointments WHERE service_id = ?");
        $stmtCheck->execute([$id]);
        $apptCount = $stmtCheck->fetchColumn();

        if ($apptCount > 0) {
            // Không xóa để bảo toàn lịch sử dữ liệu, chuyển sang inactive
            $stmtDeact = $db->prepare("UPDATE services SET status = 'inactive' WHERE id = ?");
            $stmtDeact->execute([$id]);
            setFlash("Dịch vụ này đã có {$apptCount} lịch hẹn, hệ thống đã chuyển trạng thái sang 'Tạm ngưng' để bảo toàn lịch sử!", 'warning');
        } else {
            $stmtDel = $db->prepare("DELETE FROM services WHERE id = ?");
            $stmtDel->execute([$id]);
            setFlash('Xóa dịch vụ thành công!', 'success');
        }
        redirect('admin/services.php');
    }

    // Đổi trạng thái active / inactive
    if ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT status FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $svc = $stmt->fetch();
        if ($svc) {
            $newStatus = ($svc['status'] === 'active') ? 'inactive' : 'active';
            $stmtUpdate = $db->prepare("UPDATE services SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $id]);
            setFlash('Đã cập nhật trạng thái dịch vụ!', 'success');
        }
        redirect('admin/services.php');
    }
}

// Lấy danh mục để lọc
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Bộ lọc & Tìm kiếm
$categoryId = intval($_GET['category_id'] ?? 0);
$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT s.*, c.name AS category_name,
           (SELECT COUNT(*) FROM staff_services ss WHERE ss.service_id = s.id) AS staff_count,
           (SELECT COUNT(*) FROM appointments a WHERE a.service_id = s.id) AS total_bookings
    FROM services s
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE 1=1
";
$params = [];

if ($categoryId > 0) {
    $sql .= " AND s.category_id = ?";
    $params[] = $categoryId;
}

if ($statusFilter !== 'all' && in_array($statusFilter, ['active', 'inactive'])) {
    $sql .= " AND s.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $sql .= " AND (s.name LIKE ? OR s.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY s.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

$pageTitle = 'Quản lý dịch vụ';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-stars me-2 text-primary"></i>Danh sách dịch vụ</h4>
        <p class="text-muted small mb-0">Quản lý giá cả, thời lượng và thông tin chi tiết các liệu trình làm đẹp</p>
    </div>
    <a href="<?= BASE_URL ?>admin/service_form.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Thêm dịch vụ mới
    </a>
</div>

<!-- Bộ lọc & Tìm kiếm -->
<div class="card table-card mb-4">
    <div class="card-body p-3">
        <form action="" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Tìm theo tên dịch vụ..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Mọi trạng thái</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Tạm ngưng</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-secondary w-100">Lọc</button>
                <a href="<?= BASE_URL ?>admin/services.php" class="btn btn-outline-secondary">Xóa lọc</a>
            </div>
        </form>
    </div>
</div>

<!-- Bảng dịch vụ -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 60px;">ID</th>
                    <th style="width: 70px;">Hình ảnh</th>
                    <th>Tên dịch vụ</th>
                    <th>Danh mục</th>
                    <th>Giá dịch vụ</th>
                    <th>Thời lượng</th>
                    <th>Chuyên viên</th>
                    <th>Lượt đặt</th>
                    <th>Trạng thái</th>
                    <th class="text-end pe-3">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">Không tìm thấy dịch vụ nào phù hợp.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($services as $svc): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-muted">#<?= $svc['id'] ?></td>
                        <td>
                            <img src="<?= htmlspecialchars($svc['image'] ?: 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=150&q=80') ?>" 
                                 class="rounded shadow-sm" 
                                 style="width: 52px; height: 52px; object-fit: cover; border: 1px solid rgba(197, 168, 128, 0.3);" 
                                 alt="<?= htmlspecialchars($svc['name']) ?>"
                                 onerror="this.src='https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=150&q=80'">
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($svc['name']) ?></div>
                            <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;">
                                <?= htmlspecialchars($svc['description'] ?: 'Chưa có mô tả') ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= htmlspecialchars($svc['category_name'] ?? 'Chưa phân loại') ?>
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold text-danger"><?= formatCurrency($svc['price']) ?></span>
                        </td>
                        <td>
                            <span class="small text-secondary"><i class="bi bi-clock me-1"></i><?= $svc['duration'] ?> phút</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border">
                                <?= $svc['staff_count'] ?> người
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border">
                                <?= $svc['total_bookings'] ?> lượt
                            </span>
                        </td>
                        <td>
                            <form action="" method="POST" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $svc['id'] ?>">
                                <?php if ($svc['status'] === 'active'): ?>
                                    <button type="submit" class="btn btn-sm btn-success py-0 px-2 rounded-pill" title="Click để tạm ẩn">
                                        Hoạt động
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-sm btn-secondary py-0 px-2 rounded-pill" title="Click để kích hoạt">
                                        Tạm ngưng
                                    </button>
                                <?php endif; ?>
                            </form>
                        </td>
                        <td class="text-end pe-3">
                            <a href="<?= BASE_URL ?>admin/service_form.php?id=<?= $svc['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa dịch vụ này?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $svc['id'] ?>">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
