<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
requireLogin();
$user = currentUser();
$pdo = getDb();
if (($user['VaiTro'] ?? '') === 'nguoithue') {$stmt=$pdo->prepare('SELECT c.*,p.SoPhong FROM ChiSoDienNuoc c JOIN Phong p ON p.Id=c.PhongId JOIN HopDong h ON h.PhongId=p.Id JOIN NguoiThue nt ON nt.Id=h.NguoiThueId WHERE nt.TaiKhoanId=:u ORDER BY c.Nam DESC,c.Thang DESC');$stmt->execute([':u'=>$user['Id']]);$rows=$stmt->fetchAll();} else {$rows = $pdo->query("SELECT c.*, p.SoPhong FROM ChiSoDienNuoc c JOIN Phong p ON p.Id = c.PhongId ORDER BY c.Nam DESC, c.Thang DESC")->fetchAll();}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Điện / Nước</title>
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
        <?php require_once __DIR__ . '/../../includes/helpers.php'; $avatar = getAvatarUrl($user); ?>
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
            <a class="nav-item active" href="/views/chisodiennuoc/index.php"><i class="bi bi-lightning-charge"></i> Điện / Nước</a>
            <a class="nav-item" href="/views/hoadon/index.php"><i class="bi bi-receipt"></i> Hóa đơn</a>
            <a class="nav-item" href="/views/tamtru/index.php"><i class="bi bi-person-badge"></i> Tạm trú</a>
            <a class="nav-item" href="/views/baocao/index.php"><i class="bi bi-bar-chart"></i> Báo cáo</a>
            <a class="nav-item" href="/views/taikhoan/index.php"><i class="bi bi-person-gear"></i> Tài khoản</a>
        </nav>
        
    </aside>
    <main class="main-panel">
        <header class="topbar">
            <div>
                <h2>Điện / Nước</h2>
                <p>Nhật ký chỉ số điện và nước theo từng phòng.</p>
            </div>
            <div class="top-actions">
                <button id="openAddChiSo" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nhập chỉ số</button>
            </div>
        </header>
        <section class="card-panel mb-4" id="meterNavigator"><div class="panel-head"><h5>Chọn khu / dãy để ghi điện nước</h5></div><div id="meterTree" class="d-flex flex-wrap gap-2"></div></section>
        <section class="card-panel mb-3" id="meterStats"><div class="d-flex gap-4"><div><small>Đã ghi kỳ này</small><strong id="meterDone" class="d-block text-success">0</strong></div><div><small>Chưa ghi trong dãy chọn</small><strong id="meterPending" class="d-block text-warning">—</strong></div><div><small>Kỳ ghi</small><strong id="meterPeriod" class="d-block">—</strong></div></div></section>
        <section class="card-panel">
            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead>
                        <tr>
                            <th>Phòng</th>
                            <th>Dãy / Khu</th>
                            <th>Diện tích</th>
                            <th>Giá thuê</th>
                            <th>Chỉ số kỳ gần nhất</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="meterTableBody">
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['SoPhong']); ?></td>
                                <td>—</td><td>—</td><td>—</td>
                                <td><?php echo $row['Thang']; ?>/<?php echo $row['Nam']; ?> · Điện <?php echo $row['ChiSoDienCuoi']; ?> · Nước <?php echo $row['ChiSoNuocCuoi']; ?></td><td>—</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<div class="modal fade" id="chiSoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="chiSoForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nhập chỉ số điện nước</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Phòng</label>
                    <select id="PhongId" class="form-select" required></select>
                </div>
                <div class="col-md-6 d-flex align-items-end"><div id="previousMeterInfo" class="alert alert-info small py-2 mb-0 w-100">Chọn phòng để lấy đơn giá và kỳ trước.</div></div><div class="col-12" id="meterHistory"></div>
                <div class="col-md-3">
                    <label class="form-label">Tháng</label>
                    <input type="number" id="Thang" class="form-control" min="1" max="12" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Năm</label>
                    <input type="number" id="Nam" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Điện đầu</label>
                    <input type="number" id="ChiSoDienDau" class="form-control" step="0.01" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Điện cuối</label>
                    <input type="number" id="ChiSoDienCuoi" class="form-control" step="0.01" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Đơn giá điện</label>
                    <input type="text" inputmode="numeric" id="DonGiaDien" class="form-control money-input" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Đơn giá nước</label>
                    <input type="text" inputmode="numeric" id="DonGiaNuoc" class="form-control money-input" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nước đầu</label>
                    <input type="number" id="ChiSoNuocDau" class="form-control" step="0.01" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nước cuối</label>
                    <input type="number" id="ChiSoNuocCuoi" class="form-control" step="0.01" required>
                </div>
                <div class="col-md-6"><label class="form-label">Tiền dịch vụ (rác, wifi,...)</label><input type="text" inputmode="numeric" id="TienDichVu" class="form-control money-input" value="0"></div>
                <div class="col-md-6"><label class="form-label">Ghi chú dịch vụ</label><input type="text" id="GhiChu" class="form-control" placeholder="Ví dụ: Wifi, rác..."></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-receipt"></i> Tạo hóa đơn</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
