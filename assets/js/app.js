const app = {
    api(path, options = {}) {
        const defaultHeaders = { 'Content-Type': 'application/json' };
        const config = {
            ...options,
            cache: 'no-store',
            headers: { ...defaultHeaders, ...(options.headers || {}) }
        };

        return fetch(path, config).then(async (response) => {
            const text = await response.text();
            const payload = text ? JSON.parse(text) : {};
            if (!response.ok) {
                throw new Error(payload.message || 'Request failed');
            }
            return payload;
        });
    }
};

window.app = app;

// Các trang quản trị cũ vẫn dựng sidebar trực tiếp. Bổ sung tab tài khoản
// thuê trọ cho chủ trọ cho đến khi toàn bộ view dùng navigation.php.
document.addEventListener('DOMContentLoaded', () => {
    const role = document.querySelector('.sidebar-top .user-info small')?.textContent.trim();
    const menu = document.querySelector('.sidebar .nav-menu');
    if (role !== 'chutro' || !menu || menu.querySelector('a[href="/views/taikhoanthue/index.php"]')) return;
    const link = document.createElement('a');
    link.className = 'nav-item' + (location.pathname === '/views/taikhoanthue/index.php' ? ' active' : '');
    link.href = '/views/taikhoanthue/index.php';
    link.innerHTML = '<i class="bi bi-person-lock"></i> Tài khoản thuê trọ';
    const about = menu.querySelector('a[href="/about.php"]');
    if (about) menu.insertBefore(link, about); else menu.appendChild(link);
});

/* Management pages use their card header as the page header.  It removes the
   duplicated large title/description and keeps the relevant actions close to
   the data on both desktop and mobile. */
app.compactPageHeaders = () => {
    document.querySelectorAll('.main-panel > .topbar:not([data-compact-header])').forEach((topbar) => {
        const actions = topbar.querySelector('.top-actions');
        const title = topbar.querySelector('h1, h2, h3')?.textContent.trim();
        const panel = topbar.parentElement?.querySelector('.card-panel');
        // Dashboard headers have no management actions and must stay intact.
        if (!actions || !title || !panel) return;
        let panelHead = panel.querySelector(':scope > .panel-head');
        if (!panelHead) {
            panelHead = document.createElement('div');
            panelHead.className = 'panel-head';
            panel.prepend(panelHead);
        }
        panelHead.classList.add('compact-page-head');
        let heading = panelHead.querySelector('h5, h4, h3');
        if (!heading) { heading = document.createElement('h5'); panelHead.prepend(heading); }
        heading.textContent = title;
        panelHead.appendChild(actions);
        topbar.dataset.compactHeader = 'true';
        topbar.remove();
    });
};

app.applyRoleView = () => {
    const role = document.querySelector('.sidebar-top small')?.textContent.trim();
    if (role === 'admin') {
        const menu = document.querySelector('.sidebar .nav-menu');
        if (!menu) return;
        menu.querySelectorAll('a').forEach((link) => {
            const path = new URL(link.href, window.location.origin).pathname;
            if (!['/views/taikhoan/index.php', '/views/chutro/index.php', '/views/auditlog/index.php', '/views/minhchung/index.php', '/views/hethong/index.php', '/about.php'].includes(path)) link.remove();
        });
        if (!menu.querySelector('a[href="/views/taikhoan/index.php"]')) {
            const account = document.createElement('a');
            account.className = 'nav-item'; account.href = '/views/taikhoan/index.php';
            account.innerHTML = '<i class="bi bi-person-gear"></i> Tài khoản';
            menu.appendChild(account);
        }
        if (!menu.querySelector('a[href="/views/chutro/index.php"]')) {
            const owners = document.createElement('a');
            owners.className = 'nav-item'; owners.href = '/views/chutro/index.php';
            owners.innerHTML = '<i class="bi bi-buildings"></i> Quản lý chủ trọ';
            menu.appendChild(owners);
        }
        if (!menu.querySelector('a[href="/views/auditlog/index.php"]')) {
            const audit = document.createElement('a');
            audit.className = 'nav-item'; audit.href = '/views/auditlog/index.php';
            audit.innerHTML = '<i class="bi bi-clipboard-data"></i> Nhật ký hoạt động';
            menu.appendChild(audit);
        }
        if (!menu.querySelector('a[href="/views/minhchung/index.php"]')) {
            const proofs = document.createElement('a');
            proofs.className = 'nav-item'; proofs.href = '/views/minhchung/index.php';
            proofs.innerHTML = '<i class="bi bi-file-earmark-image"></i> Tệp minh chứng';
            menu.appendChild(proofs);
        }
        if (!menu.querySelector('a[href="/views/hethong/index.php"]')) {
            const system = document.createElement('a');
            system.className = 'nav-item'; system.href = '/views/hethong/index.php';
            system.innerHTML = '<i class="bi bi-database-gear"></i> Dung lượng hệ thống';
            menu.appendChild(system);
        }
        if (!menu.querySelector('a[href="/about.php"]')) {
            const about = document.createElement('a');
            about.className = 'nav-item'; about.href = '/about.php';
            about.innerHTML = '<i class="bi bi-info-circle"></i> Về ứng dụng';
            menu.appendChild(about);
        }
        // Legacy views may already contain "Về ứng dụng" in a different position.
        // Re-append all admin links in the canonical order on every admin page.
        ['/views/taikhoan/index.php', '/views/chutro/index.php', '/views/auditlog/index.php', '/views/minhchung/index.php', '/views/hethong/index.php', '/about.php'].forEach((href) => {
            const link = menu.querySelector(`a[href="${href}"]`);
            if (link) menu.appendChild(link);
        });
        menu.querySelectorAll('a').forEach((link) => link.classList.toggle('active', new URL(link.href, location.origin).pathname === location.pathname));
        return;
    }
    if (role !== 'nguoithue') return;
    const seenTenantLinks=new Set();document.querySelectorAll('.nav-menu .nav-item').forEach(link=>{const path=new URL(link.href,location.origin).pathname;if(seenTenantLinks.has(path))link.remove();else seenTenantLinks.add(path);});
    document.querySelectorAll('.main-panel #openAddHoaDon, .main-panel #openAddChiSo, .main-panel .top-actions a[href="/views/khu/index.php"]').forEach((button) => button.remove());
    if (window.location.pathname === '/views/tamtru/index.php') {
        const add = document.getElementById('openAddTamTru');
        if (add) add.innerHTML = '<i class="bi bi-person-plus"></i> Khai báo lưu trú';
        document.querySelectorAll('.main-panel h2, .main-panel h5').forEach((heading) => {
            if (/tạm trú/i.test(heading.textContent)) heading.textContent = heading.textContent.replace(/tạm trú/ig, 'lưu trú');
        });
        const type = document.getElementById('Loai');
        if (type) { type.value = 'LuuTru'; type.querySelectorAll('option').forEach(option => option.hidden = option.value !== 'LuuTru'); }
        document.getElementById('openAddTamTru')?.addEventListener('click', () => { const select=document.getElementById('Loai'); if(select) select.value='LuuTru'; });
    }
};

