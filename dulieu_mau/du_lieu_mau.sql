INSERT INTO TaiKhoan (TenDangNhap, MatKhau, HoTen, Email, SoDienThoai, VaiTro, TrangThai) VALUES
('admin', '$2y$10$yrx9L5B3E7qtojIEY0D1eO79aD8Q0DkO12S/Q4bUsjbKQ6kCSjYWe', 'Quản trị viên', 'admin@demo.com', '0900000000', 'admin', 1),
('chutro', 'Thu@123', 'Lê Thị Thu', 'lethithu@gmail.com', '0123456789', 'chutro', 1),
('nguoithue', '$2y$10$yrx9L5B3E7qtojIEY0D1eO79aD8Q0DkO12S/Q4bUsjbKQ6kCSjYWe', 'Người thuê demo', 'nguoithue@demo.com', '0922222222', 'nguoithue', 1);

INSERT INTO Khu (TenKhu, DiaChi, MoTa) VALUES
('Khu A', 'Đồng Nai', 'Khu nhà trọ chính'),
('Khu B', 'Long Khánh', 'Khu căn hộ mini');

INSERT INTO Day (KhuId, TenDay, MoTa) VALUES
(1, 'Dãy 1', 'Phòng sinh viên'),
(1, 'Dãy 2', 'Phòng trẻ lập nghiệp'),
(2, 'Dãy 3', 'Căn hộ mini');

INSERT INTO Phong (DayId, SoPhong, DienTich, GiaThue, TrangThai, MoTa) VALUES
(1, 'A101', 22.5, 3500000, 'DangThue', 'Phòng 1 người'),
(1, 'A102', 25, 3800000, 'Trong', 'Phòng có máy lạnh'),
(2, 'B201', 30, 4500000, 'DangThue', 'Phòng có gác lửng'),
(3, 'C301', 28.5, 4200000, 'BaoTri', 'Đang sửa chữa');

INSERT INTO NguoiThue (HoTen, CCCD, NgaySinh, GioiTinh, SoDienThoai, Email, DiaChiThuongTru, NgheNghiep, TaiKhoanId) VALUES
('Nguyễn Văn A', '012345678901', '2002-05-10', 'Nam', '0987654321', 'a@email.com', 'Đồng Nai', 'Sinh viên', 3);

INSERT INTO HopDong (PhongId, NguoiThueId, NgayBatDau, NgayKetThuc, TienCoc, GiaThue, DieuKhoan, TrangThai) VALUES
(1, 1, '2026-01-01', '2026-12-31', 7000000, 3500000, 'Thu tiền đúng hạn', 'DangHieuLuc');

INSERT INTO ChiSoDienNuoc (PhongId, Thang, Nam, ChiSoDienDau, ChiSoDienCuoi, DonGiaDien, ChiSoNuocDau, ChiSoNuocCuoi, DonGiaNuoc) VALUES
(1, 8, 2026, 150, 250, 3500, 20, 28, 20000);

INSERT INTO HoaDon (HopDongId, Thang, Nam, TienPhong, TienDien, TienNuoc, TienDichVu, TongTien, DaTra, TrangThai) VALUES
(1, 8, 2026, 3500000, 350000, 160000, 200000, 4210000, 3000000, 'ThanhToanMotPhan');

INSERT INTO TamTru (PhongId, NguoiThueId, HoTen, CCCDKhach, QuanHe, NgayBatDau, NgayKetThuc, GhiChu, TrangThai) VALUES
(1, 1, 'Khách lưu trú A', '123456789012', 'Bạn bè', '2026-08-10', '2026-08-20', 'Lưu trú ngắn hạn', 0);