<script>
const parseMeterMoney = value => Number(String(value || '').replace(/[^0-9]/g, '')) || 0;
const formatMeterMoney = value => parseMeterMoney(value).toLocaleString('en-US');
window.parseMeterMoney = parseMeterMoney;
window.formatMeterMoney = formatMeterMoney;
const chiSoModal = new bootstrap.Modal(document.getElementById('chiSoModal'));
const chiSoForm = document.getElementById('chiSoForm');
const phongSelect = document.getElementById('PhongId');
const isAdmin = <?php echo json_encode(($user['VaiTro'] ?? '') === 'admin'); ?>;
const isChuTroMeter = <?php echo json_encode(($user['VaiTro'] ?? '') === 'chutro'); ?>;
window.isChuTroMeter = isChuTroMeter;
let meterKhu=[], meterDay=[], meterRooms=[], meterRecorded=[];
window.meterRooms = meterRooms;
window.meterConfigCache = null;
const meterFilter=document.createElement('div');meterFilter.className='ms-auto d-flex gap-2 align-items-end';meterFilter.innerHTML='<div><small class="d-block text-muted">Tháng</small><select id="meterFilterMonth" class="form-select form-select-sm"></select></div><div><small class="d-block text-muted">Năm</small><select id="meterFilterYear" class="form-select form-select-sm"></select></div>';document.getElementById('meterStats')?.firstElementChild?.append(meterFilter);const mfMonth=document.getElementById('meterFilterMonth'),mfYear=document.getElementById('meterFilterYear'),nowMeter=new Date();mfMonth.innerHTML=Array.from({length:12},(_,i)=>`<option value="${i+1}" ${i+1===nowMeter.getMonth()+1?'selected':''}>${i+1}</option>`).join('');mfYear.innerHTML=[nowMeter.getFullYear(),nowMeter.getFullYear()-1,nowMeter.getFullYear()-2].map(y=>`<option value="${y}">${y}</option>`).join('');mfMonth.onchange=mfYear.onchange=()=>loadMeterTree();

async function loadMeterTree(){
    const safe=(path,fallback={data:[]})=>window.app.api(path).catch(error=>{console.error('Không thể tải '+path,error);return fallback;});
    const period=`&thang=${mfMonth?.value||nowMeter.getMonth()+1}&nam=${mfYear?.value||nowMeter.getFullYear()}`;const [k,d,p,record]=await Promise.all([safe('/api/khu.php'),safe('/api/day.php'),safe('/api/phong.php'),safe('/api/chisodiennuoc.php?action=thongKe'+period,{data:{phongDaGhi:[]}})]);meterKhu=k.data||[];meterDay=d.data||[];meterRooms=p.data||[];window.meterRooms=meterRooms;meterRecorded=record.data?.phongDaGhi||[];document.getElementById('meterDone').textContent=meterRecorded.length;document.getElementById('meterPeriod').textContent=record.data?.thang+'/'+record.data?.nam;renderMeterTree();
}
function renderMeterTree(){
    const box=document.getElementById('meterTree');let previous=null;box.innerHTML=meterKhu.map(k=>{const owner=isAdmin&&previous!==k.TaiKhoanId?`<span class="badge text-bg-primary w-100 text-start py-2">Chủ trọ: ${k.ChuTro||'Chưa gán'}</span>`:'';previous=k.TaiKhoanId;const days=meterDay.filter(d=>Number(d.KhuId)===Number(k.Id));return owner+`<div class="border rounded p-2"><strong>${k.TenKhu}</strong><div class="mt-2 d-flex flex-wrap gap-1">${days.map(d=>`<button class="btn btn-sm btn-outline-primary meter-day" data-id="${d.Id}">${d.TenDay}</button>`).join('')||'<small class="text-muted">Chưa có dãy</small>'}</div></div>`}).join('');}
