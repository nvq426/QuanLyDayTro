# ĐẶC TẢ KỸ THUẬT HỆ THỐNG QUẢN LÝ NHÀ TRỌ

Tài liệu này mô tả trạng thái hiện tại của dự án và là ngữ cảnh chuẩn khi sửa lỗi hoặc phát triển tính năng. Nếu có khác biệt, ưu tiên: cấu trúc database → kiểm tra quyền/API → nghiệp vụ server → giao diện → tài liệu.

## 1. Mục tiêu và kiến trúc

Ứng dụng phục vụ ba vai trò `Admin`, `ChuTro`, `NguoiThue`. Nguyên tắc bắt buộc là cách ly dữ liệu: chủ trọ chỉ quản lý dữ liệu thuộc khu trọ của mình; người thuê chỉ truy cập dữ liệu gắn với hồ sơ, hợp đồng hoặc tư cách thành viên phòng.

- Backend: PHP thuần, PHP session, API JSON.
- Database: SQLite qua PDO.
- Frontend: HTML, CSS, JavaScript thuần.
- Điều hướng: SPA, nội dung trong `pages/` được tải động.
- Tệp tải lên: `uploads/` chứa ảnh hồ sơ, hợp đồng, sự cố, minh chứng và QR ngân hàng.

```text
api/          API nghiệp vụ
assets/       CSS, JavaScript, tài nguyên giao diện
database/     khởi tạo, nâng cấp và dữ liệu mẫu
includes/     database, xác thực, điều hướng, hàm chung
pages/        trang tải động theo vai trò
uploads/      tệp người dùng tải lên
index.php     điểm vào ứng dụng
login.php     đăng nhập
version.php   API phiên bản ứng dụng
```

## 2. Quy tắc SPA bắt buộc

`assets/js/app.js` tải HTML và chạy lại script của trang. Script động có phạm vi riêng khi chạy qua `Function`, do đó:

- Hàm dùng chung phải gắn vào `window` hoặc nằm trong tệp JavaScript toàn cục.
- Không giả định hàm/biến của một script trang còn tồn tại ở lần tải khác.
- Dọn event listener, timer và trạng thái cũ trước khi khởi tạo lại.
- Không chèn lặp thẻ thống kê, nút, modal hoặc menu khi quay lại tab.
- Khởi tạo trang sau khi HTML mới đã được gắn vào DOM.
- Không dùng lại HTML trang cũ; chuyển tab phải thấy giao diện mới mà không cần refresh.

Đây là nơi kiểm tra đầu tiên khi gặp `ReferenceError`, modal không mở, giao diện cũ xuất hiện hoặc thành phần bị nhân đôi.

## 3. Ma trận vai trò và tab

### Admin

Admin chỉ có sáu tab, theo đúng thứ tự và `Về ứng dụng` luôn cuối:

1. Tài khoản
2. Quản lý chủ trọ
3. Nhật ký hoạt động
4. Tệp minh chứng
5. Dung lượng hệ thống
6. Về ứng dụng

Chi tiết:

- `Tài khoản`: tìm kiếm, phân trang, nhập số trang, sửa, cấp lại mật khẩu, khóa/mở khóa.
- `Quản lý chủ trọ`: quản lý tài khoản chủ trọ; giao diện không có nút xóa.
- `Nhật ký hoạt động`: đăng nhập và thao tác CRUD, có IP và kết quả.
- `Tệp minh chứng`: quản lý tệp người dùng tải lên, nhất là minh chứng thanh toán.
- `Dung lượng hệ thống`: dung lượng dự án đã dùng so với giới hạn 5 GB của hosting, dung lượng còn lại, phiên bản PHP và SQLite.
- `Về ứng dụng`: quản lý lịch sử phiên bản từ bảng `PhienBanUngDung`; không tạo tab phiên bản riêng.

Admin không truy cập phòng, người thuê, hợp đồng, điện nước, hóa đơn hoặc nghiệp vụ khu trọ.

### Chủ trọ

Các tab hiện tại:

1. Thông báo
2. Xác nhận hồ sơ
3. Tổng quan
4. Quản lý phòng thuê
5. Người thuê
6. Hợp đồng
7. Điện/Nước
8. Hóa đơn
9. Quản lý tạm trú/lưu trú
10. Báo cáo
11. Tài khoản thuê trọ
12. Về ứng dụng

