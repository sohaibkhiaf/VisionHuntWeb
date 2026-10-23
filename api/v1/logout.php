<?php
require_once __DIR__ . '/_init.php';
api_method('POST');
$player = api_auth_player();
$header = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
if ($header === '' && function_exists('getallheaders')) {
    $headers = getallheaders();
    $header = (string)($headers['Authorization'] ?? $headers['authorization'] ?? '');
}
if (!preg_match('/^Bearer\s+([a-f0-9]{64})$/i', trim($header), $matches)) {
    api_json(['success'=>false,'message'=>'رمز الوصول غير صالح.'], 401);
}
db()->prepare('DELETE FROM api_tokens WHERE player_id=? AND token_hash=?')->execute([(int)$player['id'], hash('sha256', $matches[1])]);
api_json(['success'=>true,'message'=>'تم تسجيل الخروج وإبطال رمز الوصول الحالي.']);
