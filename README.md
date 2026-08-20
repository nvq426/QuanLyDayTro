# Trọ Tốt — Hệ thống quản lý phòng trọ

Trọ Tốt là ứng dụng web quản lý khu trọ viết bằng PHP thuần, PDO SQLite và JavaScript. Hệ thống quản lý tài khoản, khu/dãy/phòng, người thuê, hợp đồng, điện nước, hóa đơn, thanh toán, hồ sơ, lưu trú, sự cố, thông báo, báo cáo và hoạt động quản trị.

Tài liệu này phản ánh mã nguồn hiện tại. Nguồn chuẩn theo thứ tự: `includes/db.php` → `includes/demo_seed.php` → `api/*.php` → `views/**/*.php` và `assets/js/app.js`.

## 1. Công nghệ

| Thành phần | Công nghệ |
|---|---|
| Backend | PHP 8.1+, PHP Session, PDO |
| Database | SQLite, tệp `data/du_lieu.db` |
| Frontend | HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, JavaScript thuần |
| Giao tiếp | Fetch API, JSON |
| PDF hợp đồng | Helper nội bộ, lưu tại `data/hopdong/` |
| Build/dependency | Không dùng Composer, npm hoặc framework backend |

`getDb()` tự tạo bảng và thêm các cột còn thiếu khi ứng dụng kết nối. Nếu database chưa có tài khoản, `seedDemoData()` sẽ nạp dữ liệu mẫu.

## 2. Cấu trúc dự án

```text
QUANLYTRO/
├── index.php                 # Trang vào/Dashboard theo vai trò
├── login.php, logout.php     # Xác thực
├── about.php, version.php    # Về ứng dụng và quản lý phiên bản
├── api/                      # API JSON theo từng module
├── views/                    # Giao diện chức năng
├── includes/
│   ├── db.php                # Schema, migration runtime
│   ├── demo_seed.php         # Dữ liệu mẫu chuẩn
│   ├── auth.php              # Session, role, audit logger
│   ├── response.php          # JSON response
│   ├── navigation.php        # Menu theo vai trò
│   └── helpers.php, mobile.php
├── assets/css/style.css
├── assets/js/app.js          # API client, SPA navigation, UI dùng chung
├── data/du_lieu.db
├── data/hopdong/
├── dulieu_mau/               # SQL tham khảo, không phải migration runtime
└── swagger/openapi.yaml      # Đặc tả tham khảo, chưa bao phủ toàn bộ API
```

Sidebar dùng điều hướng SPA: chỉ thay `.main-panel`, tải HTML mới bằng `cache: no-store` và `_spa_ts`, sau đó chạy lại script của view. Biến/helper cần dùng qua nhiều thẻ `<script>` phải gắn vào `window`.

## 3. Vai trò, tab và phạm vi truy cập

### 3.1. Admin (`admin`)

Admin chỉ hiển thị các tab quản trị sau, đúng thứ tự:

1. **Tài khoản** — tìm kiếm, số dòng/trang, đi đến trang, tạo/sửa/khóa tài khoản.
2. **Quản lý chủ trọ** — tạo, sửa, cấp lại mật khẩu, khóa/mở khóa; giao diện không có nút xóa.
3. **Nhật ký hoạt động** — xem đăng nhập và thao tác ghi dữ liệu, lọc theo từ khóa, vai trò, ngày và IP.
4. **Tệp minh chứng** — tìm, xem, tải và xóa ảnh minh chứng thanh toán.
5. **Dung lượng hệ thống** — PHP runtime, SQLite, kích thước database và dung lượng dự án so với 5 GB InfinityFree.
6. **Về ứng dụng** — thông tin và lịch sử phiên bản; nút quản lý phiên bản chỉ dành cho admin.

Admin API có quyền toàn hệ thống ở các module được cấp quyền, nhưng menu quản trị không hiển thị các màn hình vận hành của chủ trọ.