`Thông báo` và `Xác nhận hồ sơ` có thể được thêm động; tuyệt đối không nhân đôi khi tải lại. Chủ trọ chỉ thao tác dữ liệu thuộc khu của mình; được sửa, cấp lại mật khẩu, khóa/mở khóa tài khoản thuê trọ nhưng giao diện không có nút xóa.

### Người thuê

Các tab hiện tại:

1. Thông báo
2. Thông tin cá nhân
3. Hợp đồng
4. Điện/Nước
5. Hóa đơn
6. Báo cáo sự cố
7. Khai báo lưu trú
8. Về ứng dụng

Người ký hợp đồng và thành viên phòng đều phải xem được dữ liệu phù hợp. Không chỉ truy vấn qua người đứng tên hợp đồng; phải xét `ThanhVienPhong`.

Người thuê không có quyền xóa, không tự cập nhật trạng thái đăng ký hành chính và không có thẻ thống kê quản trị. Khi khai báo lưu trú, họ chọn phòng từ danh sách phòng có hợp đồng/tư cách thành viên, không nhập ID. Ngày hiển thị theo `dd/mm/yyyy`.

## 4. Cấu trúc dữ liệu hiện tại

### Tổ chức và tài khoản

- `TaiKhoan`: đăng nhập, họ tên, liên hệ, vai trò, trạng thái, quan hệ chủ trọ; thông tin ngân hàng, số tài khoản, chủ tài khoản, QR; hỗ trợ trạng thái xóa mềm nếu schema có `IsDeleted`.
- `Khu`: thuộc một chủ trọ, là gốc kiểm tra sở hữu.
- `Day`: thuộc khu.
- `Phong`: thuộc dãy, có tên/số phòng, giá và trạng thái.

### Người thuê và hợp đồng

- `NguoiThue`: hồ sơ cá nhân, CCCD, liên hệ, địa chỉ và ảnh giấy tờ.
- `ThanhVienPhong`: gắn thành viên với phòng/hợp đồng, dùng để cấp quyền cho người không đứng tên.
- `HopDong`: phòng, người đứng tên, thời hạn, tiền cọc, trạng thái và tệp hợp đồng.
- `YeuCauHoSoNguoiThue`: yêu cầu đổi hồ sơ để chủ trọ duyệt; phải hỗ trợ đúng cả người đứng tên và thành viên.

Quy tắc tạo tài khoản thành viên hiện tại: CCCD được dùng làm tên đăng nhập và mật khẩu khởi tạo trong luồng tạo thành viên. Mật khẩu phải được băm trước khi lưu và cần thông báo rõ sau khi tạo. Tài khoản trong dữ liệu mẫu dùng mật khẩu `123456`; không áp dụng quy tắc này cho môi trường thật.

### Điện, nước, hóa đơn và thanh toán

- `CauHinhDienNuoc`: đơn giá theo chủ trọ.
- `ChiSoDienNuoc`: chỉ số cũ/mới, lượng dùng, kỳ và phòng.
- `HoaDon`: tiền phòng, điện, nước, dịch vụ, giảm trừ, tổng tiền, hạn và trạng thái.
- `ThanhToan`: hình thức, số tiền, ghi chú, minh chứng và trạng thái xác nhận.

Luồng ghi điện/nước chuẩn:

1. Chủ trọ chọn phòng/kỳ, nhập chỉ số.
2. Server kiểm tra chỉ số và tính theo cấu hình giá.
3. Nút chính là `Tạo hóa đơn`.
4. Hệ thống tạo/cập nhật chỉ số và hóa đơn.
5. Modal tổng hợp tiền phòng, điện, nước, dịch vụ và tổng cộng.
6. Chủ trọ kiểm tra rồi gửi hóa đơn.
7. Người thuê nhận thông báo có liên kết đến tab hóa đơn.

Hóa đơn chờ chủ trọ xác nhận thanh toán phải được đưa lên đầu danh sách phía chủ trọ.

Khi người thuê đóng tiền:

- Server tự tính số tiền còn phải trả; không cho nhập tùy ý.
- Tiền hiển thị có phân tách hàng nghìn, ví dụ `10,000`.
- Tiền mặt chỉ cần ghi chú, ví dụ đã giao trực tiếp.
- Chuyển khoản hiển thị ngân hàng và QR chủ trọ, có nút lưu QR và tải minh chứng.
- Kiểm tra loại tệp và giới hạn hiện hành 5 MB.
- Sau khi gửi, hóa đơn hiển thị `chờ xác nhận thanh toán`; chủ trọ xác nhận hoặc từ chối.

