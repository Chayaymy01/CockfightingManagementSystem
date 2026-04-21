<?php
session_start();

// --- 1. ตั้งค่าการเชื่อมต่อ (ควรแยกไปไฟล์ config.php ถ้าโปรเจกต์ใหญ่ขึ้น) ---
$conn = new mysqli("localhost", "root", "", "cockfighting_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$error = "";

// --- 2. ตรวจสอบการส่งค่าจาก Form ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_input = $_POST['user_name'];
    $pass_input = $_POST['u_password'];

    // ป้องกัน SQL Injection ด้วย Prepared Statement
    $stmt = $conn->prepare("SELECT user_id, u_password, real_name FROM member WHERE user_name = ? LIMIT 1");
    $stmt->bind_param("s", $user_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // ตรวจสอบรหัสผ่าน (ในรูปของคุณเป็น Plain Text)
        if ($pass_input === $row['u_password']) {
            session_regenerate_id(true); // ป้องกัน Session Hijacking
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['real_name'] = $row['real_name'];
            
            header("Location: dashboard.php"); 
            exit();
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบชื่อผู้ใช้งานนี้";
    }
    $stmt->close();
}
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

        <a href="forgotpw.PHP" class="forgot-password">ลืมรหัสผ่าน?</a>
        
        <button type="submit" class="btn-login">เข้าสู่ระบบ</button>
    </form>

    <div class="footer-links">
        ยังไม่มีบัญชีใช่ไหม? <a href="apply.PHP">สมัครสมาชิก</a>
</div>

</body>
</html>