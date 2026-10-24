<?php
declare(strict_types=1);

// Set application values here or define the corresponding server environment variables.
date_default_timezone_set('Africa/Algiers');

define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_ROOT', APP_ROOT);

// Load environment variables from the .env file when it exists.
$envFile = APP_ROOT . '/.env';
if (file_exists($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            if (getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Detect BASE_URL automatically when it is not configured explicitly.
function detect_base_url(): string
{
    $envUrl = getenv('BASE_URL');
    if ($envUrl !== false && trim($envUrl) !== '') {
        return rtrim(trim($envUrl), '/');
    }

    if (!empty($_SERVER['HTTP_HOST'])) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        $appRoot = rtrim(str_replace('\\', '/', APP_ROOT), '/');
        if ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) {
            $basePath = substr($appRoot, strlen($docRoot));
        } elseif (($pos = strpos($scriptName, '/api/')) !== false || ($pos = strpos($scriptName, '/admin/')) !== false) {
            $basePath = substr($scriptName, 0, $pos);
        } else {
            $basePath = rtrim(dirname($scriptName), '/');
        }

        return rtrim($scheme . '://' . $host . $basePath, '/');
    }

    return 'http://localhost:8000';
}

define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('STYLE_VERSION', getenv('STYLE_VERSION') ?: '2.0.0');
define('BASE_URL', detect_base_url());
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'visionhunt_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('APK_FILE', PUBLIC_ROOT . '/assets/apk/VisionHunt.apk');

// The following lines are commented out to allow for easy switching between development and production environments. Uncomment and adjust as needed for your deployment setup.
// define('APP_ENV', getenv('APP_ENV') ?: 'production');
// define('STYLE_VERSION', getenv('STYLE_VERSION') ?: '2.0.0');
// define('BASE_URL', detect_base_url());
// define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
// define('DB_PORT', getenv('DB_PORT') ?: '3306');
// define('DB_NAME', getenv('DB_NAME') ?: 'ofzdtjusrx_visionhunt');
// define('DB_USER', getenv('DB_USER') ?: 'ofzdtjusrx_sohaibkhiaf');
// define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'KEZ[;z{H.$C7^#nE');
// define('APK_FILE', PUBLIC_ROOT . '/assets/apk/VisionHunt.apk');