### Lưu trú

`TamTru` gồm loại khai báo và hai nhóm trạng thái độc lập:

- `Loai`: người thuê chỉ được tạo `LuuTru`.
- `TrangThaiXuLy`: chủ trọ tiếp nhận/xác nhận.
- `TrangThaiDangKy`: kết quả đăng ký hành chính do chủ trọ cập nhật.

Luồng chuẩn: người thuê khai báo → chờ chủ trọ xác nhận → chủ trọ xác nhận lưu trú → chủ trọ làm thủ tục và cập nhật `Đã đăng ký với UBND Phường/Xã`. Không dùng cụm `Đã đăng ký tạm trú` thay cho trạng thái này và không cho người thuê tự xác nhận.

### Sự cố, thông báo và quản trị

- `SuCo`: người thuê báo sự cố cho phòng hợp lệ; chủ trọ xử lý trong phạm vi khu của mình.
- `ThongBao`: người nhận, loại, trạng thái đọc và đường dẫn đích. Bấm thông báo phải đánh dấu đã đọc rồi chuyển đúng tab liên quan.
- `AuditLog`: chỉ ghi đăng nhập thành công/thất bại và thay đổi POST/PUT/PATCH/DELETE, kèm người thao tác, đối tượng, IP, thời gian, kết quả; không ghi mọi GET hoặc bí mật.
- `PhienBanUngDung`: lịch sử phiên bản đọc động từ database trong `Về ứng dụng`, tránh làm mất phiên bản đã lưu.

## 5. Quy tắc truy vấn và phân quyền

Chủ trọ phải được kiểm tra quyền theo chuỗi sở hữu:

```text
Dữ liệu nghiệp vụ -> Phòng -> Dãy -> Khu -> Chủ trọ
```

Không tin ID chủ trọ/khu/dãy/phòng client gửi trước khi xác minh. Người thuê được xác định theo cả hai nhánh:

```text
Tài khoản -> Người thuê đứng tên -> Hợp đồng -> Phòng
Tài khoản -> Thành viên phòng -> Hợp đồng/Phòng
```

API hồ sơ, hợp đồng, điện nước, hóa đơn, lưu trú và sự cố phải xét cả hai nhánh khi nghiệp vụ cho phép thành viên. Tài khoản bị khóa không được đăng nhập. Cấp lại mật khẩu phải lưu hash và không trả hash về client.

## 6. Quy ước API

Nhóm API gồm: xác thực/tài khoản; khu-dãy-phòng; người thuê/thành viên/hồ sơ; hợp đồng; cấu hình và chỉ số điện nước; hóa đơn/thanh toán; lưu trú; sự cố; thông báo; audit/tệp/hệ thống/phiên bản.

- JSON trả trạng thái và thông báo rõ ràng.
- HTTP status: 400 dữ liệu sai, 401 chưa đăng nhập, 403 không quyền, 404 không thấy, 409 xung đột, 500 lỗi server.
- Kiểm tra phương thức và vai trò ở server, không chỉ ẩn nút.
- Dùng prepared statement.
- Dùng transaction cho luồng nhiều bảng, nhất là hợp đồng, thành viên, chỉ số/hóa đơn và thanh toán.
- Kiểm tra quyền từng bản ghi trước cập nhật/xóa.
- API lưu trú chỉ trả phòng người thuê thực sự được chọn.
- API hóa đơn tự xác định số tiền thanh toán.
- API hệ thống tính dung lượng dự án so với tối đa 5 GB và trả rõ PHP/SQLite.
- API phiên bản chỉ Admin được thay đổi.
- Endpoint xóa cũ có thể còn để tương thích, nhưng giao diện chủ trọ và quản lý chủ trọ không hiển thị nút xóa.

## 7. Yêu cầu giao diện

### Đăng nhập

- Không hiển thị thông tin tài khoản demo.
- Placeholder là `Nhập tên người dùng`, `Nhập mật khẩu`.
- Có icon con mắt ẩn/hiện mật khẩu.

### Quản lý phòng

