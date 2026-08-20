<?php
function formatMoney($value): string
{
    $value = (float) $value;
    return number_format($value, 0, ',', '.') . ' ₫';
}

function normalizeString(?string $value): string
{
    return trim((string) $value);
}

function requireFields(array $data, array $fields): void
{
    foreach ($fields as $field) {
        if (!array_key_exists($field, $data) || normalizeString((string) $data[$field]) === '') {
            throw new InvalidArgumentException('Thiếu trường bắt buộc: ' . $field);
        }
    }
}

function getPaginationValues(): array
{
    $page = (int) ($_GET['trang'] ?? 1);
    $limit = (int) ($_GET['gioiHan'] ?? 20);
    if ($page < 1) {
        $page = 1;
    }
    if ($limit < 1) {
        $limit = 20;
    }

    return [$page, $limit];
}

function nowSql(): string
{
    return date('Y-m-d H:i:s');
}

function generateContractPdf(string $outputPath, array $data): string
{
    $dir = dirname($outputPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $lines = [
        'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM',
        'Độc lập – Tự do – Hạnh phúc',
        '',
        'HỢP ĐỒNG THUÊ PHÒNG TRỌ',
        '',
        'Hôm nay, ngày ' . ($data['Ngay'] ?? '...') . ' tháng ' . ($data['Thang'] ?? '...') . ' năm ' . ($data['Nam'] ?? '20..') . ', tại ' . ($data['DiaChi'] ?? '...') . '.',
        '',
        'BÊN CHO THUÊ PHÒNG TRỌ (Bên A):',
        'Ông/bà: ' . ($data['BenA'] ?? 'Chủ nhà'),
        'CMND/CCCD: ' . ($data['CccdA'] ?? '...') . '          Cấp ngày: ' . ($data['CapA'] ?? '...') . '          Nơi cấp: ' . ($data['NoiCapA'] ?? '...'),
        'Thường trú tại: ' . ($data['ThuongTruA'] ?? '...'),
        '',
        'BÊN THUÊ PHÒNG TRỌ (Bên B):',
        'Ông/bà: ' . ($data['BenB'] ?? 'Người thuê'),
        'CMND/CCCD: ' . ($data['CccdB'] ?? '...') . '          Cấp ngày: ' . ($data['CapB'] ?? '...') . '          Nơi cấp: ' . ($data['NoiCapB'] ?? '...'),
        'Thường trú tại: ' . ($data['ThuongTruB'] ?? '...'),
        '',
        'Sau khi thỏa thuận, hai bên thống nhất như sau:',
        '1. Nội dung thuê phòng trọ',
        'Bên A cho Bên B thuê phòng số ' . ($data['SoPhong'] ?? '...') . ' tại ' . ($data['DiaChi'] ?? '...') . '.',
        'Thời hạn: ' . ($data['ThoiHan'] ?? '...') . ' tháng, giá thuê: ' . number_format((float) ($data['GiaThue'] ?? 0), 0, ',', '.') . ' đồng.',
        '',
        '2. Trách nhiệm Bên A',
        '- Đảm bảo căn nhà cho thuê không có tranh chấp, khiếu kiện.',
        '- Đăng ký với chính quyền địa phương về thủ tục cho thuê phòng trọ.',
        '',
        '3. Trách nhiệm Bên B',
        '- Đặt cọc: ' . number_format((float) ($data['TienCoc'] ?? 0), 0, ',', '.') . ' đồng.',
        '- Thanh toán tiền thuê vào ngày ' . ($data['NgayThanhToan'] ?? '...') . ' hằng tháng.',
        '- Giữ gìn an ninh, trật tự, không vi phạm pháp luật.',
        '',
        '4. Điều khoản thực hiện',
        'Hai bên thực hiện nghiêm túc các quy định trên trong thời hạn thuê. Nếu phát sinh tranh chấp, hai bên sẽ giải quyết trên tinh thần hợp tác.',
        '',
        'Bên A                                                        Bên B',
        '(Ký, ghi rõ họ tên)                                  (Ký, ghi rõ họ tên)',
        '',
        'Hợp đồng này chỉ mang tính chất tham khảo.'
    ];

    $content = '';
    $y = 770;
    foreach ($lines as $line) {
        $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
        $safe = iconv('UTF-8', 'windows-1258//IGNORE', $safe);
        $content .= "BT\n/F1 12 Tf\n50 $y Td\n($safe) Tj\nET\n";
        $y -= 18;
    }

    $objects = [];
    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>";
    $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $i => $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
    }

    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        if ($offset === 0) {
            continue;
        }
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";

    file_put_contents($outputPath, $pdf);
    return $outputPath;
}

function getAvatarUrl(?array $user = null): string
{
    // Prefer an explicitly configured avatar when one is available.
    if (!empty($user['Avatar'])) {
        $candidate = __DIR__ . '/../' . ltrim($user['Avatar'], '/');
        if (is_file($candidate) && preg_match('/\.(png|jpe?g|webp)$/i', $candidate)) {
            return '/' . trim(str_replace('\\', '/', $user['Avatar']), '/');
        }
    }

    // Demo accounts use only these portrait assets.  Do not scan the whole
    // directory because it also contains the large logo illustration.
    $avatarFiles = ['a1.jpg', 'a2.png', 'a3.jpg'];
    $avatarFile = $avatarFiles[array_rand($avatarFiles)];
    return '/assets/pics/' . $avatarFile;
}
