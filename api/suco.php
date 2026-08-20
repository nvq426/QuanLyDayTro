<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/response.php';

try {
    $pdo=getDb();requireRole(['admin','chutro','nguoithue']);$user=currentUser();$role=$user['VaiTro']??'';$method=$_SERVER['REQUEST_METHOD'];
    $tenantRoom=function()use($pdo,$user){$stmt=$pdo->prepare('SELECT p.Id,p.SoPhong,k.TaiKhoanId AS ChuTroId FROM HopDong h JOIN Phong p ON p.Id=h.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 JOIN NguoiThue nt ON nt.Id=h.NguoiThueId AND nt.IsDeleted=0 WHERE h.IsDeleted=0 AND h.TrangThai IN ("DangHieuLuc","GiaHan") AND (nt.TaiKhoanId=:user OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=h.Id AND tv.TaiKhoanId=:user AND tv.IsDeleted=0)) LIMIT 1');$stmt->execute([':user'=>$user['Id']]);return $stmt->fetch();};
    $canManage=function(int $id)use($pdo,$user,$role):array{$sql='SELECT s.*,p.SoPhong,k.TaiKhoanId AS ChuTroId FROM SuCo s JOIN Phong p ON p.Id=s.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE s.Id=:id AND s.IsDeleted=0';$params=[':id'=>$id];if($role==='chutro'){$sql.=' AND k.TaiKhoanId=:owner';$params[':owner']=$user['Id'];}$stmt=$pdo->prepare($sql);$stmt->execute($params);$row=$stmt->fetch();if(!$row)errorResponse('Không tìm thấy sự cố hoặc bạn không có quyền xử lý.',404);return $row;};
    if($method==='GET'){
        $sql='SELECT s.*,p.SoPhong,u.HoTen AS NguoiBao FROM SuCo s JOIN Phong p ON p.Id=s.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 JOIN TaiKhoan u ON u.Id=s.NguoiBaoId AND u.IsDeleted=0 WHERE s.IsDeleted=0';$params=[];
        if($role==='nguoithue'){$sql.=' AND s.NguoiBaoId=:user';$params[':user']=$user['Id'];}elseif($role==='chutro'){$sql.=' AND k.TaiKhoanId=:owner';$params[':owner']=$user['Id'];}
        $stmt=$pdo->prepare($sql.' ORDER BY s.Id DESC');$stmt->execute($params);successResponse($stmt->fetchAll(),'Danh sách sự cố');
    }
    if($method==='POST'){
        if($role!=='nguoithue')errorResponse('Chỉ người thuê được báo cáo sự cố.',403);$input=readJsonBody();if(trim((string)($input['TieuDe']??''))===''||trim((string)($input['NoiDung']??''))==='')errorResponse('Cần nhập tiêu đề và nội dung sự cố.',422);$room=$tenantRoom();if(!$room)errorResponse('Không tìm thấy phòng đang thuê.',404);
        $pdo->prepare('INSERT INTO SuCo(PhongId,NguoiBaoId,TieuDe,NoiDung,AnhDinhKem) VALUES(:room,:user,:title,:content,:image)')->execute([':room'=>$room['Id'],':user'=>$user['Id'],':title'=>$input['TieuDe'],':content'=>$input['NoiDung'],':image'=>$input['AnhDinhKem']??null]);
        if($room['ChuTroId'])$pdo->prepare('INSERT INTO ThongBao(TaiKhoanId,TieuDe,NoiDung,Loai) VALUES(:user,"Báo cáo sự cố mới",:content,"ThongTin")')->execute([':user'=>$room['ChuTroId'],':content'=>'Phòng '.$room['SoPhong'].': '.$input['TieuDe']]);successResponse(['id'=>$pdo->lastInsertId()],'Đã gửi sự cố cho chủ trọ.',201);
    }
    if($method==='PUT'){
        if(!in_array($role,['admin','chutro'],true))errorResponse('Chỉ chủ trọ được xử lý sự cố.',403);$row=$canManage((int)($_GET['id']??0));$input=readJsonBody();$status=$input['TrangThai']??'';if(!in_array($status,['DaTiepNhan','DaKhacPhuc'],true))errorResponse('Trạng thái không hợp lệ.',422);
        $pdo->prepare('UPDATE SuCo SET TrangThai=:status,NguoiXuLyId=:processor,NgayCapNhat=datetime("now","localtime") WHERE Id=:id AND IsDeleted=0')->execute([':status'=>$status,':processor'=>$user['Id'],':id'=>$row['Id']]);
        $pdo->prepare('INSERT INTO ThongBao(TaiKhoanId,TieuDe,NoiDung,Loai) VALUES(:user,"Cập nhật sự cố",:content,"ThongTin")')->execute([':user'=>$row['NguoiBaoId'],':content'=>'Sự cố “'.$row['TieuDe'].'” '.($status==='DaKhacPhuc'?'đã được khắc phục.':'đã được tiếp nhận.')]);successResponse([],'Đã cập nhật sự cố.');
    }
    if($method==='DELETE'){
        $id=(int)($_GET['id']??0);if($role==='nguoithue'){$stmt=$pdo->prepare('SELECT Id FROM SuCo WHERE Id=:id AND NguoiBaoId=:user AND IsDeleted=0');$stmt->execute([':id'=>$id,':user'=>$user['Id']]);if(!$stmt->fetch())errorResponse('Bạn không có quyền xóa sự cố này.',403);}else{$canManage($id);}$pdo->prepare('UPDATE SuCo SET IsDeleted=1 WHERE Id=:id AND IsDeleted=0')->execute([':id'=>$id]);successResponse([],'Đã chuyển sự cố vào trạng thái đã xóa.');
    }
    errorResponse('Phương thức không hỗ trợ.',405);
}catch(Throwable $e){errorResponse($e->getMessage(),500);}
