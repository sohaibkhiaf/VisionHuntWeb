<?php
$pageTitle = $pageTitle ?? 'Vision Hunt';
$adminPage = $adminPage ?? false;
$flashMessage = pull_flash();
$scriptPath = '/' . ltrim(str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$currentFile = basename($scriptPath);
$isAdminRoute = str_contains($scriptPath, '/admin/');
$activeNav = match (true) {
    !$isAdminRoute && $currentFile === 'index.php' => 'home',
    !$isAdminRoute && $currentFile === 'events.php' => 'events',
    $isAdminRoute && $currentFile === 'dashboard.php' => 'dashboard',
    $isAdminRoute && $currentFile === 'events.php' => 'admin-events',
    $isAdminRoute && $currentFile === 'players.php' => 'players',
    $isAdminRoute && $currentFile === 'login.php' => 'admin-login',
    default => '',
};
$navClass = static fn(string $key): string => $activeNav === $key ? ' class="active" aria-current="page"' : '';
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="لوحة ترتيب لاعبي Vision Hunt">
  <title><?= e($pageTitle) ?> | Vision Hunt</title>
  <link rel="icon" type="image/png" href="<?= e(url('/assets/images/logo.png')) ?>">
  <link rel="icon" type="image/png" href="<?= e(url('/assets/images/logo.png')) ?>">
  <link rel="icon" type="image/png" href="<?= e(url('/assets/images/logo.png')) ?>">
  <link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('/assets/css/events.css')) ?>">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="<?= e(url('/')) ?>"><img class="brand-logo" src="<?= e(url('/assets/images/logo.png')) ?>" alt=""><span>Vision <b>Hunt</b></span></a>
    <nav class="main-nav" aria-label="التنقل الرئيسي">
      <a<?= $navClass('home') ?> href="<?= e(url('/')) ?>">الترتيب</a>
      <a<?= $navClass('events') ?> href="<?= e(url('/events.php')) ?>">الأحداث</a>
      <?php if (admin_logged_in()): ?>
        <a<?= $navClass('dashboard') ?> href="<?= e(url('/admin/dashboard.php')) ?>">لوحة الإدارة</a>
        <a<?= $navClass('admin-events') ?> href="<?= e(url('/admin/events.php')) ?>">إدارة الأحداث</a>
        <a<?= $navClass('players') ?> href="<?= e(url('/admin/players.php')) ?>">اللاعبون</a>
        <form class="nav-inline" action="<?= e(url('/admin/logout.php')) ?>" method="post" onsubmit="return confirm('هل أنت متأكد من تسجيل الخروج؟');"><?= csrf_field() ?><button class="nav-link" type="submit">خروج</button></form>
      <?php else: ?>
        <a<?= $navClass('admin-login') ?> href="<?= e(url('/admin/login.php')) ?>">دخول المشرف</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container main-content">
<?php if ($flashMessage): ?>
  <div class="alert alert-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div>
<?php endif; ?>
