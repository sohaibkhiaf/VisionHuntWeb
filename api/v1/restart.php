<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
api_method('POST');
$player = api_auth_player();

$pdo = db();
try {
    $pdo->beginTransaction();

    // Restrict resets to the scheduled event window and serialize with event finalization.
    $now = date('Y-m-d H:i:s');
    $eventLock = $pdo->prepare('SELECT id FROM events WHERE processed_at IS NULL AND starts_at <= ? AND ends_at > ? ORDER BY starts_at DESC, id DESC LIMIT 1 FOR UPDATE');
    $eventLock->execute([$now, $now]);
    if (!$eventLock->fetchColumn()) {
        $pdo->rollBack();
        api_json(['success' => false, 'message' => 'لا يمكن إعادة التعيين خارج فترة حدث جارٍ.'], 409);
    }

    $pdo->prepare('DELETE FROM completed_tasks WHERE player_id = ?')
        ->execute([(int)$player['id']]);
    $currentScore = $pdo->prepare('SELECT score FROM players WHERE id = ? FOR UPDATE');
    $currentScore->execute([(int)$player['id']]);
    if ((int)$currentScore->fetchColumn() !== 0) {
        $pdo->prepare('UPDATE players SET score = 0, updated_at = NOW() WHERE id = ?')
            ->execute([(int)$player['id']]);
    }
    $updatedAtStmt = $pdo->prepare('SELECT updated_at FROM players WHERE id = ?');
    $updatedAtStmt->execute([(int)$player['id']]);
    $updatedAt = $updatedAtStmt->fetchColumn();
    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log((string)$ex);
    api_json(['success' => false, 'message' => 'فشلت إعادة تعيين البيانات.'], 500);
}

api_json([
    'success' => true,
    'message' => 'تمت إعادة تعيين نقاط اللاعب وقائمة المهام بنجاح.',
    'data' => [
        'username'   => $player['username'],
        'score'      => 0,
        'updated_at' => $updatedAt,
    ],
]);