/* On a phone, a wide data table is condensed to its first two identifying
   columns.  The info control opens a readable full-row modal.  Desktop tables
   are untouched because the control and hidden cells are CSS-only on mobile. */
(() => {
    const infoIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path></svg>';
    const eligible = (table) => {
        if (!table || table.closest('#mobileRowDetailModal') || table.dataset.mobileDetailIgnore !== undefined) return false;
        const headers = table.querySelectorAll('thead th');
        return headers.length > 3 && table.closest('.main-panel, .modal-body');
    };
    const ensureModal = () => {
        let modal = document.getElementById('mobileRowDetailModal');
        if (modal) return modal;
        modal = document.createElement('div');
        modal.id = 'mobileRowDetailModal'; modal.className = 'modal fade'; modal.tabIndex = -1;
        modal.innerHTML = '<div class="modal-dialog modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Chi tiết</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div><div class="modal-body"></div></div></div>';
        document.body.appendChild(modal);
        return modal;
    };
    const prepareTable = (table) => {
        if (!eligible(table)) return;
        table.classList.add('mobile-compact-table');
        const headerRow = table.querySelector('thead tr');
        const originalHeads = headerRow ? [...headerRow.children] : [];
        const actionIndexes = new Set(originalHeads.map((cell, index) => {
            const label = cell.textContent.trim().toLowerCase();
            return /thao tác|hành động|chức năng/.test(label) || (!label && index === originalHeads.length - 1) ? index : -1;
        }).filter((index) => index >= 0));
        if (headerRow && !headerRow.querySelector('.mobile-detail-col')) {
            [...headerRow.children].forEach((cell, index) => {
                if (actionIndexes.has(index)) cell.classList.add('mobile-action-cell');
                else if (index >= 2) cell.classList.add('mobile-hide-cell');
            });
            const head = document.createElement('th'); head.className = 'mobile-detail-col'; head.scope = 'col'; head.setAttribute('aria-label', 'Xem chi tiết'); head.textContent = '';
            headerRow.appendChild(head);
        }
        const headers = [...table.querySelectorAll('thead th:not(.mobile-detail-col)')].map((cell) => cell.textContent.trim() || 'Thông tin');
        table.querySelectorAll('tbody tr').forEach((row) => {
            if (row.querySelector('.mobile-detail-col') || !row.querySelector('td')) return;
            const cells = [...row.children].filter((cell) => cell.tagName === 'TD');
            cells.forEach((cell, index) => {
                const isAction = actionIndexes.has(index) || Boolean(cell.querySelector('button, a.btn, [role="button"]'));
                if (isAction) cell.classList.add('mobile-action-cell');
                else if (index >= 2) cell.classList.add('mobile-hide-cell');
            });
            const detailCell = document.createElement('td'); detailCell.className = 'mobile-detail-col';
            const button = document.createElement('button'); button.type = 'button'; button.className = 'mobile-row-detail'; button.title = 'Xem chi tiết'; button.setAttribute('aria-label', 'Xem chi tiết dòng này'); button.innerHTML = infoIcon;
            button.addEventListener('click', () => {
                const modal = ensureModal();
                const values = [...row.children].filter((cell) => cell.tagName === 'TD' && !cell.classList.contains('mobile-detail-col'));
                const list = document.createElement('dl'); list.className = 'mobile-detail-list';
                values.forEach((cell, index) => {
                    const item = document.createElement('div'); const label = document.createElement('dt'); const value = document.createElement('dd');
                    label.textContent = headers[index] || `Thông tin ${index + 1}`;
                    const text = cell.innerText.replace(/\s+/g, ' ').trim(); value.textContent = text || '—';
                    item.append(label, value); list.appendChild(item);
                });
                modal.querySelector('.modal-title').textContent = table.dataset.mobileDetailTitle || 'Chi tiết thông tin';
                modal.querySelector('.modal-body').replaceChildren(list);
                if (window.bootstrap?.Modal) window.bootstrap.Modal.getOrCreateInstance(modal).show();
            });
            detailCell.appendChild(button); row.appendChild(detailCell);
        });
    };
    const prepareAll = (root = document) => root.querySelectorAll?.('table').forEach(prepareTable);
    document.addEventListener('DOMContentLoaded', () => {
        prepareAll();
        new MutationObserver((changes) => changes.forEach((change) => change.addedNodes.forEach((node) => {
            if (node.nodeType !== 1) return;
            if (node.matches?.('table')) prepareTable(node);
            if (node.closest?.('table')) prepareTable(node.closest('table'));
            prepareAll(node);
        }))).observe(document.body, { childList: true, subtree: true });
    });
})();

