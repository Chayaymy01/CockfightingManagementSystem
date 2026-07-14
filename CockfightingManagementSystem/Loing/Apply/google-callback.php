<?php
session_start();
require_once __DIR__ . '/../../config.php';

function returnToApply(string $message)
{
    $_SESSION['oauth_error'] = $message;
    header('Location: apply.php');
    exit();
}

if (
    GOOGLE_CLIENT_ID === '' ||
    GOOGLE_CLIENT_SECRET === '' ||
    empty($_GET['code']) ||
    empty($_GET['state']) ||
    empty($_SESSION['google_oauth_state']) ||
    !hash_equals($_SESSION['google_oauth_state'], (string) $_GET['state'])
) {
    returnToApply('ไม่สามารถยืนยันการเข้าสู่ระบบด้วย Google ได้');
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$callback_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/google-callback.php';
$token_fields = http_build_query([
    'code' => (string) $_GET['code'],
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => $callback_url,
    'grant_type' => 'authorization_code',
]);

$token_ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($token_ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $token_fields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);
$token_response = curl_exec($token_ch);
$token_status = curl_getinfo($token_ch, CURLINFO_HTTP_CODE);
curl_close($token_ch);
$token = json_decode((string) $token_response, true);

if ($token_status !== 200 || empty($token['access_token'])) {
    returnToApply('Google Login ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
}

$profile_ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
curl_setopt_array($profile_ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token['access_token']],
]);
$profile_response = curl_exec($profile_ch);
$profile_status = curl_getinfo($profile_ch, CURLINFO_HTTP_CODE);
curl_close($profile_ch);
$profile = json_decode((string) $profile_response, true);

if (
    $profile_status !== 200 ||
    empty($profile['sub']) ||
    empty($profile['email']) ||
    empty($profile['email_verified'])
) {
    returnToApply('ไม่สามารถอ่านข้อมูลบัญชี Google ที่ยืนยันอีเมลแล้วได้');
}

$email = substr((string) $profile['email'], 0, 50);
$real_name = mb_substr((string) ($profile['name'] ?? $email), 0, 50);
$stmt = $conn->prepare('SELECT user_id, real_name FROM member WHERE u_email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$member) {
    $username = $email;
    $google_id = substr('google:' . (string) $profile['sub'], 0, 50);
    $password_hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $address = '-';
    $insert = $conn->prepare('INSERT INTO member (user_name, real_name, user_line, u_password, u_address, u_email) VALUES (?, ?, ?, ?, ?, ?)');
    $insert->bind_param('ssssss', $username, $real_name, $google_id, $password_hash, $address, $email);

    if (!$insert->execute()) {
        $insert->close();
        returnToApply('ไม่สามารถสร้างบัญชีจาก Google ได้');
    }

    $member = ['user_id' => $insert->insert_id, 'real_name' => $real_name];
    $insert->close();
}

$conn->close();
session_regenerate_id(true);
$_SESSION['user_id'] = (int) $member['user_id'];
$_SESSION['real_name'] = (string) $member['real_name'];
$_SESSION['login_attempt'] = 0;
unset($_SESSION['google_oauth_state']);
header('Location: ../dashboard.php');
exit();
