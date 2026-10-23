<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    exit('معرّف لاعب غير صالح.');
}

$stmt = db()->prepare('SELECT * FROM players WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$player = $stmt->fetch();
if (!$player) {
    http_response_code(404);
    exit('اللاعب غير موجود.');
}

// Fetch completed tasks from newest to oldest.
$tasksStmt = db()->prepare(
    'SELECT object_name_ar, object_name_en, rarity, points, confidence, created_at
     FROM completed_tasks
     WHERE player_id = ?
     ORDER BY created_at DESC, id DESC'
);
$tasksStmt->execute([$id]);
$tasks = $tasksStmt->fetchAll();

// Count completed tasks by rarity.
$rarityCount = ['COMMON' => 0, 'UNCOMMON' => 0, 'RARE' => 0];
foreach ($tasks as $t) {
    $r = $t['rarity'] ?? 'COMMON';
    if (isset($rarityCount[$r])) $rarityCount[$r]++;
}

// Map rarity values to their display labels and styles.
const RARITY_LABELS = [
    'COMMON'   => ['label' => 'شائع',      'class' => 'confidence-high'],
    'uncommon' => ['label' => 'غير شائع',  'class' => 'confidence-mid'],
    'RARE'     => ['label' => 'نادر',       'class' => 'confidence-low'],
];
function rarity_label(string $r): string {
    return match(strtoupper($r)) {
        'COMMON'   => 'شائع',
        'UNCOMMON' => 'غير شائع',
        'RARE'     => 'نادر',
        default    => $r,
    };
}
function rarity_class(string $r): string {
    return match(strtoupper($r)) {
        'COMMON'   => 'confidence-high',
        'UNCOMMON' => 'confidence-mid',
        'RARE'     => 'confidence-low',
        default    => '',
    };
}

$pageTitle = 'اللاعب ' . $player['username'];
require APP_ROOT . '/includes/header.php';
?>
<section class="page-title-row">
  <div>
    <span class="eyebrow">PLAYER DETAILS</span>
    <h1><?= e($player['username']) ?></h1>
    <p class="muted">تفاصيل الحساب وسجل المهام المنجزة.</p>
  </div>
  <a class="button button-secondary" href="<?= e(url('/admin/players.php')) ?>">العودة للاعبين</a>
</section>

<div class="player-detail-grid">
  <article class="detail-card">
    <span class="detail-label">مجموع النقاط</span>
    <strong class="detail-value"><?= e(format_score($player['score'])) ?></strong>
  </article>
  <article class="detail-card">
    <span class="detail-label">عدد المهام المكتملة</span>
    <strong class="detail-value"><?= number_format(count($tasks)) ?></strong>
  </article>
  <article class="detail-card">
    <span class="detail-label">رقم الهاتف</span>
    <strong class="detail-value detail-text"><?= e($player['phone'] ?: 'غير مسجّل') ?></strong>
  </article>
  <article class="detail-card">
    <span class="detail-label">آخر تحديث للنشاط</span>
    <strong class="detail-value detail-text"><?= e(format_datetime($player['updated_at'])) ?></strong>
  </article>
  <article class="detail-card">
    <span class="detail-label">تاريخ إنشاء الحساب</span>
    <strong class="detail-value detail-text"><?= e(format_datetime($player['created_at'])) ?></strong>
  </article>
</div>

<?php if ($tasks): ?>
<!-- Rarity statistics. -->
<div class="stats-grid" style="margin-top:22px">
  <article class="stat-card">
    <span class="stat-icon">◎</span>
    <span class="muted">شائع (<?= $rarityCount['COMMON'] * 10 ?> نقطة)</span>
    <strong><?= number_format($rarityCount['COMMON']) ?></strong>
  </article>
  <article class="stat-card">
    <span class="stat-icon">◈</span>
    <span class="muted">غير شائع (<?= $rarityCount['UNCOMMON'] * 20 ?> نقطة)</span>
    <strong><?= number_format($rarityCount['UNCOMMON']) ?></strong>
  </article>
  <article class="stat-card">
    <span class="stat-icon">✦</span>
    <span class="muted">نادر (<?= $rarityCount['RARE'] * 30 ?> نقطة)</span>
    <strong><?= number_format($rarityCount['RARE']) ?></strong>
  </article>
</div>
<?php endif; ?>

<!-- Completed tasks table. -->
<section class="section-heading">
  <div>
    <span class="eyebrow">COMPLETED TASKS</span>
    <h2>المهام المنجزة</h2>
  </div>
  <span class="count-badge"><?= number_format(count($tasks)) ?> مهمة</span>
</section>

<?php if (!$tasks): ?>
  <div class="empty-state table-card">
    <span class="empty-icon">◎</span>
    <h3>لا توجد مهام مكتملة</h3>
    <p>ستظهر المهام هنا بعد مزامنة التطبيق.</p>
  </div>
<?php else: ?>
  <div class="table-card">
    <div class="table-scroll">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>الكائن</th>
            <th>الندرة</th>
            <th>النقاط</th>
            <th>الدقة</th>
            <th>تاريخ الإنجاز</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tasks as $idx => $task):
            $conf = (float)$task['confidence'];
          ?>
            <tr>
              <td class="muted"><?= $idx + 1 ?></td>
              <td>
                <strong><?= e($task['object_name_ar']) ?></strong>
                <br><small class="muted" style="font-size:11px;direction:ltr"><?= e($task['object_name_en']) ?></small>
              </td>
              <td>
                <span class="confidence <?= rarity_class($task['rarity']) ?>">
                  <?= rarity_label($task['rarity']) ?>
                </span>
              </td>
              <td><span class="score-pill"><?= (int)$task['points'] ?></span></td>
              <td>
                <span class="confidence <?= $conf >= 85 ? 'confidence-high' : ($conf >= 50 ? 'confidence-mid' : 'confidence-low') ?>">
                  <?= e(number_format($conf, 1)) ?>%
                </span>
              </td>
              <td class="muted nowrap"><?= e(format_datetime($task['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require APP_ROOT . '/includes/footer.php'; ?>
