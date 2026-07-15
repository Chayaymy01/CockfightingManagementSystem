<?php
session_start();
require_once "../../config.php"; 

$message = "";
$message_class = "";

function sendOtpEmail(string $email, string $otp): array
{
    if (!extension_loaded('curl')) {
        return ['success' => false, 'message' => 'PHP ยังไม่ได้เปิดใช้งานส่วนขยาย cURL'];
    }

    $payload = json_encode([
        'to' => $email,
        'otp' => $otp,
        'secret' => GOOGLE_OTP_API_SECRET,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init(GOOGLE_OTP_WEB_APP_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'Super-Kaichon-OTP/1.0',
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $effectiveUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    if ($response === false) {
        error_log('Google OTP cURL error: ' . $curlError);
        return ['success' => false, 'message' => 'เชื่อมต่อบริการส่งอีเมลไม่ได้: ' . $curlError];
    }

    // ถ้า Web App ไม่เปิดเป็น Anyone ระบบจะถูกส่งไปหน้า Google Login
    if ($httpStatus === 401 || str_contains($effectiveUrl, 'accounts.google.com')) {
        return ['success' => false, 'message' => 'Google Apps Script ยังไม่อนุญาตให้บุคคลทั่วไปเรียกใช้ กรุณา Deploy ใหม่โดยเลือก Execute as: Me และ Who has access: Anyone'];
    }

    if ($httpStatus < 200 || $httpStatus >= 300) {
        error_log('Google OTP HTTP ' . $httpStatus . ': ' . substr((string) $response, 0, 500));
        return ['success' => false, 'message' => 'บริการส่ง OTP ตอบกลับ HTTP ' . $httpStatus];
    }

    if (stripos($contentType, 'text/html') !== false || preg_match('/^\s*</', (string) $response)) {
        error_log('Google OTP returned HTML: ' . substr((string) $response, 0, 500));
        return ['success' => false, 'message' => 'Google Apps Script ส่งหน้า Login/HTML กลับมา กรุณาตรวจสิทธิ์ Deployment'];
    }

    $result = json_decode((string) $response, true);
    if (!is_array($result)) {
        error_log('Google OTP invalid JSON: ' . substr((string) $response, 0, 500));
        return ['success' => false, 'message' => 'Google Apps Script ตอบกลับไม่ใช่ JSON'];
    }

    $success = ($result['success'] ?? false) === true || ($result['status'] ?? '') === 'success';
    if (!$success) {
        return ['success' => false, 'message' => (string) ($result['message'] ?? 'Google Apps Script ส่งอีเมลไม่สำเร็จ')];
    }

    return ['success' => true, 'message' => 'ส่ง OTP สำเร็จ'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $account_input = trim((string) ($_POST['account_input'] ?? ''));

    if (!empty($account_input)) {

        // ตรวจสอบว่าสิ่งที่กรอกเข้ามาเป็นรูปแบบอีเมลที่ถูกต้องหรือไม่
        if (filter_var($account_input, FILTER_VALIDATE_EMAIL)) {

            // ตรวจสอบกับฐานข้อมูล member ว่ามีอีเมลนี้อยู่จริงไหม
            $stmt = $conn->prepare("SELECT user_id, u_email FROM member WHERE u_email = ? LIMIT 1");
            $stmt->bind_param("s", $account_input);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                $message = "ไม่พบบัญชีอีเมลนี้ในระบบ";
                $message_class = "error";
            } else {
                $row = $result->fetch_assoc();

                $secondsSinceLastSend = time() - (int) ($_SESSION['otp_last_sent_at'] ?? 0);
                if ($secondsSinceLastSend < 60) {
                    $message = 'กรุณารออีก ' . (60 - $secondsSinceLastSend) . ' วินาทีก่อนขอ OTP ใหม่';
                    $message_class = 'error';
                } else {
                    $otp_code = (string) random_int(100000, 999999);
                    $sendResult = sendOtpEmail((string) $row['u_email'], $otp_code);

                    if ($sendResult['success']) {
                        session_regenerate_id(true);
                        $_SESSION['reset_user_id'] = (int) $row['user_id'];
                        $_SESSION['reset_account'] = (string) $row['u_email'];
                        $_SESSION['generated_otp'] = $otp_code;
                        $_SESSION['otp_expires_at'] = time() + 300;
                        $_SESSION['otp_attempts'] = 0;
                        $_SESSION['otp_last_sent_at'] = time();
                        unset($_SESSION['otp_verified'], $_SESSION['otp_verified_at']);

                        header('Location: Identity/identity.php');
                        exit;
                    }

                    $message = 'ส่ง OTP ไม่สำเร็จ: ' . $sendResult['message'];
                    $message_class = 'error';
                }
            }
            $stmt->close();

        } else {
            $message = "รูปแบบอีเมลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง";
            $message_class = "error";
        }
    } else {
        $message = "กรุณากรอกที่อยู่อีเมล";
        $message_class = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน? - Super Kaichon</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="../../css/Forgot-Password.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../css/motion.css">
</head>


    
<body class="login-page">
    

    <a href="../loing.php" class="back-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="rgba" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
  <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" />
</svg>
</a>

    <main class="min-h-screen flex items-center justify-center px-4">
        <section class="login-card reveal-scale w-full max-w-[90%] sm:max-w-[430px] md:max-w-[520px]">
            <h1 class="text-2xl font-bold text-center mb-6">
                ลืมรหัสผ่าน?
            </h1>

            <form
                id="forgot-form"
                method="POST"
                action="pw.php" data-success-redirect="Identity/identity.php"
            >
                <div class="mb-4">
                    <label class="login-label mb-2">กรุณากรอกหมายเลขโทรศัพท์หรืออีเมลที่ใช้สมัครสมาชิก</label>
                    <label class="login-label ">เพื่อรับรหัสยืนยันสำหรับตั้งรหัสผ่านใหม่</label>
                    
                    <div class="input-group">
                    <input
                        type="text"
                        id="account"
                        name="account_input"
                        class="login-input"
                        placeholder="กรุณากรอกอีเมล"
                        required>

                    </div>
                </div>

                <p id="forgot-error" class="text-sm font-semibold text-red-600 mb-3 min-h-[20px]"><?php echo htmlspecialchars($message); ?></p>

                <button type="submit" class="login-submit-btn">
                    ส่งรหัสยืนยัน
                </button>

            </form>
        </section>
    </main>

    <script src="../../Js/forgot-password.js"></script>
    <script src="../../Js/motion.js"></script>
</body>
</html>
