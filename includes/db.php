<?php
require_once __DIR__ . '/demo_seed.php';
function getDb(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbFile = __DIR__ . '/../data/du_lieu.db';
    $dir = dirname($dbFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    initDatabase($pdo);

    return $pdo;
}

function initDatabase(PDO $pdo): void
{
    $statements = [
        "CREATE TABLE IF NOT EXISTS TaiKhoan (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            TenDangNhap TEXT NOT NULL UNIQUE,
            MatKhau TEXT NOT NULL,
            HoTen TEXT NOT NULL,
            Email TEXT,
            SoDienThoai TEXT,
            VaiTro TEXT NOT NULL CHECK(VaiTro IN ('admin','chutro','nguoithue')),
            TrangThai INTEGER NOT NULL DEFAULT 1,
            NgayTao TEXT DEFAULT (datetime('now','localtime'))
        );",

        "CREATE TABLE IF NOT EXISTS Khu (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            TenKhu TEXT NOT NULL UNIQUE,
            DiaChi TEXT,
            MoTa TEXT,
            TaiKhoanId INTEGER REFERENCES TaiKhoan(Id),
            NgayTao TEXT DEFAULT (datetime('now','localtime'))
        );",

        "CREATE TABLE IF NOT EXISTS Day (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            KhuId INTEGER NOT NULL REFERENCES Khu(Id) ON DELETE CASCADE,
            TenDay TEXT NOT NULL,
            MoTa TEXT,
            UNIQUE(KhuId, TenDay)
        );",

        "CREATE TABLE IF NOT EXISTS Phong (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            DayId INTEGER NOT NULL REFERENCES Day(Id) ON DELETE CASCADE,
            SoPhong TEXT NOT NULL,
            DienTich REAL,
            GiaThue INTEGER NOT NULL DEFAULT 0,
            TrangThai TEXT NOT NULL DEFAULT 'Trong' CHECK(TrangThai IN ('Trong','DangThue','BaoTri')),
            MoTa TEXT,
            NgayTao TEXT DEFAULT (datetime('now', 'localtime')),
            UNIQUE(DayId, SoPhong)
        );",

        "CREATE TABLE IF NOT EXISTS NguoiThue (
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
        );",

        "CREATE TABLE IF NOT EXISTS HopDong (
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
            TrangThai TEXT NOT NULL DEFAULT 'DangHieuLuc' CHECK(TrangThai IN ('DangHieuLuc','HetHan','DaChamDut','GiaHan')),
            NgayTao TEXT DEFAULT (datetime('now', 'localtime'))
        );",

        "CREATE TABLE IF NOT EXISTS ChiSoDienNuoc (
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
        );",

        "CREATE TABLE IF NOT EXISTS CauHinhDienNuoc (
            TaiKhoanId INTEGER PRIMARY KEY REFERENCES TaiKhoan(Id),
            DonGiaDien INTEGER NOT NULL DEFAULT 0,
            DonGiaNuoc INTEGER NOT NULL DEFAULT 0,
            NgayCapNhat TEXT DEFAULT (datetime('now', 'localtime'))
        );",

        "CREATE TABLE IF NOT EXISTS HoaDon (
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
        );",

        "CREATE TABLE IF NOT EXISTS ThanhToan (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            HoaDonId INTEGER NOT NULL REFERENCES HoaDon(Id),
            SoTien INTEGER NOT NULL CHECK(SoTien > 0),
            PhuongThuc TEXT NOT NULL CHECK(PhuongThuc IN ('TienMat','ChuyenKhoan','QRCode')),
            GhiChu TEXT,
            NgayThanhToan TEXT DEFAULT CURRENT_TIMESTAMP,
            NguoiThu INTEGER REFERENCES TaiKhoan(Id)
        );",

        "CREATE TABLE IF NOT EXISTS ThanhVienPhong (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            HopDongId INTEGER NOT NULL REFERENCES HopDong(Id) ON DELETE CASCADE,
            HoTen TEXT NOT NULL,
            CCCD TEXT,
            NgaySinh TEXT,
            SoDienThoai TEXT,
            QuanHe TEXT,
            Loai TEXT NOT NULL DEFAULT 'ThanhVienPhong' CHECK(Loai IN ('ThanhVienPhong','LuuTru')),
            TaiKhoanId INTEGER REFERENCES TaiKhoan(Id)
        );",

        "CREATE TABLE IF NOT EXISTS ThongBao (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            TaiKhoanId INTEGER NOT NULL REFERENCES TaiKhoan(Id) ON DELETE CASCADE,
            TieuDe TEXT NOT NULL,
            NoiDung TEXT NOT NULL,
            Loai TEXT NOT NULL DEFAULT 'ThongTin' CHECK(Loai IN ('ThongTin','DienNuoc','HoaDon')),
            DaDoc INTEGER NOT NULL DEFAULT 0 CHECK(DaDoc IN (0,1)),
            NgayTao TEXT DEFAULT (datetime('now','localtime'))
        );",

        "CREATE TABLE IF NOT EXISTS TamTru (
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
        );"
        ,"CREATE TABLE IF NOT EXISTS YeuCauHoSoNguoiThue (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            NguoiThueId INTEGER NOT NULL REFERENCES NguoiThue(Id),
            Loai TEXT NOT NULL CHECK(Loai IN ('HoSo','ThongTinCaNhan')),
            DuLieu TEXT NOT NULL,
            TrangThai TEXT NOT NULL DEFAULT 'ChoXacNhan' CHECK(TrangThai IN ('ChoXacNhan','DaXacNhan','TuChoi')),
            GhiChuChuTro TEXT,
            NguoiXuLyId INTEGER REFERENCES TaiKhoan(Id),
            NgayTao TEXT DEFAULT (datetime('now','localtime')),
            NgayXuLy TEXT,
            IsDeleted INTEGER NOT NULL DEFAULT 0
        );"
        ,"CREATE TABLE IF NOT EXISTS SuCo (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            PhongId INTEGER NOT NULL REFERENCES Phong(Id),
            NguoiBaoId INTEGER NOT NULL REFERENCES TaiKhoan(Id),
            TieuDe TEXT NOT NULL,
            NoiDung TEXT NOT NULL,
            AnhDinhKem TEXT,
            TrangThai TEXT NOT NULL DEFAULT 'Moi' CHECK(TrangThai IN ('Moi','DaTiepNhan','DaKhacPhuc')),
            NguoiXuLyId INTEGER REFERENCES TaiKhoan(Id),
            NgayTao TEXT DEFAULT (datetime('now','localtime')),
            NgayCapNhat TEXT,
            IsDeleted INTEGER NOT NULL DEFAULT 0
        );"
        ,"CREATE TABLE IF NOT EXISTS AuditLog (
            Id INTEGER PRIMARY KEY AUTOINCREMENT,
            TaiKhoanId INTEGER REFERENCES TaiKhoan(Id),
            TenDangNhap TEXT,
            HoTen TEXT,
            VaiTro TEXT,
            HanhDong TEXT NOT NULL,
            PhuongThuc TEXT NOT NULL,
            DuongDan TEXT NOT NULL,
            QueryString TEXT,
            DuLieu TEXT,
            DiaChiIP TEXT,
            UserAgent TEXT,
            MaTrangThai INTEGER,
            ThanhCong INTEGER NOT NULL DEFAULT 1 CHECK(ThanhCong IN (0,1)),
            NgayTao TEXT DEFAULT (datetime('now','localtime'))
        );",
        "CREATE INDEX IF NOT EXISTS IX_AuditLog_NgayTao ON AuditLog(NgayTao)",
        "CREATE INDEX IF NOT EXISTS IX_AuditLog_TaiKhoanId ON AuditLog(TaiKhoanId)",
        "CREATE INDEX IF NOT EXISTS IX_AuditLog_VaiTro_PhuongThuc ON AuditLog(VaiTro, PhuongThuc)"
    ];

    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }

    foreach ([
        "ALTER TABLE TamTru ADD COLUMN Loai TEXT DEFAULT 'TamTru' CHECK(Loai IN ('TamTru', 'LuuTru'))",
        "ALTER TABLE HopDong ADD COLUMN FileHopDong TEXT",
        "ALTER TABLE HopDong ADD COLUMN SoHopDong TEXT",
        "ALTER TABLE HopDong ADD COLUMN BenAHoTen TEXT",
        "ALTER TABLE HopDong ADD COLUMN BenANgaySinh TEXT",
        "ALTER TABLE HopDong ADD COLUMN BenADiaChi TEXT",
        "ALTER TABLE HopDong ADD COLUMN BenACCCD TEXT",
        "ALTER TABLE HopDong ADD COLUMN BenASoDienThoai TEXT",
        "ALTER TABLE HopDong ADD COLUMN DonGiaDien INTEGER NOT NULL DEFAULT 0",
        "ALTER TABLE HopDong ADD COLUMN DonGiaNuoc INTEGER NOT NULL DEFAULT 0",
        "ALTER TABLE HopDong ADD COLUMN ChiSoDienDau REAL NOT NULL DEFAULT 0",
        "ALTER TABLE HopDong ADD COLUMN ChiSoNuocDau REAL NOT NULL DEFAULT 0",
        "ALTER TABLE ThanhVienPhong ADD COLUMN NgaySinh TEXT",
        "ALTER TABLE ThanhVienPhong ADD COLUMN TaiKhoanId INTEGER REFERENCES TaiKhoan(Id)",
        "ALTER TABLE ThanhVienPhong ADD COLUMN Loai TEXT NOT NULL DEFAULT 'ThanhVienPhong' CHECK(Loai IN ('ThanhVienPhong','LuuTru'))",
        "ALTER TABLE Khu ADD COLUMN TaiKhoanId INTEGER REFERENCES TaiKhoan(Id)"
        ,"ALTER TABLE TaiKhoan ADD COLUMN IsDeleted INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE Khu ADD COLUMN IsDeleted INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE Day ADD COLUMN IsDeleted INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE Phong ADD COLUMN IsDeleted INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE NguoiThue ADD COLUMN IsDeleted INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE HopDong ADD COLUMN IsDeleted INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE ChiSoDienNuoc ADD COLUMN IsDeleted INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE HoaDon ADD COLUMN IsDeleted INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE ThanhToan ADD COLUMN IsDeleted INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE ThanhVienPhong ADD COLUMN IsDeleted INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE ThongBao ADD COLUMN IsDeleted INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE TamTru ADD COLUMN IsDeleted INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE NguoiThue ADD COLUMN AnhChanDung TEXT"
        ,"ALTER TABLE NguoiThue ADD COLUMN AnhCCCDMatTruoc TEXT"
        ,"ALTER TABLE NguoiThue ADD COLUMN AnhCCCDMatSau TEXT"
        ,"ALTER TABLE NguoiThue ADD COLUMN VNeIDMuc2 TEXT"
        ,"ALTER TABLE ThanhVienPhong ADD COLUMN TrangThaiTamTru TEXT NOT NULL DEFAULT 'ChuaDangKyTamTru'"
        ,"ALTER TABLE ThanhVienPhong ADD COLUMN GioiTinh TEXT"
        ,"ALTER TABLE ThanhVienPhong ADD COLUMN Email TEXT"
        ,"ALTER TABLE ThanhVienPhong ADD COLUMN DiaChiThuongTru TEXT"
        ,"ALTER TABLE ThanhVienPhong ADD COLUMN NgheNghiep TEXT"
        ,"ALTER TABLE ThanhVienPhong ADD COLUMN AnhChanDung TEXT"
        ,"ALTER TABLE ThanhVienPhong ADD COLUMN AnhCCCDMatTruoc TEXT"
        ,"ALTER TABLE ThanhVienPhong ADD COLUMN AnhCCCDMatSau TEXT"
        ,"ALTER TABLE ThanhVienPhong ADD COLUMN VNeIDMuc2 TEXT"
        ,"ALTER TABLE TamTru ADD COLUMN TrangThaiXuLy TEXT NOT NULL DEFAULT 'DaXacNhanChuTro'"
        ,"ALTER TABLE TamTru ADD COLUMN TrangThaiDangKy TEXT NOT NULL DEFAULT 'ChuaKhaiBaoUBND'"
        ,"ALTER TABLE HoaDon ADD COLUMN HanThanhToan TEXT"
        ,"ALTER TABLE ThanhToan ADD COLUMN TrangThai TEXT NOT NULL DEFAULT 'ChoXacNhan'"
        ,"ALTER TABLE ThanhToan ADD COLUMN MinhChung TEXT"
        ,"ALTER TABLE TaiKhoan ADD COLUMN ThongTinChuyenKhoan TEXT"
        ,"ALTER TABLE TaiKhoan ADD COLUMN MaQRThanhToan TEXT"
        ,"ALTER TABLE ChiSoDienNuoc ADD COLUMN TienDichVu INTEGER NOT NULL DEFAULT 0"
        ,"ALTER TABLE ChiSoDienNuoc ADD COLUMN GhiChu TEXT"
    ] as $alter) {
        try {
            $pdo->exec($alter);
        } catch (Throwable $e) {
            // ignore if the column already exists
        }
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM TaiKhoan')->fetchColumn();
    $demoPassword = '123456';
    $demoHash = password_hash($demoPassword, PASSWORD_BCRYPT);

    foreach (['admin', 'nguoithue'] as $username) {
        $stmt = $pdo->prepare('SELECT Id, MatKhau FROM TaiKhoan WHERE TenDangNhap = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && (!password_verify($demoPassword, $user['MatKhau']) || $user['MatKhau'] === $demoPassword)) {
            $pdo->prepare('UPDATE TaiKhoan SET MatKhau = :p WHERE Id = :id')->execute([
                ':p' => $demoHash,
                ':id' => $user['Id'],
            ]);
        }
    }

    if ($count === 0) {
        seedDemoData($pdo);
        return;
    }

    // Kept only as historical reference; fresh databases use seedDemoData().
    if (false && $count === 0) {
        $ownerHash = password_hash('Thu@123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO TaiKhoan (TenDangNhap, MatKhau, HoTen, Email, SoDienThoai, VaiTro, TrangThai, NgayTao) VALUES
            ('admin', '{$demoHash}', 'Quản trị viên', 'admin@duan.com', '0900000000', 'admin', 1, datetime('now','localtime')),
            ('chutro', '{$ownerHash}', 'Lê Thị Thu', 'lethithu@gmail.com', '0123456789', 'chutro', 1, '2026-08-16 09:11:00'),
            ('nguoithue', '{$demoHash}', 'Người thuê demo', 'nguoithue@duan.com', '0922222222', 'nguoithue', 1, datetime('now','localtime'))");

        $pdo->exec("INSERT INTO Khu (TenKhu, DiaChi, MoTa) VALUES
            ('Khu A', 'Đồng Nai', 'Khu nhà trọ chính'),
            ('Khu B', 'Long Khánh', 'Khu căn hộ mini')");

        $pdo->exec("UPDATE Khu SET TaiKhoanId = 2 WHERE TaiKhoanId IS NULL");

        $pdo->exec("INSERT INTO Day (KhuId, TenDay, MoTa) VALUES
            (1, 'Dãy 1', 'Phòng cho sinh viên'),
            (1, 'Dãy 2', 'Phòng cao cấp'),
            (2, 'Dãy 3', 'Căn hộ mini')");

        $pdo->exec("INSERT INTO Phong (DayId, SoPhong, DienTich, GiaThue, TrangThai, MoTa) VALUES
            (1, 'A101', 22.5, 3500000, 'DangThue', 'Phòng 1 người, có gác'),
            (1, 'A102', 25.0, 3800000, 'Trong', 'Có máy lạnh'),
            (2, 'B201', 30.0, 4500000, 'DangThue', 'Phòng đôi, cửa sổ lớn'),
            (3, 'C301', 28.5, 4200000, 'BaoTri', 'Đang sửa chữa')");

        $pdo->exec("INSERT INTO NguoiThue (HoTen, CCCD, NgaySinh, GioiTinh, SoDienThoai, Email, DiaChiThuongTru, NgheNghiep, TaiKhoanId) VALUES
            ('Nguyễn Văn A', '012345678901', '2002-05-10', 'Nam', '0987654321', 'a@email.com', 'Đồng Nai', 'Sinh viên', 3)");

        $pdo->exec("INSERT INTO HopDong (PhongId, NguoiThueId, NgayBatDau, NgayKetThuc, TienCoc, GiaThue, DieuKhoan, TrangThai) VALUES
            (1, 1, '2026-01-01', '2026-12-31', 7000000, 3500000, 'Thu tiền đúng hạn', 'DangHieuLuc')");

        $pdo->exec("INSERT INTO ChiSoDienNuoc (PhongId, Thang, Nam, ChiSoDienDau, ChiSoDienCuoi, DonGiaDien, ChiSoNuocDau, ChiSoNuocCuoi, DonGiaNuoc) VALUES
            (1, 8, 2026, 150, 250, 3500, 20, 28, 20000)");

        $pdo->exec("INSERT INTO HoaDon (HopDongId, Thang, Nam, TienPhong, TienDien, TienNuoc, TienDichVu, TongTien, DaTra, TrangThai) VALUES
            (1, 8, 2026, 3500000, 350000, 160000, 200000, 4210000, 3000000, 'ThanhToanMotPhan')");

        $pdo->exec("INSERT INTO TamTru (PhongId, NguoiThueId, HoTen, CCCDKhach, QuanHe, NgayBatDau, NgayKetThuc, GhiChu, TrangThai) VALUES
            (1, 1, 'Khách lưu trú A', '123456789012', 'Bạn bè', '2026-08-10', '2026-08-20', 'Lưu trú ngắn hạn', 0)");
    }
}
