<?php
declare(strict_types=1);

// Creates or refreshes 30 player accounts with sequential usernames and a shared password, while preserving each player's score and completed tasks.
// Run from the project root in Windows CMD:
//   php database\seeders\seed_players.php
// This creates player_01 through player_30, using the shared password password123.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$pdo = db();
$passwordHash = password_hash('password123', PASSWORD_DEFAULT);
$upsert = $pdo->prepare(
    'INSERT INTO players (username, password_hash) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE password_hash = ?, updated_at = updated_at'
);

$createdOrUpdated = 0;
$pdo->beginTransaction();
try {
    for ($number = 1; $number <= 30; $number++) {
        $username = sprintf('player_%02d', $number);
        $upsert->execute([$username, $passwordHash, $passwordHash]);
        $createdOrUpdated++;
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Failed to seed players: {$exception->getMessage()}\n");
    exit(1);
}

echo "Ready: {$createdOrUpdated} players. Password for all: password123\n";
echo "Usernames: player_01 through player_30\n";
