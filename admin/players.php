<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$pageTitle = 'اللاعبون';
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$q = trim((string)($_GET['q'] ?? ''));
$where = '';
$params = [];
if ($q !== '') {
    $where = ' WHERE username LIKE ?';
    $params[] = '%' . $q . '%';
}
$count = db()->prepare('SELECT COUNT(*) FROM players' . $where);
$count->execute($params);
$total = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$sql = 'SELECT p.id, p.username, p.phone, p.score, p.updated_at FROM players p' . ($q !== '' ? ' WHERE p.username LIKE ?' : '') . ' ORDER BY p.updated_at DESC, p.id DESC LIMIT ? OFFSET ?';
$stmt = db()->prepare($sql);
$i = 1;
if ($q !== '') {
    $stmt->bindValue($i++, '%' . $q . '%', PDO::PARAM_STR);
}
$stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($i, $offset, PDO::PARAM_INT);
$stmt->execute();
$players = $stmt->fetchAll();

require APP_ROOT . '/includes/header.php';
?>
<section class="page-title-row">
  <div>
    <span class="eyebrow">PLAYER MANAGEMENT</span>
    <h1>اللاعبون</h1>
    <p class="muted">يُرتّب الجدول حسب آخر تحديث، من الأحدث نشاطًا إلى الأقدم.</p>
  </div>
  <a class="button button-primary" href="<?= e(url('/admin/create_player.php')) ?>">＋ إضافة لاعب</a>
</section>

<form method="get" class="search-bar">
  <input name="q" value="<?= e($q) ?>" placeholder="ابحث باسم المستخدم…" aria-label="ابحث باسم المستخدم">
  <button class="button button-secondary" type="submit">بحث</button>
  <?php if ($q !== ''): ?>
    <a class="text-link" href="<?= e(url('/admin/players.php')) ?>">مسح البحث</a>
  <?php endif; ?>
</form>

<div class="table-card">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>اسم المستخدم</th>
          <th>رقم الهاتف</th>
          <th>النقاط</th>
          <th>آخر تحديث</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$players): ?>
          <tr><td colspan="6" class="empty-cell">لا توجد نتائج مطابقة.</td></tr>
        <?php endif; ?>
        <?php foreach ($players as $i => $p): ?>
          <tr>
            <td><span class="rank"><?= $offset + $i + 1 ?></span></td>
            <td><div class="player-cell"><span class="avatar"><?= e(strtoupper(substr($p['username'], 0, 1))) ?></span><strong><?= e($p['username']) ?></strong></div></td>
            <td class="nowrap"><?= e($p['phone'] ?: '—') ?></td>
            <td><span class="score-pill"><?= e(format_score($p['score'])) ?></span></td>
            <td class="muted nowrap"><?= e(format_datetime($p['updated_at'])) ?></td>
            <td><a class="button button-small button-secondary" href="<?= e(url('/admin/player.php?id=' . (int)$p['id'])) ?>">تفاصيل</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php pagination_links($page, $pages, '/admin/players.php', $q !== '' ? ['q' => $q] : []); ?>
<p class="page-note">عدد اللاعبين: <?= number_format($total) ?>.</p>
<?php require APP_ROOT . '/includes/footer.php'; ?>