// Tenant list enhancement: show the shared temporary-residence status even
// when this view is restored from the SPA cache.
app.enhanceTenantPage = () => {
    if (window.location.pathname !== '/views/nguoithue/index.php') return;
    const tenantBody=document.getElementById('tenantRows');
    if (!tenantBody || tenantBody.dataset.enhancementStarted === '1') return;
    tenantBody.dataset.enhancementStarted='1';
    if(!document.getElementById('tenantEnhancementStyle')){const tenantStyle=document.createElement('style');tenantStyle.id='tenantEnhancementStyle';tenantStyle.textContent='.tenant-action{width:38px!important;height:38px!important;padding:0!important;vertical-align:middle!important;display:inline-flex!important;align-items:center!important;justify-content:center!important}.tenant-action svg{display:block!important;width:18px!important;height:18px!important;margin:0!important;transform:none!important;fill:currentColor!important}.tenant-action i{font-size:17px;line-height:1}@media(max-width:768px){#tenantRows th:nth-child(2),#tenantRows td:nth-child(2),.tenant-stay-head,.tenant-stay-cell{display:none!important}}';document.head.appendChild(tenantStyle);}
    const enrich = async () => {
        const body = document.getElementById('tenantRows');
        if (!body || !body.querySelector('tr td')) return false;
        const result = await window.app.api('/api/nguoithue.php');
        const records = new Map((result.data || []).map((item) => [String(item.CCCD || ''), item])); window.__tenantRecords=records;
        const summaryHost=document.getElementById('tenantSearch')?.parentElement?.parentElement;
        if(summaryHost && !document.getElementById('tenantSummary')){const all=[...records.values()];const registered=all.filter(item=>Number(item.DaDangKyTamTru)===1).length;const male=all.filter(item=>String(item.GioiTinh||'').toLowerCase()==='nam').length;const female=all.filter(item=>String(item.GioiTinh||'').toLowerCase()==='nữ'||String(item.GioiTinh||'').toLowerCase()==='nu').length;const cards=document.createElement('div');cards.id='tenantSummary';cards.className='stats-grid mb-3';cards.innerHTML=`<div class="stat-card primary"><div><span>Tổng người thuê</span><strong>${all.length}</strong></div></div><div class="stat-card success"><div><span>Đã ĐK tạm trú</span><strong>${registered}</strong></div></div><div class="stat-card warning"><div><span>Chưa đăng ký</span><strong>${all.length-registered}</strong></div></div><div class="stat-card primary"><div><span>Nam</span><strong>${male}</strong></div></div><div class="stat-card danger"><div><span>Nữ</span><strong>${female}</strong></div></div>`;summaryHost.insertBefore(cards,summaryHost.firstChild);}
        const header = body.closest('table')?.querySelector('thead tr');
        if (header) { header.children[1]?.classList.add('tenant-cccd-head'); if (!header.querySelector('.tenant-stay-head')) { const th=document.createElement('th'); th.className='tenant-stay-head'; th.textContent='ĐKTT'; header.insertBefore(th, header.lastElementChild); } }
        body.querySelectorAll('tr').forEach((row) => { const cells=row.children; if(cells.length<5) return; const item=records.get((cells[1]?.textContent || '').trim()); const action=row.querySelector('.view-tenant')?.closest('td'); if(!action)return; if(item?.DanhSachPhong?.length) cells[2].innerHTML=item.DanhSachPhong.map(room=>`<div>${room}</div>`).join(''); let td=row.querySelector('.tenant-stay-cell'); if(!td){const status=item?.TrangThaiDangKy||'ChuaKhaiBaoUBND',label=status==='DaDangKyUBND'?'Đã đăng ký với UBND Phường/Xã':(status==='DangKhaiBaoUBND'?'Đang khai báo với UBND Phường/Xã':'Chưa đăng ký với UBND Phường/Xã');td=document.createElement('td');td.className='tenant-stay-cell';td.innerHTML=`<span class="badge ${status==='DaDangKyUBND'?'bg-success':(status==='DangKhaiBaoUBND'?'bg-warning text-dark':'bg-secondary')}">${label}</span>`;} row.insertBefore(td,action); row.appendChild(action); if(item && !action.querySelector('.edit-tenant')){const edit=document.createElement('button');edit.type='button';edit.className='btn btn-outline-secondary tenant-action edit-tenant ms-1';edit.dataset.cccd=item.CCCD;edit.title='Sửa thông tin cá nhân';edit.innerHTML='<i class="bi bi-pencil-square"></i>';action.appendChild(edit);} });
        return true;
    };
    const timer=setInterval(() => enrich().then(done => { if(done) clearInterval(timer); }).catch(() => clearInterval(timer)), 120);
    if(!window.__tenantEditHandlerBound){window.__tenantEditHandlerBound=true;document.addEventListener('click', (event) => { const button=event.target.closest('.edit-tenant'); if(!button)return; const item=window.__tenantRecords?.get(String(button.dataset.cccd)); if(!item)return; let form=document.getElementById('tenantQuickEdit'); if(!form){form=document.createElement('form');form.id='tenantQuickEdit';form.className='modal fade';form.tabIndex=-1;form.innerHTML='<div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Cập nhật thông tin cá nhân</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-3"><input type="hidden" name="Id"><div class="col-12"><label class="form-label">Họ tên</label><input name="HoTen" class="form-control" required></div><div class="col-md-6"><label class="form-label">Ngày sinh</label><input name="NgaySinh" type="date" class="form-control"></div><div class="col-md-6"><label class="form-label">Giới tính</label><select name="GioiTinh" class="form-select"><option value="Nam">Nam</option><option value="Nữ">Nữ</option></select></div><div class="col-md-6"><label class="form-label">Số điện thoại</label><input name="SoDienThoai" class="form-control"></div><div class="col-md-6"><label class="form-label">Email</label><input name="Email" type="email" class="form-control"></div><div class="col-12"><label class="form-label">Địa chỉ thường trú</label><input name="DiaChiThuongTru" class="form-control"></div><div class="col-12"><label class="form-label">Nghề nghiệp</label><input name="NgheNghiep" class="form-control"></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu cập nhật</button></div></div></div>';document.body.append(form);form.addEventListener('submit',async e=>{e.preventDefault();const data=Object.fromEntries(new FormData(form));await window.app.api('/api/nguoithue.php?id='+data.Id,{method:'PUT',body:JSON.stringify(data)});bootstrap.Modal.getOrCreateInstance(form).hide();window.location.reload();});} for(const key of ['Id','HoTen','NgaySinh','GioiTinh','SoDienThoai','Email','DiaChiThuongTru','NgheNghiep']){const field=form.elements[key];if(field)field.value=item[key]||'';} bootstrap.Modal.getOrCreateInstance(form).show(); });}
};

