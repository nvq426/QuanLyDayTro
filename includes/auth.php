<?php
session_start();

function describeAuditAction(string $method, string $path, string $action): string
{
    if ($action === 'dangnhap') return 'Đăng nhập';
    $module = pathinfo($path, PATHINFO_FILENAME) ?: 'request';
    $labels = ['POST'=>'Tạo','PUT'=>'Cập nhật','PATCH'=>'Cập nhật','DELETE'=>'Xóa'];
    return ($labels[$method] ?? $method) . ' ' . $module . ($action !== '' ? ' · ' . $action : '');
}

/** Only audit API create/update/delete actions and login attempts. */
function registerAuditLogger(): void
{
    if (!empty($GLOBALS['audit_logger_registered'])) return;
    $GLOBALS['audit_logger_registered'] = true;
    $GLOBALS['audit_initial_user'] = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
    register_shutdown_function(function (): void {
        if (!function_exists('getDb')) return;
        $path = parse_url($_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? ''), PHP_URL_PATH) ?: '';
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $action = trim((string)($_GET['action'] ?? ''));
        $isLogin = $method === 'POST' && ($action === 'dangnhap' || str_ends_with($path, '/login.php'));
        $isCrudWrite = (str_contains($path, '/api/') || str_ends_with($path, '/version.php')) && in_array($method, ['POST','PUT','PATCH','DELETE'], true)
            && !in_array($action, ['dangnhap','dangxuat'], true);
        if (!$isLogin && !$isCrudWrite) return;
        $user = currentUser() ?: ($GLOBALS['audit_initial_user'] ?? null);
        $postUser = trim((string)($_POST['TenDangNhap'] ?? ''));
        try {
            $status = http_response_code() ?: 200;
            $success = $isLogin ? currentUser() !== null : ($status >= 200 && $status < 400);
            $pdo = getDb();
            $stmt = $pdo->prepare('INSERT INTO AuditLog(TaiKhoanId,TenDangNhap,HoTen,VaiTro,HanhDong,PhuongThuc,DuongDan,DiaChiIP,ThanhCong) VALUES(:user,:username,:name,:role,:action,:method,:path,:ip,:success)');
            $stmt->execute([
                ':user'=>$user['Id']??null, ':username'=>$user['TenDangNhap']??($postUser?:null),
                ':name'=>$user['HoTen']??null, ':role'=>$user['VaiTro']??null,
                ':action'=>describeAuditAction($method,$path,$action) . ($success ? '' : ' · Thất bại'), ':method'=>$method, ':path'=>$path,
                ':ip'=>$_SERVER['REMOTE_ADDR']??null, ':success'=>$success?1:0
            ]);
        } catch (Throwable $ignored) {
            // Auditing must never interrupt the user's primary request.
        }
    });
}

registerAuditLogger();

function currentUser(): ?array
{
    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return null;
    }

    return $_SESSION['user'];
}

function requireLogin(): void
{
    if (!currentUser()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole(array $allowedRoles): void
{
    $user = currentUser();
    if (!$user || !in_array($user['VaiTro'] ?? '', $allowedRoles, true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function loginUser(array $user): void
{
    $_SESSION['user'] = $user;
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
