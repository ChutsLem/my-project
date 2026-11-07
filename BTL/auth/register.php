<?php
/**
 * Đăng ký tài khoản khách hàng - Beauty Salon & Spa Lounge
 * Lấy cảm hứng từ Goldust Spa Bali (https://goldustspa.com/)
 */
require_once __DIR__ . '/../config/config.php';

// Nếu đã đăng nhập, chuyển hướng
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$formData = [
    'username'  => '',
    'full_name' => '',
    'email'     => '',
    'phone'     => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF()) {
        $errors[] = 'Mã bảo mật không hợp lệ hoặc phiên làm việc đã hết hạn.';
    } else {
        $username         = trim($_POST['username'] ?? '');
        $full_name        = trim($_POST['full_name'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $phone            = trim($_POST['phone'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $formData['username']  = $username;
        $formData['full_name'] = $full_name;
        $formData['email']     = $email;
        $formData['phone']     = $phone;

        // Validation phía Server (PHP)
        if (empty($username)) {
            $errors[] = 'Tên đăng nhập không được để trống.';
        } elseif (strlen($username) < 3 || strlen($username) > 30) {
            $errors[] = 'Tên đăng nhập phải từ 3 đến 30 ký tự.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Tên đăng nhập chỉ chứa chữ cái, chữ số và dấu gạch dưới (_).';
        }

        if (empty($full_name)) {
            $errors[] = 'Họ và tên không được để trống.';
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Địa chỉ email không đúng định dạng.';
        }

        if (empty($phone)) {
            $errors[] = 'Số điện thoại không được để trống.';
        } elseif (!preg_match('/^[0-9]{9,11}$/', $phone)) {
            $errors[] = 'Số điện thoại phải từ 9 đến 11 chữ số.';
        }

        if (empty($password)) {
            $errors[] = 'Mật khẩu không được để trống.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        }

        if ($password !== $confirm_password) {
            $errors[] = 'Xác nhận mật khẩu không trùng khớp.';
        }

        // Kiểm tra trùng lặp trong database
        if (empty($errors)) {
            $db = getDB();
            
            // Kiểm tra username
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = 'Tên đăng nhập này đã có người sử dụng.';
            }

            // Kiểm tra email nếu có
            if (empty($errors) && !empty($email)) {
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = 'Địa chỉ email này đã được đăng ký.';
                }
            }

            // Thực hiện thêm tài khoản mới
            if (empty($errors)) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("
                    INSERT INTO users (username, password, full_name, email, phone, role, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'customer', 'active', NOW())
                ");
                $result = $stmt->execute([$username, $hashedPassword, $full_name, $email, $phone]);

                if ($result) {
                    $newUserId = $db->lastInsertId();
                    // Tự động đăng nhập sau khi đăng ký
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['user_role'] = 'customer';
                    $_SESSION['avatar'] = null;

                    setFlash('Đăng ký tài khoản thành công! Chào mừng quý khách đến với Beauty Salon & Spa.', 'success');
                    redirect('booking/index.php');
                } else {
                    $errors[] = 'Có lỗi xảy ra trong quá trình đăng ký. Vui lòng thử lại sau.';
                }
            }
        }
    }
}

$pageTitle = 'Đăng ký tài khoản thành viên - Beauty Salon & Spa';
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
            padding: 35px 15px;
            color: #2b2723;
        }
        .auth-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.45);
            overflow: hidden;
            width: 100%;
            max-width: 540px;
            border: 1px solid rgba(197, 168, 128, 0.4);
            position: relative;
        }
        .auth-header {
            background: #1c1a17;
            color: #ffffff;
            padding: 35px 25px 25px;
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
            padding: 10px 14px;
        }
        .auth-input-group .form-control {
            background-color: #faf8f5;
            border: 1px solid #e2ddd5;
            padding: 10px 14px;
            font-size: 0.92rem;
            transition: all 0.25s ease;
        }
        .auth-input-group .form-control:focus {
            background-color: #ffffff;
            border-color: var(--gold);
            outline: none;
            box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.2);
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
            <h4 class="font-serif text-dark mb-1" style="font-size: 1.55rem;">Đăng Ký Thành Viên</h4>
            <p class="text-muted small mb-0">Tạo tài khoản để đặt lịch hẹn nhanh chóng và nhận ưu đãi độc quyền</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Vui lòng kiểm tra lại:</div>
                <ul class="mb-0 ps-3 small">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="registerForm" novalidate>
            <?= csrfField() ?>
            
            <div class="row g-3">
                <div class="col-12">
                    <label for="full_name" class="form-label small text-muted text-uppercase fw-semibold mb-1" style="letter-spacing: 0.8px;">
                        Họ và tên <span class="text-danger">*</span>
                    </label>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?= htmlspecialchars($formData['full_name']) ?>" placeholder="Ví dụ: Hoàng Thị Mai" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="username" class="form-label small text-muted text-uppercase fw-semibold mb-1" style="letter-spacing: 0.8px;">
                        Tên đăng nhập <span class="text-danger">*</span>
                    </label>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= htmlspecialchars($formData['username']) ?>" placeholder="Tên viết liền" required minlength="3">
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label small text-muted text-uppercase fw-semibold mb-1" style="letter-spacing: 0.8px;">
                        Số điện thoại <span class="text-danger">*</span>
                    </label>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?= htmlspecialchars($formData['phone']) ?>" placeholder="09xxxxxxxx" required pattern="[0-9]{9,11}">
                    </div>
                </div>

                <div class="col-12">
                    <label for="email" class="form-label small text-muted text-uppercase fw-semibold mb-1" style="letter-spacing: 0.8px;">
                        Địa chỉ Email
                    </label>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($formData['email']) ?>" placeholder="email@example.com">
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label small text-muted text-uppercase fw-semibold mb-1" style="letter-spacing: 0.8px;">
                        Mật khẩu <span class="text-danger">*</span>
                    </label>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Tối thiểu 6 ký tự" required minlength="6">
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="confirm_password" class="form-label small text-muted text-uppercase fw-semibold mb-1" style="letter-spacing: 0.8px;">
                        Xác nhận lại <span class="text-danger">*</span>
                    </label>
                    <div class="input-group auth-input-group">
                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Nhập lại mật khẩu" required minlength="6">
                    </div>
                </div>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-gold py-3 fw-bold" style="font-size: 0.9rem; letter-spacing: 1.5px;">
                    <i class="bi bi-person-check me-2"></i> TẠO TÀI KHOẢN MỚI
                </button>
            </div>
        </form>

        <div class="text-center pt-3 mt-3 border-top border-light">
            <p class="text-muted small mb-2">Đã có tài khoản thành viên? 
                <a href="<?= BASE_URL ?>auth/login.php" class="text-gold fw-bold text-decoration-none">Đăng nhập tại đây</a>
            </p>
            <a href="<?= BASE_URL ?>index.php" class="text-muted small text-decoration-none d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left text-gold"></i> Trở về Trang Chủ
            </a>
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
// Client-side validation kiểm tra mật khẩu khớp nhau
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const pass = document.getElementById('password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    const phone = document.getElementById('phone').value;
    
    if (pass !== confirmPass) {
        e.preventDefault();
        alert('Mật khẩu xác nhận không khớp! Vui lòng kiểm tra lại.');
        document.getElementById('confirm_password').focus();
        return false;
    }
    
    const phoneRegex = /^[0-9]{9,11}$/;
    if (!phoneRegex.test(phone)) {
        e.preventDefault();
        alert('Số điện thoại phải từ 9 đến 11 chữ số!');
        document.getElementById('phone').focus();
        return false;
    }
});

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
