<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/response.php';
require __DIR__ . '/../includes/helpers.php';

function contractRoom(PDO $pdo, int $roomId, array $user): array
{
    $sql = 'SELECT p.*, d.TenDay, k.TenKhu, k.DiaChi AS DiaChiKhu, k.TaiKhoanId
            FROM Phong p JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE p.Id=:id AND p.IsDeleted=0';
    if (($user['VaiTro'] ?? '') === 'chutro') $sql .= ' AND k.TaiKhoanId=:owner';
    $stmt = $pdo->prepare($sql);
    $params = [':id' => $roomId];
    if (($user['VaiTro'] ?? '') === 'chutro') $params[':owner'] = (int)$user['Id'];
    $stmt->execute($params);
    $room = $stmt->fetch();
    if (!$room) errorResponse('Phòng không tồn tại hoặc không thuộc quyền quản lý.', 404);
    return $room;
}

function contractData(PDO $pdo, int $id, array $user): array
{
    $sql = 'SELECT h.*, p.SoPhong, p.DienTich, d.TenDay, k.TenKhu, k.DiaChi AS DiaChiKhu,
                   nt.HoTen AS NguoiThue, nt.CCCD, nt.NgaySinh AS BenBNgaySinh, nt.SoDienThoai AS BenBSoDienThoai, nt.DiaChiThuongTru AS BenBDiaChi
            FROM HopDong h JOIN Phong p ON p.Id=h.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0
            JOIN NguoiThue nt ON nt.Id=h.NguoiThueId AND nt.IsDeleted=0 WHERE h.Id=:id AND h.IsDeleted=0';
    if (($user['VaiTro'] ?? '') === 'chutro') $sql .= ' AND k.TaiKhoanId=:owner';
    if (($user['VaiTro'] ?? '') === 'nguoithue') $sql .= ' AND (nt.TaiKhoanId=:tenant OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=h.Id AND tv.TaiKhoanId=:tenant AND tv.IsDeleted=0))';
    $stmt = $pdo->prepare($sql);
    $params = [':id' => $id];
    if (($user['VaiTro'] ?? '') === 'chutro') $params[':owner'] = (int)$user['Id'];
    if (($user['VaiTro'] ?? '') === 'nguoithue') $params[':tenant'] = (int)$user['Id'];
    $stmt->execute($params);
    $row = $stmt->fetch();
    if (!$row) errorResponse('Không tìm thấy hợp đồng hoặc bạn không có quyền xem.', 404);
    $members = $pdo->prepare('SELECT * FROM ThanhVienPhong WHERE HopDongId=:id AND IsDeleted=0 ORDER BY Id');
    $members->execute([':id' => $id]);
    $row['ThanhVien'] = $members->fetchAll();
    return $row;
}

