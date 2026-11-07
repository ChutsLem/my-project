<?php
/**
 * Hồ sơ cá nhân - Beauty Salon
 */
require_once __DIR__ . '/config/config.php';
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Lấy thông tin user mới nhất
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('Không tìm thấy thông tin tài khoản!', 'danger');
    redirect('auth/logout.php');
}

$activeTab = 'profile';

// Xử lý cập nhật thông tin cá nhân
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        setFlash('Mã bảo mật không hợp lệ!', 'danger');
        redirect('profile.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $activeTab = 'profile';
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');

        $errors = [];
        if (empty($full_name)) {
            $errors[] = 'Họ và tên không được để trống.';
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ.';
        }
        if (empty($phone) || !preg_match('/^[0-9]{9,11}$/', $phone)) {
            $errors[] = 'Số điện thoại phải từ 9 đến 11 chữ số.';
        }

        // Kiểm tra email không trùng với user khác
        if (empty($errors) && !empty($email)) {
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmtCheck->execute([$email, $userId]);
            if ($stmtCheck->fetch()) {
                $errors[] = 'Email này đã được sử dụng bởi tài khoản khác.';
            }
        }

        if (empty($errors)) {
            $stmtUpdate = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmtUpdate->execute([$full_name, $email, $phone, $userId]);
            $_SESSION['full_name'] = $full_name;
            setFlash('Cập nhật thông tin cá nhân thành công!', 'success');
            redirect('profile.php');
        } else {
            setFlash(implode('<br>', $errors), 'danger');
        }
    } elseif ($action === 'change_password') {
        $activeTab = 'password';
        $old_password     = $_POST['old_password'] ?? '';
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $errors = [];
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $errors[] = 'Vui lòng điền đầy đủ các trường mật khẩu.';
        } elseif (!password_verify($old_password, $user['password'])) {
            $errors[] = 'Mật khẩu hiện tại không chính xác.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Xác nhận mật khẩu mới không khớp.';
        }

        if (empty($errors)) {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmtPass = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtPass->execute([$hashed, $userId]);
            setFlash('Đổi mật khẩu thành công!', 'success');
            redirect('profile.php');
        } else {
            setFlash(implode('<br>', $errors), 'danger');
        }
    }
}

$pageTitle = 'Hồ sơ cá nhân';
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card table-card mb-4">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-person-circle me-2 text-primary"></i>Hồ sơ tài khoản</h5>
                <span class="badge bg-primary text-uppercase"><?= $user['role'] ?></span>
            </div>
            <div class="card-body p-4">
                <ul class="nav nav-tabs mb-4" id="profileTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link <?= $activeTab === 'profile' ? 'active' : '' ?>" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane">
                            <i class="bi bi-info-circle me-1"></i>Thông tin chung
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link <?= $activeTab === 'password' ? 'active' : '' ?>" id="pass-tab" data-bs-toggle="tab" data-bs-target="#pass-pane">
                            <i class="bi bi-shield-lock me-1"></i>Đổi mật khẩu
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="profileTabContent">
                    <!-- Thông tin chung -->
                    <div class="tab-pane fade <?= $activeTab === 'profile' ? 'show active' : '' ?>" id="info-pane">
                        <form action="" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_profile">

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">TÊN ĐĂNG NHẬP</label>
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                <div class="form-text">Tên đăng nhập cố định và không thể thay đổi.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">HỌ VÀ TÊN <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">SỐ ĐIỆN THOẠI <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted small fw-bold">EMAIL</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">NGÀY THAM GIA</label>
                                <input type="text" class="form-control bg-light" value="<?= formatDate($user['created_at']) ?>" readonly>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-1"></i>Lưu thay đổi
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Đổi mật khẩu -->
                    <div class="tab-pane fade <?= $activeTab === 'password' ? 'show active' : '' ?>" id="pass-pane">
                        <form action="" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">MẬT KHẨU HIỆN TẠI <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="old_password" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">MẬT KHẨU MỚI <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="new_password" placeholder="Tối thiểu 6 ký tự" required minlength="6">
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">XÁC NHẬN MẬT KHẨU MỚI <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="confirm_password" required minlength="6">
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-warning px-4">
                                    <i class="bi bi-key me-1"></i>Đổi mật khẩu
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
