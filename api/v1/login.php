<?php
require_once __DIR__ . '/_init.php';
api_method('POST');
$data = api_body();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');
api_enforce_auth_rate_limit('login', $username);
$stmt = db()->prepare('SELECT id, username, password_hash, phone, score, updated_at FROM players WHERE username=? LIMIT 1');
$stmt->execute([$username]); $player = $stmt->fetch();
if (!$player || !password_verify($password, $player['password_hash'])) api_json(['success'=>false,'message'=>'اسم المستخدم أو كلمة المرور غير صحيحة.'], 401);
$token = issue_player_token((int)$player['id']);
unset($player['password_hash']);
api_json(['success'=>true,'message'=>'تم تسجيل الدخول.','data'=>['player'=>$player,'token'=>$token,'token_type'=>'Bearer','expires_in'=>2592000]]);
