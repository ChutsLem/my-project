<?php
/**
 * Quản lý người dùng - Admin
 * Hệ thống quản lý dịch vụ đặt lịch làm đẹp
 */
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$currentAdminId = currentUserId() ?? ($_SESSION['user_id'] ?? 0);

// === Xử lý POST actions ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        setFlash('Token bảo mật không hợp lệ!', 'danger');
        redirect('admin/users.php');
    }

    $action = $_POST['action'] ?? '';

    // Thêm người dùng mới
    if ($action === 'add') {
        $username  = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $password  = $_POST['password'] ?? '';
        $role      = $_POST['role'] ?? 'customer';
        $status    = $_POST['status'] ?? 'active';

        $errors = [];
        if (empty($username) || strlen($username) < 3) $errors[] = 'Tên đăng nhập phải ít nhất 3 ký tự.';
        if (empty($full_name)) $errors[] = 'Họ tên không được để trống.';
        if (empty($password) || strlen($password) < 6) $errors[] = 'Mật khẩu phải ít nhất 6 ký tự.';
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
        if (!in_array($role, ['admin', 'staff', 'customer'])) $errors[] = 'Vai trò không hợp lệ.';

        // Kiểm tra username duy nhất
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) $errors[] = 'Tên đăng nhập đã tồn tại.';

        if (!empty($errors)) {
            setFlash(implode('<br>', $errors), 'danger');
        } else {
            $hashedPw = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, phone, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$username, $hashedPw, $full_name, $email, $phone, $role, $status]);
            setFlash('Thêm người dùng mới thành công!', 'success');
        }
        redirect('admin/users.php');
    }

    // Chỉnh sửa người dùng
    if ($action === 'edit') {
        $id        = (int)($_POST['user_id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $role      = $_POST['role'] ?? 'customer';
        $status    = $_POST['status'] ?? 'active';
        $password  = $_POST['password'] ?? '';

        $errors = [];
        if (empty($full_name)) $errors[] = 'Họ tên không được để trống.';
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
        if (!in_array($role, ['admin', 'staff', 'customer'])) $errors[] = 'Vai trò không hợp lệ.';

        if (!empty($errors)) {
            setFlash(implode('<br>', $errors), 'danger');
        } else {
            if (!empty($password) && strlen($password) >= 6) {
                $hashedPw = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, status=?, password=? WHERE id=?");
                $stmt->execute([$full_name, $email, $phone, $role, $status, $hashedPw, $id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, status=? WHERE id=?");
                $stmt->execute([$full_name, $email, $phone, $role, $status, $id]);
            }
            setFlash('Cập nhật thông tin người dùng thành công!', 'success');
        }
        redirect('admin/users.php');
    }

    // Khóa / Mở khóa tài khoản (toggle_status)
    if ($action === 'toggle_status') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id === (int)$currentAdminId) {
            setFlash('Không thể khóa tài khoản của chính mình!', 'danger');
        } else {
            $stmt = $db->prepare("SELECT status FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $u = $stmt->fetch();
            if ($u) {
                $newStatus = ($u['status'] === 'active') ? 'locked' : 'active';
                $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                $msg = ($newStatus === 'active') ? 'Đã mở khóa tài khoản thành công.' : 'Đã khóa tài khoản người dùng.';
                setFlash($msg, 'success');
            }
        }
        redirect('admin/users.php');
    }

    // Xóa người dùng
    if ($action === 'delete') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id === (int)$currentAdminId) {
            setFlash('Không thể xóa tài khoản của chính mình!', 'danger');
        } else {
            // Kiểm tra ràng buộc lịch hẹn
            $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM appointments WHERE customer_id = ? OR staff_id = ?");
            $stmt->execute([$id, $id]);
            $cnt = $stmt->fetch()['cnt'];
            if ($cnt > 0) {
                setFlash('Không thể xóa người dùng đang có lịch hẹn trong hệ thống! Hãy chọn khóa tài khoản thay vì xóa.', 'warning');
            } else {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                setFlash('Xóa tài khoản người dùng thành công!', 'success');
            }
        }
        redirect('admin/users.php');
    }
}

