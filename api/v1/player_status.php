<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
api_method('GET');
$player = api_auth_player();
$stmt = db()->prepare('SELECT id, object_name_ar, object_name_en, rarity, points, confidence, created_at FROM completed_tasks WHERE player_id = ? ORDER BY created_at, id');
$stmt->execute([(int)$player['id']]);
$tasks = $stmt->fetchAll();
api_json(['success' => true, 'data' => [
    'id' => (int)$player['id'], 'username' => $player['username'], 'phone' => $player['phone'],
    'score' => (int)$player['score'], 'completed_tasks' => $tasks,
    'completed_tasks_count' => count($tasks), 'updated_at' => $player['updated_at'],
    'server_time' => date('Y-m-d H:i:s'),
]]);
