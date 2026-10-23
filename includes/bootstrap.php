<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once APP_ROOT . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('visionhunt_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
