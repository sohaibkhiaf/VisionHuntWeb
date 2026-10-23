<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
api_method('GET');

try {
    db()->query('SELECT 1');
} catch (Throwable $exception) {
    error_log((string)$exception);
    api_json([
        'success' => false,
        'data' => ['status' => 'unavailable'],
    ], 503);
}

api_json([
    'success' => true,
    'data' => [
        'status' => 'ok',
        'server_time' => date('Y-m-d H:i:s'),
    ],
]);
