<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
    $_SESSION['oauth_error'] = 'ยังไม่ได้ตั้งค่า Google Client ID และ Client Secret';
    header('Location: apply.php');
    exit();
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$callback_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/google-callback.php';
$_SESSION['google_oauth_state'] = bin2hex(random_bytes(24));

$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => $callback_url,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $_SESSION['google_oauth_state'],
    'prompt' => 'select_account',
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit();
