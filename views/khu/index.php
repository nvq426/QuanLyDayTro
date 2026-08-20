<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/auth.php';
requireLogin();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khu / Dãy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/assets/pics/logo.webp" type="image/webp" sizes="512x512">
    <style>
        .khu-tree-layout > .col-lg-6:first-child { width: 100%; }
        .khu-tree-layout > .col-lg-6:last-child { display: none; }
        .tree-khu-row { cursor: pointer; }
        .tree-owner-row { background: #dbeafe !important; cursor: pointer; }
        .tree-khu-row { background: #f5f3ff; }
        .tree-day-row { background: #ecfeff; cursor: pointer; }
        .tree-room-row { background: #f0fdf4; cursor: pointer; }
        .tree-khu-row:hover, .tree-day-row:hover, .tree-room-row:hover { filter: brightness(.97); }
        .tree-branch { color: #94a3b8; font-family: monospace; }
        #openAddDay { display: none; }
        .khu-tree-layout .table-responsive { overflow-x: hidden; }
        .khu-tree-layout table { width: 100%; table-layout: fixed; }
        .khu-tree-layout td, .khu-tree-layout th { overflow-wrap: anywhere; vertical-align: middle; }
        .tree-day-row td { white-space: normal; }
        .tree-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 6px; }
        .tree-actions .btn { width: 34px; height: 34px; padding: 0; display: inline-grid; place-items: center; }
        .tree-actions svg { width: 16px; height: 16px; fill: currentColor; }
        #khuTableBody .tree-khu-row td:first-child, #khuTableBody .tree-day-row td:first-child, #khuTableBody .tree-room-row td:first-child,
        .khu-tree-layout thead th:first-child { display: none; }
        .tree-owner-button { border: 0; background: transparent; font-weight: 700; color: #1e3a8a; padding: 4px 0; }
        /* Từng cấp là một mục danh sách tách biệt để nhìn rõ Khu - Dãy - Phòng. */
        .khu-tree-layout .table-modern { border-collapse: separate; border-spacing: 0 9px; }
        .khu-tree-layout .table-modern thead th { border: 0; }
        #khuTableBody > tr > td { border: 0 !important; padding: 14px 12px; box-shadow: inset 0 1px 0 rgba(15, 23, 42, .05), inset 0 -1px 0 rgba(15, 23, 42, .05); }
        #khuTableBody > tr > td:nth-child(2) { border-radius: 13px 0 0 13px; box-shadow: inset 1px 0 0 rgba(15, 23, 42, .05), inset 0 1px 0 rgba(15, 23, 42, .05), inset 0 -1px 0 rgba(15, 23, 42, .05); }
        #khuTableBody > tr > td:last-child { border-radius: 0 13px 13px 0; box-shadow: inset -1px 0 0 rgba(15, 23, 42, .05), inset 0 1px 0 rgba(15, 23, 42, .05), inset 0 -1px 0 rgba(15, 23, 42, .05); }
        #khuTableBody .tree-owner-row > td { background: #dbeafe !important; border-radius: 13px !important; color: #1e3a8a; }
        #khuTableBody .tree-khu-row > td { background: #ede9fe !important; }
        #khuTableBody .tree-day-row > td { background: #e0f2fe !important; }
        #khuTableBody .tree-room-row > td { background: #ecfdf5 !important; }
        #khuTableBody .tree-khu-row:hover > td { background: #ddd6fe !important; }
        #khuTableBody .tree-day-row:hover > td { background: #bae6fd !important; }
        #khuTableBody .tree-room-row:hover > td { background: #d1fae5 !important; }
        .detail-card { cursor: pointer; transition: .2s ease; height: 100%; background: linear-gradient(135deg, #fff, #f8fafc); }
        .detail-card:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(15,23,42,.1); border-color: #6366f1 !important; }
        .room-detail-shell { border: 0; border-radius: 18px; overflow: hidden; }
        .room-detail-shell > .modal-header { background: linear-gradient(135deg, #4338ca, #2563eb); color: #fff; border: 0; }
        .room-detail-shell > .modal-header .btn-close { filter: brightness(0) invert(1); }
        .room-summary-card { border-width: 1px !important; border-radius: 14px !important; min-height: 118px; box-shadow: 0 5px 14px rgba(15,23,42,.07); }
        .room-contract-card { color: #14532d; border-color: #86efac !important; background: linear-gradient(135deg, #f0fdf4, #dcfce7) !important; }
        .room-invoice-card { color: #1e3a8a; border-color: #93c5fd !important; background: linear-gradient(135deg, #eff6ff, #dbeafe) !important; }
        .room-meter-card { color: #713f12; border-color: #fde047 !important; background: linear-gradient(135deg, #fefce8, #fef3c7) !important; }
        .detail-section { border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; background: #fff; }
        #roomHistoryModal { z-index: 1080; }
        .contract-party-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; background: #f8fafc; height: 100%; }
        .contract-party-card h6 { color: #1e3a8a; margin-bottom: 12px; }
        .stay-modal-card .modal-header { background: linear-gradient(135deg, #0f766e, #14b8a6); color: #fff; border: 0; }
        .stay-modal-card .btn-close { filter: brightness(0) invert(1); }
        .stay-person-summary { background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 12px; padding: 12px; color: #134e4a; }
        .rental-management-head { align-items: center; gap: 12px; }
        .rental-management-head h5 { font-size: 1.15rem; color: #172554; }
        .rental-management-actions { display: flex; align-items: center; gap: 7px; margin-left: auto; flex-wrap: wrap; }
        .rental-management-actions .btn { padding: .42rem .68rem; font-size: .84rem; border-radius: 9px; }
        .rental-room-search { display: flex; align-items: center; gap: .5rem; }
        .rental-room-search .form-control { min-width: 0; }
        .rental-room-search #openRoomFilters {
            height: 38px;
            min-height: 38px;
            min-width: 42px;
            padding: .375rem .65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .rental-room-search #openRoomFilters svg { width: 19px; height: 19px; }
        #roomFilterModal .modal-header { background: linear-gradient(135deg, #1d4ed8, #2563eb); color: #fff; }
        #roomFilterModal .modal-header .btn-close { filter: brightness(0) invert(1); }
        @media (max-width: 767.98px) {
            .room-members-table .member-role-col,
            .room-members-table .member-stay-status { display: none !important; }
            .room-members-table th, .room-members-table td { padding: .45rem .35rem; font-size: .82rem; }
            .room-members-table .btn { padding: .3rem .45rem; font-size: .74rem; }
        }
        @media (max-width: 520px) {
            .rental-management-head { align-items: flex-start; }
            .rental-management-head h5 { font-size: 1rem; max-width: 38%; }
            .rental-management-actions { justify-content: flex-end; gap: 5px; }
            .rental-management-actions .btn { padding: .38rem .5rem; font-size: .76rem; }
        }
    </style>
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
            <a class="nav-item active" href="/views/khu/index.php"><i class="bi bi-geo-alt"></i> Khu / Dãy</a>
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
                <h2>Quản lý phòng thuê</h2>
                <p>Quản lý cấu trúc khu nhà trọ và các dãy phòng.</p>
            </div>
            <div class="top-actions">
                <button class="btn btn-success" id="openRentRoom"><i class="bi bi-key"></i> Cho thuê phòng</button>
                <button class="btn btn-primary" id="openAddKhu"><i class="bi bi-plus-lg"></i> Thêm khu</button>
                <button class="btn btn-outline-primary" id="openAddDay"><i class="bi bi-plus-lg"></i> Thêm dãy</button>
            </div>
        </header>

        <section class="row gx-4 khu-tree-layout">
            <div class="col-lg-6">
                <div class="card-panel">
                    <div class="panel-head">
                        <h5>Danh sách khu</h5>
                    </div>
                    <div class="table-responsive">
                        <div class="mb-3 px-1 rental-room-search">
                            <input id="treeSearch" class="form-control" type="search" placeholder="Tìm tên hoặc số phòng..." aria-label="Tìm tên hoặc số phòng">
                            <button type="button" id="openRoomFilters" class="btn btn-outline-primary" title="Mở bộ lọc phòng" aria-label="Mở bộ lọc phòng">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16"></path><path d="M7 12h10"></path><path d="M10 19h4"></path></svg>
                            </button>
                        </div>
                        <table class="table table-modern align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên khu</th>
                                    <th>Địa chỉ</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="khuTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-panel">
                    <div class="panel-head">
                        <h5>Danh sách dãy</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên dãy</th>
                                    <th>Khu</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="dayTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<div class="modal fade" id="roomFilterModal" tabindex="-1" aria-labelledby="roomFilterModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomFilterModalTitle"><i class="bi bi-funnel me-2"></i>Bộ lọc phòng thuê</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12"><label class="form-label" for="treeKhuFilter">Khu trọ</label><select id="treeKhuFilter" class="form-select" aria-label="Lọc chính xác theo khu"><option value="">Tất cả khu</option></select></div>
                <div class="col-12"><label class="form-label" for="treeDayFilter">Dãy phòng</label><select id="treeDayFilter" class="form-select" aria-label="Lọc chính xác theo dãy"><option value="">Tất cả dãy</option></select></div>
                <div class="col-12"><label class="form-label" for="treeStatusFilter">Trạng thái phòng</label><select id="treeStatusFilter" class="form-select" aria-label="Lọc chính xác theo trạng thái"><option value="">Tất cả trạng thái</option><option value="Trong">Phòng trống</option><option value="DangThue">Đang thuê</option><option value="BaoTri">Bảo trì</option></select></div>
                <div class="col-12" id="treeOwnerFilterWrap" hidden><label class="form-label" for="treeOwnerFilter">Chủ trọ</label><select id="treeOwnerFilter" class="form-select"><option value="">Tất cả chủ trọ</option></select></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                <button type="button" id="clearRoomFilters" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i> Xóa lọc</button>
                <button type="button" id="applyRoomFilters" class="btn btn-primary"><i class="bi bi-check2"></i> Áp dụng bộ lọc</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="khuModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="khuForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="khuModalTitle">Thêm khu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" id="khuId">
                <div class="col-12" id="ownerField" hidden>
                    <label class="form-label">Chủ trọ quản lý</label>
                    <select id="TaiKhoanId" class="form-select"></select>
                </div>
                <div class="col-12">
                    <label class="form-label">Tên khu</label>
                    <input type="text" id="TenKhu" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" id="DiaChi" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea id="MoTaKhu" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="dayModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="dayForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dayModalTitle">Thêm dãy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <input type="hidden" id="dayId">
                <div class="col-12">
                    <label class="form-label">Khu</label>
                    <select id="DayKhuId" class="form-select" required></select>
                </div>
                <div class="col-12">
                    <label class="form-label">Tên dãy</label>
                    <input type="text" id="TenDay" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Mô tả</label>
                    <textarea id="MoTaDay" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="roomHistoryModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="roomHistoryTitle">Lịch sử</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="roomHistoryContent"></div></div></div></div>

<div class="modal fade" id="roomDetailModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content room-detail-shell">
    <div class="modal-header"><h5 class="modal-title" id="roomDetailTitle">Chi tiết phòng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="roomDetailContent"></div>
</div></div></div>

<div class="modal fade" id="roomModal" tabindex="-1"><div class="modal-dialog"><form id="roomForm" class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="roomModalTitle">Phòng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3"><input type="hidden" id="roomId">
        <div class="col-12" id="roomDayField"><label class="form-label">Dãy</label><select id="RoomDayId" class="form-select" required></select></div>
        <div class="col-md-6"><label class="form-label">Số phòng</label><input id="RoomSoPhong" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Diện tích (m²)</label><input id="RoomDienTich" type="number" step="0.1" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Giá thuê (₫)</label><input id="RoomGiaThue" type="number" step="1" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Trạng thái</label><select id="RoomTrangThai" class="form-select"><option value="Trong">Trống</option><option value="DangThue">Đang thuê</option><option value="BaoTri">Bảo trì</option></select></div>
        <div class="col-12"><label class="form-label">Mô tả</label><textarea id="RoomMoTa" class="form-control"></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button class="btn btn-primary" type="submit">Lưu</button></div>
</form></div></div>

<div class="modal fade" id="stayModal" tabindex="-1"><div class="modal-dialog"><form id="stayForm" class="modal-content stay-modal-card">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-vcard"></i> Khai báo tạm trú / lưu trú</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3"><input type="hidden" id="StayPhongId"><input type="hidden" id="StayHoTen"><input type="hidden" id="StayCCCD"><div class="col-12"><div id="stayPersonSummary" class="stay-person-summary"></div></div><div class="col-md-6"><label class="form-label">Loại khai báo</label><select id="StayLoai" class="form-select"><option value="TamTru">Tạm trú</option><option value="LuuTru">Lưu trú</option></select></div><div class="col-md-6"><label class="form-label">Trạng thái với UBND Phường/Xã</label><select id="StayTrangThaiDangKy" class="form-select"><option value="DangKhaiBaoUBND">Đang khai báo với UBND Phường/Xã</option><option value="DaDangKyUBND">Đã đăng ký với UBND Phường/Xã</option><option value="ChuaKhaiBaoUBND">Chưa đăng ký với UBND Phường/Xã</option></select></div><div class="col-md-6"><label class="form-label">Quan hệ</label><input id="StayQuanHe" class="form-control" value="Thành viên phòng" required></div><div class="col-md-6"><label class="form-label">Ngày bắt đầu</label><input id="StayNgayBatDau" type="date" class="form-control" required></div><div class="col-md-6"><label class="form-label">Ngày kết thúc</label><input id="StayNgayKetThuc" type="date" class="form-control" required></div><div class="col-12"><label class="form-label">Ghi chú</label><textarea id="StayGhiChu" class="form-control" rows="2" placeholder="Ví dụ: Đã nộp hồ sơ tại phường/xã"></textarea></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-success">Lưu khai báo</button></div>
</form></div></div>

<div class="modal fade" id="memberModal" tabindex="-1"><div class="modal-dialog"><form id="memberForm" class="modal-content">
    <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="bi bi-person-plus"></i> Thêm thành viên phòng</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3"><input type="hidden" id="MemberHopDongId"><input type="hidden" id="MemberRoomId"><div class="col-md-6"><label class="form-label">Loại thành viên</label><select id="MemberLoai" class="form-select"><option value="ThanhVienPhong">Thành viên phòng</option><option value="LuuTru">Thành viên lưu trú</option></select></div><div class="col-md-6"><label class="form-label">Quan hệ</label><input id="MemberQuanHe" class="form-control" value="Thành viên"></div><div class="col-md-6"><label class="form-label">Họ và tên *</label><input id="MemberHoTen" class="form-control" required></div><div class="col-md-6"><label class="form-label">CCCD *</label><input id="MemberCCCD" class="form-control" required></div><div class="col-md-6"><label class="form-label">Ngày sinh</label><input id="MemberNgaySinh" type="date" class="form-control"></div><div class="col-md-6"><label class="form-label">Số điện thoại</label><input id="MemberSoDienThoai" class="form-control"></div><div class="col-12"><p class="small text-muted mb-0">Tài khoản người thuê được tạo tự động theo CCCD nếu chưa tồn tại.</p></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary">Thêm thành viên</button></div>
</form></div></div>

<div class="modal fade" id="rentRoomModal" tabindex="-1"><div class="modal-dialog modal-xl"><form id="rentRoomForm" class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Cho thuê phòng & tạo hợp đồng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div id="rentRoomPicker"><p class="text-muted mb-3">Chọn một phòng trống để bắt đầu lập hợp đồng.</p><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Khu / dãy / phòng</th><th>Diện tích</th><th>Giá thuê</th><th></th></tr></thead><tbody id="availableRooms"></tbody></table></div></div><div id="rentContractFields" hidden><div class="alert alert-light border small mb-3">Thông tin hợp đồng cho phòng đã chọn.</div>
        <div class="row g-3"><div class="col-md-6"><label class="form-label">Phòng trống</label><select id="RentPhongId" class="form-select" required></select></div><div class="col-md-3"><label class="form-label">Giá thuê (₫)</label><input id="RentGiaThue" inputmode="numeric" class="form-control money-input" required></div><div class="col-md-3"><label class="form-label">Tiền cọc (₫)</label><input id="RentTienCoc" inputmode="numeric" class="form-control money-input" value="0"></div>
        <div class="col-md-6"><label class="form-label">Ngày bắt đầu</label><input id="RentNgayBatDau" type="date" class="form-control" required></div><div class="col-md-6"><label class="form-label">Ngày kết thúc</label><input id="RentNgayKetThuc" type="date" class="form-control" required></div></div>
        <hr><h6>Bên A — chủ cho thuê</h6><div class="row g-3"><div class="col-md-4"><label class="form-label">Họ tên</label><input id="RentBenAHoTen" class="form-control"></div><div class="col-md-4"><label class="form-label">CCCD</label><input id="RentBenACCCD" class="form-control"></div><div class="col-md-4"><label class="form-label">SĐT</label><input id="RentBenASoDienThoai" class="form-control"></div><div class="col-md-6"><label class="form-label">Ngày sinh</label><input id="RentBenANgaySinh" type="date" class="form-control"></div><div class="col-md-6"><label class="form-label">Địa chỉ</label><input id="RentBenADiaChi" class="form-control"></div></div>
        <hr><h6>Bên B — người thuê chính</h6><div class="row g-3"><div class="col-md-4"><label class="form-label">Họ tên *</label><input id="RentBenBHoTen" class="form-control" required></div><div class="col-md-4"><label class="form-label">CCCD *</label><input id="RentBenBCCCD" class="form-control" required></div><div class="col-md-4"><label class="form-label">SĐT</label><input id="RentBenBSoDienThoai" class="form-control"></div><div class="col-md-4"><label class="form-label">Ngày sinh</label><input id="RentBenBNgaySinh" type="date" class="form-control"></div><div class="col-md-4"><label class="form-label">Email</label><input id="RentBenBEmail" type="email" class="form-control"></div><div class="col-md-4"><label class="form-label">Địa chỉ thường trú</label><input id="RentBenBDiaChi" class="form-control"></div></div>
        <hr><div class="d-flex justify-content-between align-items-center"><h6 class="mb-0">Thành viên cùng phòng</h6><button type="button" id="addContractMember" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus"></i> Thêm thành viên</button></div><div class="table-responsive mt-2"><table class="table table-sm"><thead><tr><th>Họ tên</th><th>CCCD</th><th>Ngày sinh</th><th>SĐT</th><th>Quan hệ</th><th></th></tr></thead><tbody id="contractMembers"></tbody></table></div>
        <hr><h6>Điện nước đầu kỳ</h6><div class="row g-3"><div class="col-md-3"><label class="form-label">Chỉ số điện đầu</label><input id="RentChiSoDienDau" type="number" step="0.01" class="form-control" value="0"></div><div class="col-md-3"><label class="form-label">Đơn giá điện (₫)</label><input id="RentDonGiaDien" inputmode="numeric" class="form-control money-input" value="0"></div><div class="col-md-3"><label class="form-label">Chỉ số nước đầu</label><input id="RentChiSoNuocDau" type="number" step="0.01" class="form-control" value="0"></div><div class="col-md-3"><label class="form-label">Đơn giá nước (₫)</label><input id="RentDonGiaNuoc" inputmode="numeric" class="form-control money-input" value="0"></div><div class="col-12"><label class="form-label">Điều khoản bổ sung</label><textarea id="RentDieuKhoan" class="form-control" rows="2"></textarea></div></div>
    </div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" id="previewContract" hidden>Xem trước / in PDF</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button><button class="btn btn-success" id="confirmContract" type="submit" hidden>Xác nhận hợp đồng</button></div>
</form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
<script>
const khuModal = new bootstrap.Modal(document.getElementById('khuModal'));
const dayModal = new bootstrap.Modal(document.getElementById('dayModal'));
const khuForm = document.getElementById('khuForm');
const dayForm = document.getElementById('dayForm');
const dayKhuSelect = document.getElementById('DayKhuId');
const khuTableBody = document.getElementById('khuTableBody');
const dayTableBody = document.getElementById('dayTableBody');
const roomModal = new bootstrap.Modal(document.getElementById('roomModal'));
const roomForm = document.getElementById('roomForm');
const roomDetailModal = new bootstrap.Modal(document.getElementById('roomDetailModal'));
const roomHistoryModal = new bootstrap.Modal(document.getElementById('roomHistoryModal'));
const rentRoomModal = new bootstrap.Modal(document.getElementById('rentRoomModal'));
const stayModal = new bootstrap.Modal(document.getElementById('stayModal'));
const memberModal = new bootstrap.Modal(document.getElementById('memberModal'));
document.getElementById('roomHistoryModal').addEventListener('shown.bs.modal', () => {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    const backdrop = backdrops[backdrops.length - 1];
    if (backdrop) backdrop.style.zIndex = '1070';
});
const isAdmin = <?php echo json_encode(($user['VaiTro'] ?? '') === 'admin'); ?>;

const state = {
    khuList: [],
    dayList: [],
    selectedKhuId: null,
    expandedKhuIds: new Set(),
    expandedOwnerIds: new Set(),
    ownerList: [],
    roomList: [],
    expandedDayIds: new Set()
};
const appliedRoomFilters = { khu: '', day: '', status: '', owner: '' };

const actionIcons = {
    add: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 2a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 8 2Z"/></svg>',
    edit: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M12.15.85a2.1 2.1 0 0 1 3 3l-8.8 8.8-3.3.7.7-3.3 8.4-8.4ZM4.98 10.73l-.3 1.4 1.4-.3 7.99-7.99-1.1-1.1-7.99 7.99Z"/></svg>',
    remove: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M6 1h4l1 2h3v1.5H2V3h3l1-2Zm-2 5h8l-.55 8H4.55L4 6Zm2 2v4.5h1.5V8H6Zm2.5 0v4.5H10V8H8.5Z"/></svg>'
};

actionIcons.view = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3C4.3 3 1.5 5.4.5 8c1 2.6 3.8 5 7.5 5s6.5-2.4 7.5-5C14.5 5.4 11.7 3 8 3Zm0 8.2A3.2 3.2 0 1 1 8 4.8a3.2 3.2 0 0 1 0 6.4Zm0-1.5A1.7 1.7 0 1 0 8 6.3a1.7 1.7 0 0 0 0 3.4Z"/></svg>';

function renderKhuSelect() {
    dayKhuSelect.innerHTML = state.khuList.map(khu => `
        <option value="${khu.Id}">${khu.TenKhu}</option>
    `).join('');

    if (state.selectedKhuId) {
        dayKhuSelect.value = String(state.selectedKhuId);
    } else if (state.khuList.length) {
        dayKhuSelect.value = String(state.khuList[0].Id);
    }
}

function renderKhuTable() {
    khuTableBody.innerHTML = state.khuList.map(item => `
        <tr>
            <td>#${item.Id}</td>
            <td>${item.TenKhu || ''}</td>
            <td>${item.DiaChi || ''}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary btn-edit-khu" data-id="${item.Id}">Sửa</button>
                <button class="btn btn-sm btn-outline-danger btn-delete-khu" data-id="${item.Id}">Xóa</button>
            </td>
        </tr>
    `).join('');
}

function renderRoomFilterOptions() {
    const khuFilter = document.getElementById('treeKhuFilter');
    const dayFilter = document.getElementById('treeDayFilter');
    if (!khuFilter || !dayFilter) return;
    const selectedKhu = khuFilter.value;
    const selectedDay = dayFilter.value;
    khuFilter.innerHTML = '<option value="">Tất cả khu</option>' + state.khuList.map(khu =>
        `<option value="${khu.Id}">${khu.TenKhu}</option>`).join('');
    khuFilter.value = state.khuList.some(khu => String(khu.Id) === selectedKhu) ? selectedKhu : '';
    const visibleDays = state.dayList.filter(day => !khuFilter.value || String(day.KhuId) === khuFilter.value);
    dayFilter.innerHTML = '<option value="">Tất cả dãy</option>' + visibleDays.map(day => {
        const khu = state.khuList.find(item => Number(item.Id) === Number(day.KhuId));
        return `<option value="${day.Id}">${khuFilter.value ? '' : (khu?.TenKhu || '') + ' · '}${day.TenDay}</option>`;
    }).join('');
    dayFilter.value = visibleDays.some(day => String(day.Id) === selectedDay) ? selectedDay : '';
}

function renderKhuTree() {
    const keyword = (document.getElementById('treeSearch')?.value || '').trim().toLocaleLowerCase('vi');
    const khuFilter = appliedRoomFilters.khu;
    const dayFilter = appliedRoomFilters.day;
    const statusFilter = appliedRoomFilters.status;
    const ownerFilter = appliedRoomFilters.owner;
    const hasRoomCriteria = Boolean(keyword || dayFilter || statusFilter);
    const roomMatches = (room) => {
        const matchesName = !keyword || String(room.SoPhong || '').trim().toLocaleLowerCase('vi').includes(keyword);
        const matchesDay = !dayFilter || String(room.DayId) === dayFilter;
        const matchesStatus = !statusFilter || room.TrangThai === statusFilter;
        return matchesName && matchesDay && matchesStatus;
    };
    const filteredKhuList = state.khuList.filter((khu) => {
        const days = state.dayList.filter((day) => Number(day.KhuId) === Number(khu.Id));
        const rooms = state.roomList.filter((room) => days.some((day) => Number(day.Id) === Number(room.DayId)));
        const matchesKhu = !khuFilter || String(khu.Id) === khuFilter;
        const matchesRooms = !hasRoomCriteria || rooms.some(roomMatches);
        return matchesKhu && matchesRooms && (!ownerFilter || String(khu.TaiKhoanId) === ownerFilter);
    });

    let previousOwnerId = null;
    khuTableBody.innerHTML = filteredKhuList.map((khu) => {
        const ownerId = Number(khu.TaiKhoanId || 0);
        const ownerExpanded = state.expandedOwnerIds.has(ownerId);
        const ownerRow = isAdmin && previousOwnerId !== ownerId ? `<tr class="tree-owner-row table-primary" data-owner-id="${ownerId}"><td colspan="3"><button type="button" class="tree-owner-button"><i class="bi bi-caret-${ownerExpanded ? 'down' : 'right'}-fill"></i> Chủ trọ: ${khu.ChuTro || 'Chưa gán chủ trọ'}</button></td></tr>` : '';
        previousOwnerId = ownerId;
        if (isAdmin && !ownerExpanded) return ownerRow;
        const allDays = state.dayList.filter((day) => Number(day.KhuId) === Number(khu.Id));
        const days = hasRoomCriteria
            ? allDays.filter(day => (!dayFilter || String(day.Id) === dayFilter) && state.roomList.some(room => Number(room.DayId) === Number(day.Id) && roomMatches(room)))
            : allDays;
        const expanded = state.expandedKhuIds.has(Number(khu.Id));
        const children = expanded ? days.map((day) => {
            const rooms = state.roomList.filter((room) => Number(room.DayId) === Number(day.Id) && roomMatches(room));
            const dayExpanded = state.expandedDayIds.has(Number(day.Id));
            const roomRows = dayExpanded ? rooms.map((room) => `
                <tr class="tree-room-row" data-room-id="${room.Id}"><td></td><td><span class="tree-branch">&nbsp;&nbsp;└─</span> <i class="bi bi-door-open"></i> ${room.SoPhong}</td>
                <td>${Number(room.DienTich || 0)} m² · ${formatMoney(room.GiaThue)} · <span class="badge bg-secondary">${room.TrangThai === 'DangThue' ? 'Đang thuê' : room.TrangThai === 'BaoTri' ? 'Bảo trì' : 'Trống'}</span></td>
                <td><div class="tree-actions"><button class="btn btn-sm btn-outline-info btn-view-room" data-id="${room.Id}" title="Xem thành viên và dịch vụ" aria-label="Xem phòng">${actionIcons.view}</button><button class="btn btn-sm btn-outline-primary btn-edit-room" data-id="${room.Id}" title="Sửa phòng" aria-label="Sửa phòng">${actionIcons.edit}</button><button class="btn btn-sm btn-outline-danger btn-delete-room" data-id="${room.Id}" title="Xóa phòng" aria-label="Xóa phòng">${actionIcons.remove}</button></div></td></tr>`).join('') : '';
            return `
            <tr class="tree-day-row" data-day-id="${day.Id}">
                <td></td>
                <td><span class="tree-branch">└─</span> <i class="bi bi-caret-${dayExpanded ? 'down' : 'right'}-fill"></i> <i class="bi bi-building"></i> ${day.TenDay || ''} <span class="badge bg-light text-dark">${rooms.length} phòng</span></td>
                <td>${day.MoTa || '<span class="text-muted">Chưa có mô tả</span>'}</td>
                <td><div class="tree-actions">
                    <button class="btn btn-sm btn-outline-success btn-add-room" data-day-id="${day.Id}" title="Thêm phòng" aria-label="Thêm phòng">${actionIcons.add}</button>
                    <button class="btn btn-sm btn-outline-primary btn-edit-day" data-id="${day.Id}" title="Sửa dãy" aria-label="Sửa dãy">${actionIcons.edit}</button>
                    <button class="btn btn-sm btn-outline-danger btn-delete-day" data-id="${day.Id}" title="Xóa dãy" aria-label="Xóa dãy">${actionIcons.remove}</button>
                </div></td>
            </tr>${roomRows}`;
        }).join('') : '';
        const empty = expanded && !days.length
            ? '<tr class="tree-day-row"><td></td><td colspan="3" class="text-muted ps-4">Khu này chưa có dãy.</td></tr>' : '';

        const row = `
            <tr class="tree-khu-row" data-khu-id="${khu.Id}">
                <td><button class="btn btn-sm btn-link p-0 btn-toggle-khu" data-id="${khu.Id}" aria-expanded="${expanded}">
                    <i class="bi bi-caret-${expanded ? 'down' : 'right'}-fill"></i> #${khu.Id}
                </button></td>
                <td><strong>${khu.TenKhu || ''}</strong> <span class="badge bg-light text-dark ms-2">${days.length} dãy</span></td>
                <td>${khu.DiaChi || ''}</td>
                <td><div class="tree-actions">
                    <button class="btn btn-sm btn-outline-success btn-add-day" data-khu-id="${khu.Id}" title="Thêm dãy" aria-label="Thêm dãy">${actionIcons.add}</button>
                    <button class="btn btn-sm btn-outline-primary btn-edit-khu" data-id="${khu.Id}" title="Sửa khu" aria-label="Sửa khu">${actionIcons.edit}</button>
                    <button class="btn btn-sm btn-outline-danger btn-delete-khu" data-id="${khu.Id}" title="Xóa khu" aria-label="Xóa khu">${actionIcons.remove}</button>
                </div></td>
            </tr>${children}${empty}`;
        return ownerRow + row;
    }).join('');
    if (!filteredKhuList.length) {
        khuTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Không có phòng phù hợp chính xác với bộ lọc.</td></tr>';
    }
}

function renderKhuStats() {
    let host=document.getElementById('rentalStats');
    if(!host){host=document.createElement('section');host.id='rentalStats';host.className='stats-grid mb-3';document.querySelector('.khu-tree-layout')?.before(host);}
    const rooms=state.roomList, ownerCount=new Set(state.khuList.map(x=>x.TaiKhoanId).filter(Boolean)).size;
    const cards=[['Khu trọ',state.khuList.length,'primary'],['Tổng phòng',rooms.length,'primary'],['Đang cho thuê',rooms.filter(x=>x.TrangThai==='DangThue').length,'success'],['Phòng trống',rooms.filter(x=>x.TrangThai==='Trong').length,'warning'],['Phòng bảo trì',rooms.filter(x=>x.TrangThai==='BaoTri').length,'danger']];
    if(isAdmin)cards.splice(1,0,['Chủ trọ',ownerCount,'primary']);
    host.innerHTML=cards.map(x=>`<div class="stat-card ${x[2]}"><div><span>${x[0]}</span><strong>${x[1]}</strong></div></div>`).join('');
}

function renderDayTable() {
    // The old standalone day table is intentionally removed.  Its data is
    // rendered inside the Khu → Dãy → Phòng tree instead.
    if (!dayTableBody) return;
    const filtered = state.selectedKhuId
        ? state.dayList.filter(item => Number(item.KhuId) === Number(state.selectedKhuId))
        : state.dayList;

    dayTableBody.innerHTML = filtered.map(item => `
        <tr>
            <td>#${item.Id}</td>
            <td>${item.TenDay || ''}</td>
            <td>${state.khuList.find(k => Number(k.Id) === Number(item.KhuId))?.TenKhu || item.KhuId}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary btn-edit-day" data-id="${item.Id}">Sửa</button>
                <button class="btn btn-sm btn-outline-danger btn-delete-day" data-id="${item.Id}">Xóa</button>
            </td>
        </tr>
    `).join('');
}

async function loadKhu() {
    const result = await window.app.api('/api/khu.php');
    state.khuList = result.data || [];

    if (isAdmin) {
        state.khuList.forEach((khu) => state.expandedOwnerIds.add(Number(khu.TaiKhoanId || 0)));
    }

    if (!state.selectedKhuId && state.khuList.length) {
        state.selectedKhuId = null;
    }

    renderKhuSelect();
    renderKhuTree(); renderKhuStats();
    // Render khu immediately. A transient error in a child endpoint must not
    // make the entire admin screen appear empty.
    await Promise.all([loadDay(), loadRooms()]);
    renderRoomFilterOptions();
    // Open the complete rental hierarchy by default: owner → khu → dãy → phòng.
    state.khuList.forEach((khu) => state.expandedKhuIds.add(Number(khu.Id)));
    state.dayList.forEach((day) => state.expandedDayIds.add(Number(day.Id)));
    renderKhuTree(); renderKhuStats();
}

async function loadOwners() {
    if (!isAdmin) return;
    const result = await window.app.api('/api/khu.php?action=chutro');
    state.ownerList = result.data || [];
    document.getElementById('TaiKhoanId').innerHTML = state.ownerList.map((owner) =>
        `<option value="${owner.Id}">${owner.HoTen} (${owner.TenDangNhap})</option>`).join('');
    const wrap=document.getElementById('treeOwnerFilterWrap'), select=document.getElementById('treeOwnerFilter');if(wrap&&select){wrap.hidden=false;select.innerHTML='<option value="">Tất cả chủ trọ</option>'+state.ownerList.map(owner=>`<option value="${owner.Id}">${owner.HoTen}</option>`).join('');}
}

async function loadDay() {
    try { const result = await window.app.api('/api/day.php'); state.dayList = result.data || []; }
    catch (error) { console.error('Không thể tải dãy:', error); state.dayList = []; }
    renderDayTable();
}

async function loadRooms() {
    try { const result = await window.app.api('/api/phong.php'); state.roomList = result.data || []; }
    catch (error) { console.error('Không thể tải phòng:', error); state.roomList = []; }
}

function openKhuModal(item = null) {
    khuForm.reset();
    document.getElementById('ownerField').hidden = !isAdmin;
    document.getElementById('khuId').value = item ? item.Id : '';
    document.getElementById('TenKhu').value = item ? (item.TenKhu || '') : '';
    document.getElementById('DiaChi').value = item ? (item.DiaChi || '') : '';
    document.getElementById('MoTaKhu').value = item ? (item.MoTa || '') : '';
    if (isAdmin && item?.TaiKhoanId) document.getElementById('TaiKhoanId').value = String(item.TaiKhoanId);
    document.getElementById('khuModalTitle').textContent = item ? 'Sửa khu' : 'Thêm khu';
    khuModal.show();
}

function openDayModal(item = null, fixedKhuId = null) {
    dayForm.reset();
    document.getElementById('dayId').value = item ? item.Id : '';
    const targetKhuId = item ? item.KhuId : (fixedKhuId ?? state.selectedKhuId ?? state.khuList[0]?.Id ?? '');
    if (targetKhuId) {
        document.getElementById('DayKhuId').value = String(targetKhuId);
    }
    dayKhuSelect.closest('.col-12').hidden = Boolean(fixedKhuId) && !item;
    document.getElementById('TenDay').value = item ? (item.TenDay || '') : '';
    document.getElementById('MoTaDay').value = item ? (item.MoTa || '') : '';
    document.getElementById('dayModalTitle').textContent = item ? 'Sửa dãy' : 'Thêm dãy';
    dayModal.show();
}

function openRoomModal(item = null, fixedDayId = null) {
    roomForm.reset();
    const select = document.getElementById('RoomDayId');
    select.innerHTML = state.dayList.map((day) => `<option value="${day.Id}">${day.TenDay}</option>`).join('');
    const dayId = item ? item.DayId : fixedDayId;
    if (dayId) select.value = String(dayId);
    document.getElementById('roomDayField').hidden = Boolean(fixedDayId) && !item;
    document.getElementById('roomId').value = item ? item.Id : '';
    document.getElementById('RoomSoPhong').value = item?.SoPhong || '';
    document.getElementById('RoomDienTich').value = item?.DienTich || '';
    document.getElementById('RoomGiaThue').value = item?.GiaThue || 0;
    document.getElementById('RoomTrangThai').value = item?.TrangThai || 'Trong';
    document.getElementById('RoomMoTa').value = item?.MoTa || '';
    document.getElementById('roomModalTitle').textContent = item ? 'Sửa phòng' : 'Thêm phòng';
    roomModal.show();
}

function memberRow() {
    return '<tr><td><input class="form-control form-control-sm cm-name" required></td><td><input class="form-control form-control-sm cm-cccd" required></td><td><input type="date" class="form-control form-control-sm cm-birth"></td><td><input class="form-control form-control-sm cm-phone"></td><td><input class="form-control form-control-sm cm-relation" value="Thành viên"></td><td><button type="button" class="btn btn-sm btn-outline-danger remove-contract-member"><i class="bi bi-trash"></i></button></td></tr>';
}

function rentPayload() {
    const get = (id) => document.getElementById(id).value;
    const numberMoney=(value)=>Number(String(value||'').replace(/,/g,''))||0;
    return { PhongId:Number(get('RentPhongId')), GiaThue:numberMoney(get('RentGiaThue')), TienCoc:numberMoney(get('RentTienCoc')), NgayBatDau:get('RentNgayBatDau'), NgayKetThuc:get('RentNgayKetThuc'), BenAHoTen:get('RentBenAHoTen'), BenACCCD:get('RentBenACCCD'), BenASoDienThoai:get('RentBenASoDienThoai'), BenANgaySinh:get('RentBenANgaySinh'), BenADiaChi:get('RentBenADiaChi'), BenBHoTen:get('RentBenBHoTen'), BenBCCCD:get('RentBenBCCCD'), BenBSoDienThoai:get('RentBenBSoDienThoai'), BenBNgaySinh:get('RentBenBNgaySinh'), BenBEmail:get('RentBenBEmail'), BenBDiaChi:get('RentBenBDiaChi'), ChiSoDienDau:Number(get('RentChiSoDienDau')||0), DonGiaDien:numberMoney(get('RentDonGiaDien')), ChiSoNuocDau:Number(get('RentChiSoNuocDau')||0), DonGiaNuoc:numberMoney(get('RentDonGiaNuoc')), DieuKhoan:get('RentDieuKhoan'), ThanhVien:[...document.querySelectorAll('#contractMembers tr')].map(row=>({HoTen:row.querySelector('.cm-name').value,CCCD:row.querySelector('.cm-cccd').value,NgaySinh:row.querySelector('.cm-birth').value,SoDienThoai:row.querySelector('.cm-phone').value,QuanHe:row.querySelector('.cm-relation').value})) };
}

document.addEventListener('blur', (event) => {
    if (!event.target.matches('.money-input')) return;
    const value=Number(String(event.target.value||'').replace(/,/g,''))||0;
    event.target.value=value.toLocaleString('en-US');
}, true);

async function openRentRoomModal() {
    const result=await window.app.api('/api/hopdong.php?action=phongTrong'); const rooms=result.data||[];
    if(!rooms.length){ alert('Hiện không có phòng trống để cho thuê.'); return; }
    const select=document.getElementById('RentPhongId');
    select.innerHTML=rooms.map(r=>`<option value="${r.Id}" data-price="${r.GiaThue}">${r.TenKhu} · ${r.TenDay} · Phòng ${r.SoPhong}</option>`).join('');
    document.getElementById('availableRooms').innerHTML=rooms.map(r=>`<tr><td><strong>${r.TenKhu}</strong><br><span class="text-muted">${r.TenDay} · Phòng ${r.SoPhong}</span></td><td>${Number(r.DienTich||0)} m²</td><td>${formatMoney(r.GiaThue)}</td><td><button type="button" class="btn btn-sm btn-success btn-create-contract" data-room="${r.Id}">Tạo hợp đồng mới</button></td></tr>`).join('');
    const setPrice=()=>document.getElementById('RentGiaThue').value=Number(select.selectedOptions[0]?.dataset.price||0).toLocaleString('en-US'); setPrice(); select.onchange=setPrice;
    document.getElementById('contractMembers').innerHTML=''; const today=new Date(); document.getElementById('RentNgayBatDau').value=today.toISOString().slice(0,10); today.setFullYear(today.getFullYear()+1); document.getElementById('RentNgayKetThuc').value=today.toISOString().slice(0,10);
    const fields=document.getElementById('rentContractFields');
    if (!document.getElementById('partyColumns')) {
        const headings=fields.querySelectorAll('h6');
        const aTitle=headings[0], bTitle=headings[1], aRow=aTitle.nextElementSibling, bRow=bTitle.nextElementSibling;
        const columns=document.createElement('div'); columns.id='partyColumns'; columns.className='row g-3 mb-3';
        const aCol=document.createElement('div'); aCol.className='col-lg-6'; aCol.innerHTML='<div class="contract-party-card"></div>'; aCol.firstElementChild.append(aTitle,aRow);
        const bCol=document.createElement('div'); bCol.className='col-lg-6'; bCol.innerHTML='<div class="contract-party-card"></div>'; bCol.firstElementChild.append(bTitle,bRow);
        fields.insertBefore(columns, fields.querySelector('hr')); columns.append(aCol,bCol);
    }
    document.getElementById('rentRoomPicker').hidden=false; document.getElementById('rentContractFields').hidden=true; document.getElementById('previewContract').hidden=true; document.getElementById('confirmContract').hidden=true;
    rentRoomModal.show();
}

function formatDate(value) {
    if (!value) return '—';
    const match = String(value).match(/(\d{4})-(\d{2})-(\d{2})/);
    return match ? `${match[3]}/${match[2]}/${match[1]}` : value;
}

function formatMoney(value) {
    return Number(value || 0).toLocaleString('en-US') + ' ₫';
}

async function openRoomDetail(roomId) {
    const result = await window.app.api('/api/phong.php?id=' + roomId + '&action=chitiet');
    const data = result.data;
    state.lastRoomDetail = data;
    const invoice = data.hoaDon;
    const meter = data.chiSo;
    const stayText=s=>s==='DaDangKyUBND'?'Đã đăng ký với UBND Phường/Xã':(s==='DangKhaiBaoUBND'?'Đang khai báo với UBND Phường/Xã':'Chưa đăng ký với UBND Phường/Xã'); const stayClass=s=>s==='DaDangKyUBND'?'bg-success':(s==='DangKhaiBaoUBND'?'bg-warning text-dark':'bg-secondary');
    const members = (data.thanhVien || []).map((member) => { const latest=(member.TamTru||[])[0]||{}; const status=member.TrangThaiTamTru==='DaDangKyUBND'||member.TrangThaiTamTru==='DangKhaiBaoUBND'||member.TrangThaiTamTru==='ChuaKhaiBaoUBND'?member.TrangThaiTamTru:(Number(latest.TrangThai)===1?'DaDangKyUBND':(latest.TrangThaiXuLy==='ChoChuTroXacNhan'?'DangKhaiBaoUBND':'ChuaKhaiBaoUBND')); const role=member.Loai==='LuuTru'?'Lưu trú':(member.Loai==='ThanhVienPhong'?(member.VaiTro||'Thành viên phòng'):(member.Loai||'Thành viên')); return `<tr><td class="member-role-col">${role}</td><td>${member.HoTen || ''}</td><td>${member.CCCD || ''}</td><td>${member.SoDienThoai || ''}</td><td><span class="badge member-stay-status ${stayClass(status)}">${stayText(status)}</span> ${member.HopDongId ? `<button class="btn btn-sm btn-outline-primary btn-view-contract" data-id="${member.HopDongId}">Xem hợp đồng</button>` : ''} <button class="btn btn-sm btn-outline-success btn-register-stay" data-room="${data.phong.Id}" data-name="${member.HoTen||''}" data-cccd="${member.CCCD||''}" data-status="${status}">Khai báo</button></td></tr>`; }).join('') || '<tr><td colspan="5" class="text-muted">Chưa có thành viên.</td></tr>';
    const stays = (data.tamTru || []).map((item) => {const s=Number(item.TrangThai)===1?'DaDangKyUBND':(item.TrangThaiXuLy==='ChoChuTroXacNhan'?'DangKhaiBaoUBND':'ChuaKhaiBaoUBND');return `<tr><td>${item.Loai || 'Tạm trú'}</td><td>${item.HoTen}</td><td>${item.NgayBatDau} → ${item.NgayKetThuc}</td><td><span class="badge ${stayClass(s)}">${stayText(s)}</span></td></tr>`;}).join('') || '<tr><td colspan="4" class="text-muted">Không có khai báo tạm trú/lưu trú.</td></tr>';
    document.getElementById('roomDetailTitle').textContent = `Phòng ${data.phong.SoPhong} · ${data.phong.TenDay}`;
    document.getElementById('roomDetailContent').innerHTML = `
        <div class="row g-3 mb-3"><div class="col-md-4"><div class="border rounded p-3 room-summary-card room-contract-card"><strong><i class="bi bi-file-earmark-text"></i> Hợp đồng</strong><br>${data.hopDong ? `${data.hopDong.HoTen} · ${data.hopDong.NgayBatDau} → ${data.hopDong.NgayKetThuc}` : 'Chưa có hợp đồng hiệu lực'}</div></div>
        <div class="col-md-4"><div class="border rounded p-3 room-summary-card room-invoice-card"><strong><i class="bi bi-receipt"></i> Hóa đơn gần nhất</strong><br>${invoice ? `${invoice.Thang}/${invoice.Nam}: ${formatMoney(invoice.DaTra)} / ${formatMoney(invoice.TongTien)} · ${invoice.TrangThai === 'DaThanhToan' ? 'Đã thanh toán' : 'Chưa thanh toán đủ'}` : 'Chưa có hóa đơn'}</div></div>
        <div class="col-md-4"><div class="border rounded p-3 room-summary-card room-meter-card"><strong><i class="bi bi-lightning-charge"></i> Điện / nước gần nhất</strong><br>${meter ? `Điện: ${meter.ChiSoDienCuoi - meter.ChiSoDienDau} kWh · Nước: ${meter.ChiSoNuocCuoi - meter.ChiSoNuocDau} m³` : 'Chưa có chỉ số'}</div></div></div>
        <div class="d-flex justify-content-between align-items-center"><h6>Thành viên phòng</h6>${data.hopDong?`<button class="btn btn-sm btn-primary btn-add-room-member" data-contract="${data.hopDong.Id}" data-room="${data.phong.Id}"><i class="bi bi-person-plus"></i> Thêm thành viên</button>`:''}</div><div class="table-responsive mb-4"><table class="table table-sm room-members-table"><thead><tr><th class="member-role-col">Vai trò</th><th>Họ tên</th><th>CCCD</th><th>SĐT</th><th></th></tr></thead><tbody>${members}</tbody></table></div>
        <h6>Danh sách lưu trú</h6><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Loại</th><th>Họ tên</th><th>Thời gian</th><th>Trạng thái</th></tr></thead><tbody>${stays}</tbody></table></div>`;
    const cards = document.querySelectorAll('#roomDetailContent .row > div > div');
    if (cards[0]) cards[0].classList.add('detail-card');
    if (cards[1]) {
        cards[1].classList.add('detail-card');
        cards[1].innerHTML = `<strong>Hóa đơn gần nhất</strong><br>${invoice ? `Ngày tạo: ${formatDate(invoice.NgayTao)}<br>${formatMoney(invoice.DaTra)} / ${formatMoney(invoice.TongTien)} · ${invoice.TrangThai === 'DaThanhToan' ? 'Đã thanh toán' : 'Chưa thanh toán đủ'}` : 'Chưa có hóa đơn'}<div class="small text-primary mt-2">Bấm để xem lịch sử</div>`;
        cards[1].classList.add('btn-history-invoice');
    }
    if (cards[2]) {
        cards[2].classList.add('detail-card');
        cards[2].innerHTML = `<strong>Điện / nước gần nhất</strong><br>${meter ? `Ngày ghi: ${formatDate(meter.NgayGhi)}<br>Điện: ${meter.ChiSoDienCuoi - meter.ChiSoDienDau} kWh · Nước: ${meter.ChiSoNuocCuoi - meter.ChiSoNuocDau} m³` : 'Chưa có chỉ số'}<div class="small text-primary mt-2">Bấm để xem lịch sử</div>`;
        cards[2].classList.add('btn-history-meter');
    }
    roomDetailModal.show();
}

khuForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = {
        TenKhu: document.getElementById('TenKhu').value,
        DiaChi: document.getElementById('DiaChi').value,
        MoTa: document.getElementById('MoTaKhu').value,
        ...(isAdmin ? { TaiKhoanId: Number(document.getElementById('TaiKhoanId').value) } : {})
    };

    const id = document.getElementById('khuId').value;
    const url = id ? '/api/khu.php?id=' + id : '/api/khu.php';
    const method = id ? 'PUT' : 'POST';
    await window.app.api(url, { method, body: JSON.stringify(payload) });
    khuModal.hide();
    await loadKhu();
});

dayForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = {
        KhuId: Number(document.getElementById('DayKhuId').value),
        TenDay: document.getElementById('TenDay').value,
        MoTa: document.getElementById('MoTaDay').value
    };

    const id = document.getElementById('dayId').value;
    const url = id ? '/api/day.php?id=' + id : '/api/day.php';
    const method = id ? 'PUT' : 'POST';
    await window.app.api(url, { method, body: JSON.stringify(payload) });
    dayModal.hide();
    state.selectedKhuId = payload.KhuId;
    await loadKhu();
});

roomForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const id = document.getElementById('roomId').value;
    const payload = { DayId: Number(document.getElementById('RoomDayId').value), SoPhong: document.getElementById('RoomSoPhong').value,
        DienTich: Number(document.getElementById('RoomDienTich').value || 0), GiaThue: Number(document.getElementById('RoomGiaThue').value || 0),
        TrangThai: document.getElementById('RoomTrangThai').value, MoTa: document.getElementById('RoomMoTa').value };
    await window.app.api(id ? '/api/phong.php?id=' + id : '/api/phong.php', { method: id ? 'PUT' : 'POST', body: JSON.stringify(payload) });
    roomModal.hide(); await loadKhu();
});

document.getElementById('openAddKhu').addEventListener('click', () => openKhuModal());
document.getElementById('openRentRoom').addEventListener('click', openRentRoomModal);
document.getElementById('openAddDay')?.addEventListener('click', () => openDayModal());
document.getElementById('treeSearch').addEventListener('input', renderKhuTree);
document.getElementById('treeKhuFilter').addEventListener('change', () => {
    document.getElementById('treeDayFilter').value = '';
    renderRoomFilterOptions();
});
const roomFilterModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('roomFilterModal'));
document.getElementById('openRoomFilters').addEventListener('click', () => roomFilterModal.show());
document.getElementById('applyRoomFilters').addEventListener('click', () => {
    appliedRoomFilters.khu = document.getElementById('treeKhuFilter').value;
    appliedRoomFilters.day = document.getElementById('treeDayFilter').value;
    appliedRoomFilters.status = document.getElementById('treeStatusFilter').value;
    appliedRoomFilters.owner = document.getElementById('treeOwnerFilter')?.value || '';
    renderKhuTree();
    roomFilterModal.hide();
});
document.getElementById('clearRoomFilters')?.addEventListener('click', () => {
    document.getElementById('treeSearch').value = '';
    document.getElementById('treeKhuFilter').value = '';
    document.getElementById('treeDayFilter').value = '';
    document.getElementById('treeStatusFilter').value = '';
    const ownerFilter = document.getElementById('treeOwnerFilter');
    if (ownerFilter) ownerFilter.value = '';
    Object.assign(appliedRoomFilters, { khu: '', day: '', status: '', owner: '' });
    renderRoomFilterOptions();
    renderKhuTree();
    roomFilterModal.hide();
    document.getElementById('treeSearch').focus();
});

document.addEventListener('click', async (event) => {
    const addRoomMember=event.target.closest('.btn-add-room-member');
    if(addRoomMember){document.getElementById('memberForm').reset();document.getElementById('MemberHopDongId').value=addRoomMember.dataset.contract;document.getElementById('MemberRoomId').value=addRoomMember.dataset.room;document.getElementById('MemberQuanHe').value='Thành viên';memberModal.show();return;}
    const createContract = event.target.closest('.btn-create-contract');
    if (createContract) {
        document.getElementById('RentPhongId').value=createContract.dataset.room;
        document.getElementById('RentGiaThue').value=Number(document.getElementById('RentPhongId').selectedOptions[0]?.dataset.price||0).toLocaleString('en-US');
        document.getElementById('rentRoomPicker').hidden=true; document.getElementById('rentContractFields').hidden=false; document.getElementById('previewContract').hidden=false; document.getElementById('confirmContract').hidden=false;
        return;
    }
    if (event.target.closest('#addContractMember')) { document.getElementById('contractMembers').insertAdjacentHTML('beforeend', memberRow()); return; }
    const removeMember = event.target.closest('.remove-contract-member');
    if (removeMember) { removeMember.closest('tr').remove(); return; }
    const registerStay = event.target.closest('.btn-register-stay');
    if (registerStay) {
        const today=new Date().toISOString().slice(0,10);
        document.getElementById('stayForm').reset(); document.getElementById('StayPhongId').value=registerStay.dataset.room; document.getElementById('StayHoTen').value=registerStay.dataset.name; document.getElementById('StayCCCD').value=registerStay.dataset.cccd; document.getElementById('StayTrangThaiDangKy').value=registerStay.dataset.status||'DangKhaiBaoUBND';
        document.getElementById('stayPersonSummary').innerHTML=`<strong>${registerStay.dataset.name}</strong><br><span class="small">CCCD: ${registerStay.dataset.cccd}</span>`;
        document.getElementById('StayNgayBatDau').value=today; document.getElementById('StayNgayKetThuc').value=today;
        stayModal.show(); return;
    }
    const invoiceHistory = event.target.closest('.btn-history-invoice');
    if (invoiceHistory && state.lastRoomDetail) {
        const rows = (state.lastRoomDetail.hoaDonLichSu || []).map((item) => `<tr><td>${item.Thang}/${item.Nam}</td><td>${formatDate(item.NgayTao)}</td><td>${formatMoney(item.TongTien)}</td><td>${formatMoney(item.DaTra)}</td><td>${item.TrangThai === 'DaThanhToan' ? 'Đã thanh toán' : 'Chưa thanh toán đủ'}</td></tr>`).join('') || '<tr><td colspan="5">Chưa có hóa đơn.</td></tr>';
        document.getElementById('roomHistoryTitle').textContent = 'Lịch sử hóa đơn';
        document.getElementById('roomHistoryContent').innerHTML = `<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Kỳ</th><th>Ngày tạo</th><th>Tổng tiền</th><th>Đã trả</th><th>Trạng thái</th></tr></thead><tbody>${rows}</tbody></table></div>`;
        roomHistoryModal.show(); return;
    }

    const meterHistory = event.target.closest('.btn-history-meter');
    if (meterHistory && state.lastRoomDetail) {
        const rows = (state.lastRoomDetail.chiSoLichSu || []).map((item) => `<tr><td>${item.Thang}/${item.Nam}</td><td>${formatDate(item.NgayGhi)}</td><td>${item.ChiSoDienDau} → ${item.ChiSoDienCuoi}</td><td>${item.ChiSoNuocDau} → ${item.ChiSoNuocCuoi}</td><td>${formatMoney(item.DonGiaDien)} / ${formatMoney(item.DonGiaNuoc)}</td></tr>`).join('') || '<tr><td colspan="5">Chưa có chỉ số.</td></tr>';
        document.getElementById('roomHistoryTitle').textContent = 'Lịch sử điện nước';
        document.getElementById('roomHistoryContent').innerHTML = `<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Kỳ</th><th>Ngày ghi</th><th>Điện</th><th>Nước</th><th>Đơn giá</th></tr></thead><tbody>${rows}</tbody></table></div>`;
        roomHistoryModal.show(); return;
    }

    const roomRow = event.target.closest('.tree-room-row');
    if (roomRow && !event.target.closest('button')) {
        const room = state.roomList.find((item) => String(item.Id) === String(roomRow.dataset.roomId));
        if (room) openRoomDetail(room.Id);
        return;
    }

    const dayRow = event.target.closest('.tree-day-row');
    if (dayRow && !event.target.closest('button')) {
        const id = Number(dayRow.dataset.dayId);
        if (state.expandedDayIds.has(id)) state.expandedDayIds.delete(id);
        else state.expandedDayIds.add(id);
        renderKhuTree();
        return;
    }

    const ownerRow = event.target.closest('.tree-owner-row');
    if (ownerRow) {
        const id = Number(ownerRow.dataset.ownerId);
        if (state.expandedOwnerIds.has(id)) state.expandedOwnerIds.delete(id);
        else state.expandedOwnerIds.add(id);
        renderKhuTree();
        return;
    }
    const khuRow = event.target.closest('.tree-khu-row');
    if (khuRow && !event.target.closest('button')) {
        const id = Number(khuRow.dataset.khuId);
        state.selectedKhuId = id;
        if (state.expandedKhuIds.has(id)) state.expandedKhuIds.delete(id);
        else state.expandedKhuIds.add(id);
        renderKhuTree();
        return;
    }

    const toggleKhu = event.target.closest('.btn-toggle-khu');
    if (toggleKhu) {
        const id = Number(toggleKhu.dataset.id);
        state.selectedKhuId = id;
        if (state.expandedKhuIds.has(id)) state.expandedKhuIds.delete(id);
        else state.expandedKhuIds.add(id);
        renderKhuTree();
        return;
    }

    const addDay = event.target.closest('.btn-add-day');
    if (addDay) {
        state.selectedKhuId = Number(addDay.dataset.khuId);
        openDayModal(null, state.selectedKhuId);
        return;
    }

    const addRoom = event.target.closest('.btn-add-room');
    if (addRoom) {
        openRoomModal(null, Number(addRoom.dataset.dayId));
        return;
    }

    const viewRoom = event.target.closest('.btn-view-room');
    if (viewRoom) {
        await openRoomDetail(viewRoom.dataset.id);
        return;
    }

    const viewContract = event.target.closest('.btn-view-contract');
    if (viewContract) {
        window.open('/views/hopdong/xem.php?id=' + viewContract.dataset.id, '_blank', 'noopener');
        return;
        const result = await window.app.api('/api/hopdong.php?id=' + viewContract.dataset.id);
        const contract = result.data;
        alert(`Hợp đồng #${contract.Id}\nNgười thuê: ${contract.NguoiThue}\nThời hạn: ${contract.NgayBatDau} → ${contract.NgayKetThuc}\nGiá thuê: ${Number(contract.GiaThue).toLocaleString('vi-VN')} ₫`);
        return;
    }

    const editRoom = event.target.closest('.btn-edit-room');
    if (editRoom) {
        const room = state.roomList.find((item) => String(item.Id) === String(editRoom.dataset.id));
        if (room) openRoomModal(room);
        return;
    }

    const deleteRoom = event.target.closest('.btn-delete-room');
    if (deleteRoom) {
        if (!confirm('Xóa phòng này?')) return;
        await window.app.api('/api/phong.php?id=' + deleteRoom.dataset.id, { method: 'DELETE' });
        await loadKhu();
        return;
    }

    const editKhu = event.target.closest('.btn-edit-khu');
    if (editKhu) {
        const item = state.khuList.find(k => String(k.Id) === String(editKhu.dataset.id));
        if (item) openKhuModal(item);
    }

    const deleteKhu = event.target.closest('.btn-delete-khu');
    if (deleteKhu) {
        if (!confirm('Xóa khu này?')) return;
        await window.app.api('/api/khu.php?id=' + deleteKhu.dataset.id, { method: 'DELETE' });
        await loadKhu();
    }

    const editDay = event.target.closest('.btn-edit-day');
    if (editDay) {
        const item = state.dayList.find(d => String(d.Id) === String(editDay.dataset.id));
        if (item) openDayModal(item);
    }

    const deleteDay = event.target.closest('.btn-delete-day');
    if (deleteDay) {
        if (!confirm('Xóa dãy này?')) return;
        await window.app.api('/api/day.php?id=' + deleteDay.dataset.id, { method: 'DELETE' });
        await loadKhu();
    }
});

document.getElementById('rentRoomForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await window.app.api('/api/hopdong.php', { method: 'POST', body: JSON.stringify(rentPayload()) });
    rentRoomModal.hide();
    await loadKhu();
    window.open('/views/hopdong/xem.php?id=' + result.data.id, '_blank', 'noopener');
});

document.getElementById('stayForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const name=document.getElementById('StayHoTen').value; const cccd=document.getElementById('StayCCCD').value;
    const roomId=Number(document.getElementById('StayPhongId').value);
    await window.app.api('/api/tamtru.php',{method:'POST',body:JSON.stringify({PhongId:roomId,HoTen:name,CCCDKhach:cccd,QuanHe:document.getElementById('StayQuanHe').value,NgayBatDau:document.getElementById('StayNgayBatDau').value,NgayKetThuc:document.getElementById('StayNgayKetThuc').value,GhiChu:document.getElementById('StayGhiChu').value,TrangThaiDangKy:document.getElementById('StayTrangThaiDangKy').value,Loai:document.getElementById('StayLoai').value})});
    stayModal.hide(); await openRoomDetail(roomId);
});

document.getElementById('memberForm').addEventListener('submit', async (event) => {
    event.preventDefault(); const roomId=Number(document.getElementById('MemberRoomId').value);
    await window.app.api('/api/hopdong.php?action=thanhvien',{method:'POST',body:JSON.stringify({HopDongId:Number(document.getElementById('MemberHopDongId').value),HoTen:document.getElementById('MemberHoTen').value,CCCD:document.getElementById('MemberCCCD').value,NgaySinh:document.getElementById('MemberNgaySinh').value,SoDienThoai:document.getElementById('MemberSoDienThoai').value,QuanHe:document.getElementById('MemberQuanHe').value,Loai:document.getElementById('MemberLoai').value})});
    memberModal.hide(); await openRoomDetail(roomId);
});

document.getElementById('previewContract').addEventListener('click', async () => {
    const data = rentPayload();
    if (!data.PhongId || !data.BenBHoTen || !data.BenBCCCD) { alert('Hãy chọn phòng và nhập họ tên, CCCD của người thuê chính.'); return; }
    const result = await window.app.api('/api/hopdong.php?action=xemTruoc', { method: 'POST', body: JSON.stringify(data) });
    window.open('/views/hopdong/xemtruoc.php', '_blank', 'noopener'); return;
    const room = result.data.room; const tab = window.open('', '_blank'); if (!tab) return;
    tab.document.write(`<!doctype html><html lang="vi"><meta charset="utf-8"><title>Hợp đồng xem trước</title><style>body{font:16px/1.55 Arial;max-width:800px;margin:32px auto;color:#111}h1,h2{text-align:center}table{width:100%;border-collapse:collapse}td,th{border:1px solid #aaa;padding:8px}@media print{button{display:none}}</style><body><button onclick="window.print()">In / Lưu PDF</button><h2>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</h2><h1>HỢP ĐỒNG THUÊ PHÒNG TRỌ</h1><p><b>Phòng:</b> ${room.TenKhu} · ${room.TenDay} · ${room.SoPhong} (${room.DienTich || 0} m²)</p><h3>Bên A — cho thuê</h3><p>${data.BenAHoTen || 'Chủ trọ'} · CCCD: ${data.BenACCCD || '—'} · SĐT: ${data.BenASoDienThoai || '—'}<br>${data.BenADiaChi || room.DiaChiKhu || ''}</p><h3>Bên B — người thuê</h3><p>${data.BenBHoTen} · CCCD: ${data.BenBCCCD} · SĐT: ${data.BenBSoDienThoai || '—'}<br>${data.BenBDiaChi || ''}</p><p><b>Thời hạn:</b> ${formatDate(data.NgayBatDau)} đến ${formatDate(data.NgayKetThuc)}<br><b>Giá thuê:</b> ${Number(data.GiaThue).toLocaleString('vi-VN')} ₫/tháng · <b>Tiền cọc:</b> ${Number(data.TienCoc).toLocaleString('vi-VN')} ₫</p><h3>Thành viên cùng phòng</h3><table><thead><tr><th>Họ tên</th><th>CCCD</th><th>Quan hệ</th></tr></thead><tbody>${data.ThanhVien.map(m=>`<tr><td>${m.HoTen}</td><td>${m.CCCD}</td><td>${m.QuanHe}</td></tr>`).join('') || '<tr><td colspan="3">Không có</td></tr>'}</tbody></table><p><b>Điện nước đầu kỳ:</b> Điện ${data.ChiSoDienDau}, đơn giá ${Number(data.DonGiaDien).toLocaleString('vi-VN')} ₫; nước ${data.ChiSoNuocDau}, đơn giá ${Number(data.DonGiaNuoc).toLocaleString('vi-VN')} ₫.</p><p><b>Điều khoản:</b> ${data.DieuKhoan || 'Hai bên cam kết thực hiện đúng thỏa thuận.'}</p><br><table><tr><td style="text-align:center;border:0"><b>BÊN A</b><br><i>(Ký, ghi rõ họ tên)</i></td><td style="text-align:center;border:0"><b>BÊN B</b><br><i>(Ký, ghi rõ họ tên)</i></td></tr></table></body></html>`);
    tab.document.close();
});

// Keep the screen title and its two primary actions in the same compact panel
// header, leaving the table area for the actual rental-management data.
(() => {
    const pageHeader = document.querySelector('.main-panel > .topbar');
    const panelHead = document.querySelector('.khu-tree-layout .panel-head');
    const rentButton = document.getElementById('openRentRoom');
    const addKhuButton = document.getElementById('openAddKhu');
    if (!pageHeader || !panelHead || !rentButton || !addKhuButton) return;
    panelHead.classList.add('rental-management-head');
    panelHead.querySelector('h5').textContent = 'Quản lý phòng thuê';
    const actions = document.createElement('div');
    actions.className = 'rental-management-actions';
    actions.append(rentButton, addKhuButton);
    panelHead.appendChild(actions);
    pageHeader.remove();
})();

Promise.all([loadOwners(), loadKhu()]);
</script>
</body>
</html>