### 3.2. Chủ trọ (`chutro`)

Các tab thực tế:

1. **Thông báo**.
2. **Xác nhận hồ sơ**.
3. **Dashboard**.
4. **Quản lý phòng thuê** — quản lý tích hợp Khu → Dãy → Phòng.
5. **Người thuê**.
6. **Hợp đồng**.
7. **Điện / Nước**.
8. **Hóa đơn**.
9. **Quản lý tạm trú / lưu trú**.
10. **Báo cáo**.
11. **Tài khoản thuê trọ**.
12. **Về ứng dụng**.

Chủ trọ chỉ được thao tác dữ liệu nối về khu có `Khu.TaiKhoanId = currentUser.Id`. Điều kiện này phải được kiểm tra tại API, không chỉ ẩn nút ở giao diện.

Quyền chính của chủ trọ:

- Tạo/sửa/xóa mềm khu, dãy, phòng của mình.
- Lập hợp đồng, thêm thành viên phòng và xem PDF hợp đồng.
- Xem/sửa hồ sơ người thuê thuộc khu; duyệt yêu cầu hồ sơ.
- Ghi điện nước, cấu hình đơn giá mặc định, tạo/xem trước/gửi hóa đơn.
- Cập nhật thông tin nhận chuyển khoản và ảnh QR.
- Xác nhận yêu cầu thanh toán của người thuê.
- Xác nhận người lưu trú trước, sau đó cập nhật trạng thái đăng ký UBND.
- Xem báo cáo theo khu sở hữu.
- Quản lý tài khoản người ký/thành viên: sửa, cấp lại mật khẩu, khóa/mở khóa; giao diện không có nút xóa.

### 3.3. Người thuê (`nguoithue`)

Người thuê có thể là người ký hợp đồng (`NguoiThue.TaiKhoanId`) hoặc thành viên phòng (`ThanhVienPhong.TaiKhoanId`). Các tab:

1. **Thông báo** — bấm thông báo để đánh dấu đã đọc và mở tab liên quan.
2. **Thông tin cá nhân** — hỗ trợ cả người ký và thành viên; thay đổi phải chờ chủ trọ duyệt.
3. **Hợp đồng** — người ký và thành viên đều xem được hợp đồng của phòng mình.
4. **Điện / Nước** — chỉ xem.
5. **Hóa đơn** — xem hóa đơn, gửi yêu cầu đóng tiền.
6. **Báo cáo sự cố** — tạo sự cố gửi chủ trọ.
7. **Khai báo lưu trú** — chỉ khai báo lưu trú cho phòng có hợp đồng hiệu lực.
8. **Về ứng dụng**.

Người thuê không được tạo/sửa phòng, hợp đồng, chỉ số, hóa đơn; không được tự xác nhận thanh toán, tự cập nhật trạng thái UBND hoặc xóa khai báo lưu trú.

## 4. Mô hình dữ liệu

```text
TaiKhoan 1 ─ n Khu 1 ─ n Day 1 ─ n Phong
TaiKhoan 1 ─ 0..1 NguoiThue
Phong 1 ─ n HopDong n ─ 1 NguoiThue
HopDong 1 ─ n ThanhVienPhong
Phong 1 ─ n ChiSoDienNuoc
HopDong 1 ─ n HoaDon 1 ─ n ThanhToan
Phong 1 ─ n TamTru
Phong 1 ─ n SuCo
TaiKhoan 1 ─ n ThongBao
NguoiThue 1 ─ n YeuCauHoSoNguoiThue
TaiKhoan(chutro) 1 ─ 1 CauHinhDienNuoc
TaiKhoan 1 ─ n AuditLog
```

