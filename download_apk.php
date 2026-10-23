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

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="VisionHunt.apk"');
header('Content-Length: ' . filesize(APK_FILE));
header('X-Content-Type-Options: nosniff');
readfile(APK_FILE);
exit;
