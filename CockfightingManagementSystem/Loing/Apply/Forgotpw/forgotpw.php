<?php
$conn = new mysqli("localhost","root","","cockfighting_system");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("SELECT user_id FROM member WHERE u_phonenumber = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        // สุ่ม OTP
        $otp = rand(100000, 999999);
        $expire = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        // บันทึก OTP
        $stmt2 = $conn->prepare("UPDATE member SET otp_code=?, otp_expire=? WHERE user_id=?");
        $stmt2->bind_param("ssi", $otp, $expire, $row['user_id']);
        $stmt2->execute();

        // (ตอนนี้ยังไม่ส่ง SMS → แสดง OTP ชั่วคราว)
        echo "OTP ของคุณคือ: $otp";

        header("Location: verify.php?phone=$phone");
        exit();

    } else {
        echo "ไม่พบเบอร์นี้";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="forgotpwstyle.css">
</head>
<body>
    <div class="forgotpw-container">
        <h1>ลืมรหัสผ่าน?</h1>
        <h3>กรุณาระบุบัญชีที่คุณต้องการรีเช็ตรหัสผ่าน</h3>
        <h2>หมายเลขโทรศัพท์หรืออีเมลล์</h2>

    <form method="POST">
        <input type="text" name="phone" placeholder="กรุณากรอกหมายเลขโทรศัพท์หรืออีเมล" required>

        <div class="form-buttons">
            <button type="button" class="btn-back " onclick="window.location.href='loing.PHP'">
    ย้อนกลับ
</button>
            <button type="submit" class="btn-confirm">ยืนยัน</button>
        </div>
    </form>
    </div>
</body>
</html>