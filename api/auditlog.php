<?php
require __DIR__.'/../includes/db.php';require __DIR__.'/../includes/auth.php';require __DIR__.'/../includes/response.php';
try{
    requireRole(['admin']);$pdo=getDb();
    if($_SERVER['REQUEST_METHOD']!=='GET')errorResponse('Chỉ hỗ trợ xem nhật ký.',405);
    $page=max(1,(int)($_GET['trang']??1));$limit=max(10,min(100,(int)($_GET['gioiHan']??25)));
    $where=['1=1'];$params=[];
    $keyword=trim((string)($_GET['tuKhoa']??''));if($keyword!==''){$where[]='(TenDangNhap LIKE :keyword OR HoTen LIKE :keyword OR HanhDong LIKE :keyword OR DiaChiIP LIKE :keyword)';$params[':keyword']='%'.$keyword.'%';}
    $role=trim((string)($_GET['vaiTro']??''));if($role!==''){$where[]='VaiTro=:role';$params[':role']=$role;}
    $from=trim((string)($_GET['tuNgay']??''));if($from!==''){$where[]='date(NgayTao)>=date(:from)';$params[':from']=$from;}
    $to=trim((string)($_GET['denNgay']??''));if($to!==''){$where[]='date(NgayTao)<=date(:to)';$params[':to']=$to;}
    $clause=implode(' AND ',$where);$count=$pdo->prepare('SELECT COUNT(*) FROM AuditLog WHERE '.$clause);$count->execute($params);$total=(int)$count->fetchColumn();$pages=max(1,(int)ceil($total/$limit));$page=min($page,$pages);
    $stmt=$pdo->prepare('SELECT Id,TenDangNhap,HoTen,HanhDong,DiaChiIP,NgayTao FROM AuditLog WHERE '.$clause.' ORDER BY Id DESC LIMIT :limit OFFSET :offset');foreach($params as $key=>$value)$stmt->bindValue($key,$value);$stmt->bindValue(':limit',$limit,PDO::PARAM_INT);$stmt->bindValue(':offset',($page-1)*$limit,PDO::PARAM_INT);$stmt->execute();
    successResponse(['items'=>$stmt->fetchAll(),'pagination'=>['trang'=>$page,'gioiHan'=>$limit,'tong'=>$total,'tongTrang'=>$pages]],'Nhật ký hoạt động');
}catch(Throwable $e){errorResponse($e->getMessage(),500);}
