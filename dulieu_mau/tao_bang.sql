PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS TaiKhoan (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    TenDangNhap TEXT NOT NULL UNIQUE,
    MatKhau TEXT NOT NULL,
    HoTen TEXT NOT NULL,
    Email TEXT,
    SoDienThoai TEXT,
    VaiTro TEXT NOT NULL CHECK(VaiTro IN ('admin','chutro','nguoithue')),
    TrangThai INTEGER NOT NULL DEFAULT 1,
    NgayTao TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS Khu (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    TenKhu TEXT NOT NULL UNIQUE,
    DiaChi TEXT,
    MoTa TEXT,
    NgayTao TEXT DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS Day (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    KhuId INTEGER NOT NULL REFERENCES Khu(Id) ON DELETE CASCADE,
    TenDay TEXT NOT NULL,
    MoTa TEXT,
    UNIQUE(KhuId, TenDay)
);

CREATE TABLE IF NOT EXISTS Phong (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    DayId INTEGER NOT NULL REFERENCES Day(Id) ON DELETE CASCADE,
    SoPhong TEXT NOT NULL,
    DienTich REAL,
    GiaThue INTEGER NOT NULL DEFAULT 0,
    TrangThai TEXT NOT NULL DEFAULT 'Trong' CHECK(TrangThai IN ('Trong','DangThue','BaoTri')),
    MoTa TEXT,
    NgayTao TEXT DEFAULT (datetime('now', 'localtime')),
    UNIQUE(DayId, SoPhong)
);

CREATE TABLE IF NOT EXISTS NguoiThue (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    HoTen TEXT NOT NULL,
    CCCD TEXT NOT NULL UNIQUE,
    NgaySinh TEXT,
    GioiTinh TEXT,
    SoDienThoai TEXT,
    Email TEXT,
    DiaChiThuongTru TEXT,
    NgheNghiep TEXT,
    TaiKhoanId INTEGER REFERENCES TaiKhoan(Id)
);

CREATE TABLE IF NOT EXISTS HopDong (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    PhongId INTEGER NOT NULL REFERENCES Phong(Id),
    NguoiThueId INTEGER NOT NULL REFERENCES NguoiThue(Id),
    NgayBatDau TEXT NOT NULL,
    NgayKetThuc TEXT NOT NULL,
    TienCoc INTEGER NOT NULL DEFAULT 0,
    GiaThue INTEGER NOT NULL,
    DieuKhoan TEXT,
    SoHopDong TEXT,
    BenAHoTen TEXT,
    BenANgaySinh TEXT,
    BenADiaChi TEXT,
    BenACCCD TEXT,
    BenASoDienThoai TEXT,
    DonGiaDien INTEGER NOT NULL DEFAULT 0,
    DonGiaNuoc INTEGER NOT NULL DEFAULT 0,
    ChiSoDienDau REAL NOT NULL DEFAULT 0,
    ChiSoNuocDau REAL NOT NULL DEFAULT 0,
    FileHopDong TEXT,
    TrangThai TEXT NOT NULL DEFAULT 'DangHieuLuc' CHECK(TrangThai IN ('DangHieuLuc','HetHan','DaChamDut','GiaHan')),
    NgayTao TEXT DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE IF NOT EXISTS ChiSoDienNuoc (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    PhongId INTEGER NOT NULL REFERENCES Phong(Id),
    Thang INTEGER NOT NULL CHECK(Thang BETWEEN 1 AND 12),
    Nam INTEGER NOT NULL,
    ChiSoDienDau REAL NOT NULL DEFAULT 0,
    ChiSoDienCuoi REAL NOT NULL DEFAULT 0,
    DonGiaDien INTEGER NOT NULL DEFAULT 0,
    ChiSoNuocDau REAL NOT NULL DEFAULT 0,
    ChiSoNuocCuoi REAL NOT NULL DEFAULT 0,
    DonGiaNuoc INTEGER NOT NULL DEFAULT 0,
    NgayGhi TEXT DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(PhongId, Thang, Nam)
);

CREATE TABLE IF NOT EXISTS HoaDon (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    HopDongId INTEGER NOT NULL REFERENCES HopDong(Id),
    Thang INTEGER NOT NULL,
    Nam INTEGER NOT NULL,
    TienPhong INTEGER NOT NULL DEFAULT 0,
    TienDien INTEGER NOT NULL DEFAULT 0,
    TienNuoc INTEGER NOT NULL DEFAULT 0,
    TienDichVu INTEGER NOT NULL DEFAULT 0,
    TongTien INTEGER NOT NULL DEFAULT 0,
    DaTra INTEGER NOT NULL DEFAULT 0,
    TrangThai TEXT NOT NULL DEFAULT 'ChuaThanhToan' CHECK(TrangThai IN ('ChuaThanhToan','ThanhToanMotPhan','DaThanhToan')),
    NgayTao TEXT DEFAULT (datetime('now', 'localtime')),
    UNIQUE(HopDongId, Thang, Nam)
);

CREATE TABLE IF NOT EXISTS ThanhToan (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    HoaDonId INTEGER NOT NULL REFERENCES HoaDon(Id),
    SoTien INTEGER NOT NULL CHECK(SoTien > 0),
    PhuongThuc TEXT NOT NULL CHECK(PhuongThuc IN ('TienMat','ChuyenKhoan','QRCode')),
    GhiChu TEXT,
    NgayThanhToan TEXT DEFAULT CURRENT_TIMESTAMP,
    NguoiThu INTEGER REFERENCES TaiKhoan(Id)
);

CREATE TABLE IF NOT EXISTS ThanhVienPhong (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    HopDongId INTEGER NOT NULL REFERENCES HopDong(Id) ON DELETE CASCADE,
    HoTen TEXT NOT NULL,
    CCCD TEXT,
    NgaySinh TEXT,
    SoDienThoai TEXT,
    QuanHe TEXT,
    Loai TEXT NOT NULL DEFAULT 'ThanhVienPhong' CHECK(Loai IN ('ThanhVienPhong','LuuTru')),
    TaiKhoanId INTEGER REFERENCES TaiKhoan(Id)
);

CREATE TABLE IF NOT EXISTS TamTru (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    PhongId INTEGER NOT NULL REFERENCES Phong(Id),
    NguoiThueId INTEGER REFERENCES NguoiThue(Id),
    HoTen TEXT NOT NULL,
    CCCDKhach TEXT NOT NULL,
    QuanHe TEXT NOT NULL,
    NgayBatDau TEXT NOT NULL,
    NgayKetThuc TEXT NOT NULL,
    GhiChu TEXT,
    TrangThai INTEGER NOT NULL DEFAULT 0 CHECK(TrangThai IN (0,1)),
    NgayTao TEXT DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE IF NOT EXISTS ThongBao (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    TaiKhoanId INTEGER NOT NULL REFERENCES TaiKhoan(Id) ON DELETE CASCADE,
    TieuDe TEXT NOT NULL,
    NoiDung TEXT NOT NULL,
    Loai TEXT NOT NULL DEFAULT 'ThongTin' CHECK(Loai IN ('ThongTin','DienNuoc','HoaDon')),
    DaDoc INTEGER NOT NULL DEFAULT 0 CHECK(DaDoc IN (0,1)),
    NgayTao TEXT DEFAULT (datetime('now','localtime'))
);

CREATE INDEX IF NOT EXISTS idx_phong_trangthai ON Phong(TrangThai);
CREATE INDEX IF NOT EXISTS idx_hopdong_phong ON HopDong(PhongId);
CREATE INDEX IF NOT EXISTS idx_hoadon_hopdong ON HoaDon(HopDongId);
CREATE INDEX IF NOT EXISTS idx_hoadon_trangthai ON HoaDon(TrangThai);
CREATE INDEX IF NOT EXISTS idx_tamtru_phong ON TamTru(PhongId);
