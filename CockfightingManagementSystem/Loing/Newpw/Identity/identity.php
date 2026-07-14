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
    <title>ยืนยันตัวตนด้วย OTP</title>
    <link rel="stylesheet" href="./identitystyle.css?v=<?php echo filemtime(__DIR__ . '/identitystyle.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
    <main class="container">
        <section class="otp-card">
            <h2>ยืนยันตัวตนของคุณ</h2>
            <p>
                กรุณากรอกรหัส OTP 6 หลักที่ส่งไปยังอีเมล<br>
                <span class="phone"><?php echo htmlspecialchars($display_account, ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="../pw.php" class="change-num">เปลี่ยนอีเมล</a>
            </p>

            <?php if ($alert_message !== ''): ?>
                <div class="alert <?php echo htmlspecialchars($alert_class, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                    <?php echo htmlspecialchars($alert_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($_SESSION['otp_verified'])): ?>
                <form action="" method="POST" id="otp-form">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['otp_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"
                    >

                    <div class="otp-inputs">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <input
                                type="text"
                                name="otp[]"
                                maxlength="1"
                                inputmode="numeric"
                                pattern="[0-9]"
                                class="otp-field"
                                aria-label="OTP หลักที่ <?php echo $i + 1; ?>"
                                <?php echo $i === 0 ? 'autocomplete="one-time-code" autofocus' : 'autocomplete="off"'; ?>
                                required
                            >
                        <?php endfor; ?>
                    </div>

                    <p class="timer">รหัส OTP มีอายุ 5 นาที</p>

                    <div class="btn-group">
                        <button type="button" class="btn btn-back" onclick="window.location.href='../pw.php'">ย้อนกลับ</button>
                        <button type="submit" class="btn btn-confirm">ยืนยัน</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="btn-group">
                    <button type="button" class="btn btn-back" onclick="window.location.href='../pw.php'">ย้อนกลับ</button>
                    <button type="button" class="btn btn-confirm" onclick="window.location.href='Setnewpw/setpw.php'">ตั้งรหัสผ่านใหม่</button>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="./identityscrip..js?v=<?php echo filemtime(__DIR__ . '/identityscrip..js'); ?>"></script>
</body>
</html>
