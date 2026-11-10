<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $tzOffset = (new DateTimeImmutable('now', new DateTimeZone('Africa/Algiers')))->format('P');
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '{$tzOffset}'",
    ]);
    $pdo->exec("SET time_zone = '{$tzOffset}'");
    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    if ($path === '') return BASE_URL . '/';
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return (string)$_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = (string)($_POST['_csrf'] ?? '');
    if ($sent === '' || !hash_equals((string)($_SESSION['_csrf'] ?? ''), $sent)) {
        http_response_code(419);
        exit('انتهت صلاحية النموذج. ارجع إلى الصفحة وحاول مجددًا.');
    }
}

function admin_login_ip_address(): string
{
    $ipAddress = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    return $ipAddress !== '' && strlen($ipAddress) <= 45 ? $ipAddress : 'unknown';
}

function admin_login_rate_limit_remaining(string $username): int
{
    $pdo = db();
    $ipAddress = admin_login_ip_address();
    $usernameHash = hash('sha256', 'admin-login:user:' . strtolower(trim($username)));
    $ipHash = hash('sha256', 'admin-login:ip');
    $lockoutSeconds = 60;
    $retryAfter = 0;

    $checks = [
        [
            'sql' => 'SELECT COUNT(*) AS attempts, TIMESTAMPDIFF(SECOND, MAX(attempted_at), NOW()) AS latest_age
                     FROM auth_attempts
                     WHERE ip_address = ? AND username_hash = ?
                       AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)',
            'params' => [$ipAddress, $usernameHash],
            'limit' => 5,
        ],
        [
            'sql' => 'SELECT COUNT(*) AS attempts, TIMESTAMPDIFF(SECOND, MAX(attempted_at), NOW()) AS latest_age
                     FROM auth_attempts
                     WHERE ip_address = ? AND username_hash = ?
                       AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)',
            'params' => [$ipAddress, $ipHash],
            'limit' => 20,
        ],
    ];

    foreach ($checks as $check) {
        $stmt = $pdo->prepare($check['sql']);
        $stmt->execute($check['params']);
        $stats = $stmt->fetch();

        if ((int)$stats['attempts'] >= $check['limit']) {
            $latestAge = max(0, (int)($stats['latest_age'] ?? 0));
            if ($latestAge < $lockoutSeconds) {
                $retryAfter = max($retryAfter, $lockoutSeconds - $latestAge);
            }
        }
    }

    return $retryAfter;
}

function record_admin_login_failure(string $username): void
{
    $stmt = db()->prepare(
        'INSERT INTO auth_attempts (ip_address, username_hash) VALUES (?, ?)'
    );
    $ipAddress = admin_login_ip_address();
    $stmt->execute([$ipAddress, hash('sha256', 'admin-login:user:' . strtolower(trim($username)))]);
    $stmt->execute([$ipAddress, hash('sha256', 'admin-login:ip')]);

    if (random_int(1, 100) === 1) {
        db()->exec('DELETE FROM auth_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }
}

function clear_admin_login_failures(string $username): void
{
    $stmt = db()->prepare(
        'DELETE FROM auth_attempts WHERE ip_address = ? AND username_hash IN (?, ?)'
    );
    $stmt->execute([
        admin_login_ip_address(),
        hash('sha256', 'admin-login:user:' . strtolower(trim($username))),
        hash('sha256', 'admin-login:ip'),
    ]);
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function pull_flash(): ?array
{
    $item = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return is_array($item) ? $item : null;
}

function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) redirect('/admin/login.php');
    $stmt = db()->prepare('SELECT id, username FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    if (!$admin) {
        unset($_SESSION['admin_id']);
        redirect('/admin/login.php');
    }
    $GLOBALS['current_admin'] = $admin;
}

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function format_datetime(?string $value): string
{
    if (!$value) return '—';
    $time = strtotime($value);
    return $time === false ? '—' : date('Y-m-d H:i', $time);
}

function unicode_length(string $value): int
{
    $count = preg_match_all('/./us', $value, $matches);
    return $count === false ? strlen($value) : $count;
}

function format_score(mixed $score): string
{
    return number_format((int)$score, 0, ',', ' ');
}

function pagination_links(int $page, int $pages, string $basePath, array $extra = []): void
{
    if ($pages <= 1) return;
    $start = max(1, $page - 2);
    $end = min($pages, $page + 2);
    echo '<nav class="pagination" aria-label="ترقيم الصفحات">';
    if ($page > 1) echo '<a href="' . e(page_url($basePath, $page - 1, $extra)) . '">السابق</a>';
    if ($start > 1) {
        echo '<a href="' . e(page_url($basePath, 1, $extra)) . '">1</a>';
        if ($start > 2) echo '<span class="pagination-gap">…</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $class = $i === $page ? ' class="active" aria-current="page"' : '';
        echo '<a' . $class . ' href="' . e(page_url($basePath, $i, $extra)) . '">' . $i . '</a>';
    }
    if ($end < $pages) {
        if ($end < $pages - 1) echo '<span class="pagination-gap">…</span>';
        echo '<a href="' . e(page_url($basePath, $pages, $extra)) . '">' . $pages . '</a>';
    }
    if ($page < $pages) echo '<a href="' . e(page_url($basePath, $page + 1, $extra)) . '">التالي</a>';
    echo '</nav>';
}

function page_url(string $basePath, int $page, array $extra = []): string
{
    $query = array_merge($extra, ['page' => $page]);
    return url($basePath) . '?' . http_build_query($query);
}

function valid_username(string $username): bool
{
    return (bool)preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username);
}

