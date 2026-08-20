<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
requireLogin();
$user = currentUser();
$pdo = getDb();
if (($user['VaiTro'] ?? '') === 'nguoithue') {$stmt=$pdo->prepare('SELECT h.*,CASE WHEN EXISTS(SELECT 1 FROM ThanhToan tt WHERE tt.HoaDonId=h.Id AND tt.IsDeleted=0 AND tt.TrangThai="ChoXacNhan") THEN "ChoXacNhanThanhToan" ELSE h.TrangThai END AS TrangThaiHienThi,p.SoPhong,nt.HoTen AS NguoiThue FROM HoaDon h JOIN HopDong hd ON hd.Id=h.HopDongId JOIN Phong p ON p.Id=hd.PhongId JOIN NguoiThue nt ON nt.Id=hd.NguoiThueId WHERE nt.TaiKhoanId=:u ORDER BY h.Id DESC');$stmt->execute([':u'=>$user['Id']]);$rows=$stmt->fetchAll();foreach($rows as &$invoiceRow)$invoiceRow['TrangThai']=$invoiceRow['TrangThaiHienThi'];unset($invoiceRow);} else {$rows = $pdo->query('SELECT h.*, p.SoPhong, nt.HoTen AS NguoiThue FROM HoaDon h JOIN HopDong hd ON hd.Id = h.HopDongId JOIN Phong p ON p.Id = hd.PhongId JOIN NguoiThue nt ON nt.Id = hd.NguoiThueId ORDER BY h.Id DESC')->fetchAll();}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" href="/assets/pics/logo.webp" type="image/webp" sizes="512x512">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand"><div class="brand-badge"><img src="/assets/pics/logo.webp" class="brand-logo" alt="Logo Trọ Tốt"></div><div><div class="brand-title">Trọ Tốt</div><div class="brand-subtitle">Management System</div></div></div>
        <?php require_once __DIR__ . '/../..//includes/helpers.php'; $avatar = getAvatarUrl($user); ?>
        <div class="user-mini sidebar-top">
            <?php if ($avatar): ?>
                <img src="<?php echo $avatar; ?>" class="avatar-img" alt="avatar">
            <?php else: ?>
                <div class="avatar"><?php echo strtoupper(substr($user['HoTen'] ?? 'U',0,1)); ?></div>
            <?php endif; ?>
            <div class="user-info">
                <strong><?php echo htmlspecialchars($user['HoTen'] ?? 'User'); ?></strong>
                <small><?php echo htmlspecialchars($user['VaiTro'] ?? 'user'); ?></small>
            </div>
            <a href="/logout.php" class="logout-btn"><i class="bi bi-box-arrow-right"></i></a>
        </div>
        <nav class="nav-menu">
            <a class="nav-item" href="/index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a class="nav-item" href="/views/khu/index.php"><i class="bi bi-geo-alt"></i> Khu / Dãy</a>
            <a class="nav-item" href="/views/phong/index.php"><i class="bi bi-building"></i> Quản lý phòng</a>
            <a class="nav-item" href="/views/nguoithue/index.php"><i class="bi bi-people"></i> Người thuê</a>
            <a class="nav-item" href="/views/hopdong/index.php"><i class="bi bi-file-earmark-text"></i> Hợp đồng</a>
            <a class="nav-item" href="/views/chisodiennuoc/index.php"><i class="bi bi-lightning-charge"></i> Điện / Nước</a>
            <a class="nav-item active" href="/views/hoadon/index.php"><i class="bi bi-receipt"></i> Hóa đơn</a>
            <a class="nav-item" href="/views/tamtru/index.php"><i class="bi bi-person-badge"></i> Tạm trú</a>
            <a class="nav-item" href="/views/baocao/index.php"><i class="bi bi-bar-chart"></i> Báo cáo</a>
            <a class="nav-item" href="/views/taikhoan/index.php"><i class="bi bi-person-gear"></i> Tài khoản</a>
        </nav>
        
    </aside>
    <main class="main-panel">
        <header class="topbar"><div><h2>Hóa đơn</h2><p>Danh sách hóa đơn theo tháng.</p></div><div class="top-actions"><button id="openAddHoaDon" class="btn btn-primary">Tạo hóa đơn</button></div></header>
        <section class="card-panel"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>ID</th><th>Phòng</th><th>Người thuê</th><th>Tháng</th><th>Tổng tiền</th><th>Đã trả</th><th>Trạng thái</th></tr></thead><tbody><?php foreach($rows as $row): $invoiceMeta=['ChuaThanhToan'=>['Chưa thanh toán','table-warning','bg-warning text-dark'],'ChoXacNhanThanhToan'=>['Đang chờ chủ trọ xác nhận thanh toán','table-info','bg-info text-dark'],'ThanhToanMotPhan'=>['Thanh toán một phần','table-primary','bg-primary'],'DaThanhToan'=>['Đã thanh toán','table-success','bg-success']][$row['TrangThai']]??[$row['TrangThai'],'','bg-secondary']; ?><tr class="<?= $invoiceMeta[1] ?>"><td>#<?= $row['Id']; ?></td><td><?= htmlspecialchars($row['SoPhong']); ?></td><td><?= htmlspecialchars($row['NguoiThue']); ?></td><td><?= $row['Thang'].'/'.$row['Nam']; ?></td><td><?= number_format((float)$row['TongTien'],0,',','.'); ?> ₫</td><td><?= number_format((float)$row['DaTra'],0,',','.'); ?> ₫</td><td><span class="badge <?= $invoiceMeta[2] ?>"><?= htmlspecialchars($invoiceMeta[0]); ?></span></td></tr><?php endforeach; ?></tbody></table></div></section>
    </main>