function showDayRooms(dayId){const rooms=meterRooms.filter(r=>Number(r.DayId)===Number(dayId));document.getElementById('meterTableBody').innerHTML=rooms.map(r=>`<tr class="meter-room" data-id="${r.Id}" style="cursor:pointer"><td><strong>${r.SoPhong}</strong></td><td>${r.TenDay||''} / ${r.TenKhu||''}</td><td>${r.DienTich||0} m²</td><td>${Number(r.GiaThue||0).toLocaleString('en-US')} ₫</td><td class="text-muted">Bấm để lấy kỳ trước</td><td><button class="btn btn-sm btn-success meter-room" data-id="${r.Id}"><i class="bi bi-lightning-charge"></i> Ghi điện / nước</button></td></tr>`).join('')||'<tr><td colspan="6" class="text-muted text-center">Dãy này chưa có phòng.</td></tr>';}

// Once a dãy has been selected, its name and parent khu are already known.
// Keep this screen focused on choosing a room and recording its readings.
function renderMeterRoomRows(rooms) {
    const body = document.getElementById('meterTableBody');
    body.innerHTML = rooms.map(room => {const done=meterRecorded.includes(Number(room.Id));return `<tr class="meter-room ${done?'table-success':'table-warning'}" data-id="${room.Id}" style="cursor:pointer"><td><strong>${room.SoPhong}</strong><br><small>${done?'Đã ghi điện/nước':'Chưa ghi điện/nước'}</small></td><td class="text-end"><button class="btn btn-sm ${done?'btn-outline-success':'btn-success'} meter-room" data-id="${room.Id}"><i class="bi bi-lightning-charge"></i> ${done?'Xem / sửa chỉ số':'Ghi điện / nước'}</button></td></tr>`}).join('') || '<tr><td colspan="2" class="text-muted text-center">Dãy này chưa có phòng.</td></tr>';
}
function showDayRooms(dayId) { const rooms=meterRooms.filter(room => Number(room.DayId) === Number(dayId));document.getElementById('meterPending').textContent=rooms.filter(room=>!meterRecorded.includes(Number(room.Id))).length;renderMeterRoomRows(rooms); }
function compactMeterTable() {
    const table = document.querySelector('#meterTableBody')?.closest('table');
    if (!table) return;
    table.querySelector('thead tr').innerHTML = '<th>Phòng</th><th class="text-end">Thao tác</th>';
}

async function loadPhongOptions() {
    const result = await window.app.api('/api/phong.php');
    const rooms = result.data || [];
    renderPhongOptions(rooms);
    if (rooms.length) await loadRoomMeterDefaults();
}

function renderPhongOptions(rooms) {
    phongSelect.innerHTML = rooms.map(room => `<option value="${room.Id}">${room.SoPhong} - ${room.TenDay || ''}</option>`).join('');
}

async function loadRoomMeterDefaults() {
    const roomId=phongSelect.value; if(!roomId)return;
    const roomRequest=window.app.api('/api/chisodiennuoc.php?action=nhap&phongId='+roomId);
    const configRequest=isChuTroMeter
        ? (window.meterConfigCache ? Promise.resolve({data:window.meterConfigCache}) : window.app.api('/api/chisodiennuoc.php?action=cauHinh').then(result=>{window.meterConfigCache=result.data||{};return result;}))
        : Promise.resolve({data:{}});
    const [result,configResult]=await Promise.all([roomRequest,configRequest]); const data=result.data,config=configResult.data||{};
    if (Number(config.DonGiaDien) > 0) data.phong.DonGiaDien = config.DonGiaDien;
    if (Number(config.DonGiaNuoc) > 0) data.phong.DonGiaNuoc = config.DonGiaNuoc;
    const previous=data.kyTruoc;
    document.getElementById('DonGiaDien').value=formatMeterMoney(data.phong.DonGiaDien||0);
    document.getElementById('DonGiaNuoc').value=formatMeterMoney(data.phong.DonGiaNuoc||0);
    document.getElementById('ChiSoDienDau').value=previous?previous.ChiSoDienCuoi:0;
    document.getElementById('ChiSoNuocDau').value=previous?previous.ChiSoNuocCuoi:0;
    document.getElementById('previousMeterInfo').innerHTML=(previous?`Kỳ trước: ${previous.Thang}/${previous.Nam} · ghi ngày ${String(previous.NgayGhi||'').slice(0,10).split('-').reverse().join('/')} · điện cuối ${previous.ChiSoDienCuoi}, nước cuối ${previous.ChiSoNuocCuoi}`:'Chưa có kỳ ghi trước. Đơn giá lấy từ hợp đồng hiện hiệu lực.')+` <button type="button" class="btn btn-sm btn-outline-info ms-2 show-meter-history">Lịch sử ghi</button>`;
}

