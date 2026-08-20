<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/response.php';

try {
    $pdo=getDb(); requireRole(['admin','chutro','nguoithue']);
    $user=currentUser(); $role=$user['VaiTro']??''; $isTenant=$role==='nguoithue'; $method=$_SERVER['REQUEST_METHOD'];
    $assertRoom=function(int $roomId)use($pdo,$user,$role):array{
        $sql='SELECT p.Id,p.SoPhong,k.TaiKhoanId AS ChuTroId FROM Phong p JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE p.Id=:room AND p.IsDeleted=0';$params=[':room'=>$roomId];
        if($role==='chutro'){$sql.=' AND k.TaiKhoanId=:owner';$params[':owner']=$user['Id'];}
        if($role==='nguoithue'){$sql.=' AND EXISTS(SELECT 1 FROM HopDong h JOIN NguoiThue nt ON nt.Id=h.NguoiThueId AND nt.IsDeleted=0 WHERE h.PhongId=p.Id AND h.IsDeleted=0 AND h.TrangThai IN ("DangHieuLuc","GiaHan") AND (nt.TaiKhoanId=:tenant OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=h.Id AND tv.TaiKhoanId=:tenant AND tv.IsDeleted=0)))';$params[':tenant']=$user['Id'];}
        $stmt=$pdo->prepare($sql);$stmt->execute($params);$room=$stmt->fetch();if(!$room)errorResponse('Không tìm thấy phòng hoặc bạn không có quyền truy cập.',404);return $room;
    };
    $stayById=function(int $id)use($pdo):array{$stmt=$pdo->prepare('SELECT * FROM TamTru WHERE Id=:id AND IsDeleted=0');$stmt->execute([':id'=>$id]);$stay=$stmt->fetch();if(!$stay)errorResponse('Không tìm thấy khai báo.',404);return $stay;};
    $notifyTenant=function(?int $tenantId,string $title,string $content)use($pdo):void{if(!$tenantId)return;$stmt=$pdo->prepare('SELECT TaiKhoanId FROM NguoiThue WHERE Id=:id AND IsDeleted=0');$stmt->execute([':id'=>$tenantId]);if($account=$stmt->fetchColumn())$pdo->prepare('INSERT INTO ThongBao(TaiKhoanId,TieuDe,NoiDung,Loai) VALUES(:user,:title,:content,"ThongTin")')->execute([':user'=>$account,':title'=>$title,':content'=>$content]);};

    if($method==='GET'){
        if(($_GET['action']??'')==='phong'){
            $sql='SELECT DISTINCT p.Id,p.SoPhong,d.TenDay,k.TenKhu FROM Phong p JOIN Day d ON d.Id=p.DayId AND COALESCE(d.IsDeleted,0)=0 JOIN Khu k ON k.Id=d.KhuId AND COALESCE(k.IsDeleted,0)=0 WHERE COALESCE(p.IsDeleted,0)=0';$params=[];
            if($isTenant){$sql.=' AND EXISTS(SELECT 1 FROM HopDong h JOIN NguoiThue n ON n.Id=h.NguoiThueId AND COALESCE(n.IsDeleted,0)=0 WHERE h.PhongId=p.Id AND COALESCE(h.IsDeleted,0)=0 AND h.TrangThai IN ("DangHieuLuc","GiaHan") AND (n.TaiKhoanId=:tenant OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=h.Id AND tv.TaiKhoanId=:tenant AND COALESCE(tv.IsDeleted,0)=0)))';$params[':tenant']=$user['Id'];}
            elseif($role==='chutro'){$sql.=' AND k.TaiKhoanId=:owner';$params[':owner']=$user['Id'];}
            $stmt=$pdo->prepare($sql.' ORDER BY k.TenKhu,d.TenDay,p.SoPhong');$stmt->execute($params);successResponse($stmt->fetchAll(),'Danh sách phòng được phép khai báo');
        }
        $sql='SELECT t.*,p.SoPhong,d.TenDay,k.TenKhu,nt.HoTen AS NguoiThue FROM TamTru t JOIN Phong p ON p.Id=t.PhongId AND COALESCE(p.IsDeleted,0)=0 JOIN Day d ON d.Id=p.DayId AND COALESCE(d.IsDeleted,0)=0 JOIN Khu k ON k.Id=d.KhuId AND COALESCE(k.IsDeleted,0)=0 LEFT JOIN NguoiThue nt ON nt.Id=t.NguoiThueId AND COALESCE(nt.IsDeleted,0)=0 WHERE COALESCE(t.IsDeleted,0)=0';$params=[];
        if(($_GET['loai']??'')!==''){$sql.=' AND t.Loai=:type';$params[':type']=$_GET['loai'];}
        if($isTenant){$sql.=' AND EXISTS(SELECT 1 FROM HopDong h JOIN NguoiThue n ON n.Id=h.NguoiThueId AND n.IsDeleted=0 WHERE h.PhongId=t.PhongId AND h.IsDeleted=0 AND h.TrangThai IN ("DangHieuLuc","GiaHan") AND (n.TaiKhoanId=:tenant OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=h.Id AND tv.TaiKhoanId=:tenant AND tv.IsDeleted=0)))';$params[':tenant']=$user['Id'];}
        elseif($role==='chutro'){$sql.=' AND k.TaiKhoanId=:owner';$params[':owner']=$user['Id'];}
        $stmt=$pdo->prepare($sql.' ORDER BY t.NgayBatDau DESC,t.Id DESC');$stmt->execute($params);successResponse($stmt->fetchAll(),'Danh sách tạm trú / lưu trú');
    }
    if($method==='POST'){
        $input=readJsonBody();foreach(['PhongId','HoTen','CCCDKhach','QuanHe','NgayBatDau','NgayKetThuc'] as $field)if(trim((string)($input[$field]??''))==='')errorResponse('Thiếu dữ liệu: '.$field,422);
        if(($input['NgayKetThuc']??'')<($input['NgayBatDau']??''))errorResponse('Ngày kết thúc phải sau ngày bắt đầu.',422);
        $room=$assertRoom((int)$input['PhongId']);$tenantId=$input['NguoiThueId']??null;
        if($isTenant){$self=$pdo->prepare('SELECT Id FROM NguoiThue WHERE TaiKhoanId=:user AND IsDeleted=0');$self->execute([':user'=>$user['Id']]);$tenantId=$self->fetchColumn()?:null;}
        $type=$isTenant?'LuuTru':(($input['Loai']??'TamTru')==='LuuTru'?'LuuTru':'TamTru');$ubnd=$isTenant?'ChuaKhaiBaoUBND':($input['TrangThaiDangKy']??null);if(!in_array($ubnd,['ChuaKhaiBaoUBND','DangKhaiBaoUBND','DaDangKyUBND'],true))$ubnd=(int)($input['TrangThai']??0)?'DaDangKyUBND':'ChuaKhaiBaoUBND';$status=$ubnd==='DaDangKyUBND'?1:0;$process=$isTenant?'ChoChuTroXacNhan':($ubnd==='DangKhaiBaoUBND'?'ChoChuTroXacNhan':'DaXacNhanChuTro');
        $pdo->prepare('INSERT INTO TamTru(PhongId,NguoiThueId,HoTen,CCCDKhach,QuanHe,NgayBatDau,NgayKetThuc,GhiChu,TrangThai,Loai,TrangThaiXuLy,TrangThaiDangKy) VALUES(:room,:tenant,:name,:cccd,:relation,:start,:end,:note,:status,:type,:process,:registration)')->execute([':room'=>$room['Id'],':tenant'=>$tenantId,':name'=>$input['HoTen'],':cccd'=>$input['CCCDKhach'],':relation'=>$input['QuanHe'],':start'=>$input['NgayBatDau'],':end'=>$input['NgayKetThuc'],':note'=>$input['GhiChu']??null,':status'=>$status,':type'=>$type,':process'=>$process,':registration'=>$ubnd]);
        if($isTenant && $room['ChuTroId'])$pdo->prepare('INSERT INTO ThongBao(TaiKhoanId,TieuDe,NoiDung,Loai) VALUES(:user,"Yêu cầu khai báo lưu trú",:content,"ThongTin")')->execute([':user'=>$room['ChuTroId'],':content'=>'Phòng '.$room['SoPhong'].' có khai báo lưu trú mới chờ xác nhận.']);
        successResponse(['id'=>$pdo->lastInsertId()],'Đã gửi khai báo '.($isTenant?'lưu trú, chờ chủ trọ xác nhận.':'thành công.'),201);
    }
    if($method==='PUT'){
        if($isTenant)errorResponse('Người thuê không thể sửa trực tiếp sau khi đã gửi khai báo.',403);
        $id=(int)($_GET['id']??0);$old=$stayById($id);$assertRoom((int)$old['PhongId']);$input=readJsonBody();
        $decision=$input['QuyetDinh']??'';
        if($old['Loai']==='LuuTru' && $old['TrangThaiXuLy']==='ChoChuTroXacNhan' && !in_array($decision,['XacNhan','TuChoi'],true))errorResponse('Chủ trọ cần xác nhận hoặc từ chối yêu cầu lưu trú trước khi cập nhật trạng thái UBND.',422);
        if($decision==='XacNhan'){$ubnd='ChuaKhaiBaoUBND';$process='DaXacNhanChuTro';$status=0;}
        elseif($decision==='TuChoi'){$ubnd='ChuaKhaiBaoUBND';$process='TuChoi';$status=0;}
        else{$ubnd=$input['TrangThaiDangKy']??null;if(!in_array($ubnd,['ChuaKhaiBaoUBND','DangKhaiBaoUBND','DaDangKyUBND'],true))$ubnd=(int)($input['TrangThai']??$old['TrangThai'])?'DaDangKyUBND':'ChuaKhaiBaoUBND';$process='DaXacNhanChuTro';$status=$ubnd==='DaDangKyUBND'?1:0;}
        $newRoom=(int)($input['PhongId']??$old['PhongId']);$assertRoom($newRoom);
        $pdo->prepare('UPDATE TamTru SET PhongId=:room,NguoiThueId=:tenant,HoTen=:name,CCCDKhach=:cccd,QuanHe=:relation,NgayBatDau=:start,NgayKetThuc=:end,GhiChu=:note,TrangThai=:status,Loai=:type,TrangThaiXuLy=:process,TrangThaiDangKy=:registration WHERE Id=:id AND IsDeleted=0')->execute([':room'=>$newRoom,':tenant'=>$input['NguoiThueId']??$old['NguoiThueId'],':name'=>$input['HoTen']??$old['HoTen'],':cccd'=>$input['CCCDKhach']??$old['CCCDKhach'],':relation'=>$input['QuanHe']??$old['QuanHe'],':start'=>$input['NgayBatDau']??$old['NgayBatDau'],':end'=>$input['NgayKetThuc']??$old['NgayKetThuc'],':note'=>$input['GhiChu']??$old['GhiChu'],':status'=>$status,':type'=>$input['Loai']??$old['Loai'],':process'=>$process,':registration'=>$ubnd,':id'=>$id]);
        $notifyTenant($old['NguoiThueId'],'Cập nhật khai báo '.(($input['Loai']??$old['Loai'])==='LuuTru'?'lưu trú':'tạm trú'),$decision==='XacNhan'?'Chủ trọ đã xác nhận người lưu trú. Trạng thái đăng ký UBND sẽ được cập nhật riêng.':($process==='TuChoi'?'Chủ trọ đã từ chối khai báo, vui lòng kiểm tra lại.':($status===1?'Đã đăng ký với UBND Phường/Xã.':'Trạng thái đăng ký UBND của khai báo đã được cập nhật.')));
        successResponse([],'Đã cập nhật khai báo.');
    }
    if($method==='DELETE'){
        if($isTenant)errorResponse('Người thuê không có quyền xóa khai báo lưu trú.',403);$old=$stayById((int)($_GET['id']??0));$assertRoom((int)$old['PhongId']);$pdo->prepare('UPDATE TamTru SET IsDeleted=1 WHERE Id=:id AND IsDeleted=0')->execute([':id'=>$old['Id']]);successResponse([],'Đã chuyển khai báo vào trạng thái đã xóa.');
    }
    errorResponse('Phương thức không hỗ trợ.',405);
}catch(Throwable $e){errorResponse($e->getMessage(),500);}
