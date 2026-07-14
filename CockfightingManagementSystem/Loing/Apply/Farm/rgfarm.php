<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../../loing.php');
    exit();
}

if (empty($_SESSION['farm_csrf_token'])) {
    $_SESSION['farm_csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$farm_data = $_SESSION['farm_registration'] ?? [];
$farm_name = (string) ($farm_data['farm_name'] ?? '');
$farm_contact = (string) ($farm_data['farm_contact'] ?? '');
$farm_location = (string) ($farm_data['farm_location'] ?? '');
$farm_details = (string) ($farm_data['farm_details'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = (string) ($_POST['csrf_token'] ?? '');
    $farm_name = trim((string) ($_POST['farm_name'] ?? ''));
    $farm_contact = trim((string) ($_POST['farm_contact'] ?? ''));
    $farm_location = trim((string) ($_POST['farm_location'] ?? ''));
    $farm_details = trim((string) ($_POST['farm_details'] ?? ''));

    if (!hash_equals($_SESSION['farm_csrf_token'], $csrf_token)) {
        $message = 'คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } elseif ($farm_name === '' || mb_strlen($farm_name) > 50) {
        $message = 'กรุณากรอกชื่อฟาร์มไม่เกิน 50 ตัวอักษร';
    } elseif ($farm_contact === '' || mb_strlen($farm_contact) > 200) {
        $message = 'กรุณากรอกข้อมูลติดต่อไม่เกิน 200 ตัวอักษร';
    } elseif ($farm_location === '') {
        $message = 'กรุณากรอกสถานที่ตั้งฟาร์ม';
    } elseif ($farm_details === '' || mb_strlen($farm_details) > 200) {
        $message = 'กรุณากรอกรายละเอียดฟาร์มไม่เกิน 200 ตัวอักษร';
    } else {
        $_SESSION['farm_registration'] = [
            'farm_name' => $farm_name,
            'farm_contact' => $farm_contact,
            'farm_location' => $farm_location,
            'farm_details' => $farm_details,
        ];

        header('Location: bank.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครฟาร์ม</title>
    <link rel="stylesheet" href="./rgfarmstyle.css?v=<?php echo filemtime(__DIR__ . '/rgfarmstyle.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <main class="farm-page">
        <a class="back-button" href="../../loing.php" aria-label="กลับไปหน้าเข้าสู่ระบบ">←</a>
        <div class="owner-badge">🐓 เจ้าของฟาร์ม</div>

        <section class="farm-card" aria-labelledby="farm-title">
            <h1 id="farm-title">สมัครฟาร์ม</h1>
            <p class="subtitle">สมัครสมาชิกฟาร์ม</p>

            <?php if ($message !== ''): ?>
                <div class="alert" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['farm_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <label for="farm_name">ชื่อฟาร์ม</label>
                <input id="farm_name" type="text" name="farm_name" value="<?php echo htmlspecialchars($farm_name, ENT_QUOTES, 'UTF-8'); ?>" placeholder="ชื่อฟาร์ม" maxlength="50" required>

                <label for="farm_contact">ข้อมูลติดต่อของฟาร์ม</label>
                <input id="farm_contact" type="text" name="farm_contact" value="<?php echo htmlspecialchars($farm_contact, ENT_QUOTES, 'UTF-8'); ?>" placeholder="เบอร์โทร อีเมล หรือ LINE" maxlength="200" required>

                <label for="farm_location">สถานที่ตั้งฟาร์ม</label>
                <input id="farm_location" type="text" name="farm_location" value="<?php echo htmlspecialchars($farm_location, ENT_QUOTES, 'UTF-8'); ?>" placeholder="กรอกที่อยู่ฟาร์ม" required>

                <label for="farm_details">รายละเอียดฟาร์ม</label>
                <textarea id="farm_details" name="farm_details" placeholder="รายละเอียดเกี่ยวกับฟาร์ม" maxlength="200" required><?php echo htmlspecialchars($farm_details, ENT_QUOTES, 'UTF-8'); ?></textarea>

                <button class="primary-button" type="submit">ถัดไป เลือกธนาคาร <span>→</span></button>
            </form>
        </section>
    </main>
</body>
</html>
