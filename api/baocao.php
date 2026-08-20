<?php
require __DIR__.'/../includes/db.php'; require __DIR__.'/../includes/auth.php'; require __DIR__.'/../includes/response.php';
if (!function_exists('requireRole')) { function requireRole(array $allowedRoles): void { $current=currentUser(); if (!$current || !in_array($current['VaiTro']??'', $allowedRoles, true)) errorResponse('Bạn không có quyền truy cập.',403); } }
try {
    $pdo=getDb(); requireRole(['admin','chutro']); $user=currentUser();
    $month=max(1,min(12,(int)($_GET['thang']??date('n')))); $year=max(2020,(int)($_GET['nam']??date('Y')));
    $ownerSql=''; $ownerParams=[];
    if(($user['VaiTro']??'')==='chutro'){$ownerSql=' AND k.TaiKhoanId=:owner';$ownerParams[':owner']=$user['Id'];}
    $base=' FROM HoaDon h JOIN HopDong hd ON hd.Id=h.HopDongId JOIN Phong p ON p.Id=hd.PhongId JOIN Day d ON d.Id=p.DayId JOIN Khu k ON k.Id=d.KhuId JOIN NguoiThue n ON n.Id=hd.NguoiThueId WHERE COALESCE(h.IsDeleted,0)=0'.$ownerSql;
    if(($_GET['action']??'')==='ky'){$q=$pdo->prepare('SELECT DISTINCT h.Thang,h.Nam'.$base.' ORDER BY h.Nam DESC,h.Thang DESC');$q->execute($ownerParams);successResponse($q->fetchAll(),'Danh sách kỳ có dữ liệu');exit;}
    $params=array_merge([':month'=>$month,':year'=>$year],$ownerParams); $from=$base.' AND h.Thang=:month AND h.Nam=:year';
    $q=$pdo->prepare('SELECT COALESCE(SUM(h.TongTien),0) Tong,COALESCE(SUM(h.DaTra),0) DaThu,COALESCE(SUM(h.TongTien-h.DaTra),0) CongNo,COALESCE(SUM(h.TienPhong),0) TienPhong,COALESCE(SUM(h.TienDien),0) TienDien,COALESCE(SUM(h.TienNuoc),0) TienNuoc,COALESCE(SUM(h.TienDichVu),0) DichVu,COUNT(*) SoHoaDon'.$from);$q->execute($params);$summary=$q->fetch();
    $q=$pdo->prepare('SELECT k.TenKhu,d.TenDay,p.SoPhong,n.HoTen,h.TienPhong,h.TienDien,h.TienNuoc,h.TienDichVu,h.TongTien,h.DaTra,h.TongTien-h.DaTra ConNo,h.HanThanhToan,h.TrangThai'.$from.' ORDER BY k.Id,d.Id,p.SoPhong');$q->execute($params);$rows=$q->fetchAll();
    if(($_GET['export']??'')==='excel'){
        $escape=static fn($value)=>htmlspecialchars((string)$value,ENT_XML1|ENT_QUOTES,'UTF-8');
        $cell=static function($value,$type='String')use($escape){return '<Cell><Data ss:Type="'.$type.'">'.$escape($value).'</Data></Cell>';};
        $row=static function(array $values)use($cell){$out='<Row>';foreach($values as $value)$out.=$cell($value,is_numeric($value)?'Number':'String');return $out.'</Row>';};
        $grouped=[];foreach($rows as $item){$key=$item['TenKhu'].' - '.$item['TenDay'];if(!isset($grouped[$key]))$grouped[$key]=['HoaDon'=>0,'Tong'=>0,'DaThu'=>0,'ConNo'=>0];$grouped[$key]['HoaDon']++;$grouped[$key]['Tong']+=(int)$item['TongTien'];$grouped[$key]['DaThu']+=(int)$item['DaTra'];$grouped[$key]['ConNo']+=(int)$item['ConNo'];}
        $xml='<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?><Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"><Styles><Style ss:ID="head"><Font ss:Bold="1"/><Interior ss:Color="#DCE6F1" ss:Pattern="Solid"/></Style><Style ss:ID="money"><NumberFormat ss:Format="#,##0 &quot;₫&quot;"/></Style></Styles>';
        $xml.='<Worksheet ss:Name="Tổng quan"><Table>'.$row(['BÁO CÁO THÁNG '.$month.'/'.$year]).'<Row><Cell ss:StyleID="head"><Data ss:Type="String">Chỉ tiêu</Data></Cell><Cell ss:StyleID="head"><Data ss:Type="String">Giá trị</Data></Cell></Row>';
        foreach(['Tổng hóa đơn'=>'Tong','Đã thu'=>'DaThu','Còn nợ'=>'CongNo','Tiền phòng'=>'TienPhong','Tiền điện'=>'TienDien','Tiền nước'=>'TienNuoc','Tiền dịch vụ'=>'DichVu','Số hóa đơn'=>'SoHoaDon'] as $label=>$key)$xml.='<Row>'.$cell($label).'<Cell ss:StyleID="money"><Data ss:Type="Number">'.(int)($summary[$key]??0).'</Data></Cell></Row>';
        $xml.='</Table></Worksheet><Worksheet ss:Name="Chi tiết hóa đơn"><Table><Row>';foreach(['Khu','Dãy','Phòng','Người thuê','Tiền phòng','Tiền điện','Tiền nước','Dịch vụ','Tổng tiền','Đã thu','Còn nợ','Hạn đóng','Trạng thái'] as $head)$xml.='<Cell ss:StyleID="head"><Data ss:Type="String">'.$escape($head).'</Data></Cell>';$xml.='</Row>';
        foreach($rows as $item){$xml.='<Row>'.$cell($item['TenKhu']).$cell($item['TenDay']).$cell($item['SoPhong']).$cell($item['HoTen']);foreach(['TienPhong','TienDien','TienNuoc','TienDichVu','TongTien','DaTra','ConNo'] as $key)$xml.='<Cell ss:StyleID="money"><Data ss:Type="Number">'.(int)$item[$key].'</Data></Cell>';$xml.=$cell($item['HanThanhToan']).$cell($item['TrangThai']).'</Row>';}
        $xml.='</Table></Worksheet><Worksheet ss:Name="Theo khu dãy"><Table><Row>';foreach(['Khu / Dãy','Số hóa đơn','Tổng tiền','Đã thu','Còn nợ'] as $head)$xml.='<Cell ss:StyleID="head"><Data ss:Type="String">'.$escape($head).'</Data></Cell>';$xml.='</Row>';
        foreach($grouped as $name=>$item)$xml.='<Row>'.$cell($name).$cell($item['HoaDon'],'Number').'<Cell ss:StyleID="money"><Data ss:Type="Number">'.$item['Tong'].'</Data></Cell><Cell ss:StyleID="money"><Data ss:Type="Number">'.$item['DaThu'].'</Data></Cell><Cell ss:StyleID="money"><Data ss:Type="Number">'.$item['ConNo'].'</Data></Cell></Row>';
        $xml.='</Table></Worksheet></Workbook>';header('Content-Type: application/vnd.ms-excel; charset=UTF-8');header('Content-Disposition: attachment; filename="bao-cao-'.$month.'-'.$year.'.xls"');header('Cache-Control: max-age=0');echo $xml;exit;
    }
    successResponse(['thang'=>$month,'nam'=>$year,'chuTro'=>$user['VaiTro']==='chutro'?$user['HoTen']:'Toàn hệ thống','tongQuan'=>$summary,'chiTiet'=>$rows],'Báo cáo chi tiết');
} catch(Throwable $e) { errorResponse($e->getMessage(),500); }
