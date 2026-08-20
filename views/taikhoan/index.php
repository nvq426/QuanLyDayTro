<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
require __DIR__ . '/../../includes/helpers.php';
requireLogin();
requireRole(['admin']);
$user = currentUser();
$pdo = getDb();
$totalAccounts = (int)$pdo->query('SELECT COUNT(*) FROM TaiKhoan WHERE IsDeleted = 0')->fetchColumn();
$activeAccounts = (int)$pdo->query('SELECT COUNT(*) FROM TaiKhoan WHERE IsDeleted = 0 AND TrangThai = 1')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản</title>
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
        <?php $avatar = getAvatarUrl($user); ?>
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
            <a class="nav-item" href="/views/hoadon/index.php"><i class="bi bi-receipt"></i> Hóa đơn</a>
            <a class="nav-item" href="/views/tamtru/index.php"><i class="bi bi-person-badge"></i> Tạm trú</a>
            <a class="nav-item" href="/views/baocao/index.php"><i class="bi bi-bar-chart"></i> Báo cáo</a>
            <a class="nav-item active" href="/views/taikhoan/index.php"><i class="bi bi-person-gear"></i> Tài khoản</a>
        </nav>
        
    </aside>
    <main class="main-panel">
        <header class="topbar">
            <div>
                <h2>Tài khoản</h2>
                <p>Quản lý tài khoản, quyền truy cập và trạng thái hoạt động.</p>
            </div>
            <div class="top-actions">
                <button class="btn btn-primary" id="openAddAccount"><i class="bi bi-plus-lg"></i> Thêm tài khoản</button>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card primary">
                <div class="icon"><i class="bi bi-people"></i></div>
                <div>
                    <span>Tổng tài khoản</span>
                    <strong id="totalAccounts"><?php echo $totalAccounts; ?></strong>
                </div>
            </div>
            <div class="stat-card success">
                <div class="icon"><i class="bi bi-shield-check"></i></div>
                <div>
                    <span>Đang hoạt động</span>
                    <strong id="activeAccounts"><?php echo $activeAccounts; ?></strong>
                </div>
            </div>
        </section>

        <section class="card-panel mt-4">
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-6 col-lg-5">
                    <form id="accountSearchForm" class="input-group">
                        <input id="accountSearch" class="form-control" placeholder="Tìm theo họ tên, tên đăng nhập, email, số điện thoại">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Tìm kiếm</button>
                    </form>
                </div>
                <div class="col-auto ms-md-auto d-flex align-items-center gap-2">
                    <label for="accountPageSize" class="text-muted text-nowrap">Số dòng</label>
                    <select id="accountPageSize" class="form-select">
                        <option value="10">10</option><option value="20" selected>20</option><option value="50">50</option><option value="100">100</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-modern align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Tên đăng nhập</th>
                            <th>Vai trò</th>
                            <th>Email</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="accountRows"><tr><td colspan="7" class="text-center text-muted py-4">Đang tải tài khoản…</td></tr></tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <small id="accountPageSummary" class="text-muted"></small>
                <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                    <form id="accountPageJumpForm" class="input-group input-group-sm" style="width:180px">
                        <span class="input-group-text">Trang</span>
                        <input id="accountPageJump" type="number" min="1" value="1" class="form-control" aria-label="Nhập số trang">
                        <button class="btn btn-outline-primary" type="submit">Đi đến</button>
                    </form>
                    <nav aria-label="Phân trang tài khoản"><ul id="accountPagination" class="pagination pagination-sm mb-0"></ul></nav>
                </div>
            </div>
        </section>
    </main>
</div>

<div class="modal fade" id="accountModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="accountForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountModalTitle">Thêm tài khoản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" id="accountId">
                <div class="col-md-6">
                    <label class="form-label">Họ tên</label>
                    <input type="text" id="HoTen" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tên đăng nhập</label>
                    <input type="text" id="TenDangNhap" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" id="MatKhau" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Vai trò</label>
                    <select id="VaiTro" class="form-select">
                        <option value="admin">Admin</option>
                        <option value="chutro">Chủ trọ</option>
                        <option value="nguoithue">Người thuê</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" id="Email" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" id="SoDienThoai" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Trạng thái</label>
                    <select id="TrangThai" class="form-select">
                        <option value="1">Hoạt động</option>
                        <option value="0">Khóa</option>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
<script>
const accountForm = document.getElementById('accountForm');
const accountModal = new bootstrap.Modal(document.getElementById('accountModal'));
const accountId = document.getElementById('accountId');
const modalTitle = document.getElementById('accountModalTitle');
const accountRows = document.getElementById('accountRows');
const accountPagination = document.getElementById('accountPagination');
const accountPageSummary = document.getElementById('accountPageSummary');
let accountPage = 1;
let accountPageSize = 20;
let accountTotalPages = 1;
let currentAccounts = [];
const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));

function resetForm() {
    accountForm.reset();
    accountId.value = '';
    modalTitle.textContent = 'Thêm tài khoản';
    document.getElementById('MatKhau').required = true;
}