// === Lọc và Tìm kiếm ===
$search = trim($_GET['search'] ?? '');
$filterRole = $_GET['role'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}
if (!empty($filterRole) && in_array($filterRole, ['admin', 'staff', 'customer'])) {
    $sql .= " AND role = ?";
    $params[] = $filterRole;
}
if (!empty($filterStatus) && in_array($filterStatus, ['active', 'locked'])) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
}
$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Quản lý người dùng';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-people me-2 text-primary"></i>Quản lý người dùng</h4>
        <p class="text-muted small mb-0">Quản lý danh sách tài khoản, phân quyền vai trò và kiểm soát trạng thái đăng nhập</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus me-1"></i>Thêm người dùng
    </button>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card table-card mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Tìm theo tên đăng nhập, họ tên, email, SĐT..." value="<?= sanitize($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">-- Tất cả vai trò --</option>
                    <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Quản trị viên (Admin)</option>
                    <option value="staff" <?= $filterRole === 'staff' ? 'selected' : '' ?>>Nhân viên (Staff)</option>
                    <option value="customer" <?= $filterRole === 'customer' ? 'selected' : '' ?>>Khách hàng (Customer)</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">-- Trạng thái --</option>
                    <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="locked" <?= $filterStatus === 'locked' ? 'selected' : '' ?>>Đã khóa</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-secondary w-100" type="submit">Lọc</button>
                <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline-secondary">Xóa</a>
            </div>
        </form>
    </div>
</div>

<!-- Bảng danh sách người dùng -->
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 60px;">STT</th>
                    <th>Tên đăng nhập</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th class="text-center pe-3" style="width: 140px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Không tìm thấy người dùng nào phù hợp.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td class="ps-3 fw-bold text-muted"><?= $i + 1 ?></td>
                        <td>
                            <strong class="text-dark">@<?= sanitize($u['username']) ?></strong>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?= htmlspecialchars($u['avatar'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80') ?>" 
                                     class="rounded-circle me-2 shadow-sm" 
                                     style="width: 32px; height: 32px; object-fit: cover; border: 1px solid rgba(197, 168, 128, 0.4);" 
                                     alt="Avatar"
                                     onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80'">
                                <div class="fw-medium text-dark"><?= sanitize($u['full_name']) ?></div>
                            </div>
                        </td>
                        <td>
                            <span class="text-muted small"><?= sanitize($u['email'] ?: 'Chưa cập nhật') ?></span>
                        </td>
                        <td>
                            <span class="text-dark small"><?= sanitize($u['phone'] ?: 'N/A') ?></span>
                        </td>
                        <td>
                            <?php
                            $roleBadge = ['admin' => 'danger', 'staff' => 'warning text-dark', 'customer' => 'info text-dark'];
                            $roleLabel = ['admin' => 'Quản trị viên', 'staff' => 'Nhân viên', 'customer' => 'Khách hàng'];
                            ?>
                            <span class="badge bg-<?= $roleBadge[$u['role']] ?? 'secondary' ?>"><?= $roleLabel[$u['role']] ?? $u['role'] ?></span>
                        </td>
                        <td>
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="badge bg-success">Hoạt động</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Đã khóa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted"><?= formatDate($u['created_at']) ?></small>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <!-- Nút Sửa -->
                                <button type="button" class="btn btn-outline-primary" title="Sửa thông tin"
                                    data-bs-toggle="modal" data-bs-target="#editUserModal"
                                    data-id="<?= $u['id'] ?>"
                                    data-username="<?= sanitize($u['username']) ?>"
                                    data-fullname="<?= sanitize($u['full_name']) ?>"
                                    data-email="<?= sanitize($u['email'] ?? '') ?>"
                                    data-phone="<?= sanitize($u['phone'] ?? '') ?>"
                                    data-role="<?= $u['role'] ?>"
                                    data-status="<?= $u['status'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <?php if ($u['id'] != $currentAdminId): ?>
                                    <!-- Nút Khóa / Mở khóa -->
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn <?= $u['status'] === 'active' ? 'khóa' : 'mở khóa' ?> tài khoản này?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-outline-<?= $u['status'] === 'active' ? 'warning' : 'success' ?>" title="<?= $u['status'] === 'active' ? 'Khóa tài khoản' : 'Mở khóa' ?>">
                                            <i class="bi bi-<?= $u['status'] === 'active' ? 'lock' : 'unlock' ?>"></i>
                                        </button>
                                    </form>

                                    <!-- Nút Xóa -->
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn người dùng này?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Xóa người dùng">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white text-muted small py-3">
        Tổng số người dùng hiển thị: <strong><?= count($users) ?></strong> tài khoản
    </div>
</div>

<!-- Modal Thêm Người Dùng -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="addUserForm" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Thêm người dùng mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required minlength="3" pattern="[a-zA-Z0-9_]+" placeholder="Ví dụ: nhanvien4" title="Chỉ chứa chữ cái, số và dấu gạch dưới">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required placeholder="Ví dụ: Nguyễn Văn An">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="email@example.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control" placeholder="09xxxxxxxx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Vai trò</label>
                        <select name="role" class="form-select">
                            <option value="customer">Khách hàng</option>
                            <option value="staff">Nhân viên</option>
                            <option value="admin">Quản trị viên</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="active">Hoạt động</option>
                            <option value="locked">Khóa</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Thêm người dùng</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa Người Dùng -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="editUserForm" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Sửa thông tin người dùng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Tên đăng nhập</label>
                        <input type="text" id="edit_username" class="form-control bg-light" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Số điện thoại</label>
                        <input type="tel" name="phone" id="edit_phone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Mật khẩu mới (Để trống nếu giữ nguyên)</label>
                        <input type="password" name="password" class="form-control" minlength="6" placeholder="Nhập mật khẩu mới nếu muốn đổi">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Vai trò</label>
                        <select name="role" id="edit_role" class="form-select">
                            <option value="customer">Khách hàng</option>
                            <option value="staff">Nhân viên</option>
                            <option value="admin">Quản trị viên</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Trạng thái</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="active">Hoạt động</option>
                            <option value="locked">Khóa</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
// Đổ dữ liệu vào Modal Sửa
document.getElementById('editUserModal').addEventListener('show.bs.modal', function(event) {
    const btn = event.relatedTarget;
    document.getElementById('edit_user_id').value = btn.dataset.id;
    document.getElementById('edit_username').value = btn.dataset.username;
    document.getElementById('edit_full_name').value = btn.dataset.fullname;
    document.getElementById('edit_email').value = btn.dataset.email;
    document.getElementById('edit_phone').value = btn.dataset.phone;
    document.getElementById('edit_role').value = btn.dataset.role;
    document.getElementById('edit_status').value = btn.dataset.status;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