document.addEventListener('DOMContentLoaded', () => app.enhanceTenantPage());

document.addEventListener('click', (event) => {
    const button=event.target.closest('.edit-tenant'); if(!button) return;
    setTimeout(() => {
        const form=document.getElementById('tenantQuickEdit'); const item=window.__tenantRecords?.get(String(button.dataset.cccd)); if(!form||!item||form.querySelector('[name="TrangThaiDangKy"]'))return;
        form.querySelector('.modal-body').insertAdjacentHTML('beforeend','<hr class="col-12"><div class="col-12"><strong>Hồ sơ định danh</strong><small class="d-block text-muted">Dán đường dẫn ảnh hoặc dữ liệu ảnh đã tải lên.</small></div><div class="col-md-6"><label class="form-label">Ảnh chân dung</label><input name="AnhChanDung" class="form-control"></div><div class="col-md-6"><label class="form-label">VNeID mức 2</label><input name="VNeIDMuc2" class="form-control"></div><div class="col-md-6"><label class="form-label">CCCD mặt trước</label><input name="AnhCCCDMatTruoc" class="form-control"></div><div class="col-md-6"><label class="form-label">CCCD mặt sau</label><input name="AnhCCCDMatSau" class="form-control"></div><div class="col-12"><label class="form-label">Trạng thái với UBND Phường/Xã</label><select name="TrangThaiDangKy" class="form-select"><option value="ChuaKhaiBaoUBND">Chưa đăng ký với UBND Phường/Xã</option><option value="DangKhaiBaoUBND">Đang khai báo với UBND Phường/Xã</option><option value="DaDangKyUBND">Đã đăng ký với UBND Phường/Xã</option></select></div>');
        for(const key of ['AnhChanDung','AnhCCCDMatTruoc','AnhCCCDMatSau','VNeIDMuc2'])form.elements[key].value=item[key]||'';form.elements.TrangThaiDangKy.value=item.TrangThaiDangKy||'ChuaKhaiBaoUBND';
        const body=form.querySelector('.modal-body');if(!body.querySelector('.tenant-edit-left')){const left=document.createElement('div'),right=document.createElement('div');left.className='col-md-6 tenant-edit-left border-end';right.className='col-md-6 tenant-edit-right';const nodes=[...body.children];nodes.forEach((node,index)=>{if(node.matches('input[type="hidden"]')||index<7)left.appendChild(node);else right.appendChild(node);});body.append(left,right);}
    },0);
});

document.addEventListener('click', (event) => {
    const button=event.target.closest('.view-tenant'); if(!button) return;
    setTimeout(() => { const row=button.closest('tr'); const cccd=row?.children[1]?.textContent.trim(); const item=window.__tenantRecords?.get(cccd); const content=document.getElementById('tenantDetailContent'); if(!item||!content||content.querySelector('.all-tenant-contracts'))return; const rooms=(item.DanhSachPhong||[]).map(room=>`<li>${room}</li>`).join(''); const contracts=(item.DanhSachHopDong||[]).map(contract=>`<li><a target="_blank" href="/views/hopdong/xem.php?id=${Number(contract.Id)}">${contract.SoHopDong||('Hợp đồng #'+contract.Id)}</a> · ${contract.NgayBatDau||''} → ${contract.NgayKetThuc||''}</li>`).join(''); content.insertAdjacentHTML('beforeend',`<section class="all-tenant-contracts border-top mt-3 pt-3"><h6>Toàn bộ phòng và hợp đồng đang hiệu lực</h6><div class="row"><div class="col-md-6"><strong>Phòng đang thuê</strong><ul class="mb-0">${rooms||'<li>—</li>'}</ul></div><div class="col-md-6"><strong>Hợp đồng</strong><ul class="mb-0">${contracts||'<li>—</li>'}</ul></div></div></section>`); },0);
});

