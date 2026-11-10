<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
api_method('GET');

$latestVersion = trim((string)(defined('APP_VERSION') ? APP_VERSION : '1.0.0'));
$clientVersionRaw = $_GET['current_version'] ?? $_GET['version'] ?? null;
$clientVersion = is_string($clientVersionRaw) ? trim($clientVersionRaw) : null;
if ($clientVersion === '') {
    $clientVersion = null;
}

$apkExists = is_file(APK_FILE) && is_readable(APK_FILE);
$apkSize = $apkExists ? filesize(APK_FILE) : null;
if ($apkSize === false) {
    $apkSize = null;
}

$updateAvailable = null;
if ($clientVersion !== null) {
    $cleanClient = ltrim($clientVersion, 'vV');
    $cleanLatest = ltrim($latestVersion, 'vV');
    if ($cleanClient !== '' && $cleanLatest !== '' && preg_match('/^\d+(\.\d+)*$/', $cleanClient) && preg_match('/^\d+(\.\d+)*$/', $cleanLatest)) {
        $updateAvailable = version_compare($cleanClient, $cleanLatest, '<');
    } else {
        $updateAvailable = $clientVersion !== $latestVersion;
    }
}

$downloadUrl = $apkExists
    ? url('/download_apk.php') . '?v=' . rawurlencode($latestVersion)
    : null;

$message = match (true) {
    !$apkExists => 'ملف التحديث غير متوفر حاليًا على الخادم.',
    $updateAvailable === true => 'يتوفر إصدار جديد من التطبيق للتحميل.',
    $updateAvailable === false => 'أنت تستخدم أحدث إصدار من التطبيق.',
    default => 'تم جلب معلومات إصدار التطبيق بنجاح.',
};

api_json([
    'success' => true,
    'message' => $message,
    'data' => [
        'version' => $latestVersion,
        'current_version' => $clientVersion,
        'update_available' => $updateAvailable !== null ? ($updateAvailable && $apkExists) : $apkExists,
        'apk_available' => $apkExists,
        'apk_size' => $apkSize,
        'download_url' => $downloadUrl,
    ],
]);