/** Finalize one event when an administrator explicitly ends it. */
function finish_event(int $eventId): bool
{
    $pdo = db();
    $now = date('Y-m-d H:i:s');
    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT id FROM events WHERE id = ? AND processed_at IS NULL AND starts_at <= ? FOR UPDATE');
        $lock->execute([$eventId, $now]);
        if (!$lock->fetchColumn()) { $pdo->commit(); return false; }
        $players = $pdo->query('SELECT username, score FROM players ORDER BY score DESC, updated_at ASC, username ASC')->fetchAll();
        $insert = $pdo->prepare('INSERT INTO event_players (event_id, rank_position, username, score) VALUES (?, ?, ?, ?)');
        foreach ($players as $index => $player) $insert->execute([$eventId, $index + 1, $player['username'], (string)$player['score']]);
        $pdo->exec('DELETE FROM completed_tasks');
        $pdo->exec('UPDATE players SET score = 0, updated_at = NOW() WHERE score <> 0');
        $pdo->prepare('UPDATE events SET processed_at = ?, ends_at = CASE WHEN ends_at > ? THEN ? ELSE ends_at END WHERE id = ?')->execute([$now, $now, $now, $eventId]);
        $pdo->commit();
        return true;
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log((string)$ex);
        throw $ex;
    }
}

function current_event_payload(): array
{
    $pdo = db();
    $now = date('Y-m-d H:i:s');
    $fields = 'id, title, description, starts_at, ends_at';

    $active = $pdo->prepare("SELECT $fields FROM events WHERE processed_at IS NULL AND starts_at <= ? ORDER BY starts_at DESC, id DESC LIMIT 1");
    $active->execute([$now]);
    $primary = $active->fetch();
    $primaryType = $primary ? 'ongoing' : null;

    if (!$primary) {
        $upcoming = $pdo->prepare("SELECT $fields FROM events WHERE processed_at IS NULL AND starts_at > ? ORDER BY starts_at ASC, id ASC LIMIT 1");
        $upcoming->execute([$now]);
        $primary = $upcoming->fetch();
        $primaryType = $primary ? 'upcoming' : null;
    }

    $lastEndedStmt = $pdo->query("SELECT $fields FROM events WHERE processed_at IS NOT NULL ORDER BY created_at DESC, id DESC LIMIT 1");
    $lastEnded = $lastEndedStmt->fetch() ?: null;

    if ($lastEnded) {
        $rankings = $pdo->prepare('SELECT rank_position, username, score FROM event_players WHERE event_id = ? ORDER BY rank_position ASC');
        $rankings->execute([(int)$lastEnded['id']]);
        $lastEnded['players'] = $rankings->fetchAll();
        foreach ($lastEnded['players'] as &$player) {
            $player['rank_position'] = (int)$player['rank_position'];
        }
        unset($player);
    }

    if (!$primary && !$lastEnded) {
        return ['event' => null, 'event_type' => null, 'last_ended_event' => null];
    }

    return [
        'event' => $primary ?: $lastEnded,
        'event_type' => $primaryType ?: 'ended',
        'last_ended_event' => $primaryType === 'upcoming' ? $lastEnded : null,
    ];
}

/** Return the active event for server-rendered pages. */
function current_event(): ?array
{
    $now = date('Y-m-d H:i:s');
    $stmt = db()->prepare('SELECT id, title, description, starts_at, ends_at FROM events WHERE processed_at IS NULL AND starts_at <= ? ORDER BY starts_at DESC, id DESC LIMIT 1');
    $stmt->execute([$now]);
    $event = $stmt->fetch();
    return $event ?: null;
}

function delete_event(int $eventId): bool
{
    $pdo = db();
    $now = date('Y-m-d H:i:s');
    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT id, starts_at, processed_at FROM events WHERE id = ? FOR UPDATE');
        $lock->execute([$eventId]);
        $event = $lock->fetch();
        if (!$event) { $pdo->commit(); return false; }

        // Deleting a started, unfinished event is equivalent to ending it.
        if ($event['processed_at'] === null && $event['starts_at'] <= $now) {
            $pdo->exec('DELETE FROM completed_tasks');
            $pdo->exec('UPDATE players SET score = 0, updated_at = NOW() WHERE score <> 0');
        }

        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$eventId]);
        $pdo->commit();
        return true;
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log((string)$ex);
        throw $ex;
    }
}
