<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
api_method('GET');

$perPage = 10;
$totalPlayers = (int)db()->query('SELECT COUNT(*) FROM players')->fetchColumn();
$totalPages = max(1, (int)ceil($totalPlayers / $perPage));
$page = max(1, (int)($_GET['page'] ?? 1));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
    'SELECT username, score, updated_at
     FROM players
     ORDER BY score DESC, updated_at ASC, username ASC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$players = [];
foreach ($stmt->fetchAll() as $index => $player) {
    $players[] = [
        'rank' => $offset + $index + 1,
        'username' => $player['username'],
        'score' => (int)$player['score'],
        'updated_at' => $player['updated_at'],
    ];
}

api_json([
    'success' => true,
    'data' => [
        'players' => $players,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total_players' => $totalPlayers,
            'total_pages' => $totalPages,
        ],
    ],
]);
