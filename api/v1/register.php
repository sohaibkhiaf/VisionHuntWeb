<?php
require_once __DIR__ . '/_init.php';
api_method('POST');
api_enforce_auth_rate_limit('register');
$data = api_body();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');
$phone = trim((string)($data['phone'] ?? ''));
if (!valid_username($username)) api_json(['success'=>false,'message'=>'اسم المستخدم يجب أن يكون من 3 إلى 50 حرفًا/رقمًا، ويسمح بـ . _ - فقط.'], 422);
if (strlen($password) < 8 || strlen($password) > 200) api_json(['success'=>false,'message'=>'كلمة المرور يجب أن تكون من 8 أحرف على الأقل.'], 422);
if (strlen($phone) > 30) api_json(['success'=>false,'message'=>'رقم الهاتف طويل جدًا.'], 422);
try {
    $stmt = db()->prepare('INSERT INTO players (username, password_hash, phone, score) VALUES (?, ?, ?, 0)');
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $phone !== '' ? $phone : null]);
    $id = (int)db()->lastInsertId();
    $token = issue_player_token($id);
    api_json(['success'=>true,'message'=>'تم إنشاء حساب اللاعب.','data'=>['id'=>$id,'username'=>$username,'phone'=>$phone ?: null,'score'=>0,'token'=>$token,'token_type'=>'Bearer','expires_in'=>2592000]], 201);
} catch (PDOException $ex) {
    if ($ex->getCode() === '23000') api_json(['success'=>false,'message'=>'اسم المستخدم مستخدم بالفعل.'], 409);
    error_log((string)$ex);
    api_json(['success'=>false,'message'=>'تعذر إنشاء الحساب حاليًا.'], 500);
}
