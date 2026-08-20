<?php
require __DIR__.'/../includes/db.php';
require __DIR__.'/../includes/auth.php';
require __DIR__.'/../includes/response.php';

function projectDirectorySize(string $root): int
{
    $size = 0;
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && !$file->isLink()) $size += $file->getSize();
        }
    } catch (Throwable $ignored) {
        // Return the size collected so far when the host denies a directory.
    }
    return $size;
}

try {
    requireRole(['admin']);
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') errorResponse('Chỉ hỗ trợ xem thông tin hệ thống.', 405);
    $pdo = getDb();
    $dbFile = __DIR__.'/../data/du_lieu.db';
    clearstatcache(true, $dbFile);
    $dbSize = is_file($dbFile) ? (int)filesize($dbFile) : 0;
    $pageSize = (int)$pdo->query('PRAGMA page_size')->fetchColumn();
    $pageCount = (int)$pdo->query('PRAGMA page_count')->fetchColumn();
    $freePages = (int)$pdo->query('PRAGMA freelist_count')->fetchColumn();
    $hostingQuota = 5 * 1024 * 1024 * 1024;
    $projectUsed = projectDirectorySize(realpath(__DIR__.'/..') ?: dirname(__DIR__));
    $hostingFree = max(0, $hostingQuota - $projectUsed);
    $tableCount = (int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchColumn();
    $indexCount = (int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='index' AND name NOT LIKE 'sqlite_%'")->fetchColumn();

    successResponse([
        'php'=>[
            'version'=>PHP_VERSION,
            'sapi'=>PHP_SAPI,
            'architecture'=>PHP_INT_SIZE * 8 . '-bit',
            'pdoSqlite'=>extension_loaded('pdo_sqlite'),
            'uploadMax'=>ini_get('upload_max_filesize'),
            'postMax'=>ini_get('post_max_size'),
            'memoryLimit'=>ini_get('memory_limit')
        ],
        'database'=>[
            'engine'=>'SQLite',
            'version'=>(string)$pdo->query('SELECT sqlite_version()')->fetchColumn(),
            'schemaVersion'=>(int)$pdo->query('PRAGMA user_version')->fetchColumn(),
            'size'=>$dbSize,
            'pageSize'=>$pageSize,
            'pageCount'=>$pageCount,
            'freePages'=>$freePages,
            'internalFree'=>$freePages * $pageSize,
            'tableCount'=>$tableCount,
            'indexCount'=>$indexCount
        ],
        'storage'=>[
            'provider'=>'InfinityFree',
            'total'=>$hostingQuota,
            'used'=>$projectUsed,
            'free'=>$hostingFree,
            'usedPercent'=>round(($projectUsed / $hostingQuota) * 100, 2),
            'basis'=>'Tổng dung lượng tệp trong dự án so với hạn mức cấu hình 5 GB'
        ],
        'checkedAt'=>date('Y-m-d H:i:s')
    ], 'Thông tin hệ thống');
} catch (Throwable $e) {
    errorResponse($e->getMessage(), 500);
}
