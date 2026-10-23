<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$maxBodyBytes = 16 * 1024;
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > $maxBodyBytes) {
    api_json(['success' => false, 'message' => 'حجم جسم الطلب يتجاوز الحد المسموح به.'], 413);
}
$bodyStream = fopen('php://input', 'rb');
$requestBody = $bodyStream === false ? '' : stream_get_contents($bodyStream, $maxBodyBytes + 1);
if (is_resource($bodyStream)) {
    fclose($bodyStream);
}
if ($requestBody === false) {
    $requestBody = '';
}
if (strlen($requestBody) > $maxBodyBytes) {
    api_json(['success' => false, 'message' => 'حجم جسم الطلب يتجاوز الحد المسموح به.'], 413);
}
$GLOBALS['_api_request_body'] = $requestBody;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Allow: GET, POST, OPTIONS');
    http_response_code(204);
    exit;
}

function api_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_enforce_auth_rate_limit(string $scope, string $username = ''): void
{
    $ipAddress = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($ipAddress === '' || strlen($ipAddress) > 45) {
        $ipAddress = 'unknown';
    }

    if ($scope === 'login') {
        $windowSeconds = 60;
        $buckets = [
            [hash('sha256', 'api-login:user:' . strtolower(trim($username))), 10],
            [hash('sha256', 'api-login:ip'), 60],
        ];
    } elseif ($scope === 'register') {
        $windowSeconds = 3600;
        $buckets = [[hash('sha256', 'api-register:ip'), 10]];
    } else {
        throw new InvalidArgumentException('Unsupported API rate limit scope.');
    }

    $pdo = db();
    $lockName = 'vh_api_rate_' . substr(hash('sha256', $scope . ':' . $ipAddress), 0, 48);
    $lock = $pdo->prepare('SELECT GET_LOCK(?, 2)');
    $lock->execute([$lockName]);
    if ((int)$lock->fetchColumn() !== 1) {
        header('Retry-After: 1');
        api_json(['success' => false, 'message' => 'الخدمة مشغولة. أعد المحاولة بعد قليل.'], 503);
    }

    $retryAfter = 0;
    try {
        $countSql = 'SELECT COUNT(*) AS attempts,
                            TIMESTAMPDIFF(SECOND, MIN(attempted_at), NOW()) AS oldest_age
                     FROM auth_attempts
                     WHERE ip_address = ? AND username_hash = ?
                       AND attempted_at >= DATE_SUB(NOW(), INTERVAL ' . $windowSeconds . ' SECOND)';
        $countStmt = $pdo->prepare($countSql);

        foreach ($buckets as [$bucketHash, $limit]) {
            $countStmt->execute([$ipAddress, $bucketHash]);
            $stats = $countStmt->fetch();
            if ((int)$stats['attempts'] >= $limit) {
                $oldestAge = max(0, (int)($stats['oldest_age'] ?? 0));
                $retryAfter = max($retryAfter, max(1, $windowSeconds - $oldestAge));
            }
        }

        if ($retryAfter === 0) {
            $insert = $pdo->prepare(
                'INSERT INTO auth_attempts (ip_address, username_hash) VALUES (?, ?)'
            );
            foreach ($buckets as [$bucketHash]) {
                $insert->execute([$ipAddress, $bucketHash]);
            }
        }
    } finally {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute([$lockName]);
    }

    if ($retryAfter > 0) {
        header('Retry-After: ' . $retryAfter);
        api_json([
            'success' => false,
            'message' => 'تم تجاوز عدد الطلبات المسموح به. أعد المحاولة لاحقًا.',
            'retry_after' => $retryAfter,
        ], 429);
    }

    if (random_int(1, 100) === 1) {
        $pdo->exec('DELETE FROM auth_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }
}

function api_body(): array
{
    $raw = (string)($GLOBALS['_api_request_body'] ?? '');
    if (!$raw) api_json(['success' => false, 'message' => 'جسم الطلب فارغ.'], 400);
    $data = json_decode($raw, true);
    if (!is_array($data)) api_json(['success' => false, 'message' => 'JSON غير صالح.'], 400);
    return $data;
}

function api_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method . ', OPTIONS');
        api_json(['success' => false, 'message' => 'طريقة الطلب غير مدعومة.'], 405);
    }
}

function issue_player_token(int $playerId): string
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $pdo = db();
    $pdo->prepare('DELETE FROM api_tokens WHERE player_id = ? AND expires_at <= NOW()')->execute([$playerId]);
    $pdo->prepare('INSERT INTO api_tokens (player_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))')->execute([$playerId, $hash]);
    return $token;
}

function api_auth_player(): array
{
    $header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $header = (string)($headers['Authorization'] ?? $headers['authorization'] ?? '');
    }
    if (!preg_match('/^Bearer\s+([a-f0-9]{64})$/i', trim($header), $m)) {
        api_json(['success' => false, 'message' => 'رمز الوصول مفقود أو غير صالح.'], 401);
    }
    $hash = hash('sha256', $m[1]);
    $stmt = db()->prepare('SELECT p.id, p.username, p.phone, p.score, p.updated_at, t.id AS token_id FROM api_tokens t JOIN players p ON p.id=t.player_id WHERE t.token_hash=? AND t.expires_at > NOW() LIMIT 1');
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    if (!$row) api_json(['success' => false, 'message' => 'انتهت صلاحية رمز الوصول أو أنه غير صالح. سجّل الدخول مجددًا.'], 401);
    db()->prepare('UPDATE api_tokens SET last_used_at=NOW() WHERE id=?')->execute([(int)$row['token_id']]);
    unset($row['token_id']);
    return $row;
}

function api_valid_completed_at(mixed $value): string
{
    if ($value === null || $value === '') return date('Y-m-d H:i:s');
    if (!is_string($value) || strlen($value) > 40) api_json(['success' => false, 'message' => 'تاريخ إنجاز إحدى المهام غير صالح.'], 422);
    try {
        $date = new DateTimeImmutable($value);
        // Reject dates far outside the accepted range while allowing delayed synchronization.
        if ($date->getTimestamp() > time() + 300 || $date->getTimestamp() < time() - 10 * 365 * 24 * 60 * 60) {
            api_json(['success' => false, 'message' => 'تاريخ إنجاز إحدى المهام خارج النطاق المقبول.'], 422);
        }
        return $date->setTimezone(new DateTimeZone('Africa/Algiers'))->format('Y-m-d H:i:s');
    } catch (Throwable) {
        api_json(['success' => false, 'message' => 'صيغة تاريخ إنجاز إحدى المهام غير صالحة.'], 422);
    }
}
