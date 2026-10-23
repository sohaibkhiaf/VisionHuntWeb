<?php
require_once __DIR__ . '/_init.php';
api_method('POST');
$player = api_auth_player();
$data = api_body();
$oldPassword = (string)($data['old_password'] ?? '');
$newPassword = array_key_exists('new_password', $data) ? (string)$data['new_password'] : null;
$phoneProvided = array_key_exists('phone', $data);
$phone = $phoneProvided ? trim((string)($data['phone'] ?? '')) : null;
if ($oldPassword === '') api_json(['success'=>false,'message'=>'يجب إرسال كلمة المرور القديمة لتأكيد التعديل.'], 422);
$stmt = db()->prepare('SELECT password_hash FROM players WHERE id=?'); $stmt->execute([(int)$player['id']]); $hash = $stmt->fetchColumn();
if (!$hash || !password_verify($oldPassword, (string)$hash)) api_json(['success'=>false,'message'=>'كلمة المرور القديمة غير صحيحة.'], 403);
if (!$phoneProvided && ($newPassword === null || $newPassword === '')) api_json(['success'=>false,'message'=>'لم يتم إرسال أي حقل لتحديثه.'], 422);
if ($phoneProvided && strlen((string)$phone) > 30) api_json(['success'=>false,'message'=>'رقم الهاتف طويل جدًا.'], 422);
if ($newPassword !== null && $newPassword !== '' && (strlen($newPassword) < 8 || strlen($newPassword) > 200)) api_json(['success'=>false,'message'=>'كلمة المرور الجديدة يجب أن تكون من 8 أحرف على الأقل.'], 422);
$sets = []; $params = [];
if ($phoneProvided) { $sets[] = 'phone=?'; $params[] = $phone !== '' ? $phone : null; }
if ($newPassword !== null && $newPassword !== '') { $sets[] = 'password_hash=?'; $params[] = password_hash($newPassword, PASSWORD_DEFAULT); }
$params[] = (int)$player['id'];
db()->prepare('UPDATE players SET ' . implode(', ', $sets) . ' WHERE id=?')->execute($params);
$stmt = db()->prepare('SELECT id, username, phone, score, updated_at FROM players WHERE id=?'); $stmt->execute([(int)$player['id']]);
api_json(['success'=>true,'message'=>'تم تحديث معلومات الحساب. اسم المستخدم لا يمكن تغييره.','data'=>$stmt->fetch()]);