async function submitChiSo(event) {
    event.preventDefault();
    const payload = {
        PhongId: Number(document.getElementById('PhongId').value),
        Thang: Number(document.getElementById('Thang').value),
        Nam: Number(document.getElementById('Nam').value),
        ChiSoDienDau: Number(document.getElementById('ChiSoDienDau').value || 0),
        ChiSoDienCuoi: Number(document.getElementById('ChiSoDienCuoi').value || 0),
        DonGiaDien: parseMeterMoney(document.getElementById('DonGiaDien').value),
        ChiSoNuocDau: Number(document.getElementById('ChiSoNuocDau').value || 0),
        ChiSoNuocCuoi: Number(document.getElementById('ChiSoNuocCuoi').value || 0),
        DonGiaNuoc: parseMeterMoney(document.getElementById('DonGiaNuoc').value),
        TienDichVu: parseMeterMoney(document.getElementById('TienDichVu').value),
        GhiChu: document.getElementById('GhiChu').value
    };
    const saved = await window.app.api('/api/chisodiennuoc.php', { method: 'POST', body: JSON.stringify(payload) });
    let invoice = saved.data?.hoaDon || null;
    if (!invoice && saved.data?.hoaDonId) invoice = (await window.app.api('/api/hoadon.php?id=' + saved.data.hoaDonId)).data;
    if (!invoice) { alert('Phòng chưa có hợp đồng hiệu lực nên chưa thể tạo hóa đơn.'); return; }
    const modalElement=document.getElementById('chiSoModal');
    modalElement.addEventListener('hidden.bs.modal',()=>window.showMeterInvoice?.(invoice,payload),{once:true});
    chiSoModal.hide();
}

document.getElementById('openAddChiSo').addEventListener('click', () => {
    chiSoForm.reset();
    loadPhongOptions();
    chiSoModal.show();
});

