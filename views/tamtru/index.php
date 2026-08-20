<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
requireLogin();
$user = currentUser();
$isTenant = ($user['VaiTro'] ?? '') === 'nguoithue';
$rows = [];
require_once __DIR__ . '/../../includes/navigation.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isTenant ? 'Khai báo lưu trú' : 'Quản lý tạm trú / lưu trú'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/assets/pics/logo.webp" type="image/webp" sizes="512x512">
</head>
<body>
<div class="app-shell">
    <?php renderApplicationSidebar($user, '/views/tamtru/index.php'); ?>
    <main class="main-panel">
        <header class="topbar">
            <div>
                <h2><?php echo $isTenant ? 'Khai báo lưu trú' : 'Quản lý tạm trú / lưu trú'; ?></h2>
                <p><?php echo $isTenant ? 'Khai báo khách lưu trú tại phòng bạn đang thuê.' : 'Theo dõi và cập nhật trạng thái đăng ký với UBND Phường/Xã.'; ?></p>
            </div>
            <div class="top-actions">
                <button id="openAddTamTru" class="btn btn-primary"><i class="bi bi-plus-lg"></i> <?php echo $isTenant ? 'Khai báo lưu trú' : 'Thêm khai báo'; ?></button>
            </div>
        </header>
        <section class="card-panel">
            <div class="input-group mb-3">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input id="staySearch" type="search" class="form-control" placeholder="Tìm theo họ tên, CCCD, phòng, quan hệ hoặc loại khai báo">
            </div>
            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Phòng</th>
                            <th>Họ tên</th>
                            <th>Quan hệ</th>
                            <th>Thời gian</th>
                            <th>Trạng thái</th>
                            <?php if (!$isTenant): ?><th>Thao tác</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>#<?php echo $row['Id']; ?></td>
                                <td><?php echo htmlspecialchars($row['SoPhong']); ?></td>
                                <td><?php echo htmlspecialchars($row['HoTen']); ?></td>
                                <td><?php echo htmlspecialchars($row['QuanHe']); ?></td>
                                <td><?php echo htmlspecialchars($row['NgayBatDau']); ?> → <?php echo htmlspecialchars($row['NgayKetThuc']); ?></td>
                                <td><span class="badge bg-info"><?php echo ($row['TrangThai'] == 1) ? 'Hoạt động' : 'Kết thúc'; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
    <script src="/assets/js/app.js"></script>
    <script>
        setTimeout(() => {
        const form = document.getElementById('formTamTru');
        const modalTitle = document.getElementById('modalTitle');
        const idField = document.getElementById('tamTruId');
        const tbody = document.querySelector('tbody');
        const searchInput = document.getElementById('staySearch');
        const isTenant = <?php echo json_encode($isTenant); ?>;
        let allItems = [];
        const serverStayItems = <?php echo json_encode($rows, JSON_UNESCAPED_UNICODE); ?>;
        const tamTruModal = window.bootstrap?.Modal ? new window.bootstrap.Modal(document.getElementById('modalTamTru')) : null;
        const formatDate = value => { const parts=String(value||'').slice(0,10).split('-'); return parts.length===3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : (value||'—'); };
        const stayStatus = item => item.TrangThaiXuLy==='TuChoi' ? ['Từ chối','bg-danger'] : (item.Loai==='LuuTru'&&item.TrangThaiXuLy==='ChoChuTroXacNhan' ? ['Chờ chủ trọ xác nhận lưu trú','bg-warning text-dark'] : (item.TrangThaiDangKy==='DaDangKyUBND'||Number(item.TrangThai)===1 ? ['Đã đăng ký với UBND Phường/Xã','bg-success'] : (item.TrangThaiDangKy==='DangKhaiBaoUBND' ? ['Đang đăng ký với UBND Phường/Xã','bg-info'] : [item.Loai==='LuuTru'?'Đã xác nhận lưu trú · Chưa đăng ký với UBND Phường/Xã':'Chưa đăng ký với UBND Phường/Xã','bg-secondary'])));

        function renderRow(item) {
            const status=stayStatus(item);
            const awaitingApproval=item.Loai==='LuuTru'&&item.TrangThaiXuLy==='ChoChuTroXacNhan';
            return `
                <tr>
                    <td>#${item.Id}</td>
                    <td>${item.SoPhong || ''}</td>
                    <td>${item.HoTen || ''}</td>
                    <td>${item.QuanHe || ''}</td>
                    <td>${formatDate(item.NgayBatDau)} → ${formatDate(item.NgayKetThuc)}</td>
                    <td><span class="badge bg-info">${item.Loai === 'LuuTru' ? 'Lưu trú' : 'Tạm trú'}</span> <span class="badge ${status[1]}">${status[0]}</span></td>
                    ${isTenant ? '' : `<td>
                        ${awaitingApproval?`<button class="btn btn-sm btn-success btn-approve-stay" data-id="${item.Id}"><i class="bi bi-check2-circle"></i> Xác nhận lưu trú</button>`:`<button class="btn btn-sm btn-outline-primary btn-edit" data-id="${item.Id}">Sửa</button>`}
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${item.Id}">Xóa</button>
                    </td>`}
                </tr>
            `;
        }

        function renderData() {
            const keyword = (searchInput?.value || '').trim().toLocaleLowerCase('vi-VN');
            const filtered = !keyword ? allItems : allItems.filter(item => [item.HoTen, item.CCCDKhach, item.SoPhong, item.QuanHe, item.Loai, item.NguoiThue].some(value => String(value || '').toLocaleLowerCase('vi-VN').includes(keyword)));
            tbody.innerHTML = filtered.map(renderRow).join('') || '<tr><td colspan="7" class="text-center text-muted py-4">Không tìm thấy dữ liệu phù hợp.</td></tr>';
        }

        function renderStayStats() {
            if (isTenant) { document.getElementById('stayStats')?.remove(); return; }
            // The management list contains the current declarations; do not drop
            // legacy records just because their stored end-date format differs.
            const active = allItems;
            const registered = active.filter(item => Number(item.TrangThai) === 1 || item.TrangThaiDangKy === 'DaDangKyUBND').length;
            const waitingApproval = active.filter(item => item.Loai==='LuuTru' && item.TrangThaiXuLy === 'ChoChuTroXacNhan').length;
            const registering = active.filter(item => item.TrangThaiDangKy === 'DangKhaiBaoUBND').length;
            const host = document.querySelector('.main-panel .card-panel');
            let stats = document.getElementById('stayStats');
            if (!stats && host) { stats = document.createElement('section'); stats.id = 'stayStats'; stats.className = 'stats-grid mb-3'; host.before(stats); }
            if (!stats) return;
            stats.innerHTML = [['Đang lưu trú', active.filter(item => item.Loai === 'LuuTru').length, 'primary'], ['Tạm trú', active.filter(item => item.Loai !== 'LuuTru').length, 'primary'], ['Chờ xác nhận lưu trú', waitingApproval, 'warning'], ['Đang đăng ký UBND', registering, 'primary'], ['Đã đăng ký UBND', registered, 'success']].map(item => `<div class="stat-card ${item[2]}"><div><span>${item[0]}</span><strong>${item[1]}</strong></div></div>`).join('');
        }

        async function loadData() {
            const result = await window.app.api('/api/tamtru.php');
            allItems = result.data || [];
            // Old databases can have NULL soft-delete values; retain the server-rendered list as a safe fallback.
            if (!allItems.length && serverStayItems.length) allItems = serverStayItems;
            renderData();
            renderStayStats();
        }

        async function loadRoomOptions() {
            const result=await window.app.api('/api/tamtru.php?action=phong');
            const select=document.getElementById('PhongId');
            select.innerHTML=(result.data||[]).map(room=>`<option value="${room.Id}">${room.TenKhu} · ${room.TenDay} · Phòng ${room.SoPhong}</option>`).join('');
            if(!select.options.length) select.innerHTML='<option value="">Không có phòng thuộc hợp đồng đang hiệu lực</option>';
        }

        searchInput?.addEventListener('input', renderData);

        document.getElementById('openAddTamTru').addEventListener('click', () => {
            form.reset();
            idField.value = '';
            if(isTenant) document.getElementById('Loai').value='LuuTru';
            modalTitle.textContent = isTenant ? 'Khai báo lưu trú' : 'Thêm khai báo';
            tamTruModal?.show();
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                PhongId: Number(document.getElementById('PhongId').value),
                HoTen: document.getElementById('HoTen').value,
                CCCDKhach: document.getElementById('CCCDKhach').value,
                QuanHe: document.getElementById('QuanHe').value,
                NgayBatDau: document.getElementById('NgayBatDau').value,
                NgayKetThuc: document.getElementById('NgayKetThuc').value,
                GhiChu: document.getElementById('GhiChu').value,
                Loai: isTenant ? 'LuuTru' : document.getElementById('Loai').value
            };
            if(!isTenant) payload.TrangThaiDangKy=document.getElementById('TrangThaiTamTru').value;
            const method = idField.value ? 'PUT' : 'POST';
            const url = idField.value ? '/api/tamtru.php?id=' + idField.value : '/api/tamtru.php';
            await window.app.api(url, { method, body: JSON.stringify(payload) });
            tamTruModal?.hide();
            loadData();
        });

        document.addEventListener('click', async (event) => {
            const approveBtn=isTenant?null:event.target.closest('.btn-approve-stay');
            if(approveBtn){if(!confirm('Xác nhận người này đang lưu trú tại phòng?'))return;await window.app.api('/api/tamtru.php?id='+approveBtn.dataset.id,{method:'PUT',body:JSON.stringify({QuyetDinh:'XacNhan'})});await loadData();return;}
            const editBtn = isTenant ? null : event.target.closest('.btn-edit');
            if (editBtn) {
                const data = await window.app.api('/api/tamtru.php');
                const item = (data.data || []).find(x => String(x.Id) === String(editBtn.dataset.id));
                if (!item) return;
                idField.value = item.Id;
                document.getElementById('PhongId').value = item.PhongId || '';
                document.getElementById('HoTen').value = item.HoTen || '';
                document.getElementById('CCCDKhach').value = item.CCCDKhach || '';
                document.getElementById('QuanHe').value = item.QuanHe || '';
                document.getElementById('NgayBatDau').value = item.NgayBatDau || '';
                document.getElementById('NgayKetThuc').value = item.NgayKetThuc || '';
                document.getElementById('GhiChu').value = item.GhiChu || '';
                document.getElementById('Loai').value = item.Loai || 'TamTru';
                document.getElementById('TrangThaiTamTru').value = item.TrangThaiDangKy || (Number(item.TrangThai) === 1 ? 'DaDangKyUBND' : 'ChuaKhaiBaoUBND');
                modalTitle.textContent = 'Sửa danh sách';
                tamTruModal?.show();
            }

            const deleteBtn = isTenant ? null : event.target.closest('.btn-delete');
            if (deleteBtn) {
                if (!confirm('Xóa mục này?')) return;
                await window.app.api('/api/tamtru.php?id=' + deleteBtn.dataset.id, { method: 'DELETE' });
                loadData();
            }
        });

        Promise.all([loadRoomOptions(),loadData()]);
        }, 0);
    </script>

    <div class="modal fade" id="modalTamTru" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form id="formTamTru" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Thêm danh sách</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" id="tamTruId">
                    <div class="col-md-6"><label class="form-label">Phòng</label><select id="PhongId" class="form-select" required><option value="">Đang tải danh sách phòng...</option></select></div>
                    <?php if (!$isTenant): ?><div class="col-md-6"><label class="form-label">Loại</label><select id="Loai" class="form-select"><option value="TamTru">Tạm trú</option><option value="LuuTru">Lưu trú</option></select></div><div class="col-md-6"><label class="form-label">Trạng thái đăng ký</label><select id="TrangThaiTamTru" class="form-select"><option value="ChuaKhaiBaoUBND">Chưa đăng ký với UBND Phường/Xã</option><option value="DangKhaiBaoUBND">Đang khai báo với UBND Phường/Xã</option><option value="DaDangKyUBND">Đã đăng ký với UBND Phường/Xã</option></select></div><?php else: ?><input type="hidden" id="Loai" value="LuuTru"><?php endif; ?>
                    <div class="col-md-6"><label class="form-label">Họ tên</label><input type="text" id="HoTen" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">CCCD</label><input type="text" id="CCCDKhach" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Quan hệ</label><input type="text" id="QuanHe" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Ngày bắt đầu</label><input type="date" id="NgayBatDau" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Ngày kết thúc</label><input type="date" id="NgayKetThuc" class="form-control" required></div>
                    <div class="col-12"><label class="form-label">Ghi chú</label><textarea id="GhiChu" class="form-control" rows="3"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
