<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/response.php';

if (currentUser()) {
    $role = currentUser()['VaiTro'] ?? '';
    header('Location: ' . ($role === 'admin' ? '/views/taikhoan/index.php' : ($role === 'nguoithue' ? '/views/thongbao/index.php' : '/index.php')));
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['TenDangNhap'] ?? '');
    $password = trim($_POST['MatKhau'] ?? '');

    if ($username !== '' && $password !== '') {
        $pdo = getDb();
        $stmt = $pdo->prepare('SELECT * FROM TaiKhoan WHERE TenDangNhap = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        $isValidPassword = $user && (
            password_verify($password, $user['MatKhau']) ||
            hash_equals((string) $user['MatKhau'], (string) $password)
        );

        if ($isValidPassword) {
            if (!password_verify($password, $user['MatKhau']) && $user['MatKhau'] === $password) {
                $update = $pdo->prepare('UPDATE TaiKhoan SET MatKhau = :p WHERE Id = :id');
                $update->execute([
                    ':p' => password_hash($password, PASSWORD_BCRYPT),
                    ':id' => $user['Id'],
                ]);
            }

            if ((int) $user['TrangThai'] !== 1) {
                $message = 'Tài khoản đã bị khóa';
            } else {
                loginUser([
                    'Id' => $user['Id'],
                    'TenDangNhap' => $user['TenDangNhap'],
                    'HoTen' => $user['HoTen'],
                    'VaiTro' => $user['VaiTro'],
                    'Email' => $user['Email'],
                ]);
                header('Location: ' . ($user['VaiTro'] === 'admin' ? '/views/taikhoan/index.php' : ($user['VaiTro'] === 'nguoithue' ? '/views/thongbao/index.php' : '/index.php')));
                exit;
            }
        } else {
            $message = 'Tên đăng nhập hoặc mật khẩu không đúng';
        }
    } else {
        $message = 'Vui lòng nhập đủ thông tin';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trọ Tốt - Đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/assets/pics/logo.webp" type="image/webp" sizes="512x512">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="brand-badge large"><img src="/assets/pics/logo.webp" class="brand-logo large" alt="Logo Trọ Tốt"></div>
                <h2>Đăng nhập</h2>
                <p>Hệ thống quản lý phòng trọ</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Tên đăng nhập</label>
                    <input type="text" class="form-control" name="TenDangNhap" placeholder="Nhập tên người dùng" autocomplete="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <div class="input-group password-input-group">
                        <input type="password" class="form-control" id="loginPassword" name="MatKhau" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
                        <button type="button" class="btn password-toggle" id="togglePassword" aria-label="Hiện mật khẩu" aria-controls="loginPassword" aria-pressed="false">
                            <svg class="password-eye password-eye-show" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2.1 12s3.6-6 9.9-6 9.9 6 9.9 6-3.6 6-9.9 6-9.9-6-9.9-6Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="password-eye password-eye-hide d-none" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m3 3 18 18"></path>
                                <path d="M10.6 6.2A10.8 10.8 0 0 1 12 6c6.3 0 9.9 6 9.9 6a17.8 17.8 0 0 1-2.2 2.8M6.2 6.2C3.5 8 2.1 12 2.1 12s3.6 6 9.9 6c1.5 0 2.8-.3 4-.8M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-lg">Đăng nhập</button>
            </form>
        </div>
    </div>
    <script>
        (() => {
            const password = document.getElementById('loginPassword');
            const toggle = document.getElementById('togglePassword');
            const showIcon = toggle?.querySelector('.password-eye-show');
            const hideIcon = toggle?.querySelector('.password-eye-hide');

            toggle?.addEventListener('click', () => {
                const isVisible = password.type === 'text';
                password.type = isVisible ? 'password' : 'text';
                toggle.setAttribute('aria-pressed', String(!isVisible));
                toggle.setAttribute('aria-label', isVisible ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
                showIcon?.classList.toggle('d-none', !isVisible);
                hideIcon?.classList.toggle('d-none', isVisible);
                password.focus();
            });
        })();
    </script>
</body>
</html>
