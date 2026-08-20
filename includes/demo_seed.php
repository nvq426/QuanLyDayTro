<?php

/** Create deterministic, realistic rental-house demo data for a fresh database. */
function seedDemoData(PDO $pdo): void
{
    $passwordHash = password_hash('123456', PASSWORD_BCRYPT);
    $ownerPassword = password_hash('Thu@123', PASSWORD_BCRYPT);
    // The demo represents one real business: every area, room and transaction
    // belongs to the only landlord account, Lê Thị Thu.
    $owners = [
        ['chutro', 'Lê Thị Thu', 'lethithu@gmail.com', '0918245678', $ownerPassword],
    ];
    $pdo->beginTransaction();
    try {
        $account = $pdo->prepare('INSERT INTO TaiKhoan(TenDangNhap,MatKhau,HoTen,Email,SoDienThoai,VaiTro,TrangThai,NgayTao,ThongTinChuyenKhoan,MaQRThanhToan) VALUES(?,?,?,?,?,?,1,?,?,?)');
        $account->execute(['admin', $passwordHash, 'Quản trị viên', 'admin@trotot.vn', '0900000000', 'admin', '2026-08-01 08:00:00', null, null]);
        $ownerIds = [];
        foreach ($owners as $index => [$username,$name,$email,$phone,$hash]) {
            $account->execute([$username,$hash,$name,$email,$phone,'chutro','2026-08-16 09:11:00','Ngân hàng MB Bank - STK 0123456789 - '.$name,'/assets/pics/logo.webp']);
            $ownerIds[] = (int)$pdo->lastInsertId();
        }

        $areas = [
            ['Nhà trọ Bình Minh', '125 Nguyễn Văn Cừ, P. Hố Nai, Biên Hòa, Đồng Nai', 0],
            ['Nhà trọ An Phúc', '48 Đồng Khởi, P. Tân Hiệp, Biên Hòa, Đồng Nai', 0],
        ];
        $insertArea = $pdo->prepare('INSERT INTO Khu(TenKhu,DiaChi,MoTa,TaiKhoanId,IsDeleted) VALUES(?,?,?,?,0)');
        $insertRow = $pdo->prepare('INSERT INTO Day(KhuId,TenDay,MoTa,IsDeleted) VALUES(?,?,?,0)');
        $insertRoom = $pdo->prepare('INSERT INTO Phong(DayId,SoPhong,DienTich,GiaThue,TrangThai,MoTa,IsDeleted) VALUES(?,?,?,?,?,?,0)');
        $roomRecords = [];
        foreach ($areas as $areaIndex => [$areaName,$address,$ownerIndex]) {
            $insertArea->execute([$areaName,$address,'Khu phòng trọ khép kín, có camera và chỗ để xe.',$ownerIds[$ownerIndex]]);
            $areaId = (int)$pdo->lastInsertId();
            foreach (['Dãy A','Dãy B'] as $rowIndex => $rowName) {
                $insertRow->execute([$areaId,$rowName,'Dãy phòng trọ '.($rowIndex === 0 ? 'tầng trệt' : 'có gác')]);
                $rowId = (int)$pdo->lastInsertId();
                for ($roomNo=1; $roomNo<=5; $roomNo++) {
                    $serial = count($roomRecords) + 1;
                    $status = $serial % 10 === 0 ? 'BaoTri' : ($serial % 8 === 0 ? 'Trong' : 'DangThue');
                    $number = chr(65 + $areaIndex).($rowIndex + 1).str_pad((string)$roomNo, 2, '0', STR_PAD_LEFT);
                    $rent = 2200000 + (($areaIndex * 2 + $rowIndex) * 150000) + ($roomNo * 100000);
                    $insertRoom->execute([$rowId,$number,18 + $roomNo * 2 + $rowIndex * 1.5,$rent,$status,$rowIndex ? 'Phòng có gác, cửa sổ thoáng.' : 'Phòng trọ khép kín, có kệ bếp.']);
                    $roomRecords[] = ['id'=>(int)$pdo->lastInsertId(),'status'=>$status,'rent'=>$rent,'ownerId'=>$ownerIds[$ownerIndex],'ownerName'=>$owners[$ownerIndex][1],'number'=>$number];
                }
            }
        }

        $tenantNames = ['Nguyễn Minh Anh','Trần Thảo Chi','Lê Quốc Dũng','Phạm Ngọc Hà','Hoàng Gia Khang','Võ Thanh Linh','Đặng Khánh Minh','Bùi Đức Phong','Đỗ Quỳnh Như','Dương Nhật Nam','Lý Bảo Ngân','Mai Anh Thư'];
        $insertTenantAccount = $pdo->prepare('INSERT INTO TaiKhoan(TenDangNhap,MatKhau,HoTen,Email,SoDienThoai,VaiTro,TrangThai,NgayTao) VALUES(?,?,?,?,?,?,1,?)');
        $insertTenant = $pdo->prepare('INSERT INTO NguoiThue(HoTen,CCCD,NgaySinh,GioiTinh,SoDienThoai,Email,DiaChiThuongTru,NgheNghiep,TaiKhoanId,IsDeleted) VALUES(?,?,?,?,?,?,?,?,?,0)');
        $insertContract = $pdo->prepare('INSERT INTO HopDong(PhongId,NguoiThueId,NgayBatDau,NgayKetThuc,TienCoc,GiaThue,DieuKhoan,SoHopDong,BenAHoTen,BenANgaySinh,BenADiaChi,BenACCCD,BenASoDienThoai,DonGiaDien,DonGiaNuoc,ChiSoDienDau,ChiSoNuocDau,TrangThai,IsDeleted) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"DangHieuLuc",0)');
        $insertMember = $pdo->prepare('INSERT INTO ThanhVienPhong(HopDongId,HoTen,CCCD,NgaySinh,SoDienThoai,QuanHe,Loai,TaiKhoanId,TrangThaiTamTru,GioiTinh,Email,DiaChiThuongTru,NgheNghiep,IsDeleted) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,0)');
        $insertMeter = $pdo->prepare('INSERT INTO ChiSoDienNuoc(PhongId,Thang,Nam,ChiSoDienDau,ChiSoDienCuoi,DonGiaDien,ChiSoNuocDau,ChiSoNuocCuoi,DonGiaNuoc,TienDichVu,GhiChu,IsDeleted) VALUES(?,?,?,?,?,?,?,?,?,?,?,0)');
        $insertInvoice = $pdo->prepare('INSERT INTO HoaDon(HopDongId,Thang,Nam,TienPhong,TienDien,TienNuoc,TienDichVu,TongTien,DaTra,TrangThai,HanThanhToan,IsDeleted) VALUES(?,?,?,?,?,?,?,?,?,?,?,0)');
        $insertPayment = $pdo->prepare('INSERT INTO ThanhToan(HoaDonId,SoTien,PhuongThuc,GhiChu,NgayThanhToan,NguoiThu,TrangThai,IsDeleted) VALUES(?,?,?,?,?,?,"DaXacNhan",0)');
        $insertNotification = $pdo->prepare('INSERT INTO ThongBao(TaiKhoanId,TieuDe,NoiDung,Loai,DaDoc,NgayTao,IsDeleted) VALUES(?,?,?,?,?,?,0)');
        $insertStay = $pdo->prepare('INSERT INTO TamTru(PhongId,NguoiThueId,HoTen,CCCDKhach,QuanHe,NgayBatDau,NgayKetThuc,GhiChu,TrangThai,Loai,TrangThaiXuLy,IsDeleted) VALUES(?,?,?,?,?,?,?,?,?,?,?,0)');

        $tenantNumber = 0;
        foreach ($roomRecords as $room) {
            if ($room['status'] !== 'DangThue') continue;
            $tenantNumber++;
            $name = $tenantNames[($tenantNumber-1) % count($tenantNames)];
            $cccd = '079'.str_pad((string)(260000000 + $tenantNumber), 9, '0', STR_PAD_LEFT);
            $phone = '09'.str_pad((string)(10000000 + $tenantNumber), 8, '0', STR_PAD_LEFT);
            $username = 'nt'.str_pad((string)$tenantNumber, 3, '0', STR_PAD_LEFT);
            $email = $username.'@trotot.vn';
            $insertTenantAccount->execute([$username,$passwordHash,$name,$email,$phone,'nguoithue','2026-01-'.str_pad((string)(($tenantNumber%27)+1),2,'0',STR_PAD_LEFT).' 08:00:00']);
            $tenantAccountId = (int)$pdo->lastInsertId();
            $gender = $tenantNumber % 2 ? 'Nam' : 'Nữ';
            $insertTenant->execute([$name,$cccd,'200'.($tenantNumber%5).'-'.str_pad((string)(($tenantNumber%12)+1),2,'0',STR_PAD_LEFT).'-'.str_pad((string)(($tenantNumber%27)+1),2,'0',STR_PAD_LEFT),$gender,$phone,$email,'Đồng Nai','Nhân viên văn phòng',$tenantAccountId]);
            $tenantId = (int)$pdo->lastInsertId();
            $contractStart = '2026-01-01';
            $contractEnd = '2026-12-31';
            $insertContract->execute([$room['id'],$tenantId,$contractStart,$contractEnd,$room['rent']*2,$room['rent'],'Thanh toán tiền phòng trước ngày 05 hằng tháng; không tự ý chuyển nhượng phòng; giữ gìn vệ sinh, an ninh và tài sản chung.','HD-2026-'.str_pad((string)$tenantNumber,4,'0',STR_PAD_LEFT),$room['ownerName'],'1980-04-18','125 Nguyễn Văn Cừ, P. Hố Nai, Biên Hòa, Đồng Nai','075180012345','0918245678',3500,18000,100+$tenantNumber*3,10+$tenantNumber]);
            $contractId = (int)$pdo->lastInsertId();

            if ($tenantNumber % 2 === 0) {
                $memberName = ['Nguyễn Hoài An','Trần Thanh Bình','Lê Mỹ Duyên','Phạm Tuấn Kiệt','Võ Ngọc Mai','Đỗ Minh Quân'][$tenantNumber % 6];
                $memberCccd = '077'.str_pad((string)(270000000 + $tenantNumber),9,'0',STR_PAD_LEFT);
                $memberUser = 'tv'.str_pad((string)$tenantNumber,3,'0',STR_PAD_LEFT);
                $memberPhone = '08'.str_pad((string)(20000000 + $tenantNumber),8,'0',STR_PAD_LEFT);
                $insertTenantAccount->execute([$memberUser,$passwordHash,$memberName,$memberUser.'@trotot.vn',$memberPhone,'nguoithue','2026-01-15 08:00:00']);
                $memberAccount = (int)$pdo->lastInsertId();
                $insertMember->execute([$contractId,$memberName,$memberCccd,'2003-06-15',$memberPhone,'Bạn cùng phòng','ThanhVienPhong',$memberAccount,$tenantNumber%4===0?'DaDangKyUBND':'ChuaKhaiBaoUBND',$tenantNumber%4===0?'Nữ':'Nam',$memberUser.'@trotot.vn','Đồng Nai','Sinh viên']);
            }

            foreach ([7,8] as $month) {
                $electricStart = 100+$tenantNumber*3+($month===8?68:0);
                $electricUse = 55+($tenantNumber%35);
                $waterStart = 10+$tenantNumber+($month===8?8:0);
                $waterUse = 5+($tenantNumber%8);
                $service = 120000 + (($tenantNumber%3)*30000);
                $electric = $electricUse*3500; $water=$waterUse*18000; $total=$room['rent']+$electric+$water+$service;
                $paid = $month===7 ? $total : ($tenantNumber%3===0 ? $total : ($tenantNumber%3===1 ? (int)($total/2) : 0));
                $status = $paid===$total ? 'DaThanhToan' : ($paid>0 ? 'ThanhToanMotPhan' : 'ChuaThanhToan');
                $insertMeter->execute([$room['id'],$month,2026,$electricStart,$electricStart+$electricUse,3500,$waterStart,$waterStart+$waterUse,18000,$service,'Điện, nước và dịch vụ tháng '.$month.'/2026']);
                $insertInvoice->execute([$contractId,$month,2026,$room['rent'],$electric,$water,$service,$total,$paid,$status,'2026-'.str_pad((string)$month,2,'0',STR_PAD_LEFT).'-05']);
                $invoiceId=(int)$pdo->lastInsertId();
                if($paid>0) $insertPayment->execute([$invoiceId,$paid,$tenantNumber%2?'ChuyenKhoan':'TienMat','Thanh toán mẫu','2026-'.str_pad((string)$month,2,'0',STR_PAD_LEFT).'-03 10:00:00',$room['ownerId']]);
            }
            $insertNotification->execute([$tenantAccountId,'Hóa đơn tháng 08/2026','Phòng '.$room['number'].' đã có hóa đơn tiền phòng, điện nước tháng 08/2026.','HoaDon',0,'2026-08-01 09:00:00']);
            if ($tenantNumber % 3 === 0) $insertStay->execute([$room['id'],$tenantId,'Người lưu trú '.$tenantNumber,'076'.str_pad((string)(280000000+$tenantNumber),9,'0',STR_PAD_LEFT),'Bạn bè','2026-08-10','2026-08-15','Khai báo lưu trú mẫu',$tenantNumber%2,'LuuTru',$tenantNumber%2?'DaXacNhanChuTro':'ChoXacNhan']);
        }
        $ownerId = $ownerIds[0];
        $pdo->prepare('INSERT INTO CauHinhDienNuoc(TaiKhoanId,DonGiaDien,DonGiaNuoc,NgayCapNhat) VALUES(?,?,?,?)')
            ->execute([$ownerId,3500,18000,'2026-08-01 08:15:00']);
        $insertNotification->execute([$ownerId,'Tổng hợp vận hành tháng 08/2026','Các chỉ số điện nước tháng 08 đã được ghi; vui lòng theo dõi các hóa đơn chưa thanh toán.','ThongTin',0,'2026-08-16 09:11:00']);

        $tenantRows = $pdo->query("SELECT nt.Id, nt.TaiKhoanId, hd.PhongId FROM NguoiThue nt JOIN HopDong hd ON hd.NguoiThueId=nt.Id WHERE hd.TrangThai='DangHieuLuc' ORDER BY nt.Id")->fetchAll();
        if ($tenantRows) {
            $firstTenant = $tenantRows[0];
            $pdo->prepare('INSERT INTO SuCo(PhongId,NguoiBaoId,TieuDe,NoiDung,TrangThai,NguoiXuLyId,NgayTao,NgayCapNhat,IsDeleted) VALUES(?,?,?,?,?,?,?,?,0)')
                ->execute([$firstTenant['PhongId'],$firstTenant['TaiKhoanId'],'Vòi nước bồn rửa bị rò','Vòi nước khu vực bếp rò nhẹ khi khóa, đề nghị kiểm tra trong tuần.','DaTiepNhan',$ownerId,'2026-08-12 19:20:00','2026-08-13 08:30:00']);
            $pdo->prepare('INSERT INTO YeuCauHoSoNguoiThue(NguoiThueId,Loai,DuLieu,TrangThai,GhiChuChuTro,NguoiXuLyId,NgayTao,NgayXuLy,IsDeleted) VALUES(?,?,?,?,?,?,?,?,0)')
                ->execute([$firstTenant['Id'],'ThongTinCaNhan',json_encode(['SoDienThoai'=>'0911000001','NgheNghiep'=>'Kế toán'],JSON_UNESCAPED_UNICODE),'DaXacNhan','Thông tin hợp lệ.',$ownerId,'2026-08-05 09:10:00','2026-08-05 10:00:00']);
        }

        $insertAudit = $pdo->prepare('INSERT INTO AuditLog(TaiKhoanId,TenDangNhap,HoTen,VaiTro,HanhDong,PhuongThuc,DuongDan,QueryString,DuLieu,DiaChiIP,UserAgent,MaTrangThai,ThanhCong,NgayTao) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $auditRows = [
            [$ownerId,'chutro','Lê Thị Thu','chutro','Đăng nhập','POST','/login.php','',null,'192.168.1.20','Mozilla/5.0',200,1,'2026-08-01 07:55:00'],
            [$ownerId,'chutro','Lê Thị Thu','chutro','Tạo khu trọ','POST','/api/khu.php','',json_encode(['TenKhu'=>'Nhà trọ Bình Minh'],JSON_UNESCAPED_UNICODE),'192.168.1.20','Mozilla/5.0',201,1,'2026-01-02 08:10:00'],
            [$ownerId,'chutro','Lê Thị Thu','chutro','Tạo dãy phòng','POST','/api/day.php','',json_encode(['TenDay'=>'Dãy A'],JSON_UNESCAPED_UNICODE),'192.168.1.20','Mozilla/5.0',201,1,'2026-01-02 08:20:00'],
            [$ownerId,'chutro','Lê Thị Thu','chutro','Tạo phòng','POST','/api/phong.php','',json_encode(['SoPhong'=>'A101','TrangThai'=>'DangThue'],JSON_UNESCAPED_UNICODE),'192.168.1.20','Mozilla/5.0',201,1,'2026-01-02 08:30:00'],
            [$ownerId,'chutro','Lê Thị Thu','chutro','Tạo người thuê','POST','/api/nguoithue.php','',json_encode(['HoTen'=>$tenantNames[0]],JSON_UNESCAPED_UNICODE),'192.168.1.20','Mozilla/5.0',201,1,'2026-01-03 09:00:00'],
            [$ownerId,'chutro','Lê Thị Thu','chutro','Tạo hợp đồng','POST','/api/hopdong.php','',json_encode(['SoHopDong'=>'HD-2026-0001'],JSON_UNESCAPED_UNICODE),'192.168.1.20','Mozilla/5.0',201,1,'2026-01-03 09:20:00'],
            [$ownerId,'chutro','Lê Thị Thu','chutro','Cập nhật cấu hình điện nước','PUT','/api/chisodiennuoc.php','action=config',json_encode(['DonGiaDien'=>3500,'DonGiaNuoc'=>18000],JSON_UNESCAPED_UNICODE),'192.168.1.20','Mozilla/5.0',200,1,'2026-08-01 08:15:00'],
            [$ownerId,'chutro','Lê Thị Thu','chutro','Tạo chỉ số điện nước','POST','/api/chisodiennuoc.php','',json_encode(['Thang'=>8,'Nam'=>2026],JSON_UNESCAPED_UNICODE),'192.168.1.20','Mozilla/5.0',201,1,'2026-08-01 08:30:00'],
            [$ownerId,'chutro','Lê Thị Thu','chutro','Tạo hóa đơn','POST','/api/hoadon.php','',json_encode(['Thang'=>8,'Nam'=>2026],JSON_UNESCAPED_UNICODE),'192.168.1.20','Mozilla/5.0',201,1,'2026-08-01 09:00:00'],
            [$ownerId,'chutro','Lê Thị Thu','chutro','Xác nhận thanh toán','PUT','/api/hoadon.php','action=payment',json_encode(['PhuongThuc'=>'ChuyenKhoan'],JSON_UNESCAPED_UNICODE),'192.168.1.20','Mozilla/5.0',200,1,'2026-08-03 10:05:00'],
            [$ownerId,'chutro','Lê Thị Thu','chutro','Cập nhật sự cố','PUT','/api/suco.php','id=1',json_encode(['TrangThai'=>'DaTiepNhan'],JSON_UNESCAPED_UNICODE),'192.168.1.20','Mozilla/5.0',200,1,'2026-08-13 08:30:00'],
            [null,'chutro','Lê Thị Thu','chutro','Đăng nhập thất bại','POST','/login.php','',null,'192.168.1.20','Mozilla/5.0',401,0,'2026-07-28 21:14:00'],
        ];
        if ($tenantRows) $auditRows[] = [$tenantRows[0]['TaiKhoanId'],'nt001',$tenantNames[0],'nguoithue','Đăng nhập','POST','/login.php','',null,'10.0.0.15','Mozilla/5.0 (Linux; Android 13)',200,1,'2026-08-12 19:10:00'];
        foreach ($auditRows as $auditRow) $insertAudit->execute($auditRow);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}
