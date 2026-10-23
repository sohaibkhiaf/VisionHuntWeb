<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
if (admin_logged_in()) redirect('/admin/dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $retryAfter = admin_login_rate_limit_remaining($username);

    if ($retryAfter > 0) {
        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        $error = 'تم تجاوز عدد محاولات تسجيل الدخول. حاول مجددًا بعد انتهاء المهلة.';
    } else {
        $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            clear_admin_login_failures($username);
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$user['id'];
            db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);
            flash('success', 'تم تسجيل الدخول بنجاح.');
            redirect('/admin/dashboard.php');
        }

        record_admin_login_failure($username);
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
    }
}

$pageTitle = 'دخول المشرف';
require APP_ROOT . '/includes/header.php';
?>
<section class="auth-wrap"><div class="auth-card"><span class="eyebrow">ADMIN ACCESS</span><h1>تسجيل دخول المشرف</h1><p class="muted">هذه الصفحة مخصّصة لحسابات إدارة الموقع.</p>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="form-stack" autocomplete="on">
  <?= csrf_field() ?>
  <label>اسم المستخدم<input name="username" required minlength="3" maxlength="50" autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>"></label>
  <label>كلمة المرور<input type="password" name="password" required autocomplete="current-password"></label>
  <button class="button button-primary button-full" type="submit">دخول إلى لوحة الإدارة</button>
</form></div></section>
<?php require APP_ROOT . '/includes/footer.php'; ?>
