<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

$user = currentUser();
if (!$user) {
    header('Location: /login.php');
    exit;
}
if (($user['VaiTro'] ?? '') === 'nguoithue') {
    header('Location: /views/thongbao/index.php');
    exit;
}
if (($user['VaiTro'] ?? '') === 'admin') {
    header('Location: /views/taikhoan/index.php');
    exit;
}


$pdo = getDb();
$counts = [
    'phong' => $pdo->query('SELECT COUNT(*) FROM Phong WHERE COALESCE(IsDeleted,0)=0')->fetchColumn(),
    'dangthue' => $pdo->query("SELECT COUNT(*) FROM Phong WHERE TrangThai = 'DangThue' AND COALESCE(IsDeleted,0)=0")->fetchColumn(),
    'trong' => $pdo->query("SELECT COUNT(*) FROM Phong WHERE TrangThai = 'Trong' AND COALESCE(IsDeleted,0)=0")->fetchColumn(),
    'hoadon' => $pdo->query('SELECT COUNT(*) FROM HoaDon WHERE COALESCE(IsDeleted,0)=0')->fetchColumn(),
];

$recent = $pdo->query("SELECT h.Id, p.SoPhong, nt.HoTen, h.TrangThai, h.TongTien, h.NgayTao FROM HoaDon h JOIN HopDong hd ON hd.Id = h.HopDongId AND COALESCE(hd.IsDeleted,0)=0 JOIN Phong p ON p.Id = hd.PhongId AND COALESCE(p.IsDeleted,0)=0 JOIN NguoiThue nt ON nt.Id = hd.NguoiThueId AND COALESCE(nt.IsDeleted,0)=0 WHERE COALESCE(h.IsDeleted,0)=0 ORDER BY h.Id DESC LIMIT 5")->fetchAll();
if (($user['VaiTro'] ?? '') === 'chutro') {
    $scope=' FROM Phong p JOIN Day d ON d.Id=p.DayId JOIN Khu k ON k.Id=d.KhuId WHERE k.TaiKhoanId=:owner AND p.IsDeleted=0';
    $q=$pdo->prepare('SELECT COUNT(*)'.$scope);$q->execute([':owner'=>$user['Id']]);$counts['phong']=$q->fetchColumn();
    $q=$pdo->prepare('SELECT COUNT(*)'.$scope.' AND p.TrangThai="DangThue"');$q->execute([':owner'=>$user['Id']]);$counts['dangthue']=$q->fetchColumn();
    $q=$pdo->prepare('SELECT COUNT(*)'.$scope.' AND p.TrangThai="Trong"');$q->execute([':owner'=>$user['Id']]);$counts['trong']=$q->fetchColumn();
    $q=$pdo->prepare('SELECT COUNT(*) FROM HoaDon h JOIN HopDong hd ON hd.Id=h.HopDongId JOIN Phong p ON p.Id=hd.PhongId JOIN Day d ON d.Id=p.DayId JOIN Khu k ON k.Id=d.KhuId WHERE k.TaiKhoanId=:owner AND h.IsDeleted=0');$q->execute([':owner'=>$user['Id']]);$counts['hoadon']=$q->fetchColumn();
    $q=$pdo->prepare('SELECT h.Id,p.SoPhong,nt.HoTen,h.TrangThai,h.TongTien,h.NgayTao FROM HoaDon h JOIN HopDong hd ON hd.Id=h.HopDongId JOIN Phong p ON p.Id=hd.PhongId JOIN Day d ON d.Id=p.DayId JOIN Khu k ON k.Id=d.KhuId JOIN NguoiThue nt ON nt.Id=hd.NguoiThueId WHERE k.TaiKhoanId=:owner AND h.IsDeleted=0 ORDER BY h.Id DESC LIMIT 5');$q->execute([':owner'=>$user['Id']]);$recent=$q->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trọ Tốt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/assets/pics/logo.webp" type="image/webp" sizes="512x512">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-badge"><img src="/assets/pics/logo.webp" class="brand-logo" alt="Logo Trọ Tốt"></div>
                <div>
                    <div class="brand-title">Trọ Tốt</div>
                    <div class="brand-subtitle">Management System</div>
                </div>
            </div>

            <?php require_once __DIR__ . '/includes/helpers.php'; $avatar = getAvatarUrl($user); ?>
            <div class="user-mini sidebar-top">
                <?php if ($avatar): ?>
                    <img src="<?php echo $avatar; ?>" class="avatar-img" alt="avatar">
                <?php else: ?>
                    <div class="avatar"><?php echo strtoupper(substr($user['HoTen'] ?? 'U', 0, 1)); ?></div>
                <?php endif; ?>
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($user['HoTen'] ?? 'User'); ?></strong>
                    <small><?php echo htmlspecialchars($user['VaiTro'] ?? 'user'); ?></small>
                </div>
                <a href="/logout.php" class="logout-btn"><i class="bi bi-box-arrow-right"></i></a>
            </div>

            <nav class="nav-menu">
                <a class="nav-item active" href="/index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-item" href="/views/khu/index.php"><i class="bi bi-geo-alt"></i> Khu / Dãy</a>
                <a class="nav-item" href="/views/phong/index.php"><i class="bi bi-building"></i> Quản lý phòng</a>
                <a class="nav-item" href="/views/nguoithue/index.php"><i class="bi bi-people"></i> Người thuê</a>
                <a class="nav-item" href="/views/hopdong/index.php"><i class="bi bi-file-earmark-text"></i> Hợp đồng</a>
                <a class="nav-item" href="/views/chisodiennuoc/index.php"><i class="bi bi-lightning-charge"></i> Điện / Nước</a>
                <a class="nav-item" href="/views/hoadon/index.php"><i class="bi bi-receipt"></i> Hóa đơn</a>
                <a class="nav-item" href="/views/tamtru/index.php"><i class="bi bi-person-badge"></i> Tạm trú</a>
                <a class="nav-item" href="/views/baocao/index.php"><i class="bi bi-bar-chart"></i> Báo cáo</a>
                <a class="nav-item" href="/views/taikhoan/index.php"><i class="bi bi-person-gear"></i> Tài khoản</a>
            </nav>

            
        </aside>

        <main class="main-panel">
            <header class="topbar">
                <div>
                    <h2>Dashboard</h2>
                    <p>Thông tin tổng quan hệ thống quản lý phòng trọ</p>
                </div>
                <div class="top-actions">
                    <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tạo hợp đồng</button>
                </div>
            </header>

            <section class="stats-grid">
                <div class="stat-card primary">
                    <div class="icon"><i class="bi bi-house-door"></i></div>
                    <div>
                        <span>Tổng phòng</span>
                        <strong><?php echo $counts['phong']; ?></strong>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="icon"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <span>Đang thuê</span>
                        <strong><?php echo $counts['dangthue']; ?></strong>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="icon"><i class="bi bi-door-open"></i></div>
                    <div>
                        <span>Phòng trống</span>
                        <strong><?php echo $counts['trong']; ?></strong>
                    </div>
                </div>
                <div class="stat-card danger">
                    <div class="icon"><i class="bi bi-receipt-cutoff"></i></div>
                    <div>
                        <span>Hóa đơn</span>
                        <strong><?php echo $counts['hoadon']; ?></strong>
                    </div>
                </div>
            </section>

            <section class="row gx-4 mt-4">
                <div class="col-lg-8">
                    <div class="card-panel">
                            <div class="panel-head">
                                <h5>Biểu đồ doanh thu</h5>
                                <span>8/2026</span>
                            </div>
                            <div id="dashboardRevenue" class="d-flex align-items-end gap-2" style="height:240px"></div>
                        </div>
                </div>
                <div class="col-lg-4">
                    <div class="card-panel">
                        <div class="panel-head">
                            <h5>Trạng thái phòng</h5>
                            <span>Thống kê</span>
                        </div>
                        <div class="progress-stack">
                            <div class="progress-item">
                                <label>Trống</label>
                                <div class="progress"><div class="progress-bar bg-success" style="width: 40%"></div></div>
                            </div>
                            <div class="progress-item">
                                <label>Đang thuê</label>
                                <div class="progress"><div class="progress-bar bg-primary" style="width: 50%"></div></div>
                            </div>
                            <div class="progress-item">
                                <label>Bảo trì</label>
                                <div class="progress"><div class="progress-bar bg-warning" style="width: 10%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card-panel mt-4">
                <div class="panel-head">
                    <h5>Hóa đơn gần đây</h5>
                    <a href="/views/hoadon/index.php">Xem tất cả</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Phòng</th>
                                <th>Người thuê</th>
                                <th>Trạng thái</th>
                                <th>Tổng tiền</th>
                                <th>Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $item): ?>
                                <tr>
                                    <td>#<?php echo $item['Id']; ?></td>
                                    <td><?php echo htmlspecialchars($item['SoPhong']); ?></td>
                                    <td><?php echo htmlspecialchars($item['HoTen']); ?></td>
                                    <?php $label=['ChuaThanhToan'=>'Chưa thanh toán','ThanhToanMotPhan'=>'Thanh toán một phần','DaThanhToan'=>'Đã thanh toán'][$item['TrangThai']] ?? $item['TrangThai']; $badge=['ChuaThanhToan'=>'bg-warning text-dark','ThanhToanMotPhan'=>'bg-primary','DaThanhToan'=>'bg-success'][$item['TrangThai']] ?? 'bg-secondary'; ?>
                                    <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($label); ?></span></td>
                                    <td><?php echo formatMoney($item['TongTien']); ?></td>
                                    <td><?php echo htmlspecialchars($item['NgayTao']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/app.js"></script>
<script>
// Live dashboard data: invoices, debt and approval/payment notifications.
document.querySelector('.main-panel .top-actions .btn')?.remove();
(async()=>{try{const now=new Date(),r=await app.api(`/api/baocao.php?thang=${now.getMonth()+1}&nam=${now.getFullYear()}`),s=r.data.tongQuan;const money=x=>Number(x||0).toLocaleString('vi-VN')+' ₫';const extra=document.createElement('section');extra.className='row g-3 mt-2';extra.innerHTML=`<div class="col-lg-7"><div class="card-panel"><div class="panel-head"><h5>Tình hình thu tiền tháng ${r.data.thang}/${r.data.nam}</h5></div><div class="row g-2"><div class="col-4"><small>Đã thu</small><strong class="d-block text-success">${money(s.DaThu)}</strong></div><div class="col-4"><small>Còn nợ</small><strong class="d-block text-danger">${money(s.CongNo)}</strong></div><div class="col-4"><small>Tiền điện/nước</small><strong class="d-block">${money(Number(s.TienDien)+Number(s.TienNuoc))}</strong></div></div></div></div><div class="col-lg-5"><div class="card-panel"><div class="panel-head"><h5>Thông báo cần xử lý</h5><a href="/views/thongbao/index.php">Xem tất cả</a></div><div id="dashNotices" class="small text-muted">Đang tải…</div></div></div>`;document.querySelector('.main-panel').append(extra);const n=await app.api('/api/thongbao.php');dashNotices.innerHTML=(n.data||[]).filter(x=>!x.DaDoc).slice(0,4).map(x=>`<div class="border-bottom py-2"><b>${x.TieuDe}</b><br>${x.NoiDung}</div>`).join('')||'Không có thông báo mới.'}catch(e){console.error(e)}})();
</script>
<script>
(async()=>{try{const n=new Date(),r=await app.api(`/api/baocao.php?thang=${n.getMonth()+1}&nam=${n.getFullYear()}`),s=r.data.tongQuan,items=[['Tiền phòng',+s.TienPhong,'#4f46e5'],['Điện',+s.TienDien,'#f59e0b'],['Nước',+s.TienNuoc,'#06b6d4'],['Dịch vụ',+s.DichVu,'#22c55e']],max=Math.max(1,...items.map(x=>x[1]));dashboardRevenue.innerHTML=items.map(x=>`<div class="text-center flex-fill h-100 d-flex flex-column justify-content-end"><small>${x[0]}</small><strong class="small">${x[1].toLocaleString('vi-VN')}</strong><div style="height:${Math.max(5,x[1]/max*75)}%;background:${x[2]};border-radius:8px 8px 0 0"></div></div>`).join('')}catch(e){}})();
</script>
</body>
</html>
