<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/response.php';
if (!function_exists('requireRole')) { function requireRole(array $allowedRoles): void { $current=currentUser(); if (!$current || !in_array($current['VaiTro']??'', $allowedRoles, true)) errorResponse('Bạn không có quyền truy cập.',403); } }

try {
    $pdo = getDb();
    requireRole(['admin', 'chutro', 'nguoithue']);
    $user = currentUser(); $role = $user['VaiTro'] ?? '';
    $isTenant = $role === 'nguoithue'; $method = $_SERVER['REQUEST_METHOD']; $action = $_GET['action'] ?? '';
    $roomBase = ' FROM Phong p JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE p.IsDeleted=0 ';
    $assertRoom = function (int $roomId) use ($pdo, $user, $role, $roomBase): array {
        $sql = 'SELECT p.*,d.TenDay,k.TenKhu,k.TaiKhoanId AS ChuTroId' . $roomBase . ' AND p.Id=:room';
        $params = [':room' => $roomId];
        if ($role === 'chutro') { $sql .= ' AND k.TaiKhoanId=:owner'; $params[':owner'] = $user['Id']; }
        if ($role === 'nguoithue') { $sql .= ' AND EXISTS(SELECT 1 FROM HopDong h JOIN NguoiThue nt ON nt.Id=h.NguoiThueId AND nt.IsDeleted=0 WHERE h.PhongId=p.Id AND h.IsDeleted=0 AND h.TrangThai IN ("DangHieuLuc","GiaHan") AND (nt.TaiKhoanId=:tenant OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=h.Id AND tv.TaiKhoanId=:tenant AND tv.IsDeleted=0)))'; $params[':tenant'] = $user['Id']; }
        $stmt = $pdo->prepare($sql); $stmt->execute($params); $room = $stmt->fetch();
        if (!$room) errorResponse('Không tìm thấy phòng hoặc bạn không có quyền truy cập.', 404);
        return $room;
    };
    $notifyRoom = function (int $contractId, string $title, string $content, string $type = 'HoaDon') use ($pdo): void {
        $accounts = $pdo->prepare('SELECT TaiKhoanId FROM NguoiThue WHERE Id=(SELECT NguoiThueId FROM HopDong WHERE Id=:contract) AND IsDeleted=0 UNION SELECT TaiKhoanId FROM ThanhVienPhong WHERE HopDongId=:contract AND IsDeleted=0');
        $accounts->execute([':contract' => $contractId]);
        $notice = $pdo->prepare('INSERT INTO ThongBao(TaiKhoanId,TieuDe,NoiDung,Loai) VALUES(:user,:title,:content,:type)');
        foreach ($accounts->fetchAll() as $account) if ($account['TaiKhoanId']) $notice->execute([':user' => $account['TaiKhoanId'], ':title' => $title, ':content' => $content, ':type' => $type]);
    };

    if ($action === 'cauHinh') {
        if ($role !== 'chutro') errorResponse('Chỉ chủ trọ được cấu hình đơn giá điện nước.', 403);
        if ($method === 'GET') {$stmt=$pdo->prepare('SELECT DonGiaDien,DonGiaNuoc FROM CauHinhDienNuoc WHERE TaiKhoanId=:owner');$stmt->execute([':owner'=>$user['Id']]);successResponse($stmt->fetch() ?: ['DonGiaDien'=>0,'DonGiaNuoc'=>0],'Cấu hình đơn giá');}
        if ($method === 'PUT') {$input=readJsonBody();$pdo->prepare('INSERT INTO CauHinhDienNuoc(TaiKhoanId,DonGiaDien,DonGiaNuoc,NgayCapNhat) VALUES(:owner,:electric,:water,datetime("now","localtime")) ON CONFLICT(TaiKhoanId) DO UPDATE SET DonGiaDien=excluded.DonGiaDien,DonGiaNuoc=excluded.DonGiaNuoc,NgayCapNhat=excluded.NgayCapNhat')->execute([':owner'=>$user['Id'],':electric'=>max(0,(int)($input['DonGiaDien']??0)),':water'=>max(0,(int)($input['DonGiaNuoc']??0))]);successResponse([],'Đã lưu cấu hình đơn giá.');}
        errorResponse('Phương thức không hỗ trợ.',405);
    }
    if ($method === 'POST' && $action === 'guiThongBao') {
        if ($isTenant) errorResponse('Người thuê không có quyền gửi thông báo hóa đơn.',403);
        $invoiceId=(int)($_GET['hoaDonId']??0);$stmt=$pdo->prepare('SELECT h.*,hd.PhongId,p.SoPhong FROM HoaDon h JOIN HopDong hd ON hd.Id=h.HopDongId AND hd.IsDeleted=0 JOIN Phong p ON p.Id=hd.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE h.Id=:id AND h.IsDeleted=0'.($role==='chutro'?' AND k.TaiKhoanId=:owner':''));$params=[':id'=>$invoiceId];if($role==='chutro')$params[':owner']=$user['Id'];$stmt->execute($params);$invoice=$stmt->fetch();if(!$invoice)errorResponse('Không tìm thấy hóa đơn.',404);$notifyRoom((int)$invoice['HopDongId'],'Hóa đơn tiền trọ mới','Phòng '.$invoice['SoPhong'].' có hóa đơn '.$invoice['Thang'].'/'.$invoice['Nam'].', tổng tiền '.number_format((int)$invoice['TongTien'],0,',','.').'đ.');successResponse([],'Đã gửi thông báo hóa đơn đến các thành viên phòng.');
    }

    if ($method === 'GET' && $action === 'thongKe') {
        if ($isTenant) errorResponse('Bạn không có quyền xem thống kê ghi chỉ số.', 403);
        $month=max(1,min(12,(int)($_GET['thang'] ?? date('n')))); $year=max(2020,(int)($_GET['nam'] ?? date('Y')));
        $sql='SELECT c.PhongId FROM ChiSoDienNuoc c JOIN Phong p ON p.Id=c.PhongId AND COALESCE(p.IsDeleted,0)=0 JOIN Day d ON d.Id=p.DayId AND COALESCE(d.IsDeleted,0)=0 JOIN Khu k ON k.Id=d.KhuId AND COALESCE(k.IsDeleted,0)=0 WHERE COALESCE(c.IsDeleted,0)=0 AND c.Thang=:month AND c.Nam=:year';
        $params=[':month'=>$month, ':year'=>$year]; if($role==='chutro'){$sql.=' AND k.TaiKhoanId=:owner';$params[':owner']=$user['Id'];}
        $stmt=$pdo->prepare($sql);$stmt->execute($params);successResponse(['phongDaGhi'=>array_map(fn($row)=>(int)$row['PhongId'],$stmt->fetchAll()),'thang'=>$month,'nam'=>$year],'Thống kê kỳ ghi');
    }
    if ($method === 'GET' && $action === 'nhap') {
        if ($isTenant) errorResponse('Người thuê chỉ được xem chỉ số.', 403);
        $room = $assertRoom((int)($_GET['phongId'] ?? 0));
        $contract=$pdo->prepare('SELECT DonGiaDien,DonGiaNuoc FROM HopDong WHERE PhongId=:room AND IsDeleted=0 AND TrangThai IN ("DangHieuLuc","GiaHan") ORDER BY Id DESC LIMIT 1');$contract->execute([':room'=>$room['Id']]);
        $last=$pdo->prepare('SELECT * FROM ChiSoDienNuoc WHERE PhongId=:room AND IsDeleted=0 ORDER BY Nam DESC,Thang DESC LIMIT 1');$last->execute([':room'=>$room['Id']]);
        successResponse(['phong'=>array_merge($room,$contract->fetch() ?: []),'kyTruoc'=>$last->fetch() ?: null],'Dữ liệu ghi điện nước');
    }
    if ($method === 'GET') {
        $roomId=(int)($_GET['phongId'] ?? 0);
        if ($roomId) {
            $room=$assertRoom($roomId);
            $stmt=$pdo->prepare('SELECT * FROM ChiSoDienNuoc WHERE PhongId=:room AND IsDeleted=0 ORDER BY Nam DESC,Thang DESC LIMIT 12');$stmt->execute([':room'=>$room['Id']]);
            successResponse($stmt->fetchAll(),'Lịch sử chỉ số điện nước');
        }
        $sql='SELECT c.*,p.SoPhong,d.TenDay,k.TenKhu FROM ChiSoDienNuoc c JOIN Phong p ON p.Id=c.PhongId AND COALESCE(p.IsDeleted,0)=0 JOIN Day d ON d.Id=p.DayId AND COALESCE(d.IsDeleted,0)=0 JOIN Khu k ON k.Id=d.KhuId AND COALESCE(k.IsDeleted,0)=0 WHERE COALESCE(c.IsDeleted,0)=0';$params=[];
        if($isTenant){$sql.=' AND EXISTS(SELECT 1 FROM HopDong h JOIN NguoiThue nt ON nt.Id=h.NguoiThueId AND nt.IsDeleted=0 WHERE h.PhongId=p.Id AND h.IsDeleted=0 AND h.TrangThai IN ("DangHieuLuc","GiaHan") AND (nt.TaiKhoanId=:tenant OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=h.Id AND tv.TaiKhoanId=:tenant AND tv.IsDeleted=0)))';$params[':tenant']=$user['Id'];}
        elseif($role==='chutro'){$sql.=' AND k.TaiKhoanId=:owner';$params[':owner']=$user['Id'];}
        $month=(int)($_GET['thang']??0);$year=(int)($_GET['nam']??0);if($month>=1&&$month<=12){$sql.=' AND c.Thang=:month';$params[':month']=$month;}if($year>0){$sql.=' AND c.Nam=:year';$params[':year']=$year;}
        $stmt=$pdo->prepare($sql.' ORDER BY c.Nam DESC,c.Thang DESC,c.Id DESC');$stmt->execute($params);successResponse($stmt->fetchAll(),$isTenant?'Chỉ số điện nước của phòng':'Danh sách chỉ số điện nước');
    }
    if ($method === 'POST' || $method === 'PUT') {
        if ($isTenant) errorResponse('Người thuê chỉ được xem chỉ số.', 403);
        $input=readJsonBody(); $room=$assertRoom((int)($input['PhongId'] ?? 0));
        foreach(['Thang','Nam','ChiSoDienDau','ChiSoDienCuoi','DonGiaDien','ChiSoNuocDau','ChiSoNuocCuoi','DonGiaNuoc'] as $field) if(!array_key_exists($field,$input)) errorResponse('Thiếu dữ liệu: '.$field,422);
        if((float)$input['ChiSoDienCuoi'] < (float)$input['ChiSoDienDau'] || (float)$input['ChiSoNuocCuoi'] < (float)$input['ChiSoNuocDau']) errorResponse('Chỉ số cuối không được nhỏ hơn chỉ số đầu.',422);
        $month=max(1,min(12,(int)$input['Thang']));$year=max(2020,(int)$input['Nam']);
        $meter=$pdo->prepare('INSERT INTO ChiSoDienNuoc(PhongId,Thang,Nam,ChiSoDienDau,ChiSoDienCuoi,DonGiaDien,ChiSoNuocDau,ChiSoNuocCuoi,DonGiaNuoc,TienDichVu,GhiChu,IsDeleted) VALUES(:room,:month,:year,:electricStart,:electricEnd,:electricPrice,:waterStart,:waterEnd,:waterPrice,:service,:note,0) ON CONFLICT(PhongId,Thang,Nam) DO UPDATE SET ChiSoDienDau=excluded.ChiSoDienDau,ChiSoDienCuoi=excluded.ChiSoDienCuoi,DonGiaDien=excluded.DonGiaDien,ChiSoNuocDau=excluded.ChiSoNuocDau,ChiSoNuocCuoi=excluded.ChiSoNuocCuoi,DonGiaNuoc=excluded.DonGiaNuoc,TienDichVu=excluded.TienDichVu,GhiChu=excluded.GhiChu,IsDeleted=0,NgayGhi=datetime("now","localtime")');
        $meter->execute([':room'=>$room['Id'],':month'=>$month,':year'=>$year,':electricStart'=>(float)$input['ChiSoDienDau'],':electricEnd'=>(float)$input['ChiSoDienCuoi'],':electricPrice'=>(int)$input['DonGiaDien'],':waterStart'=>(float)$input['ChiSoNuocDau'],':waterEnd'=>(float)$input['ChiSoNuocCuoi'],':waterPrice'=>(int)$input['DonGiaNuoc'],':service'=>(int)($input['TienDichVu']??0),':note'=>$input['GhiChu']??null]);
        $contract=$pdo->prepare('SELECT h.*,nt.HoTen AS NguoiThue FROM HopDong h JOIN NguoiThue nt ON nt.Id=h.NguoiThueId AND nt.IsDeleted=0 WHERE h.PhongId=:room AND h.IsDeleted=0 AND h.TrangThai IN ("DangHieuLuc","GiaHan") ORDER BY h.Id DESC LIMIT 1');$contract->execute([':room'=>$room['Id']]);$contract=$contract->fetch();
        $invoiceId=null; $invoiceData=null;
        if($contract){
            $electric=(int)round(((float)$input['ChiSoDienCuoi']-(float)$input['ChiSoDienDau'])*(int)$input['DonGiaDien']);$water=(int)round(((float)$input['ChiSoNuocCuoi']-(float)$input['ChiSoNuocDau'])*(int)$input['DonGiaNuoc']);$rent=(int)$contract['GiaThue'];$service=(int)($input['TienDichVu']??0);$total=$rent+$electric+$water+$service;$due=$input['HanThanhToan']??date('Y-m-10',strtotime('+1 month'));
            $existing=$pdo->prepare('SELECT Id FROM HoaDon WHERE HopDongId=:contract AND Thang=:month AND Nam=:year AND IsDeleted=0');$existing->execute([':contract'=>$contract['Id'],':month'=>$month,':year'=>$year]);$invoiceId=$existing->fetchColumn();
            if($invoiceId){$pdo->prepare('UPDATE HoaDon SET TienPhong=:rent,TienDien=:electric,TienNuoc=:water,TienDichVu=:service,TongTien=:total,HanThanhToan=:due,TrangThai=CASE WHEN DaTra>=:total THEN "DaThanhToan" WHEN DaTra>0 THEN "ThanhToanMotPhan" ELSE "ChuaThanhToan" END WHERE Id=:id AND IsDeleted=0')->execute([':rent'=>$rent,':electric'=>$electric,':water'=>$water,':service'=>$service,':total'=>$total,':due'=>$due,':id'=>$invoiceId]);}
            else {$pdo->prepare('INSERT INTO HoaDon(HopDongId,Thang,Nam,TienPhong,TienDien,TienNuoc,TienDichVu,TongTien,DaTra,TrangThai,HanThanhToan) VALUES(:contract,:month,:year,:rent,:electric,:water,:service,:total,0,"ChuaThanhToan",:due)')->execute([':contract'=>$contract['Id'],':month'=>$month,':year'=>$year,':rent'=>$rent,':electric'=>$electric,':water'=>$water,':service'=>$service,':total'=>$total,':due'=>$due]);$invoiceId=$pdo->lastInsertId();}
            $invoiceData=['Id'=>(int)$invoiceId,'SoPhong'=>$room['SoPhong'],'TenKhu'=>$room['TenKhu'],'TenDay'=>$room['TenDay'],'NguoiThue'=>$contract['NguoiThue'],'Thang'=>$month,'Nam'=>$year,'TienPhong'=>$rent,'TienDien'=>$electric,'TienNuoc'=>$water,'TienDichVu'=>$service,'TongTien'=>$total,'DaTra'=>0,'HanThanhToan'=>$due,'TrangThai'=>'ChuaThanhToan'];
        }
        successResponse(['id'=>$pdo->lastInsertId(),'hoaDonId'=>$invoiceId,'hoaDon'=>$invoiceData],'Đã ghi chỉ số và lập/cập nhật hóa đơn tháng.',201);
    }
    errorResponse('Phương thức không hỗ trợ.',405);
} catch (Throwable $e) {
    errorResponse($e->getMessage(),500);
}
