<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once APP_ROOT . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionLifetime = 30 * 24 * 60 * 60; // 30 days (1 month)
    ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
    ini_set('session.cookie_lifetime', (string)$sessionLifetime);
    session_name('visionhunt_admin');
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
