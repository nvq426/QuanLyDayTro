<?php
require __DIR__ . '/../includes/db.php'; require __DIR__ . '/../includes/auth.php'; require __DIR__ . '/../includes/response.php';
if (!function_exists('requireRole')) { function requireRole(array $allowedRoles): void { $current=currentUser(); if (!$current || !in_array($current['VaiTro']??'', $allowedRoles, true)) errorResponse('Bạn không có quyền truy cập.',403); } }
try {
    $pdo=getDb(); $method=$_SERVER['REQUEST_METHOD']; $user=currentUser(); requireRole(['admin','chutro']);
    $isAdmin=($user['VaiTro']??'')==='admin'; $ownerId=(int)($user['Id']??0);
    $base=' FROM Phong p JOIN Day d ON d.Id=p.DayId AND COALESCE(d.IsDeleted,0)=0 JOIN Khu k ON k.Id=d.KhuId AND COALESCE(k.IsDeleted,0)=0';
    $whereOwner=$isAdmin?' AND COALESCE(p.IsDeleted,0)=0':' AND COALESCE(p.IsDeleted,0)=0 AND k.TaiKhoanId=:owner';
    $assertRoom=function(int $id)use($pdo,$base,$whereOwner,$ownerId){$s=$pdo->prepare('SELECT p.*,d.TenDay,k.TenKhu'.$base.' WHERE p.Id=:id'.$whereOwner);$p=[':id'=>$id];if(strpos($whereOwner, ':owner') !== false)$p[':owner']=$ownerId;$s->execute($p);$r=$s->fetch();if(!$r)errorResponse('Không tìm thấy phòng hoặc bạn không có quyền truy cập',404);return $r;};
    $assertDay=function(int $id)use($pdo,$isAdmin,$ownerId){$s=$pdo->prepare('SELECT 1 FROM Day d JOIN Khu k ON k.Id=d.KhuId WHERE d.Id=:id'.($isAdmin?'':' AND k.TaiKhoanId=:owner'));$p=[':id'=>$id];if(!$isAdmin)$p[':owner']=$ownerId;$s->execute($p);if(!$s->fetch())errorResponse('Dãy không thuộc quyền quản lý của bạn',404);};
    if($method==='GET'){
        $id=(int)($_GET['id']??0);
        if($id && ($_GET['action']??'')==='chitiet'){
            $room=$assertRoom($id);
            $contract=$pdo->prepare("SELECT h.*,nt.HoTen,nt.CCCD,nt.SoDienThoai,nt.Email FROM HopDong h JOIN NguoiThue nt ON nt.Id=h.NguoiThueId AND nt.IsDeleted=0 WHERE h.PhongId=:id AND h.IsDeleted=0 AND h.TrangThai IN ('DangHieuLuc','GiaHan') ORDER BY h.Id DESC LIMIT 1");
            $contract->execute([':id'=>$id]); $hopDong=$contract->fetch();
            $members=[];
            if($hopDong){
                $members[]=['Loai'=>'Người ký hợp đồng','HoTen'=>$hopDong['HoTen'],'CCCD'=>$hopDong['CCCD'],'SoDienThoai'=>$hopDong['SoDienThoai'],'HopDongId'=>$hopDong['Id']];
                $s=$pdo->prepare('SELECT HoTen,CCCD,NgaySinh,SoDienThoai,QuanHe,Loai,TrangThaiTamTru FROM ThanhVienPhong WHERE HopDongId=:id AND IsDeleted=0 ORDER BY Id ASC');$s->execute([':id'=>$hopDong['Id']]);
                foreach($s->fetchAll() as $member){$member['VaiTro']=$member['QuanHe']?:'Thành viên';$member['HopDongId']=$hopDong['Id'];$members[]=$member;}
            }
            $invoice=$pdo->prepare('SELECT * FROM HoaDon WHERE HopDongId=:id AND IsDeleted=0 ORDER BY Nam DESC,Thang DESC'); if($hopDong){$invoice->execute([':id'=>$hopDong['Id']]);$hoaDonLichSu=$invoice->fetchAll();$hoaDon=$hoaDonLichSu[0]??null;}else{$hoaDon=null;$hoaDonLichSu=[];}
            $meter=$pdo->prepare('SELECT * FROM ChiSoDienNuoc WHERE PhongId=:id AND IsDeleted=0 ORDER BY Nam DESC,Thang DESC');$meter->execute([':id'=>$id]);$chiSoLichSu=$meter->fetchAll();
            $stay=$pdo->prepare('SELECT * FROM TamTru WHERE PhongId=:id AND IsDeleted=0 ORDER BY NgayBatDau DESC');$stay->execute([':id'=>$id]);$stays=$stay->fetchAll();
            foreach($members as &$member){$member['TamTru']=$member['CCCD']?array_values(array_filter($stays,fn($x)=>(string)$x['CCCDKhach']===(string)$member['CCCD'])):[];}unset($member);
            successResponse(['phong'=>$room,'hopDong'=>$hopDong,'thanhVien'=>$members,'hoaDon'=>$hoaDon,'hoaDonLichSu'=>$hoaDonLichSu,'chiSo'=>$chiSoLichSu[0]??null,'chiSoLichSu'=>$chiSoLichSu,'tamTru'=>$stays],'Chi tiết phòng');
        }
        if($id)successResponse($assertRoom($id),'Chi tiết phòng');
        $s=$pdo->prepare('SELECT p.*,d.TenDay,k.TenKhu'.$base.' WHERE 1=1'.$whereOwner.' ORDER BY p.Id ASC');$p=[];if(strpos($whereOwner, ':owner') !== false)$p[':owner']=$ownerId;$s->execute($p);successResponse($s->fetchAll(),'Danh sách phòng');
    }
    if($method==='POST'){$in=readJsonBody();if(empty($in['DayId'])||empty($in['SoPhong']))errorResponse('Thiếu DayId hoặc Số phòng',400);$assertDay((int)$in['DayId']);$s=$pdo->prepare('INSERT INTO Phong (DayId,SoPhong,DienTich,GiaThue,MoTa,TrangThai) VALUES (:d,:s,:dt,:g,:m,:t)');$s->execute([':d'=>$in['DayId'],':s'=>$in['SoPhong'],':dt'=>$in['DienTich']??null,':g'=>(int)($in['GiaThue']??0),':m'=>$in['MoTa']??null,':t'=>$in['TrangThai']??'Trong']);successResponse(['id'=>$pdo->lastInsertId()],'Tạo phòng thành công',201);}
    if($method==='PUT'){$id=(int)($_GET['id']??0);$room=$assertRoom($id);$in=readJsonBody();$s=$pdo->prepare('UPDATE Phong SET SoPhong=:s,DienTich=:dt,GiaThue=:g,MoTa=:m,TrangThai=:t WHERE Id=:id');$s->execute([':s'=>$in['SoPhong']??$room['SoPhong'],':dt'=>$in['DienTich']??$room['DienTich'],':g'=>(int)($in['GiaThue']??$room['GiaThue']),':m'=>$in['MoTa']??$room['MoTa'],':t'=>$in['TrangThai']??$room['TrangThai'],':id'=>$id]);successResponse([],'Cập nhật phòng thành công');}
    if($method==='DELETE'){$id=(int)($_GET['id']??0);$assertRoom($id);$check=$pdo->prepare('SELECT 1 FROM HopDong WHERE PhongId=:id AND TrangThai IN ("DangHieuLuc","GiaHan") AND IsDeleted=0');$check->execute([':id'=>$id]);if($check->fetch())errorResponse('Không thể xóa phòng đang có hợp đồng hiệu lực.',409);$pdo->prepare('UPDATE Phong SET IsDeleted=1 WHERE Id=:id')->execute([':id'=>$id]);successResponse([],'Đã chuyển phòng vào trạng thái đã xóa');}
    errorResponse('Method không hỗ trợ',405);
}catch(Throwable $e){errorResponse($e->getMessage(),500);}
