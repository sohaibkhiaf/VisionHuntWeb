<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!is_file(APK_FILE) || !is_readable(APK_FILE)) {
    http_response_code(404);
    $pageTitle = 'التطبيق غير متوفر';
    require APP_ROOT . '/includes/header.php';
    echo '<section class="empty-state card-spacing">'
        . '<span class="empty-icon">↓</span>'
        . '<h1>ملف APK غير مرفوع بعد</h1>'
        . '<p>ضع نسخة التطبيق النهائية في <code>assets/apk/VisionHunt.apk</code> ثم أعد المحاولة.</p>'
        . '<a class="button button-primary" href="' . e(url('/')) . '">العودة إلى الترتيب</a>'
        . '</section>';
    require APP_ROOT . '/includes/footer.php';
    exit;
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

$versionSuffix = defined('APP_VERSION') && trim((string)APP_VERSION) !== ''
    ? '-' . preg_replace('/[^A-Za-z0-9._-]/', '', (string)APP_VERSION)
    : '';

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="VisionHunt' . $versionSuffix . '.apk"');
header('Content-Length: ' . filesize(APK_FILE));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
readfile(APK_FILE);
exit;
