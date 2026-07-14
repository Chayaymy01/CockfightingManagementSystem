<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../../loing.php');
    exit();
}

if (empty($_SESSION['farm_registration']) || empty($_SESSION['farm_csrf_token'])) {
    header('Location: rgfarm.php');
    exit();
}

require_once __DIR__ . '/../../../config.php';

$banks = [
    'กสิกรไทย' => 'KBANK',
    'ไทยพาณิซย์' => 'SCB',
    'กรุงไทย' => 'KTB',
    'กรุงเทพ' => 'BBL',
    'กรุงศรีอยุธยา' => 'BAY',
    'ออมสิน' => 'GSB',
];

$message = '';
$selected_bank = '';
$account_number = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = (string) ($_POST['csrf_token'] ?? '');
    $selected_bank = (string) ($_POST['bank_name'] ?? '');
    $account_number = preg_replace('/\D+/', '', (string) ($_POST['account_number'] ?? ''));

    if (!hash_equals($_SESSION['farm_csrf_token'], $csrf_token)) {
        $message = 'คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } elseif (!array_key_exists($selected_bank, $banks)) {
        $message = 'กรุณาเลือกธนาคาร';
    } elseif (!preg_match('/^\d{8,20}$/', $account_number)) {
        $message = 'เลขบัญชีต้องเป็นตัวเลข 8–20 หลัก';
    } else {
        $farm = $_SESSION['farm_registration'];
        $conn->begin_transaction();

        try {
            $id_result = $conn->query('SELECT COALESCE(MAX(farm_id), 0) + 1 AS next_id FROM farm');
            $farm_id = (int) $id_result->fetch_assoc()['next_id'];
            $user_id = (int) $_SESSION['user_id'];
            $frimage_id = null;
            $farm_name = (string) $farm['farm_name'];
            $farm_location = (string) $farm['farm_location'];
            $farm_contact = (string) $farm['farm_contact'];
            $farm_details = (string) $farm['farm_details'];

            $stmt = $conn->prepare(
                'INSERT INTO farm (farm_id, user_id, frimage_id, farm_name, farm_location, farm_contact, farm_details, bank_name, account_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'iiissssss',
                $farm_id,
                $user_id,
                $frimage_id,
                $farm_name,
                $farm_location,
                $farm_contact,
                $farm_details,
                $selected_bank,
                $account_number
            );

            if (!$stmt->execute()) {
                throw new RuntimeException('insert failed');
            }

            $stmt->close();
            $conn->commit();
            unset($_SESSION['farm_registration'], $_SESSION['farm_csrf_token']);
            $_SESSION['farm_registration_success'] = true;
            header('Location: ../../dashboard.php');
            exit();
        } catch (Throwable $error) {
            $conn->rollback();
            $message = 'ไม่สามารถบันทึกข้อมูลฟาร์มได้ กรุณาลองใหม่อีกครั้ง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เลือกบัญชีธนาคาร</title>
    <link rel="stylesheet" href="./rgfarmstyle.css?v=<?php echo filemtime(__DIR__ . '/rgfarmstyle.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <main class="farm-page bank-page">
        <a class="back-button" href="./rgfarm.php" aria-label="ย้อนกลับ">←</a>
        <div class="owner-badge">🐓 เจ้าของฟาร์ม</div>

        <section class="bank-card" aria-labelledby="bank-title">
            <h1 id="bank-title">บัญชีธนาคาร</h1>

            <?php if ($message !== ''): ?>
                <div class="alert" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['farm_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <fieldset>
                    <legend>เลือกธนาคาร</legend>
                    <div class="bank-grid">
                        <?php foreach ($banks as $bank_name => $short_name): ?>
                            <label class="bank-option">
                                <input type="radio" name="bank_name" value="<?php echo htmlspecialchars($bank_name, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected_bank === $bank_name ? 'checked' : ''; ?> required>
                                <span class="bank-logo"><?php echo htmlspecialchars($short_name, ENT_QUOTES, 'UTF-8'); ?></span>
                                <strong><?php echo htmlspecialchars($bank_name, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <label for="account_number">เลขบัญชีธนาคาร</label>
                <input id="account_number" type="text" name="account_number" value="<?php echo htmlspecialchars($account_number, ENT_QUOTES, 'UTF-8'); ?>" placeholder="กรอกเลขบัญชีธนาคาร" inputmode="numeric" maxlength="20" required>

                <button class="primary-button bank-submit" type="submit">สมัครฟาร์ม</button>
            </form>
        </section>
    </main>
</body>
</html>