</div>

<div class="modal fade" id="hoaDonModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="hoaDonForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo hóa đơn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Hợp đồng</label>
                    <select id="HopDongId" class="form-select" required></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tháng</label>
                    <input type="number" id="Thang" class="form-control" min="1" max="12" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Năm</label>
                    <input type="number" id="Nam" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tiền phòng</label>
                    <input type="number" id="TienPhong" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tiền điện</label>
                    <input type="number" id="TienDien" class="form-control" value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tiền nước</label>
                    <input type="number" id="TienNuoc" class="form-control" value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phụ phí</label>
                    <input type="number" id="TienDichVu" class="form-control" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
<script>
const hoaDonModal = new bootstrap.Modal(document.getElementById('hoaDonModal'));
const hoaDonForm = document.getElementById('hoaDonForm');
const hopDongSelect = document.getElementById('HopDongId');

async function loadHopDongOptions() {
    const result = await window.app.api('/api/hopdong.php');
    const contracts = result.data || [];
    hopDongSelect.innerHTML = contracts.map(item => `<option value="${item.Id}">${item.SoPhong} - ${item.NguoiThue}</option>`).join('');
}

hoaDonForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = {
        HopDongId: Number(document.getElementById('HopDongId').value),
        Thang: Number(document.getElementById('Thang').value),
        Nam: Number(document.getElementById('Nam').value),
        TienPhong: Number(document.getElementById('TienPhong').value || 0),
        TienDien: Number(document.getElementById('TienDien').value || 0),
        TienNuoc: Number(document.getElementById('TienNuoc').value || 0),
        TienDichVu: Number(document.getElementById('TienDichVu').value || 0)
    };
    await window.app.api('/api/hoadon.php', { method: 'POST', body: JSON.stringify(payload) });
    hoaDonForm.reset();
    hoaDonModal.hide();
    window.location.reload();
});

document.getElementById('openAddHoaDon').addEventListener('click', () => {
    hoaDonForm.reset();
    loadHopDongOptions();
    hoaDonModal.show();
});

