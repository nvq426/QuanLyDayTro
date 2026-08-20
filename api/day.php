<?php
require __DIR__ . '/../includes/db.php'; require __DIR__ . '/../includes/auth.php'; require __DIR__ . '/../includes/response.php';
try {
    $pdo=getDb(); $method=$_SERVER['REQUEST_METHOD']; $user=currentUser(); requireRole(['admin','chutro']);
    $isAdmin=($user['VaiTro']??'')==='admin'; $ownerId=(int)($user['Id']??0);
    $checkKhu=function(int $khuId) use($pdo,$isAdmin,$ownerId): void {
        $sql='SELECT 1 FROM Khu WHERE Id=:id AND IsDeleted=0'.($isAdmin?'':' AND TaiKhoanId=:owner'); $params=[':id'=>$khuId]; if(!$isAdmin)$params[':owner']=$ownerId;
        $stmt=$pdo->prepare($sql); $stmt->execute($params); if(!$stmt->fetch()) errorResponse('Khu không tồn tại hoặc không thuộc quyền quản lý của bạn',404);
    };
    $where=$isAdmin?' WHERE COALESCE(d.IsDeleted,0)=0 AND COALESCE(k.IsDeleted,0)=0':' WHERE COALESCE(d.IsDeleted,0)=0 AND COALESCE(k.IsDeleted,0)=0 AND k.TaiKhoanId=:owner'; $params=$isAdmin?[]:[':owner'=>$ownerId];
    if($method==='GET') {
        $id=(int)($_GET['id']??0); $khuId=(int)($_GET['khuId']??0);
        $sql='SELECT d.*, k.TaiKhoanId FROM Day d JOIN Khu k ON k.Id=d.KhuId'.($id?' WHERE d.Id=:id AND COALESCE(d.IsDeleted,0)=0 AND COALESCE(k.IsDeleted,0)=0'.($isAdmin?'':' AND k.TaiKhoanId=:owner'):($khuId?' WHERE d.KhuId=:khu AND COALESCE(d.IsDeleted,0)=0 AND COALESCE(k.IsDeleted,0)=0'.($isAdmin?'':' AND k.TaiKhoanId=:owner'):$where)).' ORDER BY d.Id ASC';
        $p=$id?[':id'=>$id]:($khuId?[':khu'=>$khuId]:$params); if(!$isAdmin&&($id||$khuId))$p[':owner']=$ownerId;
        $stmt=$pdo->prepare($sql);$stmt->execute($p);$rows=$stmt->fetchAll(); if($id&&!$rows)errorResponse('Không tìm thấy dãy',404); successResponse($id?$rows[0]:$rows,'Danh sách dãy');
    }
    if($method==='POST'){ $in=readJsonBody(); if(empty($in['KhuId'])||empty($in['TenDay']))errorResponse('Thiếu KhuId hoặc Tên dãy',400); $checkKhu((int)$in['KhuId']); $s=$pdo->prepare('INSERT INTO Day (KhuId,TenDay,MoTa) VALUES (:k,:t,:m)');$s->execute([':k'=>$in['KhuId'],':t'=>$in['TenDay'],':m'=>$in['MoTa']??null]);successResponse(['id'=>$pdo->lastInsertId()],'Tạo dãy thành công',201);}
    if($method==='PUT'){ $id=(int)($_GET['id']??0); $in=readJsonBody(); $row=$pdo->prepare('SELECT KhuId FROM Day WHERE Id=:id AND IsDeleted=0');$row->execute([':id'=>$id]);$day=$row->fetch();if(!$day)errorResponse('Không tìm thấy dãy',404);$checkKhu((int)$day['KhuId']);$s=$pdo->prepare('UPDATE Day SET TenDay=:t,MoTa=:m WHERE Id=:id AND IsDeleted=0');$s->execute([':t'=>$in['TenDay']??'',':m'=>$in['MoTa']??null,':id'=>$id]);successResponse([],'Cập nhật dãy thành công');}
    if($method==='DELETE'){ $id=(int)($_GET['id']??0);$row=$pdo->prepare('SELECT KhuId FROM Day WHERE Id=:id AND IsDeleted=0');$row->execute([':id'=>$id]);$day=$row->fetch();if(!$day)errorResponse('Không tìm thấy dãy',404);$checkKhu((int)$day['KhuId']);$pdo->prepare('UPDATE Day SET IsDeleted=1 WHERE Id=:id AND IsDeleted=0')->execute([':id'=>$id]);successResponse([],'Đã chuyển dãy vào trạng thái đã xóa');}
    errorResponse('Method không hỗ trợ',405);
}catch(Throwable $e){errorResponse($e->getMessage(),500);}
