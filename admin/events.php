<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$pdo = db();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'create') {
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $startRaw = (string)($_POST['starts_at'] ?? '');
        $endRaw = (string)($_POST['ends_at'] ?? '');
        $start = DateTime::createFromFormat('Y-m-d\TH:i', $startRaw);
        $end = DateTime::createFromFormat('Y-m-d\TH:i', $endRaw);
        if ($title === '' || unicode_length($title) > 150 || !$start || !$end || $end <= $start) {
            flash('error', 'أدخل عنواناً صحيحاً وحدد نهاية بعد وقت البداية.');
        } else {
            $startAt = $start->format('Y-m-d H:i:s');
            $endAt = $end->format('Y-m-d H:i:s');
            $creationLock = $pdo->query("SELECT GET_LOCK('visionhunt_event_create', 5)")->fetchColumn();
            if ((int)$creationLock !== 1) {
                flash('error', 'تعذر التحقق من الأحداث الحالية. أعد المحاولة.');
            } else {
                try {
                    $now = date('Y-m-d H:i:s');
                    $guard = $pdo->query('SELECT id, title, starts_at FROM events WHERE processed_at IS NULL ORDER BY starts_at ASC, id ASC LIMIT 1');
                    $pendingEvent = $guard->fetch();
                    if ($pendingEvent) {
                        $reason = $pendingEvent['starts_at'] > $now ? 'يوجد حدث قادم' : 'يوجد حدث جارٍ أو لم يُنهَ بعد';
                        flash('error', 'لا يمكن إنشاء الحدث: ' . $reason . ' (' . $pendingEvent['title'] . '). أنهِ الحدث الحالي أولاً.');
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO events (title, description, starts_at, ends_at) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$title, $description !== '' ? $description : null, $startAt, $endAt]);
                        flash('success', 'تم إنشاء الحدث.');
                    }
                } finally {
                    $pdo->query("SELECT RELEASE_LOCK('visionhunt_event_create')");
                }
            }
        }
    } elseif ($action === 'remove_player') {
        $playerId = (int)($_POST['event_player_id'] ?? 0);
        $eventStmt = $pdo->prepare('SELECT ep.event_id FROM event_players ep INNER JOIN events e ON e.id = ep.event_id WHERE ep.id = ? AND e.processed_at IS NOT NULL');
        $eventStmt->execute([$playerId]);
        $eventId = $eventStmt->fetchColumn();
        $stmt = $pdo->prepare('DELETE ep FROM event_players ep INNER JOIN events e ON e.id = ep.event_id WHERE ep.id = ? AND e.processed_at IS NOT NULL');
        $stmt->execute([(int)($_POST['event_player_id'] ?? 0)]);
        if ($eventId !== false && $stmt->rowCount()) {
            $rankStmt = $pdo->prepare('SELECT id, rank_position FROM event_players WHERE event_id = ? ORDER BY rank_position ASC, id ASC');
            $rankStmt->execute([(int)$eventId]);
            $rankedPlayers = $rankStmt->fetchAll();
            $maxRank = 0;
            foreach ($rankedPlayers as $rankedPlayer) {
                $maxRank = max($maxRank, (int)$rankedPlayer['rank_position']);
            }
            $setRank = $pdo->prepare('UPDATE event_players SET rank_position = ? WHERE id = ?');
            foreach ($rankedPlayers as $index => $rankedPlayer) {
                $setRank->execute([$maxRank + count($rankedPlayers) + $index + 1, (int)$rankedPlayer['id']]);
            }
            foreach ($rankedPlayers as $index => $rankedPlayer) {
                $setRank->execute([$index + 1, (int)$rankedPlayer['id']]);
            }
        }
        flash('success', $stmt->rowCount() ? 'تم حذف اللاعب من نتائج الحدث دون تغيير حسابه.' : 'تعذر العثور على اللاعب في الحدث.');
    } elseif ($action === 'finish_event') {
        $finished = finish_event((int)($_POST['event_id'] ?? 0));
        flash($finished ? 'success' : 'error', $finished ? 'تم إنهاء الحدث وحفظ النتائج وتصفير النقاط.' : 'لم يبدأ الحدث بعد، أو أنه منتهٍ مسبقاً أو غير موجود.');
    } elseif ($action === 'delete_event') {
        $deleted = delete_event((int)($_POST['event_id'] ?? 0));
        flash($deleted ? 'success' : 'error', $deleted ? 'تم حذف الحدث ونتائجه المحفوظة.' : 'تعذر العثور على الحدث المطلوب.');
    }
    redirect('/admin/events.php');
}
$pageTitle = 'إدارة الأحداث';
$perPage = 10;
$totalEvents = (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$totalPages = max(1, (int)ceil($totalEvents / $perPage));
$page = min(max(1, (int)($_GET['page'] ?? 1)), $totalPages);
$eventsStmt = $pdo->prepare('SELECT * FROM events ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?');
$eventsStmt->bindValue(1, $perPage, PDO::PARAM_INT);
$eventsStmt->bindValue(2, ($page - 1) * $perPage, PDO::PARAM_INT);
$eventsStmt->execute();
$events = $eventsStmt->fetchAll();
$now = date('Y-m-d H:i:s');
$pendingEventStmt = $pdo->query('SELECT id, title, starts_at FROM events WHERE processed_at IS NULL ORDER BY starts_at ASC, id ASC LIMIT 1');
$pendingEvent = $pendingEventStmt->fetch();
require APP_ROOT . '/includes/header.php';
?>
<section class="page-title-row"><div><span class="eyebrow">EVENT MANAGEMENT</span><h1>إدارة الأحداث</h1><p class="muted">عند موعد النهاية تُحفظ نتيجة أفضل عشرة وتُصفّر نقاط ومهام اللاعبين.</p></div></section>
<?php if ($pendingEvent): ?><div class="alert alert-warning"><?= $pendingEvent['starts_at'] > $now ? 'يوجد حدث قادم' : 'يوجد حدث لم يُنهَ بعد' ?> (<?= e($pendingEvent['title']) ?>). أنهِه قبل إنشاء حدث جديد.</div><?php endif; ?>
<section class="form-card card-spacing"><h2>إنشاء حدث</h2><form method="post" class="form-stack">
<?= csrf_field() ?><input type="hidden" name="action" value="create">
<label>عنوان الحدث<input name="title" maxlength="150" required></label>
<label>الوصف<input name="description" type="text"></label>
<div class="event-form-dates"><label>تاريخ البداية<input type="datetime-local" name="starts_at" required></label><label>تاريخ النهاية<input type="datetime-local" name="ends_at" required></label></div>
<div class="form-actions"><button class="button button-primary" type="submit" <?= $pendingEvent ? 'disabled' : '' ?>>إنشاء الحدث</button></div></form></section>
<section class="section-heading"><div><span class="eyebrow">EVENTS</span><h2>الأحداث والنتائج</h2></div></section>
<?php if (!$events): ?><div class="empty-state"><span class="empty-icon">◷</span><h3>لا توجد أحداث حالياً</h3><p>ستظهر هنا الأحداث القادمة ونتائج الأحداث السابقة.</p></div><?php else: ?><div class="event-list">
<?php foreach ($events as $event):
  $stmt=$pdo->prepare('SELECT id,rank_position,username,score FROM event_players WHERE event_id=? ORDER BY rank_position LIMIT 3');
  $stmt->execute([(int)$event['id']]);
  $participants=$stmt->fetchAll();
  $statusEnded = (bool)$event['processed_at'];
  $isUpcoming = !$statusEnded && strtotime($event['starts_at']) > time();
  $statusClass = $statusEnded ? ' event-status-ended' : ($isUpcoming ? ' event-status-upcoming' : ' event-status-live');
?>
<article class="event-card"><div class="event-card-head"><div><span class="eyebrow">EVENT #<?= (int)$event['id'] ?></span><h2><?= e($event['title']) ?></h2></div><span class="event-status<?= $statusClass ?>"><?= $statusEnded ? 'منتهٍ' : ($isUpcoming ? 'قادم' : 'جارٍ') ?></span></div>
<?php if ($event['description']): ?><p><?= nl2br(e($event['description'])) ?></p><?php endif; ?><div class="event-date-cards"><div class="event-date-card"><span class="event-date-icon">✦</span><span class="event-date-label">تاريخ الإنشاء</span><strong><?= e(format_datetime($event['created_at'])) ?></strong></div><div class="event-date-card"><span class="event-date-icon">◷</span><span class="event-date-label">تاريخ البداية</span><strong><?= e(format_datetime($event['starts_at'])) ?></strong></div><div class="event-date-card"><span class="event-date-icon">◷</span><span class="event-date-label">تاريخ النهاية</span><strong><?= e(format_datetime($event['ends_at'])) ?></strong></div></div>
<?php if (!$event['processed_at'] && strtotime($event['ends_at']) <= time()): ?><p class="event-overdue-notice" role="alert">انتهت مدة هذا الحدث. يُرجى إنهاؤه يدويًا لحفظ النتائج وتصفير النقاط.</p><?php endif; ?>
<div class="event-actions">
<?php if (!$event['processed_at'] && strtotime($event['starts_at']) <= time()): ?><form method="post" class="event-finish" onsubmit="return confirm('إنهاء الحدث الآن وحفظ النتائج وتصفير نقاط جميع اللاعبين؟');"><?= csrf_field() ?><input type="hidden" name="action" value="finish_event"><input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>"><button class="button button-primary" type="submit">إنهاء الحدث وحفظ النتائج</button></form><?php endif; ?>
<form method="post" class="event-delete" onsubmit="return confirm('حذف الحدث وجميع نتائجه؟');"><?= csrf_field() ?><input type="hidden" name="action" value="delete_event"><input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>"><button class="button button-danger" type="submit">حذف الحدث</button></form>
</div>
<?php if (!$event['processed_at'] && strtotime($event['starts_at']) <= time()): ?><h3 class="event-results-title">المتصدرون حاليًا</h3><div class="table-card event-table"><div class="table-scroll"><table><thead><tr><th class="rank-col">الترتيب</th><th>اللاعب</th><th>النقاط الحالية</th></tr></thead><tbody>
<?php $leadersStmt=$pdo->query('SELECT username,score FROM players ORDER BY score DESC, updated_at ASC, username ASC LIMIT 3'); $leaders=$leadersStmt->fetchAll(); foreach($leaders as $index=>$leader): $r = $index + 1; ?><tr class="<?= $r <= 3 ? 'podium-row podium-' . $r : '' ?>"><td><span class="rank <?= $r <= 3 ? 'rank-top rank-' . $r : '' ?>"><?= $r ?></span></td><td><div class="player-cell"><span class="avatar"><?= e(strtoupper(substr($leader['username'], 0, 1))) ?></span><strong><?= e($leader['username']) ?></strong></div></td><td><span class="score-pill"><?= e(format_score($leader['score'])) ?></span></td></tr><?php endforeach; ?>
<?php if (!$leaders): ?><tr><td colspan="3" class="empty-cell">لا يوجد لاعبون مسجلون.</td></tr><?php endif; ?></tbody></table></div></div><?php endif; ?>
<?php if ($event['processed_at']): ?><h3 class="event-results-title">النتائج النهائية</h3><div class="table-card event-table"><div class="table-scroll"><table><thead><tr><th class="rank-col">الترتيب</th><th>اللاعب</th><th>النقاط المحفوظة</th><th></th></tr></thead><tbody>
<?php foreach ($participants as $participant): $r = (int)$participant['rank_position']; ?><tr class="<?= $r <= 3 ? 'podium-row podium-' . $r : '' ?>"><td><span class="rank <?= $r <= 3 ? 'rank-top rank-' . $r : '' ?>"><?= $r ?></span></td><td><div class="player-cell"><span class="avatar"><?= e(strtoupper(substr($participant['username'], 0, 1))) ?></span><strong><?= e($participant['username']) ?></strong></div></td><td><span class="score-pill"><?= e(format_score($participant['score'])) ?></span></td><td><form method="post" onsubmit="return confirm('حذف اللاعب من نتائج الحدث؟ لن يتأثر حسابه.');"><?= csrf_field() ?><input type="hidden" name="action" value="remove_player"><input type="hidden" name="event_player_id" value="<?= (int)$participant['id'] ?>"><button class="button button-small button-danger" type="submit">حذف من الحدث</button></form></td></tr><?php endforeach; ?>
<?php if (!$participants): ?><tr><td colspan="4" class="empty-cell">لا يوجد لاعبون مسجلون.</td></tr><?php endif; ?></tbody></table></div></div><?php endif; ?>
</article><?php endforeach; ?></div><?php endif; ?>
<?php pagination_links($page, $totalPages, '/admin/events.php'); ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>
