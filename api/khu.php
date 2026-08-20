<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/response.php';

try {
    $pdo = getDb();
    $method = $_SERVER['REQUEST_METHOD'];
    $user = currentUser();
    requireRole(['admin', 'chutro']);
    $isAdmin = ($user['VaiTro'] ?? '') === 'admin';
    $ownerId = (int) ($user['Id'] ?? 0);

    $assertOwner = function (int $khuId) use ($pdo, $isAdmin, $ownerId): array {
        $stmt = $pdo->prepare('SELECT * FROM Khu WHERE Id = :id AND COALESCE(IsDeleted,0)=0' . ($isAdmin ? '' : ' AND TaiKhoanId = :owner'));
        $params = [':id' => $khuId];
        if (!$isAdmin) $params[':owner'] = $ownerId;
        $stmt->execute($params);
        $khu = $stmt->fetch();
        if (!$khu) errorResponse('Không tìm thấy khu hoặc bạn không có quyền truy cập', 404);
        return $khu;
    };

    if ($method === 'GET') {
        if (($_GET['action'] ?? '') === 'chutro') {
            if (!$isAdmin) errorResponse('Bạn không có quyền truy cập', 403);
            $owners = $pdo->query("SELECT Id, HoTen, TenDangNhap FROM TaiKhoan WHERE VaiTro = 'chutro' AND TrangThai = 1 AND COALESCE(IsDeleted,0)=0 ORDER BY HoTen ASC")->fetchAll();
            successResponse($owners, 'Danh sách chủ trọ');
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id) successResponse($assertOwner($id), 'Chi tiết khu');

        $sql = 'SELECT k.*, tk.HoTen AS ChuTro, tk.TenDangNhap AS TenDangNhapChuTro FROM Khu k LEFT JOIN TaiKhoan tk ON tk.Id = k.TaiKhoanId AND COALESCE(tk.IsDeleted,0)=0 WHERE COALESCE(k.IsDeleted,0)=0';
        $params = [];
        if (!$isAdmin) { $sql .= ' AND k.TaiKhoanId = :owner'; $params[':owner'] = $ownerId; }
        $sql .= ' ORDER BY k.TaiKhoanId ASC, k.Id ASC';
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        successResponse($stmt->fetchAll(), 'Danh sách khu');
    }

    if ($method === 'POST') {
        $input = readJsonBody();
        if (empty($input['TenKhu'])) errorResponse('Tên khu không được để trống', 400);
        $targetOwnerId = $isAdmin ? (int) ($input['TaiKhoanId'] ?? 0) : $ownerId;
        if (!$targetOwnerId) errorResponse('Vui lòng chọn chủ trọ quản lý', 400);
        $check = $pdo->prepare("SELECT 1 FROM TaiKhoan WHERE Id = :id AND VaiTro = 'chutro' AND TrangThai = 1");
        $check->execute([':id' => $targetOwnerId]);
        if (!$check->fetch()) errorResponse('Chủ trọ không hợp lệ', 422);
        $stmt = $pdo->prepare('INSERT INTO Khu (TenKhu, DiaChi, MoTa, TaiKhoanId) VALUES (:ten, :dc, :mt, :owner)');
        $stmt->execute([':ten'=>$input['TenKhu'], ':dc'=>$input['DiaChi']??null, ':mt'=>$input['MoTa']??null, ':owner'=>$targetOwnerId]);
        successResponse(['id'=>$pdo->lastInsertId()], 'Tạo khu thành công', 201);
    }

    if ($method === 'PUT') {
        $id = (int) ($_GET['id'] ?? 0); $assertOwner($id); $input = readJsonBody();
        $targetOwnerId = $isAdmin ? (int) ($input['TaiKhoanId'] ?? 0) : $ownerId;
        if (!$targetOwnerId) errorResponse('Vui lòng chọn chủ trọ quản lý', 400);
        $stmt = $pdo->prepare('UPDATE Khu SET TenKhu=:ten, DiaChi=:dc, MoTa=:mt, TaiKhoanId=:owner WHERE Id=:id');
        $stmt->execute([':ten'=>$input['TenKhu']??'', ':dc'=>$input['DiaChi']??null, ':mt'=>$input['MoTa']??null, ':owner'=>$targetOwnerId, ':id'=>$id]);
        successResponse([], 'Cập nhật khu thành công');
    }

    if ($method === 'DELETE') { $id=(int)($_GET['id']??0); $assertOwner($id); $pdo->prepare('UPDATE Khu SET IsDeleted=1 WHERE Id=:id')->execute([':id'=>$id]); successResponse([], 'Đã chuyển khu vào trạng thái đã xóa'); }
    errorResponse('Method không hỗ trợ', 405);
} catch (Throwable $e) { errorResponse($e->getMessage(), 500); }
