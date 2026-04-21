
<?php
session_start();

// เชื่อม DB
$conn = new mysqli("localhost", "root", "", "cockfighting_system");

if ($conn->connect_error) {
    die("DB error");
}

// สร้าง token
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!hash_equals($_SESSION['token'], $_POST['token'])) {
        die("Invalid request");
    }

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password_input = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "อีเมลไม่ถูกต้อง";
    } elseif (strlen($password_input) < 6) {
        $msg = "รหัสผ่านต้อง ≥ 6 ตัว";
    } else {

        $check = $conn->prepare("SELECT id FROM users WHERE u_email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $msg = "อีเมลถูกใช้แล้ว";
        } else {

            $password = password_hash($password_input, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (u_fullname,u_email,u_phonenumber,u_password) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $fullname, $email, $phone, $password);

            if ($stmt->execute()) {
                $msg = "สมัครสำเร็จ";
            } else {
                $msg = "สมัคไม่สำเร็จ";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>สมัครสมาชิก</title>

<!-- เรียก CSS -->
<link rel="stylesheet" href="applystyle.css">

</head>

<body>

    <div class="box">
        <h2>สมัครสมาชิก</h2>

        <form method="POST" id="form">
            <input type="text" name="fullname" placeholder="ชื่อ-นามสกุล" required>
            <input type="email" name="email" placeholder="อีเมล" required>
            <input type="text" name="phone" placeholder="เบอร์โทร" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <input type="password" name="Confirm password" placeholder="ยืนยันรหัสผ่าน" required>
            <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">

            <button type="submit">สมัครสมาชิก</button>
        </form>

        <div class="choice">
            <h4>เข้าสู้ระบบด้วย
        </div>

        <div class="terms">
            <input type="checkbox" id="agree" required>
            <label for="agree">ฉันยอมรับ <span>เงื่อนไขการใช้งาน</span> และนโยบายความเป็นส่วนตัว</label>
        </div>

        <a href="loing.PHP">มีบัญชีอยูู่แล้ว? เข้าสู้ระบบบ</a>


        <div class="msg">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    </div>

    <!-- เรียก JS -->
    <script src="script.js"></script>

</body>
</html>