| Bảng | Mục đích |
|---|---|
| `TaiKhoan` | Đăng nhập, bcrypt, vai trò, khóa, thông tin ngân hàng/QR, soft-delete |
| `Khu`, `Day`, `Phong` | Cấu trúc khu trọ và trạng thái phòng |
| `NguoiThue` | Người đại diện ký hợp đồng và hồ sơ định danh |
| `ThanhVienPhong` | Thành viên thuộc hợp đồng, tài khoản và hồ sơ riêng |
| `HopDong` | Thời hạn, giá/cọc, hai bên, đơn giá, chỉ số đầu, PDF |
| `ChiSoDienNuoc` | Chỉ số theo phòng/kỳ, đơn giá, dịch vụ và ghi chú |
| `CauHinhDienNuoc` | Đơn giá mặc định của chủ trọ |
| `HoaDon` | Tiền phòng/điện/nước/dịch vụ, tổng, đã trả, hạn thanh toán |
| `ThanhToan` | Yêu cầu tiền mặt/chuyển khoản, ghi chú, minh chứng, trạng thái duyệt |
| `TamTru` | Tạm trú/lưu trú, xác nhận chủ trọ và trạng thái UBND độc lập |
| `YeuCauHoSoNguoiThue` | Dữ liệu JSON chờ duyệt của người ký hoặc thành viên |
| `SuCo` | Sự cố, người báo/xử lý và trạng thái |
| `ThongBao` | Nội dung, loại, đã đọc; đường dẫn liên quan được suy ra khi trả API |
| `AuditLog` | Đăng nhập và thao tác POST/PUT/PATCH/DELETE, kèm IP |
| `PhienBanUngDung` | Lịch sử phiên bản, được tạo tại `about.php`/`version.php` |

Các trạng thái quan trọng:

- Phòng: `Trong`, `DangThue`, `BaoTri`.
- Hợp đồng: `DangHieuLuc`, `GiaHan`, `HetHan`, `DaChamDut`.
- Hóa đơn lưu trong DB: `ChuaThanhToan`, `ThanhToanMotPhan`, `DaThanhToan`.
- Trạng thái hiển thị bổ sung: `ChoXacNhanThanhToan` khi có giao dịch đang chờ.
- Thanh toán: `ChoXacNhan`, `DaXacNhan`.
- Lưu trú xử lý: `ChoChuTroXacNhan`, `DaXacNhanChuTro`, `TuChoi`.
- Đăng ký UBND: `ChuaKhaiBaoUBND`, `DangKhaiBaoUBND`, `DaDangKyUBND`.
- Sự cố: `Moi`, `DaTiepNhan`, `DaKhacPhuc`.

## 5. Luồng nghiệp vụ chính

### 5.1. Khu, dãy và phòng

Màn hình Quản lý phòng thuê hiển thị cây Khu → Dãy → Phòng. Ô tìm kiếm nằm cạnh nút SVG bộ lọc; modal lọc chính xác theo khu, dãy và trạng thái. `Áp dụng bộ lọc` mới áp dụng lựa chọn; `Xóa lọc` đặt lại cả từ khóa và bộ lọc.

### 5.2. Hợp đồng và tài khoản thành viên

Khi lập hợp đồng, hệ thống tạo/tái sử dụng người thuê, sinh số hợp đồng/PDF, tạo tài khoản thành viên, ghi chỉ số đầu và chuyển phòng sang `DangThue` trong transaction.

Tài khoản tạo qua giao diện hợp đồng/thêm thành viên hiện dùng:

- Tên đăng nhập: chuỗi số CCCD.
- Mật khẩu ban đầu: CCCD, được lưu bằng bcrypt.

Chủ trọ nên cấp lại mật khẩu trong tab Tài khoản thuê trọ. Dữ liệu seed dùng mật khẩu `123456` cho tài khoản người thuê/thành viên mẫu.

### 5.3. Điện nước → hóa đơn → thông báo

1. Chủ trọ chọn phòng và nhập chỉ số.
2. Bấm **Tạo hóa đơn**.
3. Server upsert chỉ số, tính tiền phòng + điện + nước + dịch vụ và tạo/cập nhật hóa đơn.
4. Sau khi modal chỉ số đóng, modal **Thông báo tiền phòng** hiển thị chi tiết tính toán.
5. Chủ trọ bấm **Gửi hóa đơn đến người thuê**.
6. Người ký và thành viên có tài khoản nhận thông báo.

