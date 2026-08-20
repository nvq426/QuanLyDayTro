<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
requireLogin();
header('Location: /views/khu/index.php');
exit;
$user = currentUser();
$pdo = getDb();
$rows = $pdo->query("SELECT p.*, d.TenDay, k.TenKhu FROM Phong p JOIN Day d ON d.Id = p.DayId JOIN Khu k ON k.Id = d.KhuId WHERE p.IsDeleted=0 AND d.IsDeleted=0 AND k.IsDeleted=0 ORDER BY p.Id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phòng</title>
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
            <a class="nav-item active" href="/views/phong/index.php"><i class="bi bi-building"></i> Quản lý phòng</a>
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
                <h2>Quản lý phòng</h2>
                <p>Danh sách phòng, dãy, tùy chọn trạng thái và giá thuê.</p>
            </div>
            <div class="top-actions">
                <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Thêm phòng</button>
            </div>
        </header>
        <section class="card-panel">
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Khu</th>
                            <th>Dãy</th>
                            <th>Phòng</th>
                            <th>Giá thuê</th>
                            <th>Diện tích</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rows as $row): ?>
                            <tr>
                                <td>#<?php echo $row['Id']; ?></td>
                                <td><?php echo htmlspecialchars($row['TenKhu']); ?></td>
                                <td><?php echo htmlspecialchars($row['TenDay']); ?></td>
                                <td><?php echo htmlspecialchars($row['SoPhong']); ?></td>
                                <td><?php echo number_format((float)$row['GiaThue'],0,',','.'); ?> ₫</td>
                                <td><?php echo htmlspecialchars($row['DienTich']); ?> m²</td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($row['TrangThai']); ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">Sửa</button>
                                    <button class="btn btn-sm btn-outline-danger">Xóa</button>
                                </td>
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
        const form = document.getElementById('formPhong');
        const modalTitle = document.getElementById('modalTitle');
        const idField = document.getElementById('phongId');
        const tbody = document.querySelector('tbody');

        function renderRow(item) {
            return `
                <tr data-id="${item.Id}">
                    <td>#${item.Id}</td>
                    <td>${item.TenKhu || ''}</td>
                    <td>${item.TenDay || ''}</td>
                    <td>${item.SoPhong}</td>
                    <td>${Number(item.GiaThue || 0).toLocaleString('vi-VN')} ₫</td>
                    <td>${item.DienTich || ''} m²</td>
                    <td><span class="badge bg-info">${item.TrangThai || 'Trong'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${item.Id}">Sửa</button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${item.Id}">Xóa</button>
                    </td>
                </tr>
            `;
        }

        async function loadData() {
            const result = await window.app.api('/api/phong.php');
            tbody.innerHTML = result.data.map(renderRow).join('');
        }

        document.getElementById('openAddPhong').addEventListener('click', () => {
            form.reset();
            idField.value = '';
            modalTitle.textContent = 'Thêm phòng';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPhong')).show();
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                DayId: Number(document.getElementById('DayId').value),
                SoPhong: document.getElementById('SoPhong').value,
                DienTich: Number(document.getElementById('DienTich').value || 0),
                GiaThue: Number(document.getElementById('GiaThue').value || 0),
                MoTa: document.getElementById('MoTa').value,
                TrangThai: document.getElementById('TrangThai').value
            };

            const method = idField.value ? 'PUT' : 'POST';
            const url = idField.value ? '/api/phong.php?id=' + idField.value : '/api/phong.php';
            await window.app.api(url, { method, body: JSON.stringify(payload) });
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPhong')).hide();
            loadData();
        });

        document.addEventListener('click', async (event) => {
            const editBtn = event.target.closest('.btn-edit');
            if (editBtn) {
                const id = editBtn.dataset.id;
                const data = await window.app.api('/api/phong.php?id=' + id);
                const item = data.data;
                idField.value = item.Id;
                document.getElementById('DayId').value = item.DayId;
                document.getElementById('SoPhong').value = item.SoPhong;
                document.getElementById('DienTich').value = item.DienTich || '';
                document.getElementById('GiaThue').value = item.GiaThue || 0;
                document.getElementById('MoTa').value = item.MoTa || '';
                document.getElementById('TrangThai').value = item.TrangThai || 'Trong';
                modalTitle.textContent = 'Sửa phòng';
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPhong')).show();
            }

            const deleteBtn = event.target.closest('.btn-delete');
            if (deleteBtn) {
                if (!confirm('Xóa phòng này?')) return;
                await window.app.api('/api/phong.php?id=' + deleteBtn.dataset.id, { method: 'DELETE' });
                loadData();
            }
        });

        loadData();
    </script>

    <div class="modal fade" id="modalPhong" tabindex="-1">
        <div class="modal-dialog">
            <form id="formPhong" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Thêm phòng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <input type="hidden" id="phongId">
                    <div class="col-md-6">
                        <label class="form-label">DayId</label>
                        <input type="number" id="DayId" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số phòng</label>
                        <input type="text" id="SoPhong" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Diện tích</label>
                        <input type="number" step="0.1" id="DienTich" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Giá thuê</label>
                        <input type="number" id="GiaThue" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mô tả</label>
                        <textarea id="MoTa" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Trạng thái</label>
                        <select id="TrangThai" class="form-select">
                            <option value="Trong">Trống</option>
                            <option value="DangThue">Đang thuê</option>
                            <option value="BaoTri">Bảo trì</option>
                        </select>
                    </div>
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
