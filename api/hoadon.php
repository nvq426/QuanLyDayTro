<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/response.php';

try {
    $pdo = getDb();
    requireRole(['admin', 'chutro', 'nguoithue']);
    $user = currentUser();
    $role = $user['VaiTro'] ?? '';
    $isTenant = $role === 'nguoithue';
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    $base = ' FROM HoaDon h JOIN HopDong hd ON hd.Id=h.HopDongId AND hd.IsDeleted=0 JOIN Phong p ON p.Id=hd.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 JOIN NguoiThue nt ON nt.Id=hd.NguoiThueId AND nt.IsDeleted=0 WHERE h.IsDeleted=0 ';
    $canManageInvoice = function (int $invoiceId) use ($pdo, $user, $role, $base): array {
        $sql = 'SELECT h.*,hd.PhongId,p.SoPhong,d.TenDay,k.TenKhu,k.TaiKhoanId AS ChuTroId,nt.HoTen AS NguoiThue,nt.TaiKhoanId AS NguoiThueTaiKhoanId' . $base . ' AND h.Id=:id';
        $params = [':id' => $invoiceId];
        if ($role === 'chutro') { $sql .= ' AND k.TaiKhoanId=:owner'; $params[':owner'] = $user['Id']; }
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        $invoice = $stmt->fetch();
        if (!$invoice) errorResponse('Không tìm thấy hóa đơn hoặc bạn không có quyền thao tác.', 404);
        return $invoice;
    };
    $canTenantInvoice = function (int $invoiceId) use ($pdo, $user, $base): array {
        $sql = 'SELECT h.*,hd.PhongId,p.SoPhong,d.TenDay,k.TenKhu,k.TaiKhoanId AS ChuTroId,nt.HoTen AS NguoiThue,nt.TaiKhoanId AS NguoiThueTaiKhoanId' . $base . ' AND h.Id=:id AND (nt.TaiKhoanId=:tenant OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=hd.Id AND tv.TaiKhoanId=:tenant AND tv.IsDeleted=0))';
        $stmt = $pdo->prepare($sql); $stmt->execute([':id' => $invoiceId, ':tenant' => $user['Id']]);
        $invoice = $stmt->fetch();
        if (!$invoice) errorResponse('Không tìm thấy hóa đơn của bạn.', 404);
        return $invoice;
    };
    $notifyRoom = function (int $contractId, string $title, string $content, string $type = 'HoaDon') use ($pdo): void {
        $recipients = $pdo->prepare('SELECT TaiKhoanId FROM NguoiThue WHERE Id=(SELECT NguoiThueId FROM HopDong WHERE Id=:contract) AND IsDeleted=0 UNION SELECT TaiKhoanId FROM ThanhVienPhong WHERE HopDongId=:contract AND IsDeleted=0');
        $recipients->execute([':contract' => $contractId]);
        $notice = $pdo->prepare('INSERT INTO ThongBao(TaiKhoanId,TieuDe,NoiDung,Loai) VALUES(:user,:title,:content,:type)');
        foreach ($recipients->fetchAll() as $recipient) if ($recipient['TaiKhoanId']) $notice->execute([':user' => $recipient['TaiKhoanId'], ':title' => $title, ':content' => $content, ':type' => $type]);
    };

    if ($method === 'GET' && $action === 'nhanTien') {
        if ($isTenant) errorResponse('Bạn không có quyền truy cập thông tin nhận tiền.', 403);
        $stmt = $pdo->prepare('SELECT ThongTinChuyenKhoan,MaQRThanhToan FROM TaiKhoan WHERE Id=:id AND IsDeleted=0');
        $stmt->execute([':id' => $user['Id']]); successResponse($stmt->fetch() ?: [], 'Thông tin nhận tiền');
    }
    if ($method === 'PUT' && $action === 'nhanTien') {
        if ($isTenant) errorResponse('Bạn không có quyền cập nhật thông tin nhận tiền.', 403);
        $input = readJsonBody();
        $qr=(string)($input['MaQRThanhToan']??'');if($qr!==''&&(!preg_match('#^data:image/(jpeg|png|webp);base64,#i',$qr)||strlen($qr)>7*1024*1024))errorResponse('Mã QR phải là ảnh JPG, PNG hoặc WEBP và không vượt quá 5 MB.',422);
        $pdo->prepare('UPDATE TaiKhoan SET ThongTinChuyenKhoan=:bank,MaQRThanhToan=:qr WHERE Id=:id AND IsDeleted=0')->execute([':bank' => $input['ThongTinChuyenKhoan'] ?? null, ':qr' => $input['MaQRThanhToan'] ?? null, ':id' => $user['Id']]);
        successResponse([], 'Đã cập nhật thông tin nhận tiền.');
    }
    if ($method === 'GET' && $action === 'thanhToanInfo') {
        if (!$isTenant) errorResponse('Chỉ người thuê có thể lấy thông tin thanh toán.', 403);
        $invoice = $canTenantInvoice((int)($_GET['id'] ?? 0));
        $bank = $pdo->prepare('SELECT ThongTinChuyenKhoan,MaQRThanhToan FROM TaiKhoan WHERE Id=:id AND IsDeleted=0');
        $bank->execute([':id' => $invoice['ChuTroId']]);
        $pending=$pdo->prepare('SELECT COALESCE(SUM(SoTien),0) FROM ThanhToan WHERE HoaDonId=:id AND IsDeleted=0 AND TrangThai="ChoXacNhan"');$pending->execute([':id'=>$invoice['Id']]);$pendingAmount=(int)$pending->fetchColumn();
        successResponse(array_merge(['Id'=>$invoice['Id'],'TongTien'=>$invoice['TongTien'],'DaTra'=>$invoice['DaTra'],'DangChoXacNhan'=>$pendingAmount,'ConLai'=>max(0,(int)$invoice['TongTien']-(int)$invoice['DaTra']-$pendingAmount)],$bank->fetch()?:[]),'Thông tin thanh toán');
    }
    if ($method === 'GET' && $action === 'choXacNhan') {
        if ($isTenant) errorResponse('Bạn không có quyền.', 403);
        $sql = 'SELECT tt.*,h.TongTien,p.SoPhong,nt.HoTen FROM ThanhToan tt JOIN HoaDon h ON h.Id=tt.HoaDonId AND h.IsDeleted=0 JOIN HopDong hd ON hd.Id=h.HopDongId AND hd.IsDeleted=0 JOIN Phong p ON p.Id=hd.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 JOIN NguoiThue nt ON nt.Id=hd.NguoiThueId AND nt.IsDeleted=0 WHERE tt.IsDeleted=0 AND tt.TrangThai="ChoXacNhan"';
        $params = [];
        if ($role === 'chutro') { $sql .= ' AND k.TaiKhoanId=:owner'; $params[':owner'] = $user['Id']; }
        $stmt = $pdo->prepare($sql . ' ORDER BY tt.Id DESC'); $stmt->execute($params);
        successResponse($stmt->fetchAll(), 'Yêu cầu thanh toán chờ xác nhận');
    }
    if ($method === 'POST' && $action === 'thanhToan') {
        if (!$isTenant) errorResponse('Chỉ người thuê có thể tạo yêu cầu thanh toán.', 403);
        $invoice = $canTenantInvoice((int)($_GET['id'] ?? 0));
        $input = readJsonBody(); $payMethod = $input['PhuongThuc'] ?? 'TienMat';
        if (!in_array($payMethod, ['TienMat', 'ChuyenKhoan'], true)) errorResponse('Hình thức thanh toán không hợp lệ.', 422);
        if ($payMethod === 'ChuyenKhoan' && empty($input['MinhChung'])) errorResponse('Vui lòng tải minh chứng chuyển khoản.', 422);
        if($payMethod==='ChuyenKhoan' && (!preg_match('#^data:image/(jpeg|png|webp);base64,#i',(string)$input['MinhChung']) || strlen((string)$input['MinhChung'])>7*1024*1024))errorResponse('Minh chứng phải là ảnh JPG, PNG hoặc WEBP và không vượt quá 5 MB.',422);
        $pending = $pdo->prepare('SELECT COALESCE(SUM(SoTien),0) FROM ThanhToan WHERE HoaDonId=:id AND IsDeleted=0 AND TrangThai="ChoXacNhan"');
        $pending->execute([':id' => $invoice['Id']]);
        $remaining = max(0, (int)$invoice['TongTien'] - (int)$invoice['DaTra'] - (int)$pending->fetchColumn());
        // The server always settles the exact outstanding amount. Tenants
        // cannot alter an invoice amount from browser tools.
        $amount = $remaining;
        if ($amount <= 0 || $amount > $remaining) errorResponse('Số tiền thanh toán không hợp lệ hoặc vượt số tiền còn lại.', 422);
        $pdo->prepare('INSERT INTO ThanhToan(HoaDonId,SoTien,PhuongThuc,GhiChu,NguoiThu,TrangThai,MinhChung) VALUES(:invoice,:amount,:method,:note,:user,"ChoXacNhan",:proof)')->execute([':invoice' => $invoice['Id'], ':amount' => $amount, ':method' => $payMethod, ':note' => $input['GhiChu'] ?? null, ':user' => $user['Id'], ':proof' => $input['MinhChung'] ?? null]);
        if ($invoice['ChuTroId']) $pdo->prepare('INSERT INTO ThongBao(TaiKhoanId,TieuDe,NoiDung,Loai) VALUES(:user,:title,:content,"HoaDon")')->execute([':user' => $invoice['ChuTroId'], ':title' => 'Yêu cầu thanh toán mới', ':content' => 'Phòng ' . $invoice['SoPhong'] . ' yêu cầu thanh toán ' . number_format($amount, 0, ',', '.') . 'đ.']);
        successResponse([], 'Đã gửi yêu cầu thanh toán, chờ chủ trọ xác nhận.', 201);
    }
    if ($method === 'POST' && $action === 'xacNhanThanhToan') {
        if ($isTenant) errorResponse('Chỉ chủ trọ hoặc quản trị viên được xác nhận.', 403);
        $paymentId = (int)($_GET['paymentId'] ?? 0);
        $payment = $pdo->prepare('SELECT tt.* FROM ThanhToan tt WHERE tt.Id=:id AND tt.IsDeleted=0 AND tt.TrangThai="ChoXacNhan"');
        $payment->execute([':id' => $paymentId]); $payment = $payment->fetch();
        if (!$payment) errorResponse('Không tìm thấy yêu cầu thanh toán.', 404);
        $invoice = $canManageInvoice((int)$payment['HoaDonId']);
        $amount = min((int)$payment['SoTien'], max(0, (int)$invoice['TongTien'] - (int)$invoice['DaTra']));
        if ($amount <= 0) errorResponse('Hóa đơn đã được thanh toán đủ.', 409);
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE ThanhToan SET TrangThai="DaXacNhan",NguoiThu=:collector WHERE Id=:id AND TrangThai="ChoXacNhan"')->execute([':collector' => $user['Id'], ':id' => $paymentId]);
        $pdo->prepare('UPDATE HoaDon SET DaTra=MIN(TongTien,DaTra+:amount) WHERE Id=:id')->execute([':amount' => $amount, ':id' => $invoice['Id']]);
        $pdo->prepare('UPDATE HoaDon SET TrangThai=CASE WHEN DaTra>=TongTien THEN "DaThanhToan" ELSE "ThanhToanMotPhan" END WHERE Id=:id')->execute([':id' => $invoice['Id']]);
        $pdo->commit();
        $notifyRoom((int)$invoice['HopDongId'], 'Thanh toán đã được xác nhận', 'Chủ trọ đã xác nhận thanh toán hóa đơn phòng ' . $invoice['SoPhong'] . '.');
        successResponse([], 'Đã xác nhận thanh toán.');
    }

    if ($method === 'GET') {
        if ($action === 'boLoc') {
            $scope = ''; $params = [];
            if ($isTenant) { $scope = ' AND (nt.TaiKhoanId=:tenant OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=hd.Id AND tv.TaiKhoanId=:tenant AND tv.IsDeleted=0))'; $params[':tenant']=$user['Id']; }
            elseif ($role === 'chutro') { $scope = ' AND k.TaiKhoanId=:owner'; $params[':owner']=$user['Id']; }
            $rows=$pdo->prepare('SELECT DISTINCT k.Id AS KhuId,k.TenKhu,d.Id AS DayId,d.TenDay,h.Thang,h.Nam'.$base.$scope.' ORDER BY h.Nam DESC,h.Thang DESC,k.TenKhu,d.TenDay'); $rows->execute($params);
            successResponse($rows->fetchAll(),'Tùy chọn bộ lọc hóa đơn');
        }
        if ($action === 'thongKe') {
            $sql='SELECT COUNT(*) AS Tong, SUM(CASE WHEN h.TrangThai="DaThanhToan" THEN 1 ELSE 0 END) AS DaThanhToan, SUM(CASE WHEN h.TrangThai="ChuaThanhToan" THEN 1 ELSE 0 END) AS ChuaThanhToan, SUM(CASE WHEN h.TrangThai="ThanhToanMotPhan" THEN 1 ELSE 0 END) AS MotPhan'.$base;$params=[];
            if ($isTenant) {$sql.=' AND (nt.TaiKhoanId=:tenant OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=hd.Id AND tv.TaiKhoanId=:tenant AND tv.IsDeleted=0))';$params[':tenant']=$user['Id'];} elseif($role==='chutro'){$sql.=' AND k.TaiKhoanId=:owner';$params[':owner']=$user['Id'];}
            $month=(int)($_GET['thang']??0);$year=(int)($_GET['nam']??0);$area=(int)($_GET['khuId']??0);$row=(int)($_GET['dayId']??0);if($month>=1&&$month<=12){$sql.=' AND h.Thang=:month';$params[':month']=$month;}if($year>0){$sql.=' AND h.Nam=:year';$params[':year']=$year;}if($area>0){$sql.=' AND k.Id=:area';$params[':area']=$area;}if($row>0){$sql.=' AND d.Id=:row';$params[':row']=$row;}$stmt=$pdo->prepare($sql);$stmt->execute($params);successResponse($stmt->fetch()?:[],'Thống kê hóa đơn');
        }
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $row = $isTenant ? $canTenantInvoice($id) : $canManageInvoice($id);
            successResponse($row, 'Chi tiết hóa đơn');
        }
        $sql = 'SELECT h.Id,h.HopDongId,h.Thang,h.Nam,h.TienPhong,h.TienDien,h.TienNuoc,h.TienDichVu,h.TongTien,h.DaTra,h.HanThanhToan,h.NgayTao,CASE WHEN EXISTS(SELECT 1 FROM ThanhToan pending WHERE pending.HoaDonId=h.Id AND pending.IsDeleted=0 AND pending.TrangThai="ChoXacNhan") THEN "ChoXacNhanThanhToan" ELSE h.TrangThai END AS TrangThai,p.SoPhong,d.TenDay,k.TenKhu,nt.HoTen AS NguoiThue' . $base;
        $params = [];
        if ($isTenant) { $sql .= ' AND (nt.TaiKhoanId=:tenant OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=hd.Id AND tv.TaiKhoanId=:tenant AND tv.IsDeleted=0))'; $params[':tenant'] = $user['Id']; }
        elseif ($role === 'chutro') { $sql .= ' AND k.TaiKhoanId=:owner'; $params[':owner'] = $user['Id']; }
        $month=(int)($_GET['thang']??0); $year=(int)($_GET['nam']??0); $area=(int)($_GET['khuId']??0); $row=(int)($_GET['dayId']??0);
        if($month>=1 && $month<=12){$sql.=' AND h.Thang=:month';$params[':month']=$month;}
        if($year>0){$sql.=' AND h.Nam=:year';$params[':year']=$year;}
        if($area>0){$sql.=' AND k.Id=:area';$params[':area']=$area;}
        if($row>0){$sql.=' AND d.Id=:row';$params[':row']=$row;}
        if(($_GET['phanTrang']??'')==='1'){
            $countSql='SELECT COUNT(*)'.substr($sql,strpos($sql,' FROM HoaDon'));$count=$pdo->prepare($countSql);$count->execute($params);$total=(int)$count->fetchColumn();$limit=max(5,min(100,(int)($_GET['gioiHan']??10)));$page=max(1,(int)($_GET['trang']??1));$pages=max(1,(int)ceil($total/$limit));$page=min($page,$pages);
            $stmt=$pdo->prepare($sql.' ORDER BY h.Nam DESC,h.Thang DESC,h.Id DESC LIMIT :limit OFFSET :offset');foreach($params as $key=>$value)$stmt->bindValue($key,$value);$stmt->bindValue(':limit',$limit,PDO::PARAM_INT);$stmt->bindValue(':offset',($page-1)*$limit,PDO::PARAM_INT);$stmt->execute();
            successResponse(['items'=>$stmt->fetchAll(),'pagination'=>['trang'=>$page,'gioiHan'=>$limit,'tong'=>$total,'tongTrang'=>$pages]],'Danh sách hóa đơn');
        }
        $stmt = $pdo->prepare($sql . ' ORDER BY h.Id DESC'); $stmt->execute($params);
        successResponse($stmt->fetchAll(), $isTenant ? 'Hóa đơn của bạn' : 'Danh sách hóa đơn');
    }
    if ($method === 'POST') {
        if ($isTenant) errorResponse('Người thuê không thể tạo hóa đơn.', 403);
        $input = readJsonBody(); $contractId = (int)($input['HopDongId'] ?? 0);
        if (!$contractId) errorResponse('Thiếu HopDongId.', 422);
        $contractInvoice = $pdo->prepare('SELECT h.Id,h.PhongId,p.SoPhong,k.TaiKhoanId AS ChuTroId FROM HopDong h JOIN Phong p ON p.Id=h.PhongId AND p.IsDeleted=0 JOIN Day d ON d.Id=p.DayId AND d.IsDeleted=0 JOIN Khu k ON k.Id=d.KhuId AND k.IsDeleted=0 WHERE h.Id=:id AND h.IsDeleted=0');
        $contractInvoice->execute([':id' => $contractId]); $contract = $contractInvoice->fetch();
        if (!$contract || ($role === 'chutro' && (int)$contract['ChuTroId'] !== (int)$user['Id'])) errorResponse('Không tìm thấy hợp đồng hoặc bạn không có quyền.', 404);
        $month = max(1, min(12, (int)($input['Thang'] ?? date('n')))); $year = max(2020, (int)($input['Nam'] ?? date('Y')));
        $exists = $pdo->prepare('SELECT Id FROM HoaDon WHERE HopDongId=:contract AND Thang=:month AND Nam=:year AND IsDeleted=0'); $exists->execute([':contract' => $contractId, ':month' => $month, ':year' => $year]);
        if ($exists->fetch()) errorResponse('Hóa đơn của kỳ này đã tồn tại.', 409);
        $rent=(int)($input['TienPhong'] ?? 0); $electric=(int)($input['TienDien'] ?? 0); $water=(int)($input['TienNuoc'] ?? 0); $service=(int)($input['TienDichVu'] ?? 0);
        $pdo->prepare('INSERT INTO HoaDon(HopDongId,Thang,Nam,TienPhong,TienDien,TienNuoc,TienDichVu,TongTien,DaTra,TrangThai,HanThanhToan) VALUES(:contract,:month,:year,:rent,:electric,:water,:service,:total,0,"ChuaThanhToan",:due)')->execute([':contract' => $contractId, ':month' => $month, ':year' => $year, ':rent' => $rent, ':electric' => $electric, ':water' => $water, ':service' => $service, ':total' => $rent + $electric + $water + $service, ':due' => $input['HanThanhToan'] ?? null]);
        $id = (int)$pdo->lastInsertId(); $notifyRoom($contractId, 'Hóa đơn tiền trọ mới', 'Phòng ' . $contract['SoPhong'] . ' có hóa đơn tháng ' . $month . '/' . $year . '.');
        successResponse(['id' => $id], 'Tạo hóa đơn thành công.', 201);
    }
    errorResponse('Phương thức không hỗ trợ.', 405);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    errorResponse($e->getMessage(), 500);
}
