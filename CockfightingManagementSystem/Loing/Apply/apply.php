<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (empty($_SESSION['register_csrf_token'])) {
    $_SESSION['register_csrf_token'] = bin2hex(random_bytes(32));
}

$member_count = 0;
$farm_count = 0;
$message = (string) ($_SESSION['oauth_error'] ?? '');
$message_class = $message !== '' ? 'error' : '';
unset($_SESSION['oauth_error']);

$member_result = $conn->query('SELECT COUNT(*) AS total FROM member');
if ($member_result) {
    $member_count = (int) $member_result->fetch_assoc()['total'];
}

$farm_result = $conn->query('SELECT COUNT(*) AS total FROM farm');
if ($farm_result) {
    $farm_count = (int) $farm_result->fetch_assoc()['total'];
}

$full_name = '';
$email = '';
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $csrf_token = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['register_csrf_token'], $csrf_token)) {
        $message = 'คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
        $message_class = 'error';
    } elseif (mb_strlen($full_name) < 2 || mb_strlen($full_name) > 50) {
        $message = 'กรุณากรอกชื่อ-นามสกุล 2–50 ตัวอักษร';
        $message_class = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 50) {
        $message = 'รูปแบบอีเมลไม่ถูกต้อง';
        $message_class = 'error';
    } elseif (!preg_match('/^[0-9+\-() ]{8,20}$/', $phone)) {
        $message = 'กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง';
        $message_class = 'error';
    } elseif ($address === '' || mb_strlen($address) > 250) {
        $message = 'กรุณากรอกที่อยู่ไม่เกิน 250 ตัวอักษร';
        $message_class = 'error';
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $message = 'รหัสผ่านต้องมี 8–72 ตัวอักษร';
        $message_class = 'error';
    } else {
        $username = $email;
        $check = $conn->prepare('SELECT user_id FROM member WHERE u_email = ? OR user_name = ? LIMIT 1');
        $check->bind_param('ss', $email, $username);
        $check->execute();
        $account_exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($account_exists) {
            $message = 'อีเมลนี้มีบัญชีอยู่แล้ว กรุณาเข้าสู่ระบบ';
            $message_class = 'error';
        } else {
            // ตารางไม่มีคอลัมน์เบอร์โทร จึงใช้ user_line เก็บเบอร์โทรศัพท์
            $line_or_phone = $phone;
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO member (user_name, real_name, user_line, u_password, u_address, u_email) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssss', $username, $full_name, $line_or_phone, $password_hash, $address, $email);

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                unset($_SESSION['register_csrf_token']);
                $_SESSION['registration_success'] = true;
                header('Location: ../loing.php');
                exit();
            }

            $stmt->close();
            $message = 'ไม่สามารถสมัครสมาชิกได้ กรุณาลองใหม่อีกครั้ง';
            $message_class = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Cockfighting System</title>
    <link rel="stylesheet" href="./applystyle.css?v=<?php echo filemtime(__DIR__ . '/applystyle.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <main class="signup-page">
        <a class="back-link" href="../loing.php" aria-label="กลับไปหน้าเข้าสู่ระบบ">←</a>

        <section class="impact-panel" aria-label="ข้อมูลสมาชิกและฟาร์ม">
            <p class="eyebrow"><span></span> SUPER KAICHON</p>
            <h1>เชื่อมโยงเกษตรกรไทยสู่<br>มาตรฐานสากล</h1>
            <div class="statistics">
                <div>
                    <strong><?php echo number_format($farm_count); ?></strong>
                    <small>ฟาร์มที่เข้าร่วม</small>
                </div>
                <div>
                    <strong><?php echo number_format($member_count); ?></strong>
                    <small>สมาชิก</small>
                </div>
            </div>
        </section>

        <section class="signup-card" aria-labelledby="signup-title">
            <h2 id="signup-title">สมัครสมาชิก</h2>

            <?php if ($message !== ''): ?>
                <div class="alert <?php echo htmlspecialchars($message_class, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['register_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <label for="full_name">ชื่อ-นามสกุล</label>
                <input id="full_name" type="text" name="full_name" value="<?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?>" placeholder="กรอกชื่อและนามสกุลของคุณ" maxlength="50" required>

                <label for="email">อีเมล</label>
                <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="example@email.com" maxlength="50" autocomplete="email" required>

                <label for="phone">เบอร์โทรศัพท์</label>
                <input id="phone" type="tel" name="phone" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" placeholder="08x-xxx-xxxx" maxlength="20" autocomplete="tel" required>

                <label for="address">ที่อยู่</label>
                <input id="address" type="text" name="address" value="<?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?>" placeholder="กรอกที่อยู่ของคุณ" maxlength="250" autocomplete="street-address" required>

                <label for="password">รหัสผ่าน</label>
                <input id="password" type="password" name="password" placeholder="อย่างน้อย 8 ตัวอักษร" minlength="8" maxlength="72" autocomplete="new-password" required>

                <p class="social-label">เข้าสู่ระบบด้วย</p>
                <div class="social-buttons">
                    <a class="social-login google-login" href="./google-login.php" aria-label="เข้าสู่ระบบด้วย Google">
                        <span>G</span>
                    </a>
                </div>

                <label class="terms">
                    <input type="checkbox" required>
                    <span>ฉันยอมรับ <a href="#">เงื่อนไขการใช้บริการ</a> และนโยบายความเป็นส่วนตัวของ Farm Champ</span>
                </label>

                <button class="submit-button" type="submit">สมัครสมาชิก <span>→</span></button>
            </form>

            <p class="login-link">มีบัญชีอยู่แล้ว? <a href="../loing.php">เข้าสู่ระบบ</a></p>
        </section>
    </main>
</body>
</html>
