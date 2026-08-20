<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/response.php';

try {
    $pdo = getDb();
    requireRole(['nguoithue', 'chutro', 'admin']);
    $user = currentUser();
    $role = $user['VaiTro'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    $findTenant = function () use ($pdo, $user) {
        $stmt = $pdo->prepare('SELECT * FROM NguoiThue WHERE TaiKhoanId=:user AND IsDeleted=0 LIMIT 1');
        $stmt->execute([':user' => $user['Id']]);
        $profile=$stmt->fetch();
        if($profile){$profile['_NguonHoSo']='NguoiThue';$profile['_NguoiThueIdChinh']=$profile['Id'];return $profile;}
        $member=$pdo->prepare('SELECT tv.*,h.NguoiThueId AS _NguoiThueIdChinh FROM ThanhVienPhong tv JOIN HopDong h ON h.Id=tv.HopDongId AND COALESCE(h.IsDeleted,0)=0 AND h.TrangThai IN ("DangHieuLuc","GiaHan") WHERE tv.TaiKhoanId=:user AND COALESCE(tv.IsDeleted,0)=0 ORDER BY h.Id DESC LIMIT 1');
        $member->execute([':user'=>$user['Id']]);$profile=$member->fetch();if($profile){$profile['_NguonHoSo']='ThanhVienPhong';$profile['_ThanhVienPhongId']=$profile['Id'];}return $profile;
    };
    $ownerCanManageTenant = function (int $tenantId) use ($pdo, $user, $role): bool {
        if ($role === 'admin') return true;
        $stmt = $pdo->prepare("SELECT 1 FROM HopDong h
            JOIN Phong p ON p.Id=h.PhongId AND p.IsDeleted=0
            JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0
            JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0
            WHERE h.NguoiThueId=:tenant AND h.IsDeleted=0
              AND h.TrangThai IN ('DangHieuLuc','GiaHan') AND k.TaiKhoanId=:owner LIMIT 1");
        $stmt->execute([':tenant' => $tenantId, ':owner' => $user['Id']]);
        return (bool)$stmt->fetch();
    };

    if ($role === 'nguoithue') {
        $tenant = $findTenant();
        if (!$tenant) errorResponse('Không tìm thấy hồ sơ người thuê.', 404);
        if ($method === 'GET') successResponse($tenant, 'Hồ sơ cá nhân');
        if ($method !== 'POST') errorResponse('Người thuê chỉ có thể gửi yêu cầu cập nhật hồ sơ.', 403);
        $input = readJsonBody();
        if(($tenant['_NguonHoSo']??'')==='ThanhVienPhong'){$input['_ThanhVienPhongId']=(int)$tenant['_ThanhVienPhongId'];$input['_TaiKhoanNhanThongBao']=(int)$user['Id'];}
        $mainTenantId=(int)($tenant['_NguoiThueIdChinh']??$tenant['Id']);
        $stmt = $pdo->prepare('INSERT INTO YeuCauHoSoNguoiThue(NguoiThueId,Loai,DuLieu) VALUES(:tenant,:type,:data)');
        $stmt->execute([':tenant'=>$mainTenantId,':type'=>($tenant['_NguonHoSo']??'')==='ThanhVienPhong'?'ThongTinCaNhan':'HoSo',':data'=>json_encode($input,JSON_UNESCAPED_UNICODE)]);
        $owners = $pdo->prepare("SELECT DISTINCT k.TaiKhoanId FROM HopDong h JOIN Phong p ON p.Id=h.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE h.NguoiThueId=:tenant AND h.IsDeleted=0 AND k.TaiKhoanId IS NOT NULL AND h.TrangThai IN ('DangHieuLuc','GiaHan')");
        $owners->execute([':tenant' => $mainTenantId]);
        $notice = $pdo->prepare('INSERT INTO ThongBao(TaiKhoanId,TieuDe,NoiDung,Loai) VALUES(:user,:title,:content,"ThongTin")');
        foreach ($owners->fetchAll() as $owner) $notice->execute([':user' => $owner['TaiKhoanId'], ':title' => 'Hồ sơ người thuê chờ xác nhận', ':content' => $tenant['HoTen'] . ' vừa gửi hồ sơ/thay đổi thông tin.']);
        successResponse(['id' => $pdo->lastInsertId()], 'Đã gửi hồ sơ, chờ chủ trọ xác nhận.', 201);
    }

    if ($method === 'GET') {
        $sql = 'SELECT y.*,n.HoTen,n.CCCD,n.TaiKhoanId FROM YeuCauHoSoNguoiThue y JOIN NguoiThue n ON n.Id=y.NguoiThueId WHERE y.IsDeleted=0 AND n.IsDeleted=0 AND y.TrangThai="ChoXacNhan"';
        $params = [];
        if ($role === 'chutro') {
            $sql .= " AND EXISTS(SELECT 1 FROM HopDong h JOIN Phong p ON p.Id=h.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE h.NguoiThueId=n.Id AND h.IsDeleted=0 AND h.TrangThai IN ('DangHieuLuc','GiaHan') AND k.TaiKhoanId=:owner)";
            $params[':owner'] = $user['Id'];
        }
        $stmt = $pdo->prepare($sql . ' ORDER BY y.Id DESC');
        $stmt->execute($params);
        $requests=$stmt->fetchAll();foreach($requests as &$requestItem){$requestData=json_decode($requestItem['DuLieu'],true)?:[];if(!empty($requestData['_ThanhVienPhongId'])){$member=$pdo->prepare('SELECT HoTen,CCCD,TaiKhoanId FROM ThanhVienPhong WHERE Id=:id AND IsDeleted=0');$member->execute([':id'=>(int)$requestData['_ThanhVienPhongId']]);if($memberData=$member->fetch()){$requestItem['HoTen']=$memberData['HoTen'];$requestItem['CCCD']=$memberData['CCCD'];$requestItem['TaiKhoanId']=$memberData['TaiKhoanId'];$requestItem['LaThanhVienPhong']=1;}}}unset($requestItem);
        successResponse($requests, 'Yêu cầu hồ sơ chờ xác nhận');
    }

    if ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        $input = readJsonBody();
        $approved = ($input['QuyetDinh'] ?? '') === 'XacNhan';
        $stmt = $pdo->prepare('SELECT y.*,n.TaiKhoanId FROM YeuCauHoSoNguoiThue y JOIN NguoiThue n ON n.Id=y.NguoiThueId WHERE y.Id=:id AND y.IsDeleted=0 AND y.TrangThai="ChoXacNhan" AND n.IsDeleted=0');
        $stmt->execute([':id' => $id]);
        $request = $stmt->fetch();
        if (!$request) errorResponse('Yêu cầu không tồn tại hoặc đã xử lý.', 404);
        if (!$ownerCanManageTenant((int)$request['NguoiThueId'])) errorResponse('Bạn không có quyền xử lý hồ sơ này.', 403);
        $pdo->beginTransaction();
        if ($approved) {
            $data = json_decode($request['DuLieu'], true) ?: [];
            $targetTable=!empty($data['_ThanhVienPhongId'])?'ThanhVienPhong':'NguoiThue';$targetId=!empty($data['_ThanhVienPhongId'])?(int)$data['_ThanhVienPhongId']:(int)$request['NguoiThueId'];
            $pdo->prepare("UPDATE {$targetTable} SET HoTen=:name,SoDienThoai=:phone,Email=:email,DiaChiThuongTru=:address,AnhChanDung=:portrait,AnhCCCDMatTruoc=:front,AnhCCCDMatSau=:back,VNeIDMuc2=:vneid WHERE Id=:id AND IsDeleted=0")->execute([':name' => $data['HoTen'] ?? '', ':phone' => $data['SoDienThoai'] ?? null, ':email' => $data['Email'] ?? null, ':address' => $data['DiaChiThuongTru'] ?? null, ':portrait' => $data['AnhChanDung'] ?? null, ':front' => $data['AnhCCCDMatTruoc'] ?? null, ':back' => $data['AnhCCCDMatSau'] ?? null, ':vneid' => $data['VNeIDMuc2'] ?? null, ':id'=>$targetId]);
        }
        $pdo->prepare('UPDATE YeuCauHoSoNguoiThue SET TrangThai=:status,GhiChuChuTro=:note,NguoiXuLyId=:processor,NgayXuLy=datetime("now","localtime") WHERE Id=:id')->execute([':status' => $approved ? 'DaXacNhan' : 'TuChoi', ':note' => $input['GhiChu'] ?? null, ':processor' => $user['Id'], ':id' => $id]);
        $requestData=json_decode($request['DuLieu'],true)?:[];$notifyAccount=(int)($requestData['_TaiKhoanNhanThongBao']??$request['TaiKhoanId']);
        if ($notifyAccount) $pdo->prepare('INSERT INTO ThongBao(TaiKhoanId,TieuDe,NoiDung,Loai) VALUES(:user,:title,:content,"ThongTin")')->execute([':user'=>$notifyAccount,':title'=>$approved?'Hồ sơ đã được xác nhận':'Hồ sơ bị từ chối',':content'=>$input['GhiChu']??($approved?'Thông tin cá nhân của bạn đã được cập nhật.':'Vui lòng kiểm tra và gửi lại hồ sơ.')]);
        $pdo->commit();
        successResponse([], $approved ? 'Đã xác nhận hồ sơ.' : 'Đã từ chối hồ sơ.');
    }
    errorResponse('Phương thức không hỗ trợ.', 405);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    errorResponse($e->getMessage(), 500);
}