chiSoForm.addEventListener('submit', submitChiSo);
phongSelect.addEventListener('change', loadRoomMeterDefaults);
document.querySelectorAll('.money-input').forEach(input => {
    input.addEventListener('focus', () => { input.value = parseMeterMoney(input.value) || ''; });
    input.addEventListener('input', () => { input.value = formatMeterMoney(input.value); });
    input.addEventListener('blur', () => { input.value = formatMeterMoney(input.value); });
});
document.addEventListener('click', async (event)=>{
    const day=event.target.closest('.meter-day'); if(day){showDayRooms(day.dataset.id);return;}
    const room=event.target.closest('.meter-room'); if(room){
        chiSoForm.reset();
        document.getElementById('previousMeterInfo').innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Đang tải dữ liệu kỳ trước…';
        chiSoModal.show();
        let rooms=meterRooms;
        if(!rooms.length){const result=await window.app.api('/api/phong.php');rooms=result.data||[];meterRooms=rooms;window.meterRooms=rooms;}
        renderPhongOptions(rooms);phongSelect.value=room.dataset.id;
        const now=new Date();document.getElementById('Thang').value=now.getMonth()+1;document.getElementById('Nam').value=now.getFullYear();
        try{await loadRoomMeterDefaults()}catch(error){document.getElementById('previousMeterInfo').textContent=error.message||'Không thể tải dữ liệu chỉ số.';}
    }
    const history=event.target.closest('.show-meter-history');if(history){const data=await window.app.api('/api/chisodiennuoc.php?phongId='+phongSelect.value);document.getElementById('meterHistory').innerHTML=`<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Kỳ</th><th>Ngày ghi</th><th>Điện</th><th>Nước</th></tr></thead><tbody>${data.data.map(x=>`<tr><td>${x.Thang}/${x.Nam}</td><td>${String(x.NgayGhi||'').slice(0,10).split('-').reverse().join('/')}</td><td>${x.ChiSoDienDau} → ${x.ChiSoDienCuoi}</td><td>${x.ChiSoNuocDau} → ${x.ChiSoNuocCuoi}</td></tr>`).join('')}</tbody></table></div>`;}
});
compactMeterTable();
if (!<?php echo json_encode(($user['VaiTro'] ?? '') === 'nguoithue'); ?>) { loadPhongOptions(); loadMeterTree(); } else { document.getElementById('meterNavigator').remove(); document.getElementById('openAddChiSo').remove(); }
</script>
<script>
/* Owner-only pricing and an immediate, itemised invoice preview after recording. */
window.isChuTroMeter = <?= json_encode(($user['VaiTro'] ?? '') === 'chutro'); ?>;
const parseMeterConfigMoney = window.parseMeterMoney || (value => Number(String(value || '').replace(/[^0-9]/g, '')) || 0);
const formatMeterConfigMoney = window.formatMeterMoney || (value => parseMeterConfigMoney(value).toLocaleString('en-US'));
const meterMoney = value => Number(value || 0).toLocaleString('vi-VN') + ' ₫';
let latestInvoice = null, latestMeterPayload = null;
if (window.isChuTroMeter) {
    const configButton = document.createElement('button');
    configButton.type = 'button'; configButton.className = 'btn btn-outline-secondary';
    configButton.innerHTML = '<i class="bi bi-sliders"></i> Cấu hình giá điện / nước';
    document.querySelector('.main-panel .top-actions')?.append(configButton);
    const configModal = document.createElement('div'); configModal.className = 'modal fade'; configModal.id = 'meterConfigModal';
    configModal.innerHTML = '<div class="modal-dialog modal-dialog-centered"><form class="modal-content"><div class="modal-header"><h5 class="modal-title">Cấu hình đơn giá điện / nước</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-3"><p class="col-12 text-muted small mb-0">Đơn giá này được tự động điền khi ghi chỉ số. Bạn vẫn có thể điều chỉnh theo từng phòng hoặc từng kỳ.</p><div class="col-md-6"><label class="form-label">Đơn giá điện (₫/kWh)</label><input name="DonGiaDien" type="number" min="0" class="form-control" required></div><div class="col-md-6"><label class="form-label">Đơn giá nước (₫/m³)</label><input name="DonGiaNuoc" type="number" min="0" class="form-control" required></div></div><div class="modal-footer"><button class="btn btn-primary">Lưu cấu hình</button></div></form></div>';
    document.body.append(configModal); const configInstance = bootstrap.Modal.getOrCreateInstance(configModal);
    configModal.querySelectorAll('input').forEach(input => { input.type='text'; input.inputMode='numeric'; input.addEventListener('focus',()=>input.value=parseMeterConfigMoney(input.value)||''); input.addEventListener('blur',()=>input.value=formatMeterConfigMoney(input.value)); });
    configButton.onclick = async () => { configInstance.show(); const data = (await app.api('/api/chisodiennuoc.php?action=cauHinh')).data || {}; window.meterConfigCache=data; configModal.querySelector('[name="DonGiaDien"]').value = formatMeterConfigMoney(data.DonGiaDien || 0); configModal.querySelector('[name="DonGiaNuoc"]').value = formatMeterConfigMoney(data.DonGiaNuoc || 0); };
    configModal.querySelector('form').onsubmit = async event => { event.preventDefault(); const form = event.currentTarget,payload={DonGiaDien:parseMeterConfigMoney(form.DonGiaDien.value),DonGiaNuoc:parseMeterConfigMoney(form.DonGiaNuoc.value)}; await app.api('/api/chisodiennuoc.php?action=cauHinh', {method:'PUT',body:JSON.stringify(payload)}); window.meterConfigCache=payload; configInstance.hide(); };
}
const meterInvoiceModal = document.createElement('div'); meterInvoiceModal.className = 'modal fade'; meterInvoiceModal.id = 'meterInvoiceModal';
meterInvoiceModal.innerHTML = '<div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Thông báo tiền phòng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="meterInvoiceContent"></div><div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button><button class="btn btn-primary" id="sendMeterInvoice"><i class="bi bi-send"></i> Gửi hóa đơn đến người thuê</button></div></div></div>';
document.body.append(meterInvoiceModal); const meterInvoiceInstance = bootstrap.Modal.getOrCreateInstance(meterInvoiceModal);
window.showMeterInvoice = function showMeterInvoice(invoice, payload) {
    latestInvoice = invoice; latestMeterPayload = payload;
    const sendButton=document.getElementById('sendMeterInvoice');sendButton.disabled=false;sendButton.innerHTML='<i class="bi bi-send"></i> Gửi hóa đơn đến người thuê';
    const electricUnits = Number(payload.ChiSoDienCuoi) - Number(payload.ChiSoDienDau), waterUnits = Number(payload.ChiSoNuocCuoi) - Number(payload.ChiSoNuocDau);
    const room = (window.meterRooms || []).find(item => Number(item.Id) === Number(payload.PhongId)) || {};
    document.getElementById('meterInvoiceContent').innerHTML = `<section class="invoice-paper"><div class="text-center mb-4"><img src="/assets/pics/logo.webp" style="width:54px;height:54px;object-fit:contain" alt=""><h4 class="mt-2 mb-1">THÔNG BÁO TIỀN PHÒNG TRỌ</h4><div class="text-muted">Tháng ${payload.Thang}/${payload.Nam}</div></div><div class="row small mb-3"><div class="col-6"><b>Phòng:</b> ${room.SoPhong || invoice.SoPhong || ''}</div><div class="col-6"><b>Người thuê:</b> ${invoice.NguoiThue || '—'}</div><div class="col-6 mt-1"><b>Khu / Dãy:</b> ${room.TenKhu || invoice.TenKhu || ''} · ${room.TenDay || invoice.TenDay || ''}</div><div class="col-6 mt-1"><b>Hạn thanh toán:</b> ${invoice.HanThanhToan ? String(invoice.HanThanhToan).split('-').reverse().join('/') : '—'}</div></div><div class="table-responsive"><table class="table table-bordered invoice-detail-table"><thead><tr><th>STT</th><th>Khoản</th><th>Chi tiết</th><th class="text-end">Thành tiền</th></tr></thead><tbody><tr><td>1</td><td>Tiền phòng</td><td>Giá thuê tháng</td><td class="text-end">${meterMoney(invoice.TienPhong)}</td></tr><tr><td>2</td><td>Tiền điện</td><td>(${payload.ChiSoDienCuoi} − ${payload.ChiSoDienDau}) = ${electricUnits} kWh × ${meterMoney(payload.DonGiaDien)}/kWh</td><td class="text-end">${meterMoney(invoice.TienDien)}</td></tr><tr><td>3</td><td>Tiền nước</td><td>(${payload.ChiSoNuocCuoi} − ${payload.ChiSoNuocDau}) = ${waterUnits} m³ × ${meterMoney(payload.DonGiaNuoc)}/m³</td><td class="text-end">${meterMoney(invoice.TienNuoc)}</td></tr><tr><td>4</td><td>Dịch vụ</td><td>${payload.GhiChu || 'Rác, wifi, dịch vụ khác'}</td><td class="text-end">${meterMoney(invoice.TienDichVu)}</td></tr><tr class="fw-bold"><td colspan="3" class="text-end">TỔNG CỘNG</td><td class="text-end">${meterMoney(invoice.TongTien)}</td></tr></tbody></table></div><div class="small mt-3"><b>Ghi chú:</b> ${payload.GhiChu || '—'}<br><b>Trạng thái:</b> Chưa thanh toán</div><div class="d-flex justify-content-between text-center mt-5 small"><div><b>QUẢN LÝ NHÀ TRỌ</b><br><i>(Ký, ghi rõ họ tên)</i></div><div><b>NGƯỜI THUÊ</b><br><i>(Ký, ghi rõ họ tên)</i></div></div></section>`;
    meterInvoiceInstance.show();
};
document.getElementById('sendMeterInvoice').onclick = async () => { if (!latestInvoice?.Id) return; const button=document.getElementById('sendMeterInvoice');button.disabled=true;button.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Đang gửi…';try{await app.api('/api/chisodiennuoc.php?action=guiThongBao&hoaDonId=' + latestInvoice.Id, {method:'POST',body:'{}'});button.innerHTML='<i class="bi bi-check2"></i> Đã gửi hóa đơn';setTimeout(()=>{meterInvoiceInstance.hide();window.location.reload()},500)}catch(error){button.disabled=false;button.innerHTML='<i class="bi bi-send"></i> Gửi hóa đơn đến người thuê';alert(error.message)}};
</script>
<style>.invoice-paper{max-width:720px;margin:auto;padding:26px;border:1px solid #f0b90b;border-radius:20px;background:linear-gradient(135deg,#fffdf6,#fff)}.invoice-detail-table thead th{background:#fff4cf}.invoice-detail-table td,.invoice-detail-table th{vertical-align:middle}</style>
</body>
</html>
