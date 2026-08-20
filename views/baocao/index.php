<?php require __DIR__.'/../../includes/db.php'; require __DIR__.'/../../includes/auth.php'; requireLogin(); ?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Báo cáo</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"><link rel="stylesheet" href="/assets/css/style.css"><style>.report-bars{display:flex;align-items:end;gap:8px;height:180px}.report-bar{flex:1;min-width:18px;background:linear-gradient(#4f46e5,#818cf8);border-radius:6px 6px 0 0;position:relative}.report-bar span{position:absolute;bottom:-22px;font-size:10px;left:50%;transform:translateX(-50%)}@media print{.sidebar,.mobile-topbar,.mobile-menu-toggle,.top-actions,.report-filter{display:none!important}.main-panel{padding:0!important}.card-panel{box-shadow:none!important}}</style></head>
<body><main class="main-panel" data-page-title="Báo cáo"><header class="topbar"><div><h2>Báo cáo tài chính</h2><p id="reportSubtitle">Báo cáo chi tiết theo kỳ.</p></div><div class="top-actions"><button id="reportExcel" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Excel</button><button id="reportPdf" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> In / PDF</button></div></header>
<section class="card-panel report-filter mb-3"><div class="row g-2"><div class="col-sm-4"><label class="form-label">Tháng</label><select id="reportMonth" class="form-select"></select></div><div class="col-sm-4"><label class="form-label">Năm</label><select id="reportYear" class="form-select"></select></div><div class="col-sm-4 d-flex align-items-end"><button id="reportLoad" class="btn btn-primary w-100">Xem báo cáo</button></div></div></section><section class="stats-grid" id="reportStats"></section>
<section class="row g-3 mt-2"><div class="col-lg-7"><div class="card-panel"><div class="panel-head"><h5>Phân bổ khoản thu</h5></div><div id="reportBreakdown" class="row g-2"></div></div></div><div class="col-lg-5"><div class="card-panel"><div class="panel-head"><h5>Thu tiền theo kỳ</h5></div><div class="report-bars" id="reportBars"></div></div></div></section>
<section class="card-panel mt-3"><div class="panel-head"><h5>Chi tiết hóa đơn</h5><span id="reportOwner"></span></div><div class="table-responsive"><table class="table table-modern"><thead><tr><th>Khu / Dãy / Phòng</th><th>Người thuê</th><th>Phòng</th><th>Điện</th><th>Nước</th><th>Tổng</th><th>Đã thu</th><th>Còn nợ</th><th>Hạn đóng</th><th>Trạng thái</th></tr></thead><tbody id="reportRows"></tbody></table></div></section></main>
<script src="/assets/js/app.js"></script>
<script>
(() => {
    const root = document.querySelector('.main-panel');
    if (!root) return;
    const byId = id => root.querySelector('#' + id);
    const money = value => Number(value || 0).toLocaleString('vi-VN') + ' ₫';
    const month = byId('reportMonth'), year = byId('reportYear'), rows = byId('reportRows');
    let currentDetails = [], currentReport = {};
    const pager = document.createElement('nav');
    pager.id = 'reportPager'; pager.className = 'mt-3 d-flex justify-content-end';
    rows?.closest('.table-responsive')?.after(pager);
    const message = text => { if (rows) rows.innerHTML = `<tr><td colspan="10" class="text-center text-muted py-4">${text}</td></tr>`; };
    const put = (id, html) => { const node = byId(id); if (node) node.innerHTML = html; };
    const text = (id, value) => { const node = byId(id); if (node) node.textContent = value; };
    function renderPage(page = 1) {
        const total = Math.max(1, Math.ceil(currentDetails.length / 10));
        const activePage = Math.max(1, Math.min(page, total));
        const slice = currentDetails.slice((activePage - 1) * 10, activePage * 10);
        if (rows) rows.innerHTML = slice.map(item => `<tr><td>${item.TenKhu} / ${item.TenDay} / <b>${item.SoPhong}</b></td><td>${item.HoTen}</td><td>${money(item.TienPhong)}</td><td>${money(item.TienDien)}</td><td>${money(item.TienNuoc)}</td><td>${money(item.TongTien)}</td><td>${money(item.DaTra)}</td><td>${money(item.ConNo)}</td><td>${item.HanThanhToan || '—'}</td><td>${item.TrangThai || '—'}</td></tr>`).join('') || '<tr><td colspan="10" class="text-center text-muted py-4">Không có hóa đơn trong kỳ đã chọn.</td></tr>';
        pager.innerHTML = currentDetails.length > 10 ? `<ul class="pagination pagination-sm mb-0"><li class="page-item ${activePage === 1 ? 'disabled' : ''}"><button class="page-link" data-report-page="${activePage - 1}">‹</button></li><li class="page-item disabled"><span class="page-link">${activePage}/${total}</span></li><li class="page-item ${activePage === total ? 'disabled' : ''}"><button class="page-link" data-report-page="${activePage + 1}">›</button></li></ul>` : '';
    }
    pager.addEventListener('click', event => { const button = event.target.closest('[data-report-page]'); if (button && Number(button.dataset.reportPage) > 0) renderPage(Number(button.dataset.reportPage)); });
    function render(data) {
        const summary = data.tongQuan || {}, details = data.chiTiet || []; currentReport = data; currentDetails = details;
        text('reportSubtitle', `Báo cáo tháng ${data.thang}/${data.nam} · ${data.chuTro || ''}`);
        text('reportOwner', data.chuTro || '');
        put('reportStats', [['Tổng hóa đơn',summary.Tong,'primary'],['Đã thu',summary.DaThu,'success'],['Còn nợ',summary.CongNo,'danger'],['Số hóa đơn',summary.SoHoaDon,'warning']].map(item => `<div class="stat-card ${item[2]}"><div><span>${item[0]}</span><strong>${item[0] === 'Số hóa đơn' ? item[1] : money(item[1])}</strong></div></div>`).join(''));
        put('reportBreakdown', [['Tiền phòng',summary.TienPhong],['Tiền điện',summary.TienDien],['Tiền nước',summary.TienNuoc],['Dịch vụ',summary.DichVu]].map(item => `<div class="col-6"><div class="border rounded p-3"><small>${item[0]}</small><strong class="d-block">${money(item[1])}</strong></div></div>`).join(''));
        const maximum = Math.max(1, ...details.map(item => Number(item.DaTra || 0)));
        put('reportBars', details.slice(0, 10).map(item => `<div class="report-bar" style="height:${Math.max(7, Number(item.DaTra || 0) / maximum * 100)}%" title="${item.SoPhong}: ${money(item.DaTra)}"><span>${item.SoPhong}</span></div>`).join(''));
        renderPage(1);
    }
    function printReport() {
        const summary = currentReport.tongQuan || {};
        const popup = window.open('', '_blank', 'width=1200,height=800');
        if (!popup) return alert('Trình duyệt đang chặn cửa sổ in. Hãy cho phép pop-up và thử lại.');
        const detailRows = currentDetails.map(item => `<tr><td>${item.TenKhu}</td><td>${item.TenDay}</td><td>${item.SoPhong}</td><td>${item.HoTen}</td><td>${money(item.TienPhong)}</td><td>${money(item.TienDien)}</td><td>${money(item.TienNuoc)}</td><td>${money(item.TienDichVu)}</td><td>${money(item.TongTien)}</td><td>${money(item.DaTra)}</td><td>${money(item.ConNo)}</td><td>${item.HanThanhToan || '—'}</td><td>${item.TrangThai || '—'}</td></tr>`).join('');
        popup.document.write(`<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Báo cáo ${currentReport.thang}/${currentReport.nam}</title><style>@page{size:landscape;margin:12mm}body{font-family:Arial,sans-serif;color:#172033}h1{margin:0;color:#1d4ed8;font-size:22px}p{margin:5px 0 16px;color:#475569}.summary{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:12px 0 18px}.summary div{border:1px solid #cbd5e1;border-radius:8px;padding:10px}.summary span{display:block;font-size:11px;color:#64748b}.summary b{font-size:15px}table{width:100%;border-collapse:collapse;font-size:9px}th{background:#1d4ed8;color:#fff}th,td{border:1px solid #cbd5e1;padding:6px;text-align:left}tr:nth-child(even){background:#f8fafc}.foot{margin-top:14px;font-size:10px;color:#64748b}</style></head><body><h1>BÁO CÁO TÀI CHÍNH</h1><p>Tháng ${currentReport.thang}/${currentReport.nam} · ${currentReport.chuTro || ''}</p><section class="summary"><div><span>Tổng hóa đơn</span><b>${money(summary.Tong)}</b></div><div><span>Đã thu</span><b>${money(summary.DaThu)}</b></div><div><span>Còn nợ</span><b>${money(summary.CongNo)}</b></div><div><span>Số hóa đơn</span><b>${summary.SoHoaDon || 0}</b></div></section><table><thead><tr><th>Khu</th><th>Dãy</th><th>Phòng</th><th>Người thuê</th><th>Phòng</th><th>Điện</th><th>Nước</th><th>Dịch vụ</th><th>Tổng</th><th>Đã thu</th><th>Còn nợ</th><th>Hạn</th><th>Trạng thái</th></tr></thead><tbody>${detailRows || '<tr><td colspan="13">Không có dữ liệu</td></tr>'}</tbody></table><div class="foot">Ngày xuất: ${new Date().toLocaleString('vi-VN')}</div><script>window.onload=()=>window.print()<\/script></body></html>`);
        popup.document.close();
    }
    async function load() { if (!month?.value || !year?.value) return; const result = await app.api(`/api/baocao.php?thang=${month.value}&nam=${year.value}`); render(result.data || {}); }
    async function init() {
        const result = await app.api('/api/baocao.php?action=ky'), periods = result.data || [];
        if (!periods.length) { message('Chưa có hóa đơn để lập báo cáo.'); return; }
        const years = [...new Set(periods.map(item => String(item.Nam)))];
        year.innerHTML = years.map(item => `<option value="${item}">${item}</option>`).join('');
        const refreshMonths = () => { month.innerHTML = periods.filter(item => String(item.Nam) === String(year.value)).map(item => `<option value="${item.Thang}">Tháng ${item.Thang}</option>`).join(''); };
        year.value = String(periods[0].Nam); refreshMonths(); month.value = String(periods[0].Thang);
        year.addEventListener('change', () => { refreshMonths(); load(); });
        await load();
    }
    byId('reportLoad')?.addEventListener('click', load);
    byId('reportExcel')?.addEventListener('click', () => { location.href = `/api/baocao.php?thang=${month.value}&nam=${year.value}&export=excel`; });
    byId('reportPdf')?.addEventListener('click', printReport);
    init().catch(error => { console.error(error); message(`Không tải được báo cáo: ${error.message || error}`); });
})();
</script></body></html>
