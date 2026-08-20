<?php
require __DIR__.'/../includes/db.php';
require __DIR__.'/../includes/auth.php';
require __DIR__.'/../includes/response.php';
require __DIR__.'/../includes/helpers.php';

try {
    requireRole(['admin']);
    $pdo = getDb();
    $method = $_SERVER['REQUEST_METHOD'];
    $id = max(0, (int)($_GET['id'] ?? 0));
    $action = trim((string)($_GET['action'] ?? ''));

    if ($method === 'GET' && $action === 'tep') {
        $stmt = $pdo->prepare('SELECT MinhChung FROM ThanhToan WHERE Id=:id AND IsDeleted=0 AND MinhChung IS NOT NULL');
        $stmt->execute([':id'=>$id]);
        $dataUrl = (string)($stmt->fetchColumn() ?: '');
        if (!preg_match('#^data:(image/(?:png|jpeg|jpg|webp|gif));base64,(.+)$#s', $dataUrl, $match)) errorResponse('Tệp minh chứng không hợp lệ hoặc không còn tồn tại.', 404);
        $binary = base64_decode($match[2], true);
        if ($binary === false) errorResponse('Không thể đọc tệp minh chứng.', 422);
        $extension = $match[1] === 'image/jpeg' || $match[1] === 'image/jpg' ? 'jpg' : substr($match[1], 6);
        header('Content-Type: ' . $match[1]);
        header('Content-Length: ' . strlen($binary));
        header('Content-Disposition: ' . (isset($_GET['tai']) ? 'attachment' : 'inline') . '; filename="minh-chung-' . $id . '.' . $extension . '"');
        echo $binary;
        exit;
    }

    if ($method === 'GET') {
        [$page,$limit] = getPaginationValues();
        $limit = min(100, max(10, $limit));
        $keyword = trim((string)($_GET['tuKhoa'] ?? ''));
        $where = ['tt.IsDeleted=0', "COALESCE(tt.MinhChung,'')<>''"];
        $params = [];
        if ($keyword !== '') {
            $where[] = '(CAST(tt.Id AS TEXT) LIKE :keyword OR tk.TenDangNhap LIKE :keyword OR tk.HoTen LIKE :keyword OR p.SoPhong LIKE :keyword OR k.TenKhu LIKE :keyword)';
            $params[':keyword'] = '%' . $keyword . '%';
        }
        $whereSql = implode(' AND ', $where);
        $joins = ' FROM ThanhToan tt JOIN HoaDon hd ON hd.Id=tt.HoaDonId JOIN HopDong h ON h.Id=hd.HopDongId JOIN Phong p ON p.Id=h.PhongId JOIN Day d ON d.Id=p.DayId JOIN Khu k ON k.Id=d.KhuId LEFT JOIN NguoiThue nt ON nt.Id=h.NguoiThueId LEFT JOIN TaiKhoan tk ON tk.Id=nt.TaiKhoanId ';
        $count = $pdo->prepare('SELECT COUNT(*)' . $joins . 'WHERE ' . $whereSql);
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $limit));
        $page = min(max(1, $page), $totalPages);
        $stmt = $pdo->prepare('SELECT tt.Id,tt.HoaDonId,tt.SoTien,tt.PhuongThuc,tt.TrangThai,tt.NgayThanhToan,tk.TenDangNhap,tk.HoTen,p.SoPhong,d.TenDay,k.TenKhu,length(tt.MinhChung) AS KichThuocMaHoa' . $joins . 'WHERE ' . $whereSql . ' ORDER BY tt.Id DESC LIMIT :limit OFFSET :offset');
        foreach ($params as $key=>$value) $stmt->bindValue($key,$value);
        $stmt->bindValue(':limit',$limit,PDO::PARAM_INT);
        $stmt->bindValue(':offset',($page-1)*$limit,PDO::PARAM_INT);
        $stmt->execute();
        successResponse(['items'=>$stmt->fetchAll(),'pagination'=>['trang'=>$page,'gioiHan'=>$limit,'tong'=>$total,'tongTrang'=>$totalPages]], 'Danh sách tệp minh chứng');
    }

    if ($method === 'DELETE') {
        if ($id < 1) errorResponse('Thiếu mã minh chứng.', 422);
        $stmt = $pdo->prepare("UPDATE ThanhToan SET MinhChung=NULL WHERE Id=:id AND IsDeleted=0 AND COALESCE(MinhChung,'')<>''");
        $stmt->execute([':id'=>$id]);
        if ($stmt->rowCount() < 1) errorResponse('Không tìm thấy minh chứng.', 404);
        successResponse([], 'Đã xóa tệp minh chứng');
    }

    errorResponse('Phương thức không được hỗ trợ.', 405);
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 500);
}
