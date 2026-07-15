<?php
session_start();

// อนุญาตให้เข้าหน้านี้ได้เฉพาะผู้ที่ขอ OTP หรือยืนยัน OTP แล้วเท่านั้น
if (!isset($_SESSION['reset_account']) || (!isset($_SESSION['generated_otp']) && empty($_SESSION['otp_verified']))) {
    header('Location: ../pw.php');
    exit();
}

$display_account = (string) $_SESSION['reset_account'];
$alert_message = '';
$alert_class = '';

if (empty($_SESSION['otp_csrf_token'])) {
    $_SESSION['otp_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_SESSION['otp_verified'])) {
    $csrf_token = (string) ($_POST['csrf_token'] ?? '');
    $otp_parts = $_POST['otp'] ?? [];
    $user_otp = is_array($otp_parts) ? implode('', $otp_parts) : '';
    $otp_attempts = (int) ($_SESSION['otp_attempts'] ?? 0);

    if (!hash_equals($_SESSION['otp_csrf_token'], $csrf_token)) {
        $alert_message = 'คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
        $alert_class = 'error';
    } elseif (!preg_match('/^\d{6}$/', $user_otp)) {
        $alert_message = 'กรุณากรอกรหัส OTP ให้ครบ 6 หลัก';
        $alert_class = 'error';
    } elseif (!isset($_SESSION['otp_expires_at']) || time() > (int) $_SESSION['otp_expires_at']) {
        unset($_SESSION['generated_otp'], $_SESSION['otp_expires_at'], $_SESSION['otp_attempts']);
        $alert_message = 'รหัส OTP หมดอายุแล้ว กรุณาขอรหัสใหม่';
        $alert_class = 'error';
    } elseif ($otp_attempts >= 5) {
        unset($_SESSION['generated_otp'], $_SESSION['otp_expires_at'], $_SESSION['otp_attempts']);
        $alert_message = 'กรอกรหัสผิดเกินจำนวนที่กำหนด กรุณาขอรหัสใหม่';
        $alert_class = 'error';
    } elseif (hash_equals((string) $_SESSION['generated_otp'], $user_otp)) {
        $_SESSION['otp_verified'] = true;
        $_SESSION['otp_verified_at'] = time();

        unset(
            $_SESSION['generated_otp'],
            $_SESSION['otp_expires_at'],
            $_SESSION['otp_attempts'],
            $_SESSION['otp_csrf_token']
        );

        $alert_message = 'ยืนยันรหัส OTP ถูกต้องแล้ว';
        $alert_class = 'success';
        header('Location: ../newpw.php');
        exit;
    } else {
        $_SESSION['otp_attempts'] = $otp_attempts + 1;
        $remaining_attempts = max(0, 5 - $_SESSION['otp_attempts']);
        $alert_message = 'รหัส OTP ไม่ถูกต้อง เหลือโอกาสอีก ' . $remaining_attempts . ' ครั้ง';
        $alert_class = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันรหัส OTP</title>

    <script src="https://cdn.tailwindcss.com"></script>
    

    <link rel="stylesheet" href="../../../css/verify-otp.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../css/motion.css">
</head>


<div id="otpModal" class="modal">

    <div class="modal-box">

        <i class="bi bi-check-circle-fill modal-icon"></i>

        <h2>ส่งรหัสสำเร็จ</h2>

        <p>
            ระบบได้ส่งรหัส OTP ใหม่แล้ว<br>
            กรุณาตรวจสอบข้อความ SMS
        </p>

        <button id="closeModal" class="login-submit-btn">
            ตกลง
        </button>

    </div>

</div>

<body class="login-page">

    <a href="../../../html/index.html" class="back-btn">
        <i class="bi bi-arrow-left"></i>
    </a>

        <a href="../pw.php" class="back-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
  <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" />
</svg>
</a>

    <main class="min-h-screen flex items-center justify-center px-4">
        <section class="login-card reveal-scale w-full max-w-[90%] sm:max-w-[430px] md:max-w-[520px]">
            <h1 class="text-2xl font-bold text-center mb-6">
                ยืนยันรหัส OTP
            </h1>

            <form
                id="otp-form"
                method="POST"
                action="identity.php" data-success-redirect="../newpw.php"
            >
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['otp_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-4">
                    <label class="login-label">
                        กรุณากรอกรหัส OTP จำนวน 6 หลัก
                        ที่ส่งไปยังอีเมลของคุณ
                    </label>

                    <div class="flex justify-between items-center mt-2">
                        <span class="login-label">อีเมล: <?php echo htmlspecialchars($display_account, ENT_QUOTES, 'UTF-8'); ?></span>
                        <a href="../pw.php" class="text-sm font-medium text-rgba-600 hover:underline">
                            เปลี่ยนเบอร์โทรศัพท์?
                        </a>
                    </div>

                    <div class="otp-group">
                        <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" required>
                        <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" required>
                        <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" required>
                        <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" required>
                        <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" required>
                        <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" required>
                    </div>

                    <p id="otp-error" class="text-center text-sm font-semibold text-red-600 mt-2 min-h-[20px]"><?php echo htmlspecialchars($alert_message); ?></p>

                    <p class="text-center text-sm text-gray-500 mt-2">
                            ไม่ได้รับรหัส?

                        <button
                            type="button"
                            id="resend-btn"
                            class="font-semibold text-emerald-600 hover:underline">
                            ส่งรหัสใหม่
                        </button>

                        <br>

                        <span id="timer" class="text-gray-400 text-xs">
                            สามารถส่งใหม่ได้ใน 01:00
                        </span>

                    </p>

                <button type="submit" class="login-submit-btn mt-6">
                    ยืนยัน
                </button>
            </form>
        </section>
    </main>

    <script src="../../../Js/verify-otp.js"></script>
    <script src="../../../Js/motion.js"></script>
</body>
</html>
