<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'ترتيب اللاعبين';
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)db()->query('SELECT COUNT(*) FROM players')->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$stmt = db()->prepare('SELECT username, score, updated_at FROM players ORDER BY score DESC, updated_at ASC, username ASC LIMIT :lim OFFSET :off');
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$players = $stmt->fetchAll();
$activeEvent = current_event();
require APP_ROOT . '/includes/header.php';
?>
<section class="hero leaderboard-hero">
  <div class="hero-copy"><span class="eyebrow">VISION HUNT · LEADERBOARD</span><h1>اكتشف الأشياء وتصدر القائمة!</h1><p>تابع ترتيب اللاعبين حسب مجموع النقاط المسجّلة في اللعبة.</p>
    <a class="button button-light" href="<?= e(url('/download_apk.php') . '?v=' . rawurlencode((string)(defined('APP_VERSION') ? APP_VERSION : '1.0.0'))) ?>"><span aria-hidden="true">↓</span> تحميل تطبيق Vision Hunt APK</a>
  </div>
  <div class="hero-stat"><span>عدد اللاعبين</span><strong><?= number_format($total) ?></strong></div>
</section>
<section class="current-event-card<?= $activeEvent ? '' : ' current-event-empty' ?>" aria-labelledby="current-event-title">
  <?php if ($activeEvent): ?>
    <div class="current-event-heading"><span class="event-live-dot"></span><span class="eyebrow">الحدث الجاري الآن</span></div>
    <h2 id="current-event-title"><?= e($activeEvent['title']) ?></h2>
    <?php if ($activeEvent['description']): ?><p><?= nl2br(e($activeEvent['description'])) ?></p><?php endif; ?>
    <div class="current-event-dates"><span><small>تاريخ البداية</small><strong><?= e(format_datetime($activeEvent['starts_at'])) ?></strong></span><span><small>تاريخ الانتهاء</small><strong><?= e(format_datetime($activeEvent['ends_at'])) ?></strong></span></div>
  <?php else: ?>
    <div class="current-event-heading"><span class="event-idle-dot"></span><span class="eyebrow">حالة الأحداث</span></div>
    <h2 id="current-event-title">لا يوجد حدث جارٍ حاليًا</h2>
    <p>تابع صفحة الأحداث لمعرفة مواعيد التحديات القادمة.</p>
  <?php endif; ?>
</section>
<section class="section-heading"><div><span class="eyebrow">الترتيب العام</span><h2>لوحة المتصدرين</h2></div><p>الصفحة <?= $page ?> من <?= $pages ?></p></section>
<div class="table-card">
  <?php if (!$players): ?>
    <div class="empty-state"><span class="empty-icon">◎</span><h3>لا يوجد لاعبون بعد</h3><p>ستظهر النتائج هنا بعد تسجيل اللاعبين ومزامنة بياناتهم.</p></div>
  <?php else: ?>
  <div class="table-scroll"><table>
    <thead><tr><th class="rank-col">الترتيب</th><th>اسم المستخدم</th><th>النقاط</th><th>آخر تحديث</th></tr></thead>
    <tbody>
    <?php foreach ($players as $index => $player):
      $rank = $offset + $index + 1;
      $rankClass = $rank <= 3 ? 'rank-top rank-' . $rank : '';
      $rowClass = $rank <= 3 ? ' class="podium-row podium-' . $rank . '"' : '';
    ?>
      <tr<?= $rowClass ?>>
        <td><span class="rank <?= $rankClass ?>"><?= $rank ?></span></td>
        <td><div class="player-cell"><span class="avatar"><?= e(strtoupper(substr($player['username'], 0, 1))) ?></span><strong><?= e($player['username']) ?></strong></div></td>
        <td><span class="score-pill"><?= e(format_score($player['score'])) ?></span></td>
        <td class="muted nowrap"><?= e(format_datetime($player['updated_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
<?php pagination_links($page, $pages, '/'); ?>
<p class="page-note">يتم ترتيب اللاعبين بالنقاط من الأعلى إلى الأقل، وعند التساوي يُقدّم الأسبق في الوصول إلى نقاطه.</p>
<?php require APP_ROOT . '/includes/footer.php'; ?>
