<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/response.php';

try {
    requireRole(['chutro']);
    $pdo = getDb();
    $ownerId = (int) (currentUser()['Id'] ?? 0);
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    $links = "(
        SELECT nt.TaiKhoanId, h.PhongId FROM NguoiThue nt
        JOIN HopDong h ON h.NguoiThueId=nt.Id AND h.IsDeleted=0
        WHERE nt.IsDeleted=0 AND nt.TaiKhoanId IS NOT NULL
        UNION
        SELECT tv.TaiKhoanId, h.PhongId FROM ThanhVienPhong tv
        JOIN HopDong h ON h.Id=tv.HopDongId AND h.IsDeleted=0
        WHERE tv.IsDeleted=0 AND tv.TaiKhoanId IS NOT NULL
    )";

    $assertAccount = function (int $accountId) use ($pdo, $ownerId, $links): void {
        if ($accountId <= 0) errorResponse('Tài khoản không hợp lệ.', 422);
        $sql = "SELECT 1 FROM TaiKhoan tk JOIN $links lk ON lk.TaiKhoanId=tk.Id
                JOIN Phong p ON p.Id=lk.PhongId AND p.IsDeleted=0
                JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0
                JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0
                WHERE tk.Id=:account AND tk.VaiTro='nguoithue' AND tk.IsDeleted=0
                  AND k.TaiKhoanId=:owner LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':account' => $accountId, ':owner' => $ownerId]);
        if (!$stmt->fetchColumn()) errorResponse('Không tìm thấy tài khoản thuê trọ hoặc bạn không có quyền thao tác.', 404);
    };

    if ($method === 'GET') {
        $sql = "SELECT tk.Id,tk.TenDangNhap,tk.HoTen,tk.Email,tk.SoDienThoai,
                       tk.VaiTro,tk.TrangThai,tk.NgayTao,
                       GROUP_CONCAT(DISTINCT k.TenKhu || ' · ' || d.TenDay || ' · Phòng ' || p.SoPhong) AS DanhSachPhong
                FROM TaiKhoan tk JOIN $links lk ON lk.TaiKhoanId=tk.Id
                JOIN Phong p ON p.Id=lk.PhongId AND p.IsDeleted=0
                JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0
                JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0
                WHERE tk.VaiTro='nguoithue' AND tk.IsDeleted=0 AND k.TaiKhoanId=:owner
                GROUP BY tk.Id,tk.TenDangNhap,tk.HoTen,tk.Email,tk.SoDienThoai,tk.VaiTro,tk.TrangThai,tk.NgayTao
                ORDER BY tk.TrangThai DESC,tk.HoTen ASC,tk.Id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':owner' => $ownerId]);
        successResponse($stmt->fetchAll(), 'Danh sách tài khoản thuê trọ');
    }

    $accountId = (int) ($_GET['id'] ?? 0);
    $assertAccount($accountId);
    $input = $method === 'PUT' ? readJsonBody() : [];

    if ($method === 'PUT' && $action === 'sua') {
        $name = trim((string) ($input['HoTen'] ?? ''));
        if ($name === '') errorResponse('Họ tên không được để trống.', 422);
        $pdo->prepare('UPDATE TaiKhoan SET HoTen=:name,Email=:email,SoDienThoai=:phone WHERE Id=:id AND IsDeleted=0')->execute([
            ':name'=>$name, ':email'=>trim((string)($input['Email']??''))?:null,
            ':phone'=>trim((string)($input['SoDienThoai']??''))?:null, ':id'=>$accountId
        ]);
        successResponse([], 'Đã cập nhật tài khoản thuê trọ.');
    }
    if ($method === 'PUT' && $action === 'capLaiMatKhau') {
        $password = (string) ($input['MatKhau'] ?? '');
        if (strlen($password) < 6) errorResponse('Mật khẩu mới phải có ít nhất 6 ký tự.', 422);
        $pdo->prepare('UPDATE TaiKhoan SET MatKhau=:password WHERE Id=:id AND IsDeleted=0')->execute([
            ':password'=>password_hash($password, PASSWORD_BCRYPT), ':id'=>$accountId
        ]);
        successResponse([], 'Đã cấp lại mật khẩu.');
    }
    if ($method === 'PUT' && $action === 'trangThai') {
        $status = (int) ($input['TrangThai'] ?? 0) === 1 ? 1 : 0;
        $pdo->prepare('UPDATE TaiKhoan SET TrangThai=:status WHERE Id=:id AND IsDeleted=0')->execute([':status'=>$status,':id'=>$accountId]);
        successResponse(['TrangThai'=>$status], $status ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
    }
    if ($method === 'DELETE') {
        $pdo->prepare('UPDATE TaiKhoan SET IsDeleted=1,TrangThai=0 WHERE Id=:id AND IsDeleted=0')->execute([':id'=>$accountId]);
        successResponse([], 'Đã xóa tài khoản thuê trọ.');
    }
    errorResponse('Phương thức hoặc thao tác không được hỗ trợ.', 405);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 500);
}
