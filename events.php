<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'الأحداث';
$pdo = db();
$perPage = 10;
$totalEvents = (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$totalPages = max(1, (int)ceil($totalEvents / $perPage));
$page = min(max(1, (int)($_GET['page'] ?? 1)), $totalPages);
$eventsStmt = $pdo->prepare('SELECT e.*, (SELECT COUNT(*) FROM event_players ep WHERE ep.event_id=e.id) AS participant_count FROM events e ORDER BY e.created_at DESC, e.id DESC LIMIT ? OFFSET ?');
$eventsStmt->bindValue(1, $perPage, PDO::PARAM_INT);
$eventsStmt->bindValue(2, ($page - 1) * $perPage, PDO::PARAM_INT);
$eventsStmt->execute();
$events = $eventsStmt->fetchAll();
require APP_ROOT . '/includes/header.php';
?>
<section class="page-title-row"><div><span class="eyebrow">VISION HUNT EVENTS</span><h1>أحداث اللعبة</h1><p class="muted">تابع مواعيد الأحداث والنتائج النهائية لأفضل عشرة لاعبين.</p></div></section>
<?php if (!$events): ?><div class="empty-state"><span class="empty-icon">◷</span><h3>لا توجد أحداث حالياً</h3><p>ستظهر هنا الأحداث القادمة ونتائج الأحداث السابقة.</p></div><?php else: ?>
<div class="event-list">
<?php foreach ($events as $event): $now = time(); $status = $event['processed_at'] ? 'منتهٍ' : (strtotime($event['starts_at']) > $now ? 'قادم' : 'جارٍ'); $statusClass = $event['processed_at'] ? 'event-status-ended' : ''; ?>
<article class="event-card"><div class="event-card-head"><div><span class="eyebrow">EVENT #<?= (int)$event['id'] ?></span><h2><?= e($event['title']) ?></h2></div><span class="event-status <?= e($statusClass) ?>"><?= e($status) ?></span></div>
<?php if ($event['description']): ?><p><?= nl2br(e($event['description'])) ?></p><?php endif; ?>
<div class="event-date-cards"><div class="event-date-card"><span class="event-date-icon">✦</span><span class="event-date-label">تاريخ الإنشاء</span><strong><?= e(format_datetime($event['created_at'])) ?></strong></div><div class="event-date-card"><span class="event-date-icon">◷</span><span class="event-date-label">تاريخ البداية</span><strong><?= e(format_datetime($event['starts_at'])) ?></strong></div><div class="event-date-card"><span class="event-date-icon">◷</span><span class="event-date-label">تاريخ النهاية</span><strong><?= e(format_datetime($event['ends_at'])) ?></strong></div></div>
<?php if (!$event['processed_at'] && strtotime($event['starts_at']) <= time()): ?><h3 class="event-results-title">المتصدرون حاليًا</h3><div class="table-scroll"><table><thead><tr><th>الترتيب</th><th>اللاعب</th><th>النقاط</th></tr></thead><tbody>
<?php $leadersStmt=$pdo->query('SELECT username,score FROM players ORDER BY score DESC, updated_at ASC, username ASC LIMIT 3'); $leaders=$leadersStmt->fetchAll(); foreach($leaders as $index=>$leader): ?><tr><td><?= $index + 1 ?></td><td><strong><?= e($leader['username']) ?></strong></td><td><span class="score-pill"><?= e(format_score($leader['score'])) ?></span></td></tr><?php endforeach; ?>
<?php if (!$leaders): ?><tr><td colspan="3" class="empty-cell">لا يوجد لاعبون مسجلون.</td></tr><?php endif; ?></tbody></table></div><?php endif; ?>
<?php if ($event['processed_at']): ?><h3 class="event-results-title">النتائج النهائية</h3><div class="table-scroll"><table><thead><tr><th>الترتيب</th><th>اللاعب</th><th>النقاط</th></tr></thead><tbody>
<?php $stmt=db()->prepare('SELECT rank_position,username,score FROM event_players WHERE event_id=? ORDER BY rank_position LIMIT 3'); $stmt->execute([(int)$event['id']]); $winners=$stmt->fetchAll(); foreach($winners as $winner): ?><tr><td><?= (int)$winner['rank_position'] ?></td><td><strong><?= e($winner['username']) ?></strong></td><td><span class="score-pill"><?= e(format_score($winner['score'])) ?></span></td></tr><?php endforeach; ?>
<?php if (!$winners): ?><tr><td colspan="3" class="empty-cell">لم يُسجّل لاعبون في هذا الحدث.</td></tr><?php endif; ?></tbody></table></div><?php endif; ?></article>
<?php endforeach; ?></div><?php pagination_links($page, $totalPages, '/events.php'); ?><?php endif; ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>
