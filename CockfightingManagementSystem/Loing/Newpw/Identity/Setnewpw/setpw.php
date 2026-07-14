<?php
session_start();

if (empty($_SESSION['otp_verified']) || empty($_SESSION['reset_account'])) {
    header('Location: ../identity.php');
    exit();
}

require_once __DIR__ . '/../../../../config.php';

$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirm_password = (string) ($_POST['confirm_password'] ?? '');

    if (strlen($password) < 8) {
        $message = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
        $message_class = 'error';
    } elseif (strlen($password) > 72) {
        $message = 'รหัสผ่านต้องไม่เกิน 72 ตัวอักษร';
        $message_class = 'error';
    } elseif (!hash_equals($password, $confirm_password)) {
        $message = 'ยืนยันรหัสผ่านไม่ตรงกัน';
        $message_class = 'error';
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE member SET u_password = ? WHERE u_email = ? LIMIT 1');
        $stmt->bind_param('ss', $password_hash, $_SESSION['reset_account']);
        $stmt->execute();

        if ($stmt->affected_rows === 1) {
            $stmt->close();
            $conn->close();

            unset(
                $_SESSION['otp_verified'],
                $_SESSION['otp_verified_at'],
                $_SESSION['reset_account']
            );

            $_SESSION['reset_success'] = true;
            header('Location: ../../../loing.php');
            exit();
        }

        $stmt->close();
        $message = 'ไม่สามารถบันทึกรหัสผ่านได้ กรุณาลองใหม่อีกครั้ง';
        $message_class = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งรหัสผ่านใหม่</title>
    <link rel="stylesheet" href="./setpwstyle.css?v=<?php echo filemtime(__DIR__ . '/setpwstyle.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <main class="page-shell">
        <section class="reset-card" aria-labelledby="page-title">
            <h1 id="page-title">ตั้งรหัสผ่านใหม่</h1>

            <?php if ($message !== ''): ?>
                <div class="alert <?php echo htmlspecialchars($message_class, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <div class="field-group">
                    <label for="password">รหัสผ่าน</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="กรุณากรอกรหัสผ่าน"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="72"
                        required
                    >
                </div>

                <div class="field-group">
                    <label for="confirm_password">ยืนยันรหัสผ่าน</label>
                    <input
                        id="confirm_password"
                        type="password"
                        name="confirm_password"
                        placeholder="กรุณากรอกยืนยันรหัสผ่าน"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="72"
                        required
                    >
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-back" onclick="window.location.href='../identity.php'">ย้อนกลับ</button>
                    <button type="submit" class="btn btn-confirm">ยืนยัน</button>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
