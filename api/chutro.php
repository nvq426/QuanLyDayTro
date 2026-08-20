<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/response.php';

try {
    requireRole(['admin']);
    $pdo = getDb();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    $assertOwner = function (int $id) use ($pdo): array {
        if ($id <= 0) errorResponse('Tài khoản chủ trọ không hợp lệ.', 422);
        $stmt = $pdo->prepare("SELECT * FROM TaiKhoan WHERE Id=:id AND VaiTro='chutro' AND IsDeleted=0");
        $stmt->execute([':id'=>$id]);
        $owner = $stmt->fetch();
        if (!$owner) errorResponse('Không tìm thấy tài khoản chủ trọ.', 404);
        return $owner;
    };

    if ($method === 'GET') {
        $sql = "SELECT tk.Id,tk.TenDangNhap,tk.HoTen,tk.Email,tk.SoDienThoai,
                       tk.TrangThai,tk.NgayTao,COUNT(k.Id) AS SoKhu,
                       GROUP_CONCAT(k.TenKhu, ', ') AS DanhSachKhu
                FROM TaiKhoan tk
                LEFT JOIN Khu k ON k.TaiKhoanId=tk.Id AND k.IsDeleted=0
                WHERE tk.VaiTro='chutro' AND tk.IsDeleted=0
                GROUP BY tk.Id,tk.TenDangNhap,tk.HoTen,tk.Email,tk.SoDienThoai,tk.TrangThai,tk.NgayTao
                ORDER BY tk.TrangThai DESC,tk.HoTen ASC";
        successResponse($pdo->query($sql)->fetchAll(), 'Danh sách chủ trọ');
    }

    if ($method === 'POST') {
        $input = readJsonBody();
        foreach (['TenDangNhap','MatKhau','HoTen'] as $field) {
            if (trim((string)($input[$field]??'')) === '') errorResponse('Thiếu dữ liệu: '.$field, 422);
        }
        if (strlen((string)$input['MatKhau']) < 6) errorResponse('Mật khẩu phải có ít nhất 6 ký tự.',422);
        $exists=$pdo->prepare('SELECT 1 FROM TaiKhoan WHERE TenDangNhap=:username');$exists->execute([':username'=>trim($input['TenDangNhap'])]);
        if($exists->fetch())errorResponse('Tên đăng nhập đã tồn tại.',409);
        $pdo->prepare("INSERT INTO TaiKhoan(TenDangNhap,MatKhau,HoTen,Email,SoDienThoai,VaiTro,TrangThai) VALUES(:username,:password,:name,:email,:phone,'chutro',1)")->execute([
            ':username'=>trim($input['TenDangNhap']), ':password'=>password_hash((string)$input['MatKhau'],PASSWORD_BCRYPT),
            ':name'=>trim($input['HoTen']), ':email'=>trim((string)($input['Email']??''))?:null,
            ':phone'=>trim((string)($input['SoDienThoai']??''))?:null
        ]);
        successResponse(['id'=>$pdo->lastInsertId()],'Đã tạo tài khoản chủ trọ.',201);
    }

    $id=(int)($_GET['id']??0);$assertOwner($id);$input=$method==='PUT'?readJsonBody():[];
    if($method==='PUT'&&$action==='sua'){
        $name=trim((string)($input['HoTen']??''));if($name==='')errorResponse('Họ tên không được để trống.',422);
        $pdo->prepare('UPDATE TaiKhoan SET HoTen=:name,Email=:email,SoDienThoai=:phone WHERE Id=:id')->execute([':name'=>$name,':email'=>trim((string)($input['Email']??''))?:null,':phone'=>trim((string)($input['SoDienThoai']??''))?:null,':id'=>$id]);
        successResponse([],'Đã cập nhật chủ trọ.');
    }
    if($method==='PUT'&&$action==='capLaiMatKhau'){
        $password=(string)($input['MatKhau']??'');if(strlen($password)<6)errorResponse('Mật khẩu mới phải có ít nhất 6 ký tự.',422);
        $pdo->prepare('UPDATE TaiKhoan SET MatKhau=:password WHERE Id=:id')->execute([':password'=>password_hash($password,PASSWORD_BCRYPT),':id'=>$id]);
        successResponse([],'Đã cấp lại mật khẩu chủ trọ.');
    }
    if($method==='PUT'&&$action==='trangThai'){
        $status=(int)($input['TrangThai']??0)===1?1:0;$pdo->prepare('UPDATE TaiKhoan SET TrangThai=:status WHERE Id=:id')->execute([':status'=>$status,':id'=>$id]);
        successResponse(['TrangThai'=>$status],$status?'Đã mở khóa chủ trọ.':'Đã khóa chủ trọ.');
    }
    if($method==='DELETE'){
        $pdo->prepare('UPDATE TaiKhoan SET IsDeleted=1,TrangThai=0 WHERE Id=:id')->execute([':id'=>$id]);
        successResponse([],'Đã xóa tài khoản chủ trọ.');
    }
    errorResponse('Phương thức hoặc thao tác không được hỗ trợ.',405);
} catch(Throwable $e){errorResponse($e->getMessage(),500);}
