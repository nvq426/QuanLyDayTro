<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/response.php';
require __DIR__ . '/../includes/helpers.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? 'danhsach';
    $pdo = getDb();

    if ($method === 'POST' && $action === 'dangnhap') {
        $input = $_POST;
        if (!isset($input['TenDangNhap'], $input['MatKhau'])) {
            errorResponse('Thiếu thông tin đăng nhập', 400);
        }

        $stmt = $pdo->prepare('SELECT * FROM TaiKhoan WHERE TenDangNhap = :u LIMIT 1');
        $stmt->execute([':u' => $input['TenDangNhap']]);
        $user = $stmt->fetch();

        $isValidPassword = $user && (
            password_verify($input['MatKhau'], $user['MatKhau']) ||
            hash_equals((string) $user['MatKhau'], (string) $input['MatKhau'])
        );

        if (!$user || !$isValidPassword) {
            errorResponse('Tên đăng nhập hoặc mật khẩu không đúng', 401);
        }

        if (!password_verify($input['MatKhau'], $user['MatKhau']) && $user['MatKhau'] === $input['MatKhau']) {
            $pdo->prepare('UPDATE TaiKhoan SET MatKhau = :p WHERE Id = :id')->execute([
                ':p' => password_hash($input['MatKhau'], PASSWORD_BCRYPT),
                ':id' => $user['Id'],
            ]);
        }

        if ((int)$user['TrangThai'] !== 1) {
            errorResponse('Tài khoản đã bị khóa', 403);
        }

        loginUser([
            'Id' => $user['Id'],
            'TenDangNhap' => $user['TenDangNhap'],
            'HoTen' => $user['HoTen'],
            'VaiTro' => $user['VaiTro'],
            'Email' => $user['Email'],
        ]);

        successResponse(['user' => ['Id' => $user['Id'], 'HoTen' => $user['HoTen'], 'VaiTro' => $user['VaiTro']]], 'Đăng nhập thành công');
    }

    if ($method === 'POST' && $action === 'dangxuat') {
        logoutUser();
        successResponse([], 'Đăng xuất thành công');
    }

    if ($method === 'GET') {
        requireRole(['admin']);
        [$page, $limit] = getPaginationValues();
        $limit = min(100, max(5, $limit));
        $offset = ($page - 1) * $limit;
        $keyword = trim((string)($_GET['tuKhoa'] ?? ''));
        $where = 'IsDeleted = 0';
        $params = [];
        if ($keyword !== '') {
            $where .= ' AND (HoTen LIKE :keyword OR TenDangNhap LIKE :keyword OR Email LIKE :keyword OR SoDienThoai LIKE :keyword)';
            $params[':keyword'] = '%' . $keyword . '%';
        }

        $count = $pdo->prepare('SELECT COUNT(*) FROM TaiKhoan WHERE ' . $where);
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $activeCount = $pdo->prepare('SELECT COUNT(*) FROM TaiKhoan WHERE ' . $where . ' AND TrangThai = 1');
        $activeCount->execute($params);
        $totalActive = (int)$activeCount->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $limit));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare('SELECT Id,TenDangNhap,HoTen,Email,SoDienThoai,VaiTro,TrangThai,NgayTao FROM TaiKhoan WHERE ' . $where . ' ORDER BY Id DESC LIMIT :limit OFFSET :offset');
        foreach ($params as $key => $value) $stmt->bindValue($key, $value);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        successResponse(['items'=>$stmt->fetchAll(),'page'=>$page,'limit'=>$limit,'total'=>$total,'totalActive'=>$totalActive,'totalPages'=>$totalPages], 'Danh sách tài khoản');
    }

    if ($method === 'POST' && $action === 'them') {
        requireRole(['admin']);
        $input = readJsonBody();
        requireFields($input, ['TenDangNhap', 'MatKhau', 'HoTen', 'VaiTro']);

        $exists = $pdo->prepare('SELECT 1 FROM TaiKhoan WHERE TenDangNhap = :u LIMIT 1');
        $exists->execute([':u' => $input['TenDangNhap']]);
        if ($exists->fetch()) {
            errorResponse('Tên đăng nhập đã tồn tại', 409);
        }

        $stmt = $pdo->prepare('INSERT INTO TaiKhoan (TenDangNhap, MatKhau, HoTen, Email, SoDienThoai, VaiTro, TrangThai) VALUES (:u, :p, :h, :e, :s, :v, :t)');
        $stmt->execute([
            ':u' => $input['TenDangNhap'],
            ':p' => password_hash($input['MatKhau'], PASSWORD_BCRYPT),
            ':h' => $input['HoTen'],
            ':e' => $input['Email'] ?? null,
            ':s' => $input['SoDienThoai'] ?? null,
            ':v' => $input['VaiTro'],
            ':t' => $input['TrangThai'] ?? 1,
        ]);

        successResponse(['id' => $pdo->lastInsertId()], 'Tạo tài khoản thành công', 201);
    }

    if ($method === 'PUT') {
        requireRole(['admin']);
        $id = $_GET['id'] ?? 0;
        $input = readJsonBody();
        $pdo->prepare('UPDATE TaiKhoan SET HoTen = :h, Email = :e, SoDienThoai = :s, VaiTro = :v, TrangThai = :t WHERE Id = :id')
            ->execute([
                ':h' => $input['HoTen'] ?? '',
                ':e' => $input['Email'] ?? null,
                ':s' => $input['SoDienThoai'] ?? null,
                ':v' => $input['VaiTro'] ?? 'nguoithue',
                ':t' => (int) ($input['TrangThai'] ?? 1),
                ':id' => $id,
            ]);

        successResponse([], 'Cập nhật tài khoản thành công');
    }

    if ($method === 'DELETE') {
        requireRole(['admin']);
        $id = $_GET['id'] ?? 0;
        $pdo->prepare('UPDATE TaiKhoan SET IsDeleted=1, TrangThai=0 WHERE Id = :id')->execute([':id' => $id]);
        successResponse([], 'Xóa tài khoản thành công');
    }

    errorResponse('Phương thức hoặc action không hợp lệ', 404);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 500);
}
