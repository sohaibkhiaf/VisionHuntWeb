<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$pageTitle = 'إضافة لاعب';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $phone = trim((string)($_POST['phone'] ?? ''));
    if (!valid_username($username)) $errors[] = 'اسم المستخدم يجب أن يتكون من 3 إلى 50 حرفًا/رقمًا، ويسمح بـ . _ - فقط كرموز إضافية.';
    if (strlen($password) < 8) $errors[] = 'كلمة المرور يجب ألا تقل عن 8 أحرف.';
    if (strlen($phone) > 30) $errors[] = 'رقم الهاتف طويل جدًا.';
    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO players (username, password_hash, phone, score) VALUES (?, ?, ?, 0)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $phone !== '' ? $phone : null]);
            flash('success', 'تم إنشاء حساب اللاعب بنجاح.');
            redirect('/admin/player.php?id=' . (int)db()->lastInsertId());
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') $errors[] = 'اسم المستخدم مستخدم بالفعل.';
            else throw $ex;
        }
    }
}
require APP_ROOT . '/includes/header.php';
?>
<section class="page-title-row"><div><span class="eyebrow">NEW PLAYER</span><h1>إضافة لاعب</h1><p class="muted">يمكن للاعب استخدام بيانات الدخول هذه في التطبيق.</p></div></section>
<div class="form-card narrow-card">
<?php foreach ($errors as $error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" class="form-stack"><?= csrf_field() ?>
<label>اسم المستخدم<input name="username" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_.-]{3,50}" value="<?= e($_POST['username'] ?? '') ?>"><small>أحرف إنجليزية وأرقام، مع . أو _ أو - عند الحاجة.</small></label>
<label>كلمة المرور المؤقتة<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
<label>رقم الهاتف (اختياري)<input name="phone" type="tel" maxlength="30" value="<?= e($_POST['phone'] ?? '') ?>"></label>
<div class="form-actions"><button class="button button-primary" type="submit">إنشاء الحساب</button><a class="button button-secondary" href="<?= e(url('/admin/players.php')) ?>">إلغاء</a></div>
</form></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
