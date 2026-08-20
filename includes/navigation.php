<?php
require_once __DIR__ . '/helpers.php';

function renderApplicationSidebar(array $user, string $active = ''): void
{
    $role = $user['VaiTro'] ?? '';
    $items = $role === 'admin'
        ? [['/views/taikhoan/index.php','bi-person-gear','Tài khoản'],['/views/chutro/index.php','bi-buildings','Quản lý chủ trọ'],['/views/auditlog/index.php','bi-clipboard-data','Nhật ký hoạt động'],['/views/minhchung/index.php','bi-file-earmark-image','Tệp minh chứng'],['/views/hethong/index.php','bi-database-gear','Dung lượng hệ thống']]
        : ($role === 'nguoithue'
            ? [['/views/thongbao/index.php','bi-bell','Thông báo'],['/views/hoso/index.php','bi-person-circle','Thông tin cá nhân'],['/views/hopdong/index.php','bi-file-earmark-text','Hợp đồng'],['/views/chisodiennuoc/index.php','bi-lightning-charge','Điện / Nước'],['/views/hoadon/index.php','bi-receipt','Hóa đơn'],['/views/suco/index.php','bi-tools','Báo cáo sự cố'],['/views/tamtru/index.php','bi-person-vcard','Khai báo lưu trú']]
            : [['/index.php','bi-speedometer2','Dashboard'],['/views/khu/index.php','bi-buildings','Quản lý phòng thuê'],['/views/nguoithue/index.php','bi-people','Người thuê'],['/views/hopdong/index.php','bi-file-earmark-text','Hợp đồng'],['/views/chisodiennuoc/index.php','bi-lightning-charge','Điện / Nước'],['/views/hoadon/index.php','bi-receipt','Hóa đơn'],['/views/tamtru/index.php','bi-person-vcard','Quản lý tạm trú / lưu trú'],['/views/baocao/index.php','bi-bar-chart','Báo cáo']]);
    if ($role === 'chutro') $items[] = ['/views/taikhoanthue/index.php','bi-person-lock','Tài khoản thuê trọ'];
    $items[] = ['/about.php','bi-info-circle','Về ứng dụng'];
    $avatar = getAvatarUrl($user); ?>
    <aside class="sidebar">
        <div class="brand"><div class="brand-badge"><img src="/assets/pics/logo.webp" class="brand-logo" alt="Logo Trọ Tốt"></div><div><div class="brand-title">Trọ Tốt</div><div class="brand-subtitle">Management System</div></div></div>
        <div class="user-mini sidebar-top"><img src="<?= htmlspecialchars($avatar) ?>" class="avatar-img" alt="Ảnh đại diện"><div class="user-info"><strong><?= htmlspecialchars($user['HoTen'] ?? 'Người dùng') ?></strong><small><?= htmlspecialchars($role) ?></small></div><a href="/logout.php" class="logout-btn" aria-label="Đăng xuất"><i class="bi bi-box-arrow-right"></i></a></div>
        <nav class="nav-menu"><?php foreach ($items as [$href,$icon,$label]): ?><a class="nav-item <?= $active === $href ? 'active' : '' ?>" href="<?= $href ?>"><i class="bi <?= $icon ?>"></i> <?= htmlspecialchars($label) ?></a><?php endforeach; ?></nav>
    </aside>
<?php }
