<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$pageTitle = 'لوحة الإدارة';
$totalPlayers = (int)db()->query('SELECT COUNT(*) FROM players')->fetchColumn();
$topScore = (int)db()->query('SELECT COALESCE(MAX(score), 0) FROM players')->fetchColumn();
$totalScore = (int)db()->query('SELECT COALESCE(SUM(score), 0) FROM players')->fetchColumn();
$recent = db()->query('SELECT id, username, score, updated_at FROM players ORDER BY updated_at DESC LIMIT 5')->fetchAll();
require APP_ROOT . '/includes/header.php';
?>
<section class="page-title-row">
  <div>
    <span class="eyebrow">ADMIN CONSOLE</span>
    <h1>لوحة الإدارة</h1>
    <p class="muted">مرحبًا <?= e($GLOBALS['current_admin']['username']) ?>، هذه نظرة عامة على نشاط اللعبة.</p>
  </div>
  <a class="button button-primary" href="<?= e(url('/admin/create_player.php')) ?>">＋ إضافة لاعب</a>
</section>

<div class="stats-grid">
  <article class="stat-card">
    <span class="stat-icon">♙</span>
    <span class="muted">إجمالي اللاعبين</span>
    <strong><?= number_format($totalPlayers) ?></strong>
  </article>
  <article class="stat-card">
    <span class="stat-icon">★</span>
    <span class="muted">أعلى نتيجة مسجلة</span>
    <strong><?= e(format_score($topScore)) ?></strong>
  </article>
  <article class="stat-card">
    <span class="stat-icon">✦</span>
    <span class="muted">مجموع نقاط اللاعبين</span>
    <strong><?= e(format_score($totalScore)) ?></strong>
  </article>
</div>

<section class="section-heading">
  <div>
    <span class="eyebrow">RECENT ACTIVITY</span>
    <h2>آخر اللاعبين نشاطًا</h2>
  </div>
  <a class="button button-secondary" href="<?= e(url('/admin/players.php')) ?>">عرض كل اللاعبين</a>
</section>

<div class="table-card">
  <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>اللاعب</th>
          <th>النقاط</th>
          <th>آخر تحديث</th>
          <th>التفاصيل</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$recent): ?>
          <tr><td colspan="4" class="empty-cell">لا يوجد لاعبون بعد.</td></tr>
        <?php endif; ?>
        <?php foreach ($recent as $p): ?>
          <tr>
            <td><strong><?= e($p['username']) ?></strong></td>
            <td><?= e(format_score($p['score'])) ?></td>
            <td class="muted nowrap"><?= e(format_datetime($p['updated_at'])) ?></td>
            <td><a class="button button-small button-secondary" href="<?= e(url('/admin/player.php?id=' . (int)$p['id'])) ?>">فحص اللاعب</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require APP_ROOT . '/includes/footer.php'; ?>
