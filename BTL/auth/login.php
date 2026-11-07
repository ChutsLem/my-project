<?php
/**
 * Đăng nhập - Beauty Salon & Spa Lounge
 * Lấy cảm hứng từ Goldust Spa Bali (https://goldustspa.com/)
 */
require_once __DIR__ . '/../config/config.php';

// Nếu đã đăng nhập, chuyển hướng theo vai trò
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/index.php');
    } elseif (isStaff()) {
        redirect('staff/index.php');
    } else {
        redirect('index.php');
    }
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        $error = 'Mã bảo mật không hợp lệ hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Tài khoản hoặc mật khẩu không chính xác.';
            } elseif ($user['status'] === 'locked') {
                $error = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.';
            } elseif (!password_verify($password, $user['password'])) {
                $error = 'Tài khoản hoặc mật khẩu không chính xác.';
            } else {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['avatar'] = $user['avatar'];

                setFlash('Đăng nhập thành công! Chào mừng ' . htmlspecialchars($user['full_name']) . '.', 'success');

                // Chuyển hướng theo vai trò
                if ($user['role'] === 'admin') {
                    redirect('admin/index.php');
                } elseif ($user['role'] === 'staff') {
                    redirect('staff/index.php');
                } else {
                    $returnUrl = $_GET['return'] ?? 'index.php';
                    redirect($returnUrl);
                }
            }
        }
    }
}

