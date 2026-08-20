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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Nhật ký hoạt động</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/assets/pics/logo.webp" type="image/webp">
</head>
<body>
<div class="app-shell">
    <?php renderApplicationSidebar($user, '/views/auditlog/index.php'); ?>
    <main class="main-panel">
        <header class="topbar"><div><h2>Nhật ký hoạt động</h2><p>Ghi nhận đăng nhập và các thao tác thêm, sửa, xóa dữ liệu.</p></div></header>
        <section class="card-panel">
            <form id="auditFilters" class="row g-2 mb-3">
                <div class="col-lg-4"><input id="auditKeyword" class="form-control" placeholder="Người dùng, hành động hoặc địa chỉ IP"></div>
                <div class="col-md-2"><select id="auditRole" class="form-select"><option value="">Tất cả vai trò</option><option value="admin">Admin</option><option value="chutro">Chủ trọ</option><option value="nguoithue">Người thuê</option></select></div>
                <div class="col-md"><input id="auditFrom" type="date" class="form-control" title="Từ ngày"></div>
                <div class="col-md"><input id="auditTo" type="date" class="form-control" title="Đến ngày"></div>
                <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-funnel"></i> Lọc</button></div>
            </form>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <small id="auditSummary" class="text-muted"></small>
                <button id="auditRefresh" type="button" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise"></i> Làm mới</button>
            </div>
            <div class="table-responsive"><table class="table table-modern align-middle">
                <thead><tr><th>Thời gian</th><th>Người dùng</th><th>Hoạt động</th><th>Địa chỉ IP</th></tr></thead>
                <tbody id="auditRows"><tr><td colspan="4" class="text-center text-muted py-4">Đang tải nhật ký…</td></tr></tbody>
            </table></div>
            <nav><ul id="auditPagination" class="pagination pagination-sm justify-content-end mb-0"></ul></nav>
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
<script>
(() => {
    const elements = Object.fromEntries(['Rows','Pagination','Summary','Filters','Refresh','Keyword','Role','From','To'].map(name => [name.toLowerCase(), document.getElementById('audit' + name)]));
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const formatDate = value => String(value || '').replace(/^(\d{4})-(\d{2})-(\d{2})\s?(.*)$/, '$3/$2/$1 $4');
    let page = 1;

    function query() {
        const params = new URLSearchParams({trang: String(page), gioiHan: '25'});
        [[elements.keyword,'tuKhoa'],[elements.role,'vaiTro'],[elements.from,'tuNgay'],[elements.to,'denNgay']]
            .forEach(([element,key]) => { if (element.value) params.set(key, element.value); });
        return params;
    }

    async function load() {
        const response = await app.api('/api/auditlog.php?' + query());
        const logs = response.data.items || [];
        const pagination = response.data.pagination || {};
        elements.summary.textContent = `Tổng ${pagination.tong || 0} hoạt động`;
        elements.rows.innerHTML = logs.map(item => `<tr>
            <td>${formatDate(item.NgayTao)}</td>
            <td><strong>${escapeHtml(item.HoTen || item.TenDangNhap || 'Không xác định')}</strong><br><small>${escapeHtml(item.TenDangNhap || '—')}</small></td>
            <td>${escapeHtml(item.HanhDong)}</td>
            <td>${escapeHtml(item.DiaChiIP || '—')}</td>
        </tr>`).join('') || '<tr><td colspan="4" class="text-center text-muted py-4">Chưa có nhật ký phù hợp.</td></tr>';
        elements.pagination.innerHTML = Array.from({length: pagination.tongTrang || 1}, (_, index) => `<li class="page-item ${index + 1 === Number(pagination.trang) ? 'active' : ''}"><button class="page-link audit-page" data-page="${index + 1}">${index + 1}</button></li>`).join('');
    }

    elements.filters.addEventListener('submit', event => { event.preventDefault(); page = 1; load(); });
    elements.refresh.addEventListener('click', load);
    document.addEventListener('click', event => {
        const button = event.target.closest('.audit-page');
        if (button) { page = Number(button.dataset.page); load(); }
    });
    load();
})();
</script>
</body>
</html>