### 5.4. Thanh toán

- Số tiền cần đóng do server tính bằng `TongTien - DaTra - tổng đang ChoXacNhan`; người thuê không được sửa.
- Tiền mặt: chỉ thêm ghi chú, ví dụ đã đóng trực tiếp cho chủ trọ.
- Chuyển khoản: hiển thị thông tin ngân hàng/QR, có nút lưu QR và bắt buộc ảnh minh chứng JPG/PNG/WEBP tối đa 5 MB.
- Sau khi gửi, hóa đơn hiển thị **Đang chờ chủ trọ xác nhận thanh toán** và không cho gửi lặp.
- Khối thanh toán chờ xác nhận nằm đầu trang Hóa đơn chủ trọ. Khi xác nhận, server tăng `DaTra`, suy lại trạng thái và thông báo người thuê.

### 5.5. Lưu trú

1. Người thuê chọn phòng từ hợp đồng hiệu lực và gửi khai báo; server ép loại `LuuTru`.
2. Trạng thái là **Chờ chủ trọ xác nhận lưu trú**.
3. Chủ trọ bấm **Xác nhận lưu trú**.
4. Trạng thái thành **Đã xác nhận lưu trú · Chưa đăng ký với UBND Phường/Xã**.
5. Chủ trọ mới được sửa và cập nhật tiến trình UBND.

Hai trạng thái xác nhận chủ trọ và đăng ký UBND được lưu riêng. Người thuê không có nút sửa/xóa, không có thống kê và không chọn trạng thái UBND.

### 5.6. Hồ sơ, sự cố và thông báo

- Hồ sơ cá nhân hỗ trợ cả `NguoiThue` và `ThanhVienPhong`; cập nhật được lưu thành yêu cầu để chủ trọ duyệt đúng đối tượng.
- Người thuê báo sự cố; chủ trọ tiếp nhận/khắc phục và thông báo lại.
- Thông báo liên quan lưu trú, sự cố, hồ sơ, hóa đơn/thanh toán và điện nước có thể mở đúng tab liên quan.

## 6. Danh mục API

Ký hiệu: A = admin, C = chủ trọ, N = người thuê.

| Endpoint | Method/quyền | Chức năng chính |
|---|---|---|
| `/api/taikhoan.php` | GET/POST/PUT/DELETE · A; đăng nhập public | Tài khoản toàn hệ thống, tìm kiếm/phân trang |
| `/api/chutro.php` | GET/POST/PUT/DELETE · A | Chủ trọ, mật khẩu, khóa/mở khóa |
| `/api/auditlog.php` | GET · A | Nhật ký có lọc/phân trang |
| `/api/minhchung.php` | GET/DELETE · A | Danh sách, xem/tải/xóa minh chứng |
| `/api/hethong.php` | GET · A | PHP, SQLite, dung lượng/5 GB |
| `/api/khu.php`, `/api/day.php`, `/api/phong.php` | A,C | CRUD scoped khu/dãy/phòng |
| `/api/nguoithue.php` | A,C | Danh sách tổng hợp, sửa người ký/thành viên |
| `/api/hopdong.php` | GET · A,C,N; POST · A,C | Danh sách, chi tiết, phòng trống, preview, PDF, tạo, thêm thành viên |
| `/api/hoso.php` | GET/POST · N; GET/PUT · A,C | Hồ sơ người ký/thành viên và duyệt |
| `/api/chisodiennuoc.php` | GET · A,C,N; POST/PUT · A,C | Chỉ số, cấu hình giá, hóa đơn, gửi thông báo |
| `/api/hoadon.php` | GET · A,C,N; POST · A,C/N theo action | Hóa đơn, thống kê, nhận tiền, yêu cầu/xác nhận thanh toán |
| `/api/tamtru.php` | GET/POST · A,C,N; PUT/DELETE · A,C | Phòng được phép, khai báo và duyệt lưu trú |
| `/api/suco.php` | GET · A,C,N; POST · N; PUT · A,C | Báo và xử lý sự cố |
| `/api/thongbao.php` | GET/PUT · đăng nhập | Danh sách, điều hướng, đánh dấu đọc |
| `/api/baocao.php` | GET · A,C | Kỳ báo cáo, tổng quan và chi tiết |
| `/api/taikhoanthue.php` | GET/PUT/DELETE · C | Tài khoản người thuê thuộc khu; UI không gọi DELETE |

