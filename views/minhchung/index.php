<?php
require __DIR__.'/../../includes/db.php';
require __DIR__.'/../../includes/auth.php';
require_once __DIR__.'/../../includes/navigation.php';
requireLogin();
requireRole(['admin']);
$user = currentUser();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Tệp minh chứng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css"><link rel="icon" href="/assets/pics/logo.webp" type="image/webp">
</head>
<body><div class="app-shell">
    <?php renderApplicationSidebar($user, '/views/minhchung/index.php'); ?>
    <main class="main-panel">
        <header class="topbar"><div><h2>Tệp minh chứng</h2><p>Quản lý ảnh minh chứng thanh toán do người dùng tải lên.</p></div></header>
        <section class="card-panel">
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-7 col-lg-6"><form id="proofSearchForm" class="input-group"><input id="proofKeyword" class="form-control" placeholder="Tên người dùng, tài khoản, phòng, khu hoặc mã giao dịch"><button class="btn btn-primary"><i class="bi bi-search"></i> Tìm kiếm</button></form></div>
                <div class="col-auto ms-md-auto d-flex align-items-center gap-2"><label for="proofLimit" class="text-muted text-nowrap">Số dòng</label><select id="proofLimit" class="form-select"><option>10</option><option selected>20</option><option>50</option><option>100</option></select></div>
            </div>
            <div class="table-responsive"><table class="table table-modern align-middle">
                <thead><tr><th>Mã</th><th>Người tải</th><th>Phòng</th><th>Thanh toán</th><th>Ngày tải</th><th>Thao tác</th></tr></thead>
                <tbody id="proofRows"><tr><td colspan="6" class="text-center text-muted py-4">Đang tải minh chứng…</td></tr></tbody>
            </table></div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"><small id="proofSummary" class="text-muted"></small><nav><ul id="proofPagination" class="pagination pagination-sm mb-0"></ul></nav></div>
        </section>
    </main>
</div>
<div class="modal fade" id="proofModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Xem tệp minh chứng</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body text-center"><img id="proofImage" class="img-fluid rounded" style="max-height:70vh" alt="Tệp minh chứng"></div></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="/assets/js/app.js"></script>
<script>
(() => {
    const rows=document.getElementById('proofRows'),pagination=document.getElementById('proofPagination'),summary=document.getElementById('proofSummary'),keyword=document.getElementById('proofKeyword'),limit=document.getElementById('proofLimit');
    const modal=bootstrap.Modal.getOrCreateInstance(document.getElementById('proofModal')),image=document.getElementById('proofImage');
    const esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const money=value=>Number(value||0).toLocaleString('vi-VN')+' ₫';
    const date=value=>String(value||'').replace(/^(\d{4})-(\d{2})-(\d{2})\s?(.*)$/,'$3/$2/$1 $4');
    let page=1;
    async function load(){
        const params=new URLSearchParams({trang:String(page),gioiHan:limit.value});if(keyword.value.trim())params.set('tuKhoa',keyword.value.trim());
        const response=await app.api('/api/minhchung.php?'+params),items=response.data.items||[],pg=response.data.pagination||{};page=Number(pg.trang||1);
        summary.textContent=`Hiển thị ${items.length} trong tổng số ${pg.tong||0} tệp`;
        rows.innerHTML=items.map(item=>`<tr><td>#${item.Id}</td><td><strong>${esc(item.HoTen||'Không xác định')}</strong><br><small>${esc(item.TenDangNhap||'—')}</small></td><td>${esc(item.SoPhong)}<br><small>${esc(item.TenDay)} · ${esc(item.TenKhu)}</small></td><td>${money(item.SoTien)}<br><small>${esc(item.TrangThai||item.PhuongThuc)}</small></td><td>${date(item.NgayThanhToan)}</td><td class="text-nowrap"><button class="btn btn-sm btn-outline-info proof-view" data-id="${item.Id}" title="Xem"><i class="bi bi-eye"></i></button> <a class="btn btn-sm btn-outline-primary" href="/api/minhchung.php?action=tep&tai=1&id=${item.Id}" title="Tải xuống"><i class="bi bi-download"></i></a> <button class="btn btn-sm btn-outline-danger proof-delete" data-id="${item.Id}" title="Xóa"><i class="bi bi-trash"></i></button></td></tr>`).join('')||'<tr><td colspan="6" class="text-center text-muted py-4">Không tìm thấy tệp minh chứng.</td></tr>';
        const totalPages=Number(pg.tongTrang||1);pagination.innerHTML=`<li class="page-item ${page<=1?'disabled':''}"><button class="page-link proof-page" data-page="${page-1}">Trước</button></li>${Array.from({length:totalPages},(_,i)=>i+1).filter(n=>n===1||n===totalPages||Math.abs(n-page)<=2).map(n=>`<li class="page-item ${n===page?'active':''}"><button class="page-link proof-page" data-page="${n}">${n}</button></li>`).join('')}<li class="page-item ${page>=totalPages?'disabled':''}"><button class="page-link proof-page" data-page="${page+1}">Sau</button></li>`;
    }
    document.getElementById('proofSearchForm').onsubmit=event=>{event.preventDefault();page=1;load()};limit.onchange=()=>{page=1;load()};
    document.addEventListener('click',async event=>{const pageButton=event.target.closest('.proof-page'),view=event.target.closest('.proof-view'),remove=event.target.closest('.proof-delete');if(pageButton&&!pageButton.closest('.disabled')){page=Number(pageButton.dataset.page);load()}if(view){image.src='/api/minhchung.php?action=tep&id='+view.dataset.id;modal.show()}if(remove){if(!confirm('Xóa tệp minh chứng này? Giao dịch thanh toán vẫn được giữ lại.'))return;await app.api('/api/minhchung.php?id='+remove.dataset.id,{method:'DELETE'});load()}});
    document.getElementById('proofModal').addEventListener('hidden.bs.modal',()=>{image.removeAttribute('src')});load();
})();
</script></body></html>