document.addEventListener('click', (event) => {
    const button=event.target.closest('.edit-tenant'); if(!button)return;
    setTimeout(() => {
        const form=document.getElementById('tenantQuickEdit');const item=window.__tenantRecords?.get(String(button.dataset.cccd));if(!form||!item)return;
        form.querySelector('.modal-dialog')?.classList.add('modal-lg');if(!document.getElementById('tenantUploadStyle')){const style=document.createElement('style');style.id='tenantUploadStyle';style.textContent='#tenantQuickEdit .modal-body{max-height:72vh;overflow:auto}#tenantQuickEdit .tenant-edit-left,#tenantQuickEdit .tenant-edit-right{display:flex;flex-wrap:wrap;align-content:flex-start;gap:.75rem}#tenantQuickEdit .tenant-edit-left>.col-12,#tenantQuickEdit .tenant-edit-right>.col-12{width:100%}#tenantQuickEdit .tenant-edit-left>.col-md-6,#tenantQuickEdit .tenant-edit-right>.col-md-6{width:calc(50% - .375rem)}#tenantQuickEdit .form-control,#tenantQuickEdit .form-select{min-height:42px;width:100%}.tenant-upload-preview{display:block;width:100%;height:100px;object-fit:cover;border:1px solid #d1d5db;border-radius:8px;margin-bottom:6px;background:#f8fafc}';document.head.append(style);}
        const imageFields={AnhChanDung:'Ảnh chân dung 3×4',AnhCCCDMatTruoc:'CCCD mặt trước',AnhCCCDMatSau:'CCCD mặt sau',VNeIDMuc2:'VNeID mức 2'};
        Object.entries(imageFields).forEach(([key,label])=>{const input=form.elements[key];if(!input||input.dataset.uploadReady)return;input.dataset.uploadReady='1';input.type='file';input.accept='image/*';input.value='';input.dataset.previous=item[key]||'';const preview=document.createElement('img');preview.className='tenant-upload-preview';preview.alt=label;preview.src=item[key]||'/assets/pics/logo.webp';input.closest('.col-md-6,.col-12')?.prepend(preview);input.addEventListener('change',()=>{const file=input.files?.[0];if(file)preview.src=URL.createObjectURL(file);});});
        if(!form.dataset.uploadHandler){form.dataset.uploadHandler='1';form.addEventListener('submit',async e=>{e.preventDefault();e.stopImmediatePropagation();const data=Object.fromEntries(new FormData(form));for(const key of Object.keys(imageFields)){const input=form.elements[key];const file=input.files?.[0];if(file)data[key]=await new Promise((resolve,reject)=>{const reader=new FileReader();reader.onload=()=>resolve(reader.result);reader.onerror=reject;reader.readAsDataURL(file);});else data[key]=input.dataset.previous||null;}await window.app.api('/api/nguoithue.php?id='+data.Id,{method:'PUT',body:JSON.stringify(data)});bootstrap.Modal.getOrCreateInstance(form).hide();window.location.reload();},true);}
    },20);
});