$pageTitle = 'Đăng nhập - Beauty Salon & Spa';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <!-- Google Fonts: Cormorant Garamond & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --gold: #c5a880;
            --gold-light: #f4ede4;
            --gold-dark: #9e7949;
            --dark: #1c1a17;
            --canvas: #faf8f5;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, rgba(28,26,23,0.92) 0%, rgba(36,33,29,0.96) 100%), 
                        url('https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            color: #2b2723;
        }
        .auth-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.45);
            overflow: hidden;
            width: 100%;
            max-width: 480px;
            border: 1px solid rgba(197, 168, 128, 0.4);
            position: relative;
        }
        .auth-header {
            background: #1c1a17;
            color: #ffffff;
            padding: 38px 25px 30px;
            text-align: center;
            border-bottom: 2px solid var(--gold);
        }
        .auth-header .brand-title {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.85rem;
            letter-spacing: 2.5px;
            font-weight: 700;
            color: #ffffff;
            text-transform: uppercase;
        }
        .auth-body {
            padding: 36px 32px;
            background: #ffffff;
        }
        .auth-input-group .input-group-text {
            background-color: #faf8f5;
            border: 1px solid #e2ddd5;
            border-right: none;
            color: var(--gold-dark);
            padding: 12px 16px;
        }
        .auth-input-group .form-control {
            background-color: #faf8f5;
            border: 1px solid #e2ddd5;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.25s ease;
        }
        .auth-input-group .form-control:focus {
            background-color: #ffffff;
            border-color: var(--gold);
            outline: none;
            box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.2);
        }
        .demo-chip {
            font-size: 0.78rem;
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid rgba(197, 168, 128, 0.4);
            background: #faf8f5;
            color: #2b2723;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }
        .demo-chip:hover {
            background: #1c1a17;
            color: var(--gold-light);
            border-color: #1c1a17;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="auth-header">
        <a href="<?= BASE_URL ?>index.php" class="text-decoration-none d-inline-block mb-2">
            <i class="bi bi-flower1" style="color: var(--gold); font-size: 2.4rem;"></i>
            <h2 class="brand-title mb-0">BEAUTY SALON</h2>
        </a>
        <div class="section-subtitle mb-0" style="color: var(--gold); font-size: 0.72rem; letter-spacing: 2.5px;">
            Goldust Sanctuary & Beauty Lounge
        </div>
    </div>

    <div class="auth-body">
        <div class="text-center mb-4">
            <h4 class="font-serif text-dark mb-1" style="font-size: 1.55rem;">Đăng Nhập Hệ Thống</h4>
            <p class="text-muted small mb-0">Không gian trải nghiệm dịch vụ làm đẹp & chăm sóc cao cấp</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php 
        $flash = getFlash();
        if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= $flash['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="loginForm" novalidate>
            <?= csrfField() ?>
            
            <div class="mb-3">
                <label for="username" class="form-label small text-muted text-uppercase fw-semibold" style="letter-spacing: 1px;">
                    Tên đăng nhập
                </label>
                <div class="input-group auth-input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($username) ?>" placeholder="Nhập tên đăng nhập" required autofocus>
                </div>
                <div class="invalid-feedback">Vui lòng nhập tên đăng nhập.</div>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label small text-muted text-uppercase fw-semibold mb-0" style="letter-spacing: 1px;">
                        Mật khẩu
                    </label>
                </div>
                <div class="input-group auth-input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Nhập mật khẩu" required>
                    <button class="btn bg-white border border-start-0 text-muted px-3" type="button" id="togglePassword" style="border-color:#e2ddd5 !important;">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="invalid-feedback">Vui lòng nhập mật khẩu.</div>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" class="btn btn-gold py-3 fw-bold" style="font-size: 0.9rem; letter-spacing: 1.5px;">
                    <i class="bi bi-box-arrow-in-right me-2"></i> ĐĂNG NHẬP TRẢI NGHIỆM
                </button>
            </div>
        </form>

        <div class="text-center pt-2 border-top border-light">
            <p class="text-muted small mb-2">Chưa có tài khoản thành viên? 
                <a href="<?= BASE_URL ?>auth/register.php" class="text-gold fw-bold text-decoration-none">Đăng ký ngay</a>
            </p>
            <a href="<?= BASE_URL ?>index.php" class="text-muted small text-decoration-none d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left text-gold"></i> Trở về Trang Chủ
            </a>
        </div>

        <!-- Tiện ích đăng nhập nhanh dành cho Giảng viên / Đánh giá -->
        <div class="mt-4 pt-3 border-top border-light">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small text-muted fw-semibold"><i class="bi bi-stars text-gold me-1"></i>Tài khoản kiểm thử nhanh:</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="demo-chip" onclick="fillDemo('admin', 'admin123')">
                    <i class="bi bi-shield-lock text-gold me-1"></i> Admin
                </button>
                <button type="button" class="demo-chip" onclick="fillDemo('nhanvien1', 'staff123')">
                    <i class="bi bi-person-badge text-gold me-1"></i> Nhân viên
                </button>
                <button type="button" class="demo-chip" onclick="fillDemo('khachhang1', 'customer123')">
                    <i class="bi bi-person text-gold me-1"></i> Khách hàng
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Ambient Audio Player -->
<div class="spa-audio-player shadow-lg" id="spaAudioContainer" title="Âm nhạc thư giãn - Goldust Spa Lounge Soundtrack">
    <button class="spa-audio-btn" id="spaAudioBtn" type="button" aria-label="Phát / Dừng nhạc nền Spa">
        <i class="bi bi-play-fill" id="spaAudioIcon"></i>
    </button>
    <div class="d-none d-sm-flex flex-column">
        <span class="spa-audio-label">Goldust Spa Ambient</span>
        <small class="text-white-50" style="font-size: 0.65rem;">Thư giãn & Tĩnh lặng</small>
    </div>
    <div class="sound-waves" id="soundWaves">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
    <audio id="spaAudioTrack" loop preload="metadata">
        <source src="https://goldust.cdn.prismic.io/goldust/Z4E0LJbqstJ99Uif_Sequence01.mp3" type="audio/mpeg">
    </audio>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle Password
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});

// Điền nhanh tài khoản kiểm thử
function fillDemo(user, pass) {
    document.getElementById('username').value = user;
    document.getElementById('password').value = pass;
}

// Client-side validation
(function() {
    'use strict';
    const form = document.getElementById('loginForm');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();

// Ambient Audio Controller
(function() {
    const audio = document.getElementById('spaAudioTrack');
    const btn = document.getElementById('spaAudioBtn');
    const icon = document.getElementById('spaAudioIcon');
    const waves = document.getElementById('soundWaves');

    if (!audio || !btn) return;
    audio.volume = 0.4;

    function updateAudioUI(isPlaying) {
        if (isPlaying) {
            icon.className = 'bi bi-pause-fill';
            waves.classList.add('playing');
        } else {
            icon.className = 'bi bi-play-fill';
            waves.classList.remove('playing');
        }
    }

    btn.addEventListener('click', function() {
        if (audio.paused) {
            audio.play().then(function() {
                updateAudioUI(true);
                sessionStorage.setItem('spa_audio_playing', 'true');
            }).catch(function(e) {
                console.log('Autoplay prevented:', e);
            });
        } else {
            audio.pause();
            updateAudioUI(false);
            sessionStorage.setItem('spa_audio_playing', 'false');
        }
    });

    if (sessionStorage.getItem('spa_audio_playing') === 'true') {
        audio.play().then(function() {
            updateAudioUI(true);
        }).catch(function() {
            updateAudioUI(false);
        });
    }
})();
</script>
</body>
</html>
