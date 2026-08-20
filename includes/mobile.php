<?php
function redirectMobileRole(array $user, string $mobileTarget): void
{
    $isMobile = preg_match('/Android|iPhone|iPad|iPod|Mobile/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($isMobile && in_array($user['VaiTro'] ?? '', ['chutro', 'nguoithue'], true)) {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Location: ' . $mobileTarget, true, 302);
        exit;
    }
}