/* Keep the sidebar mounted while navigating between management screens. */
(() => {
    let isNavigating = false;
    const isInternalSidebarLink = (link) => {
        if (!link || !link.matches('.nav-menu .nav-item')) return false;
        const target = new URL(link.href, window.location.origin);
        // These are complete root-level screens, not embedded management views.
        if (target.pathname === '/about.php' || target.pathname === '/version.php') return false;
        return target.origin === window.location.origin;
    };

    const setActiveLink = (url) => {
        const targetPath = new URL(url, window.location.origin).pathname;
        document.querySelectorAll('.nav-menu .nav-item').forEach((link) => {
            link.classList.toggle('active', new URL(link.href, window.location.origin).pathname === targetPath);
        });
    };

    const runPageScripts = async (documentFragment) => {
        for (const script of documentFragment.querySelectorAll('body > script')) {
            if (script.src) {
                if (script.src.includes('/assets/js/app.js')) continue;
                if (script.src.includes('bootstrap') && !window.bootstrap) {
                    await new Promise((resolve, reject) => {
                        const tag = document.createElement('script');
                        tag.src = script.src;
                        tag.onload = resolve;
                        tag.onerror = reject;
                        document.head.appendChild(tag);
                    });
                }
                if (script.src.includes('chart.js') && !window.Chart) {
                    await new Promise((resolve, reject) => {
                        const tag = document.createElement('script');
                        tag.src = script.src;
                        tag.onload = resolve;
                        tag.onerror = reject;
                        document.head.appendChild(tag);
                    });
                }
                continue;
            }

            // Run each page script in its own scope so the same const names can
            // safely be used after moving to another sidebar tab.
            if (script.textContent.trim()) {
                new Function(script.textContent)();
            }
        }
    };

    const navigate = async (url, pushState = true) => {
        if (isNavigating) return;
        isNavigating = true;

        try {
            const targetUrl = new URL(url, window.location.origin).pathname;
            const currentMain = document.querySelector('.main-panel');
            let extras = document.getElementById('spa-page-extras');
            if (!extras) {
                extras = document.createElement('div');
                extras.id = 'spa-page-extras';
                document.body.appendChild(extras);
            }

            // Always request fresh markup. The old DOM cache caused tabs to
            // resurrect outdated HTML/CSS/handlers until the user reloaded.
            const requestUrl = new URL(url, window.location.origin);
            requestUrl.searchParams.set('_spa_ts', String(Date.now()));
            const response = await fetch(requestUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });
            if (!response.ok) throw new Error('Không thể tải trang.');

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            // Views may define small page-specific styles. Keep them when only
            // the main panel is swapped, otherwise a cached view falls back to
            // an unstyled layout after changing tabs.
            page.querySelectorAll('style').forEach((style) => {
                const key = style.textContent.trim();
                const exists = [...document.head.querySelectorAll('style')].some((item) => item.textContent.trim() === key);
                if (!exists) document.head.appendChild(document.importNode(style, true));
            });
            const nextMain = page.querySelector('.main-panel');
            if (!nextMain || !currentMain) {
                window.location.assign(url);
                return;
            }

            extras.replaceChildren(...[...page.body.children]
                .filter((node) => node.tagName !== 'SCRIPT' && !node.classList.contains('app-shell'))
                .map((node) => document.importNode(node, true)));

            currentMain.replaceWith(document.importNode(nextMain, true));
            if (document.querySelector('.sidebar-top small')?.textContent.trim() === 'nguoithue') {
                document.querySelector('.main-panel .top-actions a[href="/views/khu/index.php"]')?.remove();
            }
            document.title = page.title || document.title;
            setActiveLink(url);
            if (pushState) window.history.pushState({ url }, '', url);
            window.scrollTo({ top: 0, behavior: 'smooth' });
            await runPageScripts(page);
            window.app.compactPageHeaders?.();
            window.app.applyRoleView?.();
            window.app.enhanceTenantPage?.();
        } catch (error) {
            console.error(error);
            window.location.assign(url);
        } finally {
            isNavigating = false;
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('.nav-menu .nav-item');
        if (!isInternalSidebarLink(link) || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        if (new URL(link.href).pathname !== window.location.pathname) navigate(link.href);
    });

    window.addEventListener('popstate', () => navigate(window.location.href, false));

    document.addEventListener('DOMContentLoaded', () => {
        // Defensive delegated opener: the temporary-residence modal is loaded
        // as a SPA extra, so this keeps the owner's Add button functional.
        document.addEventListener('click', (event) => {
            if (!event.target.closest('#openAddTamTru')) return;
            const modal = document.getElementById('modalTamTru');
            if (modal && window.bootstrap?.Modal) window.bootstrap.Modal.getOrCreateInstance(modal).show();
        });
        window.app.compactPageHeaders?.();
        const role = document.querySelector('.sidebar-top small')?.textContent.trim();
        document.querySelectorAll('.nav-menu a[href="/views/phong/index.php"]').forEach((link) => link.remove());
        if (role === 'nguoithue') document.querySelectorAll('.nav-menu a[href="/views/nguoithue/index.php"]').forEach((link) => link.remove());
        document.querySelectorAll('.nav-menu a[href="/views/khu/index.php"]').forEach((link) => {
            link.innerHTML = '<i class="bi bi-buildings"></i> Quản lý phòng thuê';
        });
        if (role !== 'admin') {
            document.querySelectorAll('.nav-menu a[href="/views/taikhoan/index.php"]').forEach((link) => link.remove());
        }
        if (role === 'nguoithue') {
            const allowed = new Set(['/views/hopdong/index.php', '/views/chisodiennuoc/index.php', '/views/hoadon/index.php', '/views/suco/index.php', '/views/tamtru/index.php', '/views/hoso/index.php', '/about.php']);
            document.querySelectorAll('.nav-menu .nav-item').forEach((link) => {
                if (!allowed.has(new URL(link.href, window.location.origin).pathname)) link.remove();
            });
            const menu = document.querySelector('.nav-menu');
            if (menu && !menu.querySelector('a[href="/views/thongbao/index.php"]')) {
                const item = document.createElement('a'); item.className = 'nav-item'; item.href = '/views/thongbao/index.php'; item.innerHTML = '<i class="bi bi-bell"></i> Thông báo'; menu.appendChild(item);
            }
        }
        if (role === 'nguoithue') {
            const menu = document.querySelector('.nav-menu');
            if (menu && !menu.querySelector('a[href="/views/suco/index.php"]')) {
                const profile = document.createElement('a'); profile.className='nav-item'; profile.href='/views/hoso/index.php'; profile.innerHTML='<i class="bi bi-person-circle"></i> Thông tin cá nhân';
                const incident = document.createElement('a'); incident.className='nav-item'; incident.href='/views/suco/index.php'; incident.innerHTML='<i class="bi bi-tools"></i> Báo cáo sự cố';
                const stay = document.createElement('a'); stay.className='nav-item'; stay.href='/views/tamtru/index.php'; stay.innerHTML='<i class="bi bi-person-vcard"></i> Khai báo lưu trú';
                menu.append(profile, incident, stay);
            }
            window.app.applyRoleView?.();
        }
        if (role === 'nguoithue') {
            const menu=document.querySelector('.nav-menu'); const notice=menu?.querySelector('a[href="/views/thongbao/index.php"]'); if(menu&&notice)menu.prepend(notice);
            document.querySelectorAll('.nav-menu a[href="/views/tamtru/index.php"]').forEach(link=>link.innerHTML='<i class="bi bi-person-vcard"></i> Khai báo lưu trú');
        }
        if (role === 'chutro') {
            const menu=document.querySelector('.nav-menu'); let notice=menu?.querySelector('a[href="/views/thongbao/index.php"]'); if(!notice&&menu){notice=document.createElement('a');notice.className='nav-item';notice.href='/views/thongbao/index.php';notice.innerHTML='<i class="bi bi-bell"></i> Thông báo';} if(menu&&notice)menu.prepend(notice);
            if(menu&&!menu.querySelector('a[href="/views/xacnhan/index.php"]')){const approval=document.createElement('a');approval.className='nav-item';approval.href='/views/xacnhan/index.php';approval.innerHTML='<i class="bi bi-person-check"></i> Xác nhận hồ sơ';menu.insertBefore(approval,notice?.nextSibling||null);}
            document.querySelectorAll('.nav-menu a[href="/views/tamtru/index.php"]').forEach(link=>link.innerHTML='<i class="bi bi-person-vcard"></i> Quản lý tạm trú / lưu trú');
        }
        if (role === 'chutro' || role === 'nguoithue') {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar && !document.querySelector('.mobile-menu-toggle')) {
                sidebar.classList.add('sidebar-mobile');
                const toggle = document.createElement('button'); toggle.type='button'; toggle.className='mobile-menu-toggle'; toggle.setAttribute('aria-label','Mở menu'); toggle.innerHTML='<i class="bi bi-list"></i>';
                const backdrop = document.createElement('div'); backdrop.className='mobile-menu-backdrop';
                const close=()=>document.body.classList.remove('mobile-sidebar-open');
                toggle.addEventListener('click',()=>document.body.classList.toggle('mobile-sidebar-open')); backdrop.addEventListener('click',close);
                sidebar.querySelectorAll('.nav-item').forEach(item=>item.addEventListener('click',close)); document.body.append(toggle,backdrop);

                const sourceAvatar = sidebar.querySelector('.sidebar-top .avatar-img');
                const sourceInitial = sidebar.querySelector('.sidebar-top .avatar')?.textContent.trim() || 'U';
                const appName = sidebar.querySelector('.brand-title')?.textContent.trim() || 'Trọ Tốt';
                const mobileTopbar = document.createElement('header');
                mobileTopbar.className = 'mobile-topbar';
                const avatarMarkup = sourceAvatar
                    ? `<img class="mobile-topbar-avatar" src="${sourceAvatar.src}" alt="Tài khoản">`
                    : `<span class="mobile-topbar-avatar" aria-label="Tài khoản">${sourceInitial}</span>`;
                mobileTopbar.innerHTML = `<a class="mobile-topbar-brand" href="/index.php" aria-label="${appName}"><img class="mobile-topbar-logo" src="/assets/pics/logo.webp" alt=""><span class="mobile-topbar-name">${appName}</span></a><div class="mobile-topbar-right">${avatarMarkup}<a class="mobile-topbar-logout" href="/logout.php" aria-label="Đăng xuất" title="Đăng xuất"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 17l5-5-5-5"></path><path d="M15 12H3"></path><path d="M21 19V5a2 2 0 0 0-2-2h-6"></path></svg></a></div>`;
                document.body.appendChild(mobileTopbar);
            }
        }
        // Show outstanding workflow items where users will notice them first.
        const noticeLink = document.querySelector('.nav-menu a[href="/views/thongbao/index.php"]');
        if (noticeLink && !noticeLink.querySelector('.nav-notice-count')) {
            window.app.api('/api/thongbao.php').then((result) => {
                const unread=(result.data||[]).filter(item=>!Number(item.DaDoc)).length;
                if(!unread)return; const badge=document.createElement('span');badge.className='nav-notice-count';badge.textContent=unread>99?'99+':unread;noticeLink.appendChild(badge);
            }).catch(()=>{});
        }
        const aboutMenu=document.querySelector('.nav-menu');if(aboutMenu&&!aboutMenu.querySelector('a[href="/about.php"]')){const about=document.createElement('a');about.className='nav-item';about.href='/about.php';about.innerHTML='<i class="bi bi-info-circle"></i> Về ứng dụng';aboutMenu.appendChild(about);}
        window.app.applyRoleView?.();
        if (window.location.pathname === '/views/tamtru/index.php' && role !== 'nguoithue') {
            const select=document.getElementById('TrangThaiTamTru');
            if(select)select.innerHTML='<option value="ChuaKhaiBaoUBND">Chưa đăng ký với UBND Phường/Xã</option><option value="DangKhaiBaoUBND">Đang khai báo với UBND Phường/Xã</option><option value="DaDangKyUBND">Đã đăng ký với UBND Phường/Xã</option>';
            const label=item=>Number(item.TrangThai)===1||item.TrangThaiDangKy==='DaDangKyUBND'?'Đã đăng ký với UBND Phường/Xã':(item.Loai==='LuuTru'&&item.TrangThaiXuLy==='ChoChuTroXacNhan'?'Chờ chủ trọ xác nhận lưu trú':(item.TrangThaiDangKy==='DangKhaiBaoUBND'?'Đang đăng ký với UBND Phường/Xã':'Chưa đăng ký với UBND Phường/Xã'));
            const cls=item=>Number(item.TrangThai)===1||item.TrangThaiDangKy==='DaDangKyUBND'?'bg-success':(item.Loai==='LuuTru'&&item.TrangThaiXuLy==='ChoChuTroXacNhan'?'bg-warning text-dark':(item.TrangThaiDangKy==='DangKhaiBaoUBND'?'bg-info':'bg-secondary'));
            const paint=async()=>{try{const data=(await window.app.api('/api/tamtru.php')).data||[];const map=new Map(data.map(x=>[String(x.Id),x]));document.querySelectorAll('.table-modern tbody tr').forEach(tr=>{const id=(tr.children[0]?.textContent||'').replace('#','').trim(),item=map.get(id);if(!item)return;const cell=tr.children[5];if(cell)cell.innerHTML=`<span class="badge bg-info">${item.Loai==='LuuTru'?'Lưu trú':'Tạm trú'}</span> <span class="badge ${cls(item)}">${label(item)}</span>`;});}catch(e){console.error(e)}};
            const observer=new MutationObserver(()=>paint()); const target=document.querySelector('.table-modern tbody');if(target)observer.observe(target,{childList:true});setTimeout(paint,300);
        }
        if (window.location.pathname === '/views/hoadon/index.php' && !document.querySelector('.invoice-filter')) {
            const table=document.querySelector('.table-modern'), body=table?.querySelector('tbody'); if(body){
                let stats=document.getElementById('invoiceStats');if(!stats){stats=document.createElement('section');stats.id='invoiceStats';stats.className='stats-grid mb-3';document.querySelector('.card-panel')?.before(stats);}
                const params=()=>{const p=new URLSearchParams(),period=document.getElementById('invoicePeriod')?.value||'',area=document.getElementById('invoiceArea')?.value,row=document.getElementById('invoiceRow')?.value;if(period){const [nam,thang]=period.split('-');p.set('nam',nam);p.set('thang',thang)}if(area)p.set('khuId',area);if(row)p.set('dayId',row);return p;};
                const paint=()=>body.querySelectorAll('tr').forEach(tr=>{const status=(tr.children[6]?.textContent||'').trim();tr.classList.remove('table-warning','table-primary','table-success');if(/chưa thanh toán/i.test(status))tr.classList.add('table-warning');else if(/một phần/i.test(status))tr.classList.add('table-primary');else if(/đã thanh toán/i.test(status))tr.classList.add('table-success');});
                const update=async()=>{try{const data=(await window.app.api('/api/hoadon.php?action=thongKe&'+params())).data||{};stats.innerHTML=[['Hóa đơn trong kỳ',data.Tong||0,'primary'],['Chưa thanh toán',data.ChuaThanhToan||0,'warning'],['Thanh toán một phần',data.MotPhan||0,'primary'],['Đã thanh toán',data.DaThanhToan||0,'success']].map(x=>`<div class="stat-card ${x[2]}"><div><span>${x[0]}</span><strong>${x[1]}</strong></div></div>`).join('');paint();}catch(e){console.error(e)}};
                new MutationObserver(paint).observe(body,{childList:true});document.addEventListener('change',e=>{if(e.target.matches('#invoicePeriod,#invoiceArea,#invoiceRow'))setTimeout(update,0)});setTimeout(update,350);
            }
        }
        if (window.location.pathname === '/views/nguoithue/index.php') {
            const body=document.getElementById('tenantRows');if(body){let page=1;const pager=document.createElement('nav');pager.className='mt-3 d-flex justify-content-end';pager.id='tenantPager';body.closest('.table-responsive')?.after(pager);const apply=()=>{const rows=[...body.querySelectorAll('tr')].filter(r=>r.children.length>1),total=Math.max(1,Math.ceil(rows.length/10));page=Math.min(page,total);rows.forEach((r,i)=>r.hidden=i<(page-1)*10||i>=page*10);pager.innerHTML=rows.length>10?`<ul class="pagination pagination-sm mb-0"><li class="page-item ${page===1?'disabled':''}"><button class="page-link" data-tenant-page="${page-1}">‹</button></li><li class="page-item disabled"><span class="page-link">${page}/${total}</span></li><li class="page-item ${page===total?'disabled':''}"><button class="page-link" data-tenant-page="${page+1}">›</button></li></ul>`:'';};new MutationObserver(()=>{page=1;apply()}).observe(body,{childList:true});pager.onclick=e=>{const b=e.target.closest('[data-tenant-page]');if(b&&Number(b.dataset.tenantPage)>0){page=Number(b.dataset.tenantPage);apply();}};setTimeout(apply,400);}
        }
        if (window.location.pathname === '/views/tamtru/index.php' && role !== 'nguoithue' && !document.getElementById('staySearch')) {
            const body=document.querySelector('.table-modern tbody');if(body){let page=1;const pager=document.createElement('nav');pager.className='mt-3 d-flex justify-content-end';body.closest('.table-responsive')?.after(pager);const paging=()=>{const rows=[...body.querySelectorAll('tr')].filter(r=>r.children.length>1),total=Math.max(1,Math.ceil(rows.length/10));page=Math.min(page,total);rows.forEach((r,i)=>r.hidden=i<(page-1)*10||i>=page*10);pager.innerHTML=rows.length>10?`<ul class="pagination pagination-sm mb-0"><li class="page-item ${page===1?'disabled':''}"><button class="page-link" data-stay-page="${page-1}">‹</button></li><li class="page-item disabled"><span class="page-link">${page}/${total}</span></li><li class="page-item ${page===total?'disabled':''}"><button class="page-link" data-stay-page="${page+1}">›</button></li></ul>`:'';};new MutationObserver(()=>{page=1;paging()}).observe(body,{childList:true});pager.onclick=e=>{const b=e.target.closest('[data-stay-page]');if(b&&Number(b.dataset.stayPage)>0){page=Number(b.dataset.stayPage);paging();}};setTimeout(async()=>{try{const list=(await window.app.api('/api/tamtru.php')).data||[],today=new Date().toISOString().slice(0,10),valid=list.filter(x=>!x.NgayKetThuc||x.NgayKetThuc>=today),registered=valid.filter(x=>Number(x.TrangThai)===1).length,waiting=valid.filter(x=>Number(x.TrangThai)!==1&&x.TrangThaiXuLy==='ChoChuTroXacNhan').length,unregistered=valid.length-registered-waiting;let stats=document.getElementById('stayStats');if(!stats){stats=document.createElement('section');stats.id='stayStats';stats.className='stats-grid mb-3';document.querySelector('.main-panel .card-panel')?.before(stats);}stats.innerHTML=[['Đang lưu trú',valid.filter(x=>x.Loai==='LuuTru').length,'primary'],['Tạm trú',valid.filter(x=>x.Loai!=='LuuTru').length,'primary'],['Đã đăng ký UBND',registered,'success'],['Đang khai báo UBND',waiting,'warning'],['Chưa đăng ký UBND',unregistered,'danger']].map(x=>`<div class="stat-card ${x[2]}"><div><span>${x[0]}</span><strong>${x[1]}</strong></div></div>`).join('');paging();}catch(e){console.error(e)}},350);}
        }
        setActiveLink(window.location.href);
    });
})();
