<?php

// ป้องกัน Session
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_start();

$registration_success = !empty($_SESSION['registration_success']);
unset($_SESSION['registration_success']);

// จำกัดการ Login ผิด
if (!isset($_SESSION['login_attempt'])) {
    $_SESSION['login_attempt'] = 0;
}

if ($_SESSION['login_attempt'] >= 5) {
    die("คุณพยายามเข้าสู่ระบบเกินกำหนด กรุณาลองใหม่ภายหลัง");
}

// --- 1. ตั้งค่าการเชื่อมต่อ ---
$conn = new mysqli("localhost", "root", "", "cockfighting_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$error = "";

// --- 2. ตรวจสอบการส่งค่าจาก Form ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_input = trim($_POST['user_name']);
    $pass_input = trim($_POST['u_password']);

    // ตรวจสอบข้อมูล
    if (empty($user_input) || empty($pass_input)) {
        $error = "กรุณากรอกชื่อผู้ใช้งานและรหัสผ่าน";
    } elseif (strlen($user_input) > 50 || strlen($pass_input) > 100) {
        $error = "ข้อมูลไม่ถูกต้อง";
    } else {

        // ป้องกัน SQL Injection ด้วย Prepared Statement
        $stmt = $conn->prepare("SELECT user_id, u_password, real_name FROM member WHERE user_name = ? LIMIT 1");
        $stmt->bind_param("s", $user_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            // ตรวจสอบรหัสผ่าน
            if (password_verify($pass_input, $row['u_password']) || hash_equals($row['u_password'], $pass_input)) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['real_name'] = $row['real_name'];

                // รีเซ็ตจำนวนครั้งที่ Login ผิด
                $_SESSION['login_attempt'] = 0;

                header("Location: dashboard.php");
                exit();

            } else {
                $_SESSION['login_attempt']++;
                $error = "รหัสผ่านไม่ถูกต้อง";
            }

        } else {
            $_SESSION['login_attempt']++;
            $error = "ไม่พบชื่อผู้ใช้งานนี้";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Cockfighting System</title>
    <link rel="stylesheet" href="loingstyle.css">
</head>
<body>

<div class="login-container">
    <h2>เข้าสู่ระบบเพื่อใช้งาน</h2>

    <?php if ($registration_success): ?>
        <div class="success-msg">สมัครสมาชิกสำเร็จแล้ว กรุณาเข้าสู่ระบบ</div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
            <label>ชื่อผู้ใช้งาน</label>
            <input type="text" name="user_name" placeholder="กรอกชื่อผู้ใช้งาน" required>
        </div>
        
        <div class="form-group">
            <label>รหัสผ่าน</label>
            <input type="password" name="u_password" placeholder="กรอกรหัสผ่าน" required>
        </div>

        <a href="Newpw/pw.php" class="forgot-password">ลืมรหัสผ่าน?</a>
        
        <button type="submit" class="btn-login">เข้าสู่ระบบ</button>
    </form>

    <div class="footer-links">
        ยังไม่มีบัญชีใช่ไหม? <a href="Apply/apply.php">สมัครสมาชิก</a>
</div>

</body>
</html>
