<?php
declare(strict_types=1);

// Creates one administrator using ADMIN_USERNAME and ADMIN_PASSWORD environment variables; it refuses to overwrite an existing username.
// Run from the project root in Windows CMD:
//   set ADMIN_USERNAME=admin
//   set ADMIN_PASSWORD=admin123password123
//   php database\seeders\seed_admin.php
// Replace the example password with a unique password of at least 12 characters.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

$username = trim((string)(getenv('ADMIN_USERNAME') ?: ''));
$password = (string)(getenv('ADMIN_PASSWORD') ?: '');

if ($username === '' || strlen($username) < 3 || strlen($username) > 50) {
    fwrite(STDERR, "Set ADMIN_USERNAME to a value between 3 and 50 characters.\n");
    exit(1);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "Set ADMIN_PASSWORD to a password of at least 12 characters.\n");
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
if ($passwordHash === false) {
    fwrite(STDERR, "Could not hash the admin password.\n");
    exit(1);
}

try {
    $stmt = db()->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
    $stmt->execute([$username, $passwordHash]);
    echo "Admin account created: {$username}\n";
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        fwrite(STDERR, "An account with username '{$username}' already exists; no changes were made.\n");
        exit(1);
    }
    fwrite(STDERR, "Failed to create admin account: {$exception->getMessage()}\n");
    exit(1);
}
