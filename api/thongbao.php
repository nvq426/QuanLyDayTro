<?php
require __DIR__ . '/../includes/db.php'; require __DIR__ . '/../includes/auth.php'; require __DIR__ . '/../includes/response.php';
try {
    $pdo=getDb(); requireLogin(); $user=currentUser(); $method=$_SERVER['REQUEST_METHOD'];
    if($method==='GET'){$s=$pdo->prepare('SELECT * FROM ThongBao WHERE TaiKhoanId=:id AND COALESCE(IsDeleted,0)=0 ORDER BY DaDoc ASC, Id DESC');$s->execute([':id'=>$user['Id']]);$items=$s->fetchAll();foreach($items as &$item){$raw=($item['TieuDe']??'').' '.($item['NoiDung']??'');$text=function_exists('mb_strtolower')?mb_strtolower($raw,'UTF-8'):strtolower($raw);if(str_contains($text,'lưu trú')||str_contains($text,'tạm trú'))$item['DuongDan']='/views/tamtru/index.php';elseif(str_contains($text,'sự cố'))$item['DuongDan']='/views/suco/index.php';elseif(str_contains($text,'hồ sơ'))$item['DuongDan']=($user['VaiTro']??'')==='chutro'?'/views/xacnhan/index.php':'/views/hoso/index.php';elseif(($item['Loai']??'')==='HoaDon'||str_contains($text,'hóa đơn')||str_contains($text,'thanh toán'))$item['DuongDan']='/views/hoadon/index.php';elseif(($item['Loai']??'')==='DienNuoc'||str_contains($text,'điện nước'))$item['DuongDan']='/views/chisodiennuoc/index.php';else $item['DuongDan']=null;}unset($item);successResponse($items,'Danh sách thông báo');}
    if($method==='PUT'){$id=(int)($_GET['id']??0);$pdo->prepare('UPDATE ThongBao SET DaDoc=1 WHERE Id=:id AND TaiKhoanId=:u')->execute([':id'=>$id,':u'=>$user['Id']]);successResponse([],'Đã đánh dấu đã đọc');}
    errorResponse('Method không hỗ trợ',405);
}catch(Throwable $e){errorResponse($e->getMessage(),500);}