try {
    $pdo = getDb();
    $method = $_SERVER['REQUEST_METHOD'];
    requireRole(['admin', 'chutro', 'nguoithue']);
    $user = currentUser();
    $action = $_GET['action'] ?? '';

    if ($method === 'GET' && $action === 'phongTrong') {
        $sql = 'SELECT p.*, d.TenDay, k.TenKhu FROM Phong p JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE p.TrangThai="Trong" AND p.IsDeleted=0';
        $params = [];
        if (($user['VaiTro'] ?? '') !== 'admin') { $sql .= ' AND k.TaiKhoanId=:owner'; $params[':owner'] = (int)$user['Id']; }
        $sql .= ' ORDER BY k.Id, d.Id, p.SoPhong';
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        successResponse($stmt->fetchAll(), 'Danh sách phòng trống');
    }

    if ($method === 'GET' && $action === 'taiFile') {
        $contract = contractData($pdo, (int)($_GET['id'] ?? 0), $user);
        $file = basename((string)($contract['FileHopDong'] ?? ''));
        $path = __DIR__ . '/../data/hopdong/' . $file;
        if (!$file || !is_file($path)) errorResponse('File hợp đồng chưa được tạo.', 404);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="hop-dong-' . $contract['Id'] . '.pdf"');
        header('Content-Length: ' . filesize($path));
        readfile($path); exit;
    }

    if ($method === 'GET') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id) successResponse(contractData($pdo, $id, $user), 'Chi tiết hợp đồng');
        $sql = 'SELECT h.*,p.SoPhong,d.TenDay,k.TenKhu,nt.HoTen AS NguoiThue FROM HopDong h JOIN Phong p ON p.Id=h.PhongId AND COALESCE(p.IsDeleted,0)=0 JOIN Day d ON d.Id=p.DayId AND COALESCE(d.IsDeleted,0)=0 JOIN Khu k ON k.Id=d.KhuId AND COALESCE(k.IsDeleted,0)=0 JOIN NguoiThue nt ON nt.Id=h.NguoiThueId AND COALESCE(nt.IsDeleted,0)=0 WHERE COALESCE(h.IsDeleted,0)=0';
        $params=[]; if (($user['VaiTro'] ?? '') === 'chutro') { $sql.=' AND k.TaiKhoanId=:owner'; $params[':owner']=(int)$user['Id']; } if (($user['VaiTro'] ?? '') === 'nguoithue') { $sql.=' AND (nt.TaiKhoanId=:tenant OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=h.Id AND tv.TaiKhoanId=:tenant AND tv.IsDeleted=0))'; $params[':tenant']=(int)$user['Id']; }
        $sql .= ' ORDER BY h.Id DESC'; $stmt=$pdo->prepare($sql); $stmt->execute($params);
        successResponse($stmt->fetchAll(), 'Danh sách hợp đồng');
    }

    if ($method === 'POST' && $action === 'xemTruoc') {
        $in=readJsonBody(); $room=contractRoom($pdo, (int)($in['PhongId'] ?? 0), $user); $_SESSION['hop_dong_xem_truoc']=['input'=>$in,'room'=>$room];
        successResponse(['room'=>$room, 'input'=>$in], 'Dữ liệu xem trước hợp đồng');
    }

    if ($method === 'POST' && $action === 'thanhvien') {
        $in=readJsonBody(); foreach(['HopDongId','HoTen','CCCD'] as $field) if(trim((string)($in[$field]??''))==='') errorResponse('Thiếu thông tin: '.$field,400);
        $contract=contractData($pdo,(int)$in['HopDongId'],$user);
        if(!in_array($contract['TrangThai'],['DangHieuLuc','GiaHan'],true)) errorResponse('Chỉ thêm thành viên cho hợp đồng còn hiệu lực.',409);
        $check=$pdo->prepare('SELECT Id FROM ThanhVienPhong WHERE HopDongId=:h AND CCCD=:c');$check->execute([':h'=>$contract['Id'],':c'=>trim($in['CCCD'])]);if($check->fetch())errorResponse('CCCD này đã có trong phòng.',409);
        $username=preg_replace('/\D/','',trim($in['CCCD']));$find=$pdo->prepare('SELECT Id FROM TaiKhoan WHERE TenDangNhap=:u');$find->execute([':u'=>$username]);$account=$find->fetch();
        if($account)$accountId=(int)$account['Id']; else {$pdo->prepare('INSERT INTO TaiKhoan(TenDangNhap,MatKhau,HoTen,SoDienThoai,VaiTro) VALUES(:u,:p,:n,:s,"nguoithue")')->execute([':u'=>$username,':p'=>password_hash(trim($in['CCCD']),PASSWORD_BCRYPT),':n'=>trim($in['HoTen']),':s'=>$in['SoDienThoai']??null]);$accountId=(int)$pdo->lastInsertId();}
        $pdo->prepare('INSERT INTO ThanhVienPhong(HopDongId,HoTen,CCCD,NgaySinh,SoDienThoai,QuanHe,Loai,TaiKhoanId) VALUES(:h,:n,:c,:ns,:s,:q,:l,:tk)')->execute([':h'=>$contract['Id'],':n'=>trim($in['HoTen']),':c'=>trim($in['CCCD']),':ns'=>$in['NgaySinh']??null,':s'=>$in['SoDienThoai']??null,':q'=>$in['QuanHe']??'Thành viên',':l'=>($in['Loai']??'ThanhVienPhong')==='LuuTru'?'LuuTru':'ThanhVienPhong',':tk'=>$accountId]);
        successResponse(['id'=>$pdo->lastInsertId()],'Đã thêm thành viên vào phòng.',201);
    }

    if ($method === 'POST') {
        $in=readJsonBody();
        foreach (['PhongId','NgayBatDau','NgayKetThuc','BenBHoTen','BenBCCCD'] as $field) if (trim((string)($in[$field] ?? '')) === '') errorResponse('Thiếu thông tin bắt buộc: '.$field, 400);
        $room=contractRoom($pdo,(int)$in['PhongId'],$user);
        if ($room['TrangThai'] !== 'Trong') errorResponse('Chỉ có thể lập hợp đồng cho phòng đang trống.', 409);
        if ($in['NgayKetThuc'] < $in['NgayBatDau']) errorResponse('Ngày kết thúc phải sau ngày bắt đầu.',400);
        $members = is_array($in['ThanhVien'] ?? null) ? $in['ThanhVien'] : [];
        $main=['HoTen'=>$in['BenBHoTen'],'CCCD'=>$in['BenBCCCD'],'NgaySinh'=>$in['BenBNgaySinh']??null,'SoDienThoai'=>$in['BenBSoDienThoai']??null,'QuanHe'=>'Người ký hợp đồng'];
        array_unshift($members,$main);
        $seen=[]; foreach($members as $m) { $cccd=trim((string)($m['CCCD']??'')); if(!$cccd || isset($seen[$cccd])) errorResponse('Mỗi thành viên phải có CCCD riêng và không trùng lặp.',400); $seen[$cccd]=true; }

        $pdo->beginTransaction();
        try {
            $tenant=$pdo->prepare('SELECT * FROM NguoiThue WHERE CCCD=:cccd AND IsDeleted=0'); $tenant->execute([':cccd'=>$main['CCCD']]); $nguoiThue=$tenant->fetch();
            $accountId=null;
            if(!$nguoiThue){
                $username=preg_replace('/\D/','',$main['CCCD']);
                $exists=$pdo->prepare('SELECT Id FROM TaiKhoan WHERE TenDangNhap=:u'); $exists->execute([':u'=>$username]);
                if($exists->fetch()) $username .= '_' . substr((string)time(), -4);
                $pdo->prepare('INSERT INTO TaiKhoan(TenDangNhap,MatKhau,HoTen,Email,SoDienThoai,VaiTro) VALUES(:u,:p,:n,:e,:s,"nguoithue")')->execute([':u'=>$username,':p'=>password_hash($main['CCCD'],PASSWORD_BCRYPT),':n'=>$main['HoTen'],':e'=>$in['BenBEmail']??null,':s'=>$main['SoDienThoai']]);
                $accountId=(int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO NguoiThue(HoTen,CCCD,NgaySinh,GioiTinh,SoDienThoai,Email,DiaChiThuongTru,NgheNghiep,TaiKhoanId) VALUES(:n,:c,:ns,:g,:s,:e,:dc,:nn,:tk)')->execute([':n'=>$main['HoTen'],':c'=>$main['CCCD'],':ns'=>$main['NgaySinh'],':g'=>$in['BenBGioiTinh']??null,':s'=>$main['SoDienThoai'],':e'=>$in['BenBEmail']??null,':dc'=>$in['BenBDiaChi']??null,':nn'=>$in['BenBNgheNghiep']??null,':tk'=>$accountId]);
                $nguoiThue=['Id'=>(int)$pdo->lastInsertId(),'TaiKhoanId'=>$accountId];
            } else $accountId=(int)($nguoiThue['TaiKhoanId']??0) ?: null;
            $so='HD-'.date('Ymd').'-'.str_pad((string)((int)$pdo->query('SELECT IFNULL(MAX(Id),0)+1 FROM HopDong')->fetchColumn()),4,'0',STR_PAD_LEFT);
            $insert=$pdo->prepare('INSERT INTO HopDong(PhongId,NguoiThueId,NgayBatDau,NgayKetThuc,TienCoc,GiaThue,DieuKhoan,SoHopDong,BenAHoTen,BenANgaySinh,BenADiaChi,BenACCCD,BenASoDienThoai,DonGiaDien,DonGiaNuoc,ChiSoDienDau,ChiSoNuocDau,TrangThai) VALUES(:p,:n,:bd,:kt,:c,:g,:dk,:so,:a,:ans,:adc,:ac,:as,:dde,:ddn,:csd,:csn,"DangHieuLuc")');
            $insert->execute([':p'=>$room['Id'],':n'=>$nguoiThue['Id'],':bd'=>$in['NgayBatDau'],':kt'=>$in['NgayKetThuc'],':c'=>(int)($in['TienCoc']??0),':g'=>(int)($in['GiaThue']??$room['GiaThue']),':dk'=>$in['DieuKhoan']??null,':so'=>$so,':a'=>$in['BenAHoTen']??null,':ans'=>$in['BenANgaySinh']??null,':adc'=>$in['BenADiaChi']??null,':ac'=>$in['BenACCCD']??null,':as'=>$in['BenASoDienThoai']??null,':dde'=>(int)($in['DonGiaDien']??0),':ddn'=>(int)($in['DonGiaNuoc']??0),':csd'=>(float)($in['ChiSoDienDau']??0),':csn'=>(float)($in['ChiSoNuocDau']??0)]);
            $contractId=(int)$pdo->lastInsertId();
            $memberInsert=$pdo->prepare('INSERT INTO ThanhVienPhong(HopDongId,HoTen,CCCD,NgaySinh,SoDienThoai,QuanHe,TaiKhoanId) VALUES(:h,:n,:c,:ns,:s,:q,:tk)');
            foreach($members as $i=>$m){
                $memberAccount=$i===0?$accountId:null;
                if ($i > 0) {
                    $username=preg_replace('/\D/','',(string)$m['CCCD']);
                    $findAccount=$pdo->prepare('SELECT Id FROM TaiKhoan WHERE TenDangNhap=:u'); $findAccount->execute([':u'=>$username]);
                    $existingAccount=$findAccount->fetch();
                    if ($existingAccount) $memberAccount=(int)$existingAccount['Id'];
                    else {
                        $pdo->prepare('INSERT INTO TaiKhoan(TenDangNhap,MatKhau,HoTen,SoDienThoai,VaiTro) VALUES(:u,:p,:n,:s,"nguoithue")')->execute([':u'=>$username,':p'=>password_hash((string)$m['CCCD'],PASSWORD_BCRYPT),':n'=>trim($m['HoTen']),':s'=>$m['SoDienThoai']??null]);
                        $memberAccount=(int)$pdo->lastInsertId();
                    }
                }
                $memberInsert->execute([':h'=>$contractId,':n'=>trim($m['HoTen']),':c'=>trim($m['CCCD']),':ns'=>$m['NgaySinh']??null,':s'=>$m['SoDienThoai']??null,':q'=>$m['QuanHe']??($i===0?'Người ký hợp đồng':'Thành viên'),':tk'=>$memberAccount]);
            }
            $month=(int)date('n',strtotime($in['NgayBatDau'])); $year=(int)date('Y',strtotime($in['NgayBatDau']));
            $pdo->prepare('INSERT OR REPLACE INTO ChiSoDienNuoc(PhongId,Thang,Nam,ChiSoDienDau,ChiSoDienCuoi,DonGiaDien,ChiSoNuocDau,ChiSoNuocCuoi,DonGiaNuoc) VALUES(:p,:t,:n,:d,:d,:gd,:w,:w,:gw)')->execute([':p'=>$room['Id'],':t'=>$month,':n'=>$year,':d'=>(float)($in['ChiSoDienDau']??0),':gd'=>(int)($in['DonGiaDien']??0),':w'=>(float)($in['ChiSoNuocDau']??0),':gw'=>(int)($in['DonGiaNuoc']??0)]);
            $dir=__DIR__.'/../data/hopdong'; if(!is_dir($dir)) mkdir($dir,0777,true); $file='hopdong_'.$contractId.'.pdf';
            generateContractPdf($dir.'/'.$file,['Ngay'=>date('d',strtotime($in['NgayBatDau'])),'Thang'=>date('m',strtotime($in['NgayBatDau'])),'Nam'=>date('Y',strtotime($in['NgayBatDau'])),'DiaChi'=>$room['DiaChiKhu']?:$room['TenKhu'],'BenA'=>$in['BenAHoTen']??'Chủ trọ','BenB'=>$main['HoTen'],'CccdA'=>$in['BenACCCD']??'','CccdB'=>$main['CCCD'],'ThuongTruA'=>$in['BenADiaChi']??'','ThuongTruB'=>$in['BenBDiaChi']??'','SoPhong'=>$room['SoPhong'],'GiaThue'=>$in['GiaThue']??$room['GiaThue'],'TienCoc'=>$in['TienCoc']??0]);
            $pdo->prepare('UPDATE HopDong SET FileHopDong=:f WHERE Id=:id')->execute([':f'=>$file,':id'=>$contractId]);
            $pdo->prepare('UPDATE Phong SET TrangThai="DangThue" WHERE Id=:id')->execute([':id'=>$room['Id']]);
            $pdo->commit(); successResponse(['id'=>$contractId,'file'=>$file,'taiFile'=>'/api/hopdong.php?id='.$contractId.'&action=taiFile'],'Đã xác nhận hợp đồng, tạo người thuê và ghi chỉ số đầu kỳ.',201);
        } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); throw $e; }
    }
    errorResponse('Method không hỗ trợ',405);
} catch(Throwable $e) { errorResponse($e->getMessage(),500); }