Response chuẩn:

```json
{"success":true,"message":"Thành công","data":{}}
```

## 7. Audit log và bảo mật

Audit logger trong `includes/auth.php` chỉ ghi:

- Đăng nhập thành công/thất bại.
- Request ghi dữ liệu `POST`, `PUT`, `PATCH`, `DELETE` trong `/api/` và `version.php`.
- Tài khoản, vai trò, hành động, method, đường dẫn, IP, kết quả và thời gian.

Không ghi request GET, đăng xuất, mật khẩu, payload, query string hoặc nội dung tệp. Lỗi audit không được làm gián đoạn nghiệp vụ chính.

Quy tắc bảo mật: bcrypt, prepared statement, session, kiểm tra role + ownership tại API, soft-delete, transaction cho luồng nhiều bảng, escape HTML và kiểm tra MIME/kích thước ảnh.

## 8. Dữ liệu mẫu hiện tại

`includes/demo_seed.php` là nguồn seed chuẩn cho database rỗng:

- 1 admin: `admin / 123456`.
- Đúng 1 chủ trọ: `chutro / Thu@123` — Lê Thị Thu.
- 2 khu, 4 dãy, 20 phòng; toàn bộ thuộc Lê Thị Thu.
- 16 người thuê chính, 8 thành viên phòng, 16 hợp đồng hiệu lực.
- 32 kỳ điện nước, 32 hóa đơn, 27 giao dịch.
- Có cấu hình giá, thông báo, lưu trú, sự cố, yêu cầu hồ sơ và audit log.
- Tài khoản người thuê/thành viên mẫu dùng mật khẩu `123456`.

Seed đã được kiểm tra khóa ngoại, trạng thái phòng/hợp đồng, tổng hóa đơn, thanh toán và chỉ số tăng hợp lệ. Seed chỉ chạy khi bảng `TaiKhoan` rỗng; không tự ghi đè database đang dùng.

## 9. Cài đặt và chạy

Yêu cầu PHP 8.1+ có `pdo_sqlite`, `sqlite3`, `session`, `json`, `mbstring` (khuyến nghị), và quyền ghi thư mục `data/`.

```bash
php -S localhost:8000
```

Mở `http://localhost:8000/login.php`. Với XAMPP, đặt dự án trong document root hoặc cấu hình virtual host trỏ đến thư mục dự án.

## 10. Lưu ý phát triển

- Schema runtime chỉ nằm ở `includes/db.php`; không coi `dulieu_mau/*.sql` là migration chuẩn.
- Khi đổi HTML, kiểm tra selector và enhancement trong `assets/js/app.js`.
- View chạy qua SPA phải hoạt động cả khi mở trực tiếp và khi script được thực thi bằng `new Function`.
- Không dựa vào việc ẩn nút để phân quyền.
- Luôn lọc `IsDeleted=0`/`COALESCE(IsDeleted,0)=0` cho dữ liệu hoạt động.
- Khi đổi schema/API/nghiệp vụ, cập nhật `README.md`, `README_PROMT.md`, seed và OpenAPI nếu có.
- Dự án chưa có test tự động/CI; cần chạy PHP lint và kiểm thử ba vai trò sau thay đổi.