- Tìm kiếm tên phòng đặt cạnh nút SVG lọc.
- Nút lọc mở modal chứa khu, dãy, trạng thái trống/đang thuê.
- Có `Áp dụng bộ lọc`, `Xóa lọc`, `Đóng`.
- Các điều khiển cùng hàng và cùng chiều cao.
- Trạng thái được lọc chính xác, không so khớp tương đối.

### Mobile

- Các thẻ thống kê của một tab phải nằm trên một hàng; giảm kích thước thẻ, icon và font.
- Không nhân đôi thẻ khi quay lại tab.
- Modal gọn, không tràn ngang; có thể ẩn cột vai trò và trạng thái đăng ký UBND trong bảng phòng để tiết kiệm chỗ.
- Card hợp đồng dùng sắc xanh lá, hóa đơn xanh dương, điện/nước gần nhất vàng.

## 8. Dữ liệu mẫu chuẩn

Dữ liệu mẫu hiện hành phải logic và chỉ có một chủ trọ:

- Chủ trọ duy nhất: `Lê Thị Thu`.
- Mọi khu, dãy, phòng, người thuê, hợp đồng, chỉ số, hóa đơn, thanh toán, sự cố, lưu trú và tài khoản thuê trọ thuộc chủ trọ này.
- Hai khu trọ, tổng 20 phòng, 16 hợp đồng và thành viên phòng hợp lý.
- Có đủ chỉ số, hóa đơn, thanh toán, thông báo, hồ sơ, sự cố, lưu trú và audit để thử màn hình.
- Phòng trống không có hợp đồng hiệu lực; phòng đang thuê phải có hợp đồng.
- Ngày hợp đồng, kỳ điện nước, hóa đơn, hạn và thanh toán không mâu thuẫn.
- Không có bản ghi mồ côi, dữ liệu rác hoặc chủ trọ thứ hai.
- Mật khẩu tài khoản mẫu là `123456` nếu seed hiện tại không ghi khác.

## 9. Bảo mật

- Dùng `password_hash`/`password_verify`.
- Không ghi mật khẩu, token hoặc dữ liệu tệp nhạy cảm vào log/response.
- Kiểm tra session, vai trò và sở hữu ở mọi API.
- Validate phía server.
- Đổi tên tệp upload an toàn, kiểm tra MIME/phần mở rộng/kích thước.
- Không cho thực thi script trong thư mục upload.
- Kiểm tra quyền trước khi trả tệp.
- Đăng nhập và CRUD phải ghi audit kèm IP.

## 10. Kiểm tra sau thay đổi

1. PHP lint và kiểm tra cú pháp JavaScript đã sửa.
2. Đăng nhập đúng/sai và tài khoản khóa.
3. Menu đúng vai trò; Admin đúng sáu tab, `Về ứng dụng` cuối.
4. Không truy cập chéo chủ trọ.
5. Người đứng tên và thành viên đều xem được hồ sơ/hợp đồng hợp lệ.
6. Chuyển tab không hiện giao diện cũ, không cần refresh, không nhân đôi component.
7. Tạo thành viên và kiểm tra tài khoản/mật khẩu khởi tạo.
8. Ghi điện nước → hóa đơn → modal tổng hợp → gửi thông báo.
9. Thanh toán tiền mặt/chuyển khoản, QR, minh chứng và xác nhận.
10. Khai báo lưu trú → xác nhận → cập nhật UBND Phường/Xã.
11. Báo sự cố và điều hướng thông báo đúng tab.
12. Tìm kiếm, phân trang, nhập số trang và lọc chính xác.
13. Mobile: stat một hàng, modal không tràn.
14. Audit đúng phạm vi và không chứa bí mật.

## 11. Tiêu chí hoàn thành

Thay đổi chỉ hoàn tất khi giao diện và API cùng đúng quyền; dữ liệu đúng phạm vi; thành viên phòng không bị bỏ sót; SPA hoạt động qua nhiều lần chuyển tab; trạng thái/thông báo đúng nghiệp vụ; desktop và mobile dùng được; seed nhất quán; và hai README được cập nhật nếu schema, API, tab, quyền hoặc quy trình thay đổi.

Trước khi viết mã, đọc trang, API, schema và hàm xác thực liên quan. Không suy đoán tên cột/trạng thái từ giao diện. Luôn lần theo toàn bộ chuỗi giao diện → JavaScript → API → database → thông báo/audit và sửa nguyên nhân gốc, không tạo tab hoặc bảng trùng chức năng.
