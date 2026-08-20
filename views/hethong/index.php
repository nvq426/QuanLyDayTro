<?php
require __DIR__.'/../../includes/db.php';
require __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/navigation.php';
requireLogin();
requireRole(['admin']);
$user = currentUser();
?>
<!doctype html><html lang="vi"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dung lượng hệ thống</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><link rel="stylesheet" href="/assets/css/style.css"><link rel="icon" href="/assets/pics/logo.webp" type="image/webp">
<style>.system-value{font-size:1.55rem;font-weight:700}.system-progress{height:12px}.system-meta td:first-child{color:#64748b;width:48%}</style>
</head><body><div class="app-shell">
<?php renderApplicationSidebar($user, '/views/hethong/index.php'); ?>
<main class="main-panel">
    <header class="topbar"><div><h2>Dung lượng hệ thống</h2><p>Theo dõi dung lượng dự án theo hạn mức InfinityFree 5 GB và phiên bản môi trường.</p></div><div class="top-actions"><button id="refreshSystem" class="btn btn-outline-primary"><i class="bi bi-arrow-clockwise"></i> Làm mới</button></div></header>
    <section class="row g-3" id="systemCards">
        <div class="col-md-6 col-xl-3"><div class="card-panel h-100"><small class="text-muted">Dung lượng database</small><div id="databaseSize" class="system-value mt-1">—</div><small id="databasePages" class="text-muted"></small></div></div>
        <div class="col-md-6 col-xl-3"><div class="card-panel h-100"><small class="text-muted">InfinityFree còn trống</small><div id="diskFree" class="system-value mt-1 text-success">—</div><small id="diskTotal" class="text-muted"></small></div></div>
        <div class="col-md-6 col-xl-3"><div class="card-panel h-100"><small class="text-muted">PHP Runtime</small><div id="phpVersion" class="system-value mt-1">—</div><small id="phpRuntime" class="text-muted"></small></div></div>
        <div class="col-md-6 col-xl-3"><div class="card-panel h-100"><small class="text-muted">SQLite Database</small><div id="databaseVersion" class="system-value mt-1">—</div><small class="text-muted">Phiên bản bộ máy SQLite</small></div></div>
    </section>
    <section class="card-panel mt-3">
        <div class="d-flex justify-content-between"><h5>Dung lượng tài khoản InfinityFree (tối đa 5 GB)</h5><strong id="diskPercent">—</strong></div>
        <div class="progress system-progress mt-3"><div id="diskProgress" class="progress-bar" role="progressbar"></div></div>
        <div class="d-flex justify-content-between mt-2 text-muted"><small id="diskUsed">Dự án hiện dùng: —</small><small id="diskAvailable">Còn lại trong 5 GB: —</small></div>
        <small id="storageBasis" class="text-muted d-block mt-2"></small>
    </section>
    <section class="row g-3 mt-0">
        <div class="col-lg-6"><div class="card-panel h-100"><h5><i class="bi bi-database text-primary"></i> Database SQLite</h5><table class="table system-meta mt-3 mb-0"><tbody id="databaseDetails"></tbody></table></div></div>
        <div class="col-lg-6"><div class="card-panel h-100"><h5><i class="bi bi-code-slash text-primary"></i> Môi trường PHP</h5><table class="table system-meta mt-3 mb-0"><tbody id="phpDetails"></tbody></table></div></div>
    </section>
    <p id="checkedAt" class="text-muted small text-end mt-3"></p>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="/assets/js/app.js"></script>
<script>
(() => {
    const formatBytes=value=>{const bytes=Number(value||0);if(!bytes)return '0 B';const units=['B','KB','MB','GB','TB'],index=Math.min(Math.floor(Math.log(bytes)/Math.log(1024)),units.length-1);return `${(bytes/Math.pow(1024,index)).toLocaleString('vi-VN',{maximumFractionDigits:2})} ${units[index]}`};
    const row=(label,value)=>`<tr><td>${label}</td><td><strong>${value}</strong></td></tr>`;
    async function load(){
        const button=document.getElementById('refreshSystem');button.disabled=true;
        try{
            const response=await app.api('/api/hethong.php'),data=response.data,db=data.database,php=data.php,storage=data.storage,percent=Math.min(100,Number(storage.usedPercent||0));
            databaseSize.textContent=formatBytes(db.size);databasePages.textContent=`${Number(db.pageCount).toLocaleString('vi-VN')} trang dữ liệu`;
            diskFree.textContent=formatBytes(storage.free);diskTotal.textContent=`Tối đa ${formatBytes(storage.total)}`;
            phpVersion.textContent=php.version;phpRuntime.textContent=`${php.sapi} · ${php.architecture}`;databaseVersion.textContent=db.version;
            diskPercent.textContent=`${percent.toLocaleString('vi-VN')}% / 5 GB`;diskProgress.style.width=percent+'%';diskProgress.className='progress-bar '+(percent>=90?'bg-danger':percent>=75?'bg-warning':'bg-primary');diskUsed.textContent='Dự án hiện dùng: '+formatBytes(storage.used);diskAvailable.textContent='Còn lại trong 5 GB: '+formatBytes(storage.free);storageBasis.textContent=storage.basis;
            databaseDetails.innerHTML=row('Hệ quản trị',db.engine+' '+db.version)+row('Dung lượng tệp',formatBytes(db.size))+row('Dung lượng trống nội bộ',formatBytes(db.internalFree))+row('Kích thước mỗi trang',formatBytes(db.pageSize))+row('Số bảng',db.tableCount)+row('Số chỉ mục',db.indexCount)+row('Schema user_version',db.schemaVersion);
            phpDetails.innerHTML=row('Phiên bản',php.version)+row('Giao diện chạy',php.sapi)+row('Kiến trúc',php.architecture)+row('PDO SQLite',php.pdoSqlite?'Đã bật':'Chưa bật')+row('Giới hạn tải tệp',php.uploadMax)+row('Giới hạn POST',php.postMax)+row('Giới hạn bộ nhớ',php.memoryLimit);
            checkedAt.textContent='Cập nhật lúc '+data.checkedAt;
        }finally{button.disabled=false}
    }
    document.getElementById('refreshSystem').addEventListener('click',load);load();
})();
</script></body></html>