window.tenantInvoiceMode = <?= json_encode(($user['VaiTro'] ?? '') === 'nguoithue'); ?>;
const tenantInvoiceMode = window.tenantInvoiceMode;
if (tenantInvoiceMode) {
    document.getElementById('openAddHoaDon')?.remove();
    document.querySelector('.table-modern thead tr').insertAdjacentHTML('beforeend','<th>Thanh toán</th>');
    document.querySelectorAll('.table-modern tbody tr').forEach(row => {
        const id=(row.children[0]?.textContent||'').replace('#','').trim();
        if(id) row.insertAdjacentHTML('beforeend',row.classList.contains('table-success')?'<td><span class="text-success small">Đã hoàn tất</span></td>':(row.classList.contains('table-info')?'<td><span class="text-info-emphasis small">Chờ xác nhận</span></td>':`<td><button class="btn btn-sm btn-success pay-bill" data-id="${id}">Đóng tiền</button></td>`));
    });
    const payModal=document.createElement('div');payModal.className='modal fade';payModal.id='payBillModal';payModal.innerHTML='<div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="payBillForm"><div class="modal-header"><h5 class="modal-title">Đóng tiền hóa đơn</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Hình thức thanh toán</label><select id="payMethod" class="form-select mb-3"><option value="TienMat">Tiền mặt</option><option value="ChuyenKhoan">Chuyển khoản</option></select><label class="form-label">Số tiền cần đóng</label><div class="input-group mb-3"><input id="payAmount" type="text" class="form-control fw-bold" readonly aria-readonly="true"><span class="input-group-text">₫</span></div><div id="cashNoteWrap"><label class="form-label">Ghi chú</label><textarea id="payNote" class="form-control" rows="2" placeholder="Ví dụ: Đã đóng trực tiếp cho chủ trọ"></textarea></div><div id="transferWrap" hidden><div id="transferInfo" class="alert alert-primary border small mb-3"></div><div id="proofWrap"><label class="form-label">Ảnh minh chứng chuyển khoản</label><input id="payProof" type="file" class="form-control" accept="image/jpeg,image/png,image/webp"><small class="text-muted">Chấp nhận JPG, PNG hoặc WEBP, tối đa 5 MB.</small></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button><button class="btn btn-primary">Gửi yêu cầu thanh toán</button></div></form></div></div>';document.body.append(payModal);const payInstance=bootstrap.Modal.getOrCreateInstance(payModal);let billId=0,proof='',paymentInfo=null;
    const payMethod=document.getElementById('payMethod'),payAmount=document.getElementById('payAmount'),payProof=document.getElementById('payProof'),payNote=document.getElementById('payNote'),transferWrap=document.getElementById('transferWrap'),cashNoteWrap=document.getElementById('cashNoteWrap'),transferInfo=document.getElementById('transferInfo'),payBillForm=document.getElementById('payBillForm');const syncPaymentMethod=()=>{const transfer=payMethod.value==='ChuyenKhoan';transferWrap.hidden=!transfer;cashNoteWrap.hidden=transfer;payProof.required=transfer;};
    document.addEventListener('click',async e=>{const b=e.target.closest('.pay-bill');if(!b)return;billId=b.dataset.id;const r=await app.api('/api/hoadon.php?action=thanhToanInfo&id='+billId);paymentInfo=r.data||{};if(Number(paymentInfo.ConLai||0)<=0){alert('Hóa đơn không còn số tiền cần thanh toán hoặc đang có yêu cầu chờ chủ trọ xác nhận.');return}payBillForm.reset();proof='';payAmount.value=Number(paymentInfo.ConLai||0).toLocaleString('en-US');transferInfo.innerHTML=`<b>Thông tin chuyển khoản của chủ trọ</b><div class="mt-1" style="white-space:pre-wrap">${paymentInfo.ThongTinChuyenKhoan||'Chủ trọ chưa cập nhật thông tin chuyển khoản.'}</div>${paymentInfo.MaQRThanhToan?`<div class="text-center mt-3"><img src="${paymentInfo.MaQRThanhToan}" style="max-width:240px;max-height:240px" class="img-fluid rounded border bg-white p-2" alt="Mã QR thanh toán"><br><a class="btn btn-sm btn-outline-primary mt-2" href="${paymentInfo.MaQRThanhToan}" download="ma-qr-thanh-toan.png"><i class="bi bi-download"></i> Lưu mã QR</a></div>`:''}`;syncPaymentMethod();payInstance.show()});
    payMethod.addEventListener('change',syncPaymentMethod);payProof.addEventListener('change',()=>{const f=payProof.files[0];proof='';if(!f)return;if(!['image/jpeg','image/png','image/webp'].includes(f.type)||f.size>5*1024*1024){payProof.value='';alert('Ảnh minh chứng phải là JPG, PNG hoặc WEBP và không vượt quá 5 MB.');return}const reader=new FileReader();reader.onload=()=>proof=reader.result;reader.readAsDataURL(f)});payBillForm.addEventListener('submit',async e=>{e.preventDefault();if(payMethod.value==='ChuyenKhoan'&&!proof){alert('Vui lòng tải ảnh minh chứng chuyển khoản.');return}await app.api('/api/hoadon.php?action=thanhToan&id='+billId,{method:'POST',body:JSON.stringify({PhuongThuc:payMethod.value,GhiChu:payMethod.value==='TienMat'?payNote.value.trim():null,MinhChung:payMethod.value==='ChuyenKhoan'?proof:null})});payInstance.hide();alert('Đã gửi yêu cầu. Chủ trọ sẽ xác nhận thanh toán.');window.location.reload()});
} else { loadHopDongOptions(); const pending=document.createElement('section');pending.className='card-panel mb-3';pending.dataset.pendingPayments='1';pending.innerHTML='<div class="panel-head"><h5><i class="bi bi-hourglass-split me-2"></i>Thanh toán chờ xác nhận</h5></div><div id="pendingPayments" class="vstack gap-2"></div>';const firstPanel=document.querySelector('.main-panel .card-panel');if(firstPanel)firstPanel.after(pending);else document.querySelector('.main-panel').append(pending);setTimeout(()=>{const invoicePanel=[...document.querySelectorAll('.main-panel .card-panel')].find(panel=>panel!==pending);if(invoicePanel)invoicePanel.before(pending)},0);const pendingPayments=document.getElementById('pendingPayments');const loadPending=async()=>{const r=await app.api('/api/hoadon.php?action=choXacNhan');pendingPayments.innerHTML=(r.data||[]).map(x=>`<div class="border rounded p-2 d-flex align-items-center gap-2 bg-warning-subtle"><div class="flex-grow-1"><b>Phòng ${x.SoPhong}</b> · ${x.HoTen}<br><small>${Number(x.SoTien).toLocaleString('vi-VN')} ₫ · ${x.PhuongThuc==='ChuyenKhoan'?'Chuyển khoản':'Tiền mặt'}</small>${x.GhiChu?`<br><small>Ghi chú: ${x.GhiChu}</small>`:''}${x.MinhChung?` <a href="${x.MinhChung}" target="_blank">Xem minh chứng</a>`:''}</div><button class="btn btn-sm btn-success confirm-payment" data-id="${x.Id}">Xác nhận</button></div>`).join('')||'<p class="text-muted mb-0">Không có yêu cầu chờ xác nhận.</p>'};document.addEventListener('click',async e=>{const b=e.target.closest('.confirm-payment');if(!b)return;await app.api('/api/hoadon.php?action=xacNhanThanhToan&paymentId='+b.dataset.id,{method:'POST',body:'{}'});window.location.reload();});loadPending(); }
if(!tenantInvoiceMode){const set=document.createElement('button');set.className='btn btn-outline-secondary';set.innerHTML='<i class="bi bi-qr-code"></i> Nhận tiền';document.querySelector('.main-panel .top-actions')?.append(set);const modal=document.createElement('div');modal.className='modal fade';modal.innerHTML='<div class="modal-dialog"><form class="modal-content"><div class="modal-header"><h5 class="modal-title">Thông tin nhận chuyển khoản</h5><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Ngân hàng / số tài khoản / chủ tài khoản</label><textarea class="form-control mb-3" id="receiveInfo" rows="3" placeholder="Ví dụ: MB Bank - 0123456789 - LÊ THỊ THU"></textarea><div id="receiveQrPreview" class="text-center mb-2"></div><label class="form-label">Ảnh mã QR</label><input type="file" id="receiveQr" class="form-control" accept="image/jpeg,image/png,image/webp"></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button><button class="btn btn-primary">Lưu</button></div></form></div>';document.body.append(modal);const m=bootstrap.Modal.getOrCreateInstance(modal);let qr='';const showReceiveQr=()=>receiveQrPreview.innerHTML=qr?`<img src="${qr}" style="max-width:220px;max-height:220px" class="img-fluid rounded border p-2" alt="Mã QR hiện tại"><br><a href="${qr}" download="ma-qr-thanh-toan.png" class="btn btn-sm btn-outline-primary mt-2"><i class="bi bi-download"></i> Lưu mã QR</a>`:'<small class="text-muted">Chưa có mã QR.</small>';set.onclick=async()=>{const r=await app.api('/api/hoadon.php?action=nhanTien');receiveInfo.value=r.data.ThongTinChuyenKhoan||'';qr=r.data.MaQRThanhToan||'';receiveQr.value='';showReceiveQr();m.show()};receiveQr.onchange=()=>{const f=receiveQr.files[0];if(!f)return;if(!['image/jpeg','image/png','image/webp'].includes(f.type)||f.size>5*1024*1024){receiveQr.value='';alert('Mã QR phải là ảnh JPG, PNG hoặc WEBP và không vượt quá 5 MB.');return}const reader=new FileReader();reader.onload=()=>{qr=reader.result;showReceiveQr()};reader.readAsDataURL(f)};modal.querySelector('form').onsubmit=async e=>{e.preventDefault();await app.api('/api/hoadon.php?action=nhanTien',{method:'PUT',body:JSON.stringify({ThongTinChuyenKhoan:receiveInfo.value.trim(),MaQRThanhToan:qr})});m.hide()}}
</script>
<script>
/* Invoice list: filters and pagination are API-driven so they stay scoped by role. */
(() => {
    const table=document.querySelector('.table-modern'), body=table?.querySelector('tbody'); if(!table||!body)return;
    const panel=document.createElement('div'); panel.className='row g-2 align-items-end mb-3 invoice-filter';
    panel.innerHTML='<div class="col-sm-4"><label class="form-label small">Kỳ hóa đơn</label><select class="form-select" id="invoicePeriod"><option value="">Tất cả các tháng</option></select></div><div class="col-sm-4"><label class="form-label small">Khu trọ</label><select class="form-select" id="invoiceArea"><option value="">Tất cả khu</option></select></div><div class="col-sm-4"><label class="form-label small">Dãy trọ</label><select class="form-select" id="invoiceRow"><option value="">Tất cả dãy</option></select></div><div class="col-12 d-flex justify-content-between align-items-center"><small class="text-muted" id="invoiceCount"></small><nav id="invoicePager"></nav></div>';
    table.parentElement.before(panel); const period=panel.querySelector('#invoicePeriod'),area=panel.querySelector('#invoiceArea'),row=panel.querySelector('#invoiceRow'),pager=panel.querySelector('#invoicePager'),count=panel.querySelector('#invoiceCount'); let options=[],page=1;
    const money=v=>Number(v||0).toLocaleString('vi-VN')+' ₫'; const status=s=>s==='DaThanhToan'?'Đã thanh toán':(s==='ChoXacNhanThanhToan'?'Đang chờ chủ trọ xác nhận thanh toán':(s==='ThanhToanMotPhan'?'Thanh toán một phần':'Chưa thanh toán')); const statusClass=s=>s==='DaThanhToan'?'table-success':(s==='ChoXacNhanThanhToan'?'table-info':(s==='ThanhToanMotPhan'?'table-primary':'table-warning'));
    const draw=async()=>{const [year,month]=(period.value||'-').split('-');const q=new URLSearchParams({phanTrang:'1',trang:String(page),gioiHan:'10'});if(year){q.set('nam',year);q.set('thang',month)}if(area.value)q.set('khuId',area.value);if(row.value)q.set('dayId',row.value);const result=await window.app.api('/api/hoadon.php?'+q);const data=result.data||{},items=data.items||[],p=data.pagination||{};body.innerHTML=items.map(x=>`<tr class="${statusClass(x.TrangThai)}"><td>#${x.Id}</td><td>${x.SoPhong}</td><td>${x.NguoiThue}</td><td>${x.Thang}/${x.Nam}</td><td>${money(x.TongTien)}</td><td>${money(x.DaTra)}</td><td><span class="badge ${x.TrangThai==='DaThanhToan'?'bg-success':(x.TrangThai==='ThanhToanMotPhan'?'bg-primary':'bg-warning text-dark')}">${status(x.TrangThai)}</span></td>${typeof tenantInvoiceMode!=='undefined'&&tenantInvoiceMode?(x.TrangThai==='DaThanhToan'?'<td><span class="text-success small">Đã hoàn tất</span></td>':`<td><button class="btn btn-sm btn-success pay-bill" data-id="${x.Id}">Đóng tiền</button></td>`):''}</tr>`).join('')||`<tr><td colspan="${typeof tenantInvoiceMode!=='undefined'&&tenantInvoiceMode?8:7}" class="text-center text-muted py-4">Không có hóa đơn phù hợp.</td></tr>`;count.textContent=`Hiển thị ${items.length}/${p.tong||0} hóa đơn`;pager.innerHTML=`<ul class="pagination pagination-sm mb-0"><li class="page-item ${p.trang<=1?'disabled':''}"><button class="page-link" data-page="${Math.max(1,(p.trang||1)-1)}">‹</button></li><li class="page-item disabled"><span class="page-link">${p.trang||1}/${p.tongTrang||1}</span></li><li class="page-item ${(p.trang||1)>=(p.tongTrang||1)?'disabled':''}"><button class="page-link" data-page="${Math.min(p.tongTrang||1,(p.trang||1)+1)}">›</button></li></ul>`;};
    const markPendingPayments=()=>body.querySelectorAll('tr').forEach(tr=>{if(!tr.textContent.includes('Đang chờ chủ trọ xác nhận thanh toán'))return;const button=tr.querySelector('.pay-bill');if(button)button.closest('td').innerHTML='<span class="text-info-emphasis small">Chờ xác nhận</span>';});new MutationObserver(markPendingPayments).observe(body,{childList:true,subtree:true});
    let statHost=document.getElementById('invoiceStats');if(!statHost){statHost=document.createElement('section');statHost.id='invoiceStats';statHost.className='stats-grid mb-3';table.closest('.card-panel')?.before(statHost);}document.querySelectorAll('.main-panel > .stats-grid').forEach(item=>{if(item!==statHost)item.remove()});const updateStats=async()=>{const [year,month]=(period.value||'-').split('-');const q=new URLSearchParams();if(year){q.set('nam',year);q.set('thang',month)}if(area.value)q.set('khuId',area.value);if(row.value)q.set('dayId',row.value);const s=(await window.app.api('/api/hoadon.php?action=thongKe&'+q)).data||{};statHost.innerHTML=[['Hóa đơn trong kỳ',s.Tong||0,'primary'],['Chưa thanh toán',s.ChuaThanhToan||0,'warning'],['Thanh toán một phần',s.MotPhan||0,'primary'],['Đã thanh toán',s.DaThanhToan||0,'success']].map(x=>`<div class="stat-card ${x[2]}"><div><span>${x[0]}</span><strong>${x[1]}</strong></div></div>`).join('');};
    const fill=async()=>{const result=await window.app.api('/api/hoadon.php?action=boLoc'), list=result.data||[];options=list;[...new Map(list.map(x=>[x.Nam+'-'+x.Thang,x])).values()].forEach(x=>period.add(new Option(`Tháng ${x.Thang}/${x.Nam}`,x.Nam+'-'+x.Thang)));[...new Map(list.map(x=>[x.KhuId,x])).values()].forEach(x=>area.add(new Option(x.TenKhu,x.KhuId)));syncRows();await draw();await updateStats();};
    const syncRows=()=>{const selected=area.value;row.innerHTML='<option value="">Tất cả dãy</option>';[...new Map(options.filter(x=>!selected||String(x.KhuId)===selected).map(x=>[x.DayId,x])).values()].forEach(x=>row.add(new Option(x.TenDay,x.DayId)));};
    period.onchange=()=>{page=1;draw();updateStats()};area.onchange=()=>{syncRows();page=1;draw();updateStats()};row.onchange=()=>{page=1;draw();updateStats()};pager.onclick=e=>{const b=e.target.closest('[data-page]');if(b){page=Number(b.dataset.page);draw()}};fill().catch(console.error);
})();
</script>
<script>
(() => {
 const table=document.querySelector('.table-modern'),body=table?.querySelector('tbody');if(!table||!body)return;
 const money=v=>Number(v||0).toLocaleString('vi-VN')+' ₫';
 const modal=document.createElement('div');modal.className='modal fade';modal.id='invoicePaperModal';modal.innerHTML='<div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Thông báo tiền phòng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="invoicePaperContent"></div><div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button><button class="btn btn-primary" id="printInvoicePaper"><i class="bi bi-printer"></i> In phiếu</button></div></div></div>';document.body.append(modal);const instance=bootstrap.Modal.getOrCreateInstance(modal);
 const decorate=()=>{const head=table.querySelector('thead tr');if(head&&!head.querySelector('.invoice-info-head')){const th=document.createElement('th');th.className='invoice-info-head text-end';th.textContent='Chi tiết';head.append(th)}body.querySelectorAll('tr').forEach(row=>{if(row.children.length<2||row.querySelector('.view-invoice'))return;const id=(row.children[0]?.textContent||'').replace('#','').trim();if(!id)return;const cell=document.createElement('td');cell.className='text-end';cell.innerHTML=`<button class="btn btn-sm btn-outline-primary view-invoice" data-id="${id}" title="Xem hóa đơn" aria-label="Xem chi tiết"><i class="bi bi-info-lg"></i></button>`;row.append(cell);});};new MutationObserver(decorate).observe(body,{childList:true});decorate();
 document.addEventListener('click',async event=>{const btn=event.target.closest('.view-invoice');if(!btn)return;const invoice=(await app.api('/api/hoadon.php?id='+btn.dataset.id)).data||{};const meter=(await app.api('/api/chisodiennuoc.php?phongId='+invoice.PhongId)).data||[];const reading=meter.find(item=>Number(item.Thang)===Number(invoice.Thang)&&Number(item.Nam)===Number(invoice.Nam))||{};const eUnits=Number(reading.ChiSoDienCuoi||0)-Number(reading.ChiSoDienDau||0),wUnits=Number(reading.ChiSoNuocCuoi||0)-Number(reading.ChiSoNuocDau||0);document.getElementById('invoicePaperContent').innerHTML=`<section class="invoice-paper"><div class="text-center mb-4"><img src="/assets/pics/logo.webp" style="width:54px;height:54px;object-fit:contain"><h4 class="mt-2 mb-1">THÔNG BÁO TIỀN PHÒNG TRỌ</h4><div>Tháng ${invoice.Thang}/${invoice.Nam}</div></div><div class="row small mb-3"><div class="col-6"><b>Người thuê:</b> ${invoice.NguoiThue||'—'}</div><div class="col-6"><b>Phòng:</b> ${invoice.SoPhong||'—'}</div><div class="col-6 mt-1"><b>Khu / Dãy:</b> ${invoice.TenKhu||''} · ${invoice.TenDay||''}</div><div class="col-6 mt-1"><b>Hạn đóng:</b> ${invoice.HanThanhToan?String(invoice.HanThanhToan).split('-').reverse().join('/'): '—'}</div></div><table class="table table-bordered invoice-detail-table"><thead><tr><th>STT</th><th>Khoản</th><th>Chi tiết</th><th class="text-end">Thành tiền</th></tr></thead><tbody><tr><td>1</td><td>Phòng</td><td>Tiền thuê tháng</td><td class="text-end">${money(invoice.TienPhong)}</td></tr><tr><td>2</td><td>Điện</td><td>(${reading.ChiSoDienCuoi??'—'} − ${reading.ChiSoDienDau??'—'}) = ${eUnits} kWh × ${money(reading.DonGiaDien)}/kWh</td><td class="text-end">${money(invoice.TienDien)}</td></tr><tr><td>3</td><td>Nước</td><td>(${reading.ChiSoNuocCuoi??'—'} − ${reading.ChiSoNuocDau??'—'}) = ${wUnits} m³ × ${money(reading.DonGiaNuoc)}/m³</td><td class="text-end">${money(invoice.TienNuoc)}</td></tr><tr><td>4</td><td>Dịch vụ</td><td>${reading.GhiChu||'Dịch vụ khác'}</td><td class="text-end">${money(invoice.TienDichVu)}</td></tr><tr class="fw-bold"><td colspan="3" class="text-end">TỔNG CỘNG</td><td class="text-end">${money(invoice.TongTien)}</td></tr></tbody></table><div class="small"><b>Đã thanh toán:</b> ${money(invoice.DaTra)} · <b>Còn nợ:</b> ${money(Number(invoice.TongTien)-Number(invoice.DaTra))}</div></section>`;instance.show();});
 document.getElementById('printInvoicePaper').onclick=()=>{const content=document.getElementById('invoicePaperContent').innerHTML,w=window.open('','_blank');if(!w)return;w.document.write('<html><head><meta charset="utf-8"><title>Thông báo tiền phòng</title><style>body{font-family:Arial;padding:25px}.invoice-paper{border:1px solid #f0b90b;border-radius:18px;padding:28px}.table{width:100%;border-collapse:collapse}.table td,.table th{border:1px solid #555;padding:8px}.invoice-detail-table thead{background:#fff4cf}</style></head><body>'+content+'<script>window.onload=()=>print()<\\/script></body></html>');w.document.close();};
})();
</script><style>.invoice-paper{max-width:720px;margin:auto;padding:26px;border:1px solid #f0b90b;border-radius:20px;background:linear-gradient(135deg,#fffdf6,#fff)}.invoice-detail-table thead th{background:#fff4cf}.invoice-detail-table td,.invoice-detail-table th{vertical-align:middle}</style>
</body>
</html>