const loadAccounts = async () => {
    const params = new URLSearchParams({trang: String(accountPage), gioiHan: String(accountPageSize)});
    const keyword = document.getElementById('accountSearch').value.trim();
    if (keyword) params.set('tuKhoa', keyword);
    const result = await window.app.api('/api/taikhoan.php?' + params);
    currentAccounts = result.data.items || [];
    const total = Number(result.data.total || 0);
    const totalPages = Number(result.data.totalPages || 1);
    accountTotalPages = totalPages;
    accountPage = Number(result.data.page || 1);
    const pageJump = document.getElementById('accountPageJump');
    pageJump.max = String(totalPages);
    pageJump.value = String(accountPage);
    accountRows.innerHTML = currentAccounts.map(item => `
        <tr>
            <td>#${item.Id}</td>
            <td>${escapeHtml(item.HoTen)}</td>
            <td>${escapeHtml(item.TenDangNhap)}</td>
            <td><span class="badge bg-primary">${escapeHtml(item.VaiTro)}</span></td>
            <td>${escapeHtml(item.Email || '')}</td>
            <td><span class="badge ${Number(item.TrangThai) === 1 ? 'bg-success' : 'bg-secondary'}">${Number(item.TrangThai) === 1 ? 'Hoạt động' : 'Khóa'}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${item.Id}">Sửa</button>
                <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${item.Id}">Xóa</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="7" class="text-center text-muted py-4">Không tìm thấy tài khoản phù hợp.</td></tr>';
    const first = total ? (accountPage - 1) * accountPageSize + 1 : 0;
    const last = Math.min(accountPage * accountPageSize, total);
    accountPageSummary.textContent = `Hiển thị ${first}–${last} trong tổng số ${total} tài khoản`;
    document.getElementById('totalAccounts').textContent = total;
    document.getElementById('activeAccounts').textContent = Number(result.data.totalActive || 0);
    accountPagination.innerHTML = `
        <li class="page-item ${accountPage <= 1 ? 'disabled' : ''}"><button class="page-link account-page" data-page="${accountPage - 1}">Trước</button></li>
        ${Array.from({length: totalPages}, (_, index) => index + 1).filter(number => number === 1 || number === totalPages || Math.abs(number - accountPage) <= 2).map((number, index, pages) => `${index && number - pages[index - 1] > 1 ? '<li class="page-item disabled"><span class="page-link">…</span></li>' : ''}<li class="page-item ${number === accountPage ? 'active' : ''}"><button class="page-link account-page" data-page="${number}">${number}</button></li>`).join('')}
        <li class="page-item ${accountPage >= totalPages ? 'disabled' : ''}"><button class="page-link account-page" data-page="${accountPage + 1}">Sau</button></li>`;
};

document.getElementById('accountSearchForm').addEventListener('submit', event => {
    event.preventDefault();
    accountPage = 1;
    loadAccounts();
});

document.getElementById('accountPageSize').addEventListener('change', event => {
    accountPageSize = Number(event.target.value);
    accountPage = 1;
    loadAccounts();
});

document.getElementById('accountPageJumpForm').addEventListener('submit', event => {
    event.preventDefault();
    const requestedPage = Number(document.getElementById('accountPageJump').value);
    if (!Number.isInteger(requestedPage) || requestedPage < 1 || requestedPage > accountTotalPages) {
        alert(`Vui lòng nhập số trang từ 1 đến ${accountTotalPages}`);
        return;
    }
    accountPage = requestedPage;
    loadAccounts();
});

document.getElementById('openAddAccount').addEventListener('click', () => {
    resetForm();
    accountModal.show();
});

accountForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = {
        HoTen: document.getElementById('HoTen').value,
        TenDangNhap: document.getElementById('TenDangNhap').value,
        MatKhau: document.getElementById('MatKhau').value,
        VaiTro: document.getElementById('VaiTro').value,
        Email: document.getElementById('Email').value,
        SoDienThoai: document.getElementById('SoDienThoai').value,
        TrangThai: Number(document.getElementById('TrangThai').value)
    };

    if (accountId.value) {
        await window.app.api('/api/taikhoan.php?id=' + accountId.value, {
            method: 'PUT',
            body: JSON.stringify(payload)
        });
    } else {
        await window.app.api('/api/taikhoan.php?action=them', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
    }

    accountModal.hide();
    loadAccounts();
});

document.addEventListener('click', async (event) => {
    const editBtn = event.target.closest('.btn-edit');
    if (editBtn) {
        const item = currentAccounts.find(x => String(x.Id) === String(editBtn.dataset.id));
        if (!item) return;
        accountId.value = item.Id;
        modalTitle.textContent = 'Sửa tài khoản';
        document.getElementById('HoTen').value = item.HoTen || '';
        document.getElementById('TenDangNhap').value = item.TenDangNhap || '';
        document.getElementById('MatKhau').value = '';
        document.getElementById('MatKhau').required = false;
        document.getElementById('VaiTro').value = item.VaiTro || 'nguoithue';
        document.getElementById('Email').value = item.Email || '';
        document.getElementById('SoDienThoai').value = item.SoDienThoai || '';
        document.getElementById('TrangThai').value = String(item.TrangThai ?? 1);
        accountModal.show();
    }

    const deleteBtn = event.target.closest('.btn-delete');
    if (deleteBtn) {
        if (!confirm('Xóa tài khoản này?')) return;
        await window.app.api('/api/taikhoan.php?id=' + deleteBtn.dataset.id, { method: 'DELETE' });
        loadAccounts();
    }

    const pageBtn = event.target.closest('.account-page');
    if (pageBtn && !pageBtn.closest('.disabled')) {
        accountPage = Number(pageBtn.dataset.page);
        loadAccounts();
    }
});

loadAccounts();
</script>
</body>
</html>
