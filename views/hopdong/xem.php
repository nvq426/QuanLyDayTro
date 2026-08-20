<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
requireLogin();
$pdo=getDb(); $user=currentUser(); $id=(int)($_GET['id']??0);
$sql='SELECT h.*,p.SoPhong,p.DienTich,d.TenDay,k.TenKhu,k.DiaChi AS DiaChiKhu,nt.HoTen AS BenBHoTen,nt.CCCD AS BenBCCCD,nt.NgaySinh AS BenBNgaySinh,nt.SoDienThoai AS BenBSoDienThoai,nt.DiaChiThuongTru AS BenBDiaChi FROM HopDong h JOIN Phong p ON p.Id=h.PhongId JOIN Day d ON d.Id=p.DayId JOIN Khu k ON k.Id=d.KhuId JOIN NguoiThue nt ON nt.Id=h.NguoiThueId WHERE h.Id=:id';
$params=[':id'=>$id]; if(($user['VaiTro']??'')==='chutro'){$sql.=' AND k.TaiKhoanId=:owner';$params[':owner']=(int)$user['Id'];} if(($user['VaiTro']??'')==='nguoithue'){$sql.=' AND (nt.TaiKhoanId=:tenant OR EXISTS(SELECT 1 FROM ThanhVienPhong tv WHERE tv.HopDongId=h.Id AND tv.TaiKhoanId=:tenant AND COALESCE(tv.IsDeleted,0)=0))';$params[':tenant']=(int)$user['Id'];}
$stmt=$pdo->prepare($sql);$stmt->execute($params);$h=$stmt->fetch(); if(!$h){http_response_code(404);exit('Không tìm thấy hợp đồng.');}
$m=$pdo->prepare('SELECT HoTen,NgaySinh,CCCD,SoDienThoai FROM ThanhVienPhong WHERE HopDongId=:id ORDER BY Id');$m->execute([':id'=>$id]);$members=$m->fetchAll();
$safe=fn($value)=>htmlspecialchars((string)($value??''),ENT_QUOTES,'UTF-8');
$dateParts=function($date){$p=explode('-',substr((string)$date,0,10));return count($p)===3?[$p[2],$p[1],$p[0]]:['','',''];};
[$ld,$lm,$ly]=$dateParts($h['NgayTao']??date('Y-m-d'));[$sd,$sm,$sy]=$dateParts($h['NgayBatDau']);[$ed,$em,$ey]=$dateParts($h['NgayKetThuc']);
$memberRows='';foreach($members as $i=>$x){$memberRows.='<tr><td class="center">'.($i+1).'</td><td class="name">'.$safe($x['HoTen']).'</td><td>'.$safe(implode('/',$dateParts($x['NgaySinh']))).'</td><td>'.$safe($x['CCCD']).'</td><td>'.$safe($x['SoDienThoai']).'</td></tr>';}
if(!$memberRows)$memberRows='<tr><td colspan="5" class="center">Chưa có thành viên</td></tr>';
$changes='<tr><td colspan="6" class="center">Chưa có thay đổi / phụ lục</td></tr>';
$money=fn($v)=>number_format((int)$v,0,'.',',');
$replace=[
 '{{so_hop_dong}}'=>$safe($h['SoHopDong']?:$h['Id']), '{{ngay_lap}}'=>$ld, '{{thang_lap}}'=>$lm, '{{nam_lap}}'=>$ly, '{{dia_chi_lap_hop_dong}}'=>$safe($h['DiaChiKhu']),
 '{{ben_a_ho_ten}}'=>$safe($h['BenAHoTen']), '{{ben_a_ngay_sinh}}'=>implode('/',$dateParts($h['BenANgaySinh'])), '{{ben_a_dia_chi_thuong_tru}}'=>$safe($h['BenADiaChi']), '{{ben_a_cccd}}'=>$safe($h['BenACCCD']), '{{ben_a_so_dien_thoai}}'=>$safe($h['BenASoDienThoai']),
 '{{ben_b_dai_dien_ho_ten}}'=>$safe($h['BenBHoTen']), '{{ben_b_dai_dien_ngay_sinh}}'=>implode('/',$dateParts($h['BenBNgaySinh'])), '{{ben_b_dai_dien_dia_chi_thuong_tru}}'=>$safe($h['BenBDiaChi']), '{{ben_b_dai_dien_cccd}}'=>$safe($h['BenBCCCD']), '{{ben_b_dai_dien_so_dien_thoai}}'=>$safe($h['BenBSoDienThoai']),
 '{{danh_sach_thanh_vien}}'=>$memberRows, '{{dia_chi_phong_thue}}'=>$safe($h['DiaChiKhu']), '{{khu_thue}}'=>$safe($h['TenKhu']), '{{day_thue}}'=>$safe($h['TenDay']), '{{so_phong}}'=>$safe($h['SoPhong']), '{{gia_thue}}'=>$money($h['GiaThue']), '{{gia_dien}}'=>$money($h['DonGiaDien']), '{{gia_nuoc}}'=>$money($h['DonGiaNuoc']), '{{tien_dat_coc}}'=>$money($h['TienCoc']),
 '{{ngay_bat_dau}}'=>$sd, '{{thang_bat_dau}}'=>$sm, '{{nam_bat_dau}}'=>$sy, '{{ngay_ket_thuc}}'=>$ed, '{{thang_ket_thuc}}'=>$em, '{{nam_ket_thuc}}'=>$ey, '{{lich_su_thay_doi_thanh_vien}}'=>$changes, '{{ten_truong}}'=>'thông tin'
];
$html=file_get_contents(__DIR__.'/hopdong.html');$html=str_replace(array_keys($replace),array_values($replace),$html);
$html=str_replace('</body>','<div style="position:fixed;right:18px;top:18px;font-family:Arial;z-index:5"><button onclick="window.print()">In / Lưu PDF</button></div></body>',$html);
echo $html;
