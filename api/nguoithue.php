<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/response.php';

try {
    $pdo = getDb();
    requireRole(['admin', 'chutro']);
    $user = currentUser(); $isAdmin = ($user['VaiTro'] ?? '') === 'admin';
    $method = $_SERVER['REQUEST_METHOD'];
    $canManage = function (int $tenantId) use ($pdo, $user, $isAdmin): bool {
        if ($isAdmin) return true;
        $stmt = $pdo->prepare('SELECT 1 FROM HopDong h JOIN Phong p ON p.Id=h.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE h.NguoiThueId=:tenant AND h.IsDeleted=0 AND h.TrangThai IN ("DangHieuLuc","GiaHan") AND k.TaiKhoanId=:owner LIMIT 1');
        $stmt->execute([':tenant' => $tenantId, ':owner' => $user['Id']]);
        return (bool)$stmt->fetch();
    };

    if ($method === 'GET') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            if (!$canManage($id)) errorResponse('Không tìm thấy người thuê hoặc bạn không có quyền truy cập.', 404);
            $stmt = $pdo->prepare('SELECT * FROM NguoiThue WHERE Id=:id AND IsDeleted=0'); $stmt->execute([':id' => $id]);
            $tenant = $stmt->fetch(); if (!$tenant) errorResponse('Không tìm thấy người thuê.', 404);
            successResponse($tenant, 'Chi tiết người thuê');
        }
        $keyword = trim((string)($_GET['tuKhoa'] ?? ''));
        $owner = $isAdmin ? '' : ' AND k.TaiKhoanId=:owner';
        $params = $isAdmin ? [] : [':owner' => $user['Id']];
        $primary = 'SELECT nt.Id,nt.HoTen,nt.CCCD,nt.NgaySinh,nt.GioiTinh,nt.SoDienThoai,nt.Email,nt.DiaChiThuongTru,nt.NgheNghiep,nt.TaiKhoanId,nt.AnhChanDung,nt.AnhCCCDMatTruoc,nt.AnhCCCDMatSau,nt.VNeIDMuc2,NULL AS TrangThaiTamTru,p.SoPhong,d.TenDay,k.TenKhu,h.Id AS HopDongId,h.SoHopDong,h.NgayBatDau,h.NgayKetThuc,h.GiaThue,h.TienCoc,h.TrangThai AS TrangThaiHopDong,"NguoiKyHopDong" AS LoaiThanhVien FROM NguoiThue nt JOIN HopDong h ON h.NguoiThueId=nt.Id AND h.IsDeleted=0 AND h.TrangThai IN ("DangHieuLuc","GiaHan") JOIN Phong p ON p.Id=h.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE nt.IsDeleted=0' . $owner;
        $members = 'SELECT -tv.Id AS Id,tv.HoTen,tv.CCCD,tv.NgaySinh,tv.GioiTinh,tv.SoDienThoai,tv.Email,tv.DiaChiThuongTru,tv.NgheNghiep,tv.TaiKhoanId,tv.AnhChanDung,tv.AnhCCCDMatTruoc,tv.AnhCCCDMatSau,tv.VNeIDMuc2,tv.TrangThaiTamTru,p.SoPhong,d.TenDay,k.TenKhu,h.Id AS HopDongId,h.SoHopDong,h.NgayBatDau,h.NgayKetThuc,h.GiaThue,h.TienCoc,h.TrangThai AS TrangThaiHopDong,tv.Loai AS LoaiThanhVien FROM ThanhVienPhong tv JOIN HopDong h ON h.Id=tv.HopDongId AND h.IsDeleted=0 AND h.TrangThai IN ("DangHieuLuc","GiaHan") JOIN Phong p ON p.Id=h.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE tv.IsDeleted=0 AND NOT EXISTS(SELECT 1 FROM NguoiThue nt2 WHERE nt2.TaiKhoanId=tv.TaiKhoanId AND nt2.IsDeleted=0)' . $owner;
        $sql = 'SELECT * FROM (' . $primary . ' UNION ALL ' . $members . ') AS ds';
        if ($keyword !== '') { $sql .= ' WHERE (HoTen LIKE :keyword OR CCCD LIKE :keyword OR SoDienThoai LIKE :keyword OR SoPhong LIKE :keyword)'; $params[':keyword'] = '%' . $keyword . '%'; }
        $stmt = $pdo->prepare($sql . ' ORDER BY TenKhu,TenDay,SoPhong,HoTen'); $stmt->execute($params);
        $rawItems = $stmt->fetchAll(); $items=[]; $grouped=[];
        foreach ($rawItems as $row) {
            $key = ($row['LoaiThanhVien'] === 'NguoiKyHopDong' ? 'tenant:' : 'member:') . ($row['TaiKhoanId'] ?: $row['CCCD']);
            $roomLabel = trim(($row['TenKhu'] ?? '') . ' · ' . ($row['TenDay'] ?? '') . ' · Phòng ' . ($row['SoPhong'] ?? ''));
            if (!isset($grouped[$key])) { $row['DanhSachPhong'] = [$roomLabel]; $row['DanhSachHopDong'] = [[ 'Id'=>$row['HopDongId'], 'SoHopDong'=>$row['SoHopDong'], 'NgayBatDau'=>$row['NgayBatDau'], 'NgayKetThuc'=>$row['NgayKetThuc'] ]]; $grouped[$key] = $row; continue; }
            $grouped[$key]['DanhSachPhong'][] = $roomLabel;
            $grouped[$key]['DanhSachHopDong'][] = [ 'Id'=>$row['HopDongId'], 'SoHopDong'=>$row['SoHopDong'], 'NgayBatDau'=>$row['NgayBatDau'], 'NgayKetThuc'=>$row['NgayKetThuc'] ];
        }
        foreach ($grouped as $row) { $row['DanhSachPhong'] = array_values(array_unique($row['DanhSachPhong'])); $row['DanhSachHopDong'] = array_values(array_unique($row['DanhSachHopDong'], SORT_REGULAR)); $items[] = $row; }
        $stayStatus = $pdo->prepare('SELECT TrangThai,TrangThaiXuLy FROM TamTru WHERE CCCDKhach=:cccd AND IsDeleted=0 ORDER BY Id DESC LIMIT 1');
        foreach ($items as &$item) { $stayStatus->execute([':cccd' => $item['CCCD']]); $stay=$stayStatus->fetch() ?: []; $saved=$item['TrangThaiTamTru']??''; if(in_array($saved,['DaDangKyUBND','DangKhaiBaoUBND','ChuaKhaiBaoUBND'],true))$status=$saved; elseif($saved==='DaDangKyTamTru'||(int)($stay['TrangThai']??0)===1)$status='DaDangKyUBND'; elseif(($stay['TrangThaiXuLy']??'')==='ChoChuTroXacNhan')$status='DangKhaiBaoUBND'; else $status='ChuaKhaiBaoUBND'; $item['TrangThaiDangKy']=$status; $item['DaDangKyTamTru']=$status==='DaDangKyUBND'?1:0; }
        unset($item);
        successResponse($items, 'Danh sách người thuê và thành viên phòng');
    }
    if ($method === 'POST') errorResponse('Người thuê phải được tạo khi xác nhận hợp đồng; không thể thêm độc lập.', 409);
    $id = (int)($_GET['id'] ?? 0);
    if ($method === 'PUT' && $id < 0) {
        $memberId = abs($id); $input = readJsonBody();
        $sql='SELECT tv.Id FROM ThanhVienPhong tv JOIN HopDong h ON h.Id=tv.HopDongId AND h.IsDeleted=0 JOIN Phong p ON p.Id=h.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE tv.Id=:id AND tv.IsDeleted=0';$params=[':id'=>$memberId];if(!$isAdmin){$sql.=' AND k.TaiKhoanId=:owner';$params[':owner']=$user['Id'];}$check=$pdo->prepare($sql);$check->execute($params);if(!$check->fetch())errorResponse('Bạn không có quyền cập nhật thành viên này.',403);
        $stay=$input['TrangThaiDangKy']??((($input['DaDangKyTamTru']??0)?'DaDangKyUBND':'ChuaKhaiBaoUBND')); if(!in_array($stay,['ChuaKhaiBaoUBND','DangKhaiBaoUBND','DaDangKyUBND'],true))$stay='ChuaKhaiBaoUBND';
        $pdo->prepare('UPDATE ThanhVienPhong SET HoTen=:name,NgaySinh=:birthday,GioiTinh=:gender,SoDienThoai=:phone,Email=:email,DiaChiThuongTru=:address,NgheNghiep=:job,AnhChanDung=:portrait,AnhCCCDMatTruoc=:front,AnhCCCDMatSau=:back,VNeIDMuc2=:vneid,TrangThaiTamTru=:stay WHERE Id=:id AND IsDeleted=0')->execute([':name'=>$input['HoTen']??'',':birthday'=>$input['NgaySinh']??null,':gender'=>$input['GioiTinh']??null,':phone'=>$input['SoDienThoai']??null,':email'=>$input['Email']??null,':address'=>$input['DiaChiThuongTru']??null,':job'=>$input['NgheNghiep']??null,':portrait'=>$input['AnhChanDung']??null,':front'=>$input['AnhCCCDMatTruoc']??null,':back'=>$input['AnhCCCDMatSau']??null,':vneid'=>$input['VNeIDMuc2']??null,':stay'=>$stay,':id'=>$memberId]);
        successResponse([], 'Đã cập nhật thành viên phòng.');
    }
    if (!$canManage($id)) errorResponse('Bạn không có quyền thao tác người thuê này.', 403);
    if ($method === 'PUT') {
        $input = readJsonBody();
        $stmt = $pdo->prepare('UPDATE NguoiThue SET HoTen=:name,NgaySinh=:birthday,GioiTinh=:gender,SoDienThoai=:phone,Email=:email,DiaChiThuongTru=:address,NgheNghiep=:job,AnhChanDung=:portrait,AnhCCCDMatTruoc=:front,AnhCCCDMatSau=:back,VNeIDMuc2=:vneid WHERE Id=:id AND IsDeleted=0');
        $stmt->execute([':name' => $input['HoTen'] ?? '', ':birthday' => $input['NgaySinh'] ?? null, ':gender' => $input['GioiTinh'] ?? null, ':phone' => $input['SoDienThoai'] ?? null, ':email' => $input['Email'] ?? null, ':address' => $input['DiaChiThuongTru'] ?? null, ':job' => $input['NgheNghiep'] ?? null, ':portrait'=>$input['AnhChanDung']??null, ':front'=>$input['AnhCCCDMatTruoc']??null, ':back'=>$input['AnhCCCDMatSau']??null, ':vneid'=>$input['VNeIDMuc2']??null, ':id' => $id]);
        if (array_key_exists('TrangThaiDangKy', $input) || array_key_exists('DaDangKyTamTru', $input)) { $status=$input['TrangThaiDangKy']??((int)($input['DaDangKyTamTru']??0)?'DaDangKyUBND':'ChuaKhaiBaoUBND');if(!in_array($status,['ChuaKhaiBaoUBND','DangKhaiBaoUBND','DaDangKyUBND'],true))$status='ChuaKhaiBaoUBND';$cccd=$pdo->prepare('SELECT CCCD,HoTen FROM NguoiThue WHERE Id=:id');$cccd->execute([':id'=>$id]);$person=$cccd->fetch();$process=$status==='DangKhaiBaoUBND'?'ChoChuTroXacNhan':'DaXacNhanChuTro';$update=$pdo->prepare('UPDATE TamTru SET TrangThai=:registered,TrangThaiXuLy=:process WHERE CCCDKhach=:cccd AND IsDeleted=0');$update->execute([':registered'=>$status==='DaDangKyUBND'?1:0,':process'=>$process,':cccd'=>$person['CCCD']]);if($update->rowCount()===0){$room=$pdo->prepare('SELECT h.PhongId FROM HopDong h WHERE h.NguoiThueId=:tenant AND h.IsDeleted=0 AND h.TrangThai IN ("DangHieuLuc","GiaHan") ORDER BY h.Id DESC LIMIT 1');$room->execute([':tenant'=>$id]);$roomId=$room->fetchColumn();if($roomId)$pdo->prepare('INSERT INTO TamTru(PhongId,NguoiThueId,HoTen,CCCDKhach,QuanHe,NgayBatDau,NgayKetThuc,GhiChu,TrangThai,Loai,TrangThaiXuLy) VALUES(?,?,?,?,?,?,?,?,?,?,?)')->execute([$roomId,$id,$person['HoTen'],$person['CCCD'],'Người thuê','2026-01-01','2030-12-31','Cập nhật từ hồ sơ người thuê',$status==='DaDangKyUBND'?1:0,'TamTru',$process]);} }
        successResponse([], 'Cập nhật người thuê thành công.');
    }
    if ($method === 'DELETE') {
        $active = $pdo->prepare('SELECT 1 FROM HopDong WHERE NguoiThueId=:id AND IsDeleted=0 AND TrangThai IN ("DangHieuLuc","GiaHan")'); $active->execute([':id' => $id]);
        if ($active->fetch()) errorResponse('Không thể xóa người thuê đang có hợp đồng hiệu lực.', 409);
        $pdo->prepare('UPDATE NguoiThue SET IsDeleted=1 WHERE Id=:id AND IsDeleted=0')->execute([':id' => $id]);
        successResponse([], 'Đã chuyển người thuê vào trạng thái đã xóa.');
    }
    errorResponse('Phương thức không hỗ trợ.', 405);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 500);
}
