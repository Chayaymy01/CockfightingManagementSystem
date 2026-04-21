<?php
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // เชื่อมต่อฐานข้อมูลตามรูป phpMyAdmin
    $conn = new mysqli("localhost", "root", "", "cockfighting_system");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $u_password = $_POST['u_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // สมมติ ID ผู้ใช้เป็น 1 (ปรับเปลี่ยนตามระบบของคุณ)
    $user_id = 1; 

    if ($u_password !== $confirm_password) {
        $msg = "<script>alert('รหัสผ่านไม่ตรงกัน!');</script>";
    } else {
        // เข้ารหัสผ่านก่อนบันทึก
        $hashed_password = password_hash($u_password, PASSWORD_DEFAULT);

        // อัปเดตคอลัมน์ u_password ในตาราง member
        $sql = "UPDATE member SET u_password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $user_id);

        if ($stmt->execute()) {
            echo "<script>alert('เปลี่ยนรหัสผ่านสำเร็จ!'); window.location.href='login.php';</script>";
        } else {
            $msg = "<script>alert('Error: " . $conn->error . "');</script>";
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งรหัสผ่านใหม่</title>
    <link rel="stylesheet" href="newpwstyle.css">
</head>
<body>

<?php echo $msg; ?>

<div class="reset-card">
    <h2>ตั้งรหัสผ่านใหม่</h2>
    
    <form action="" method="POST">
        <div class="input-group">
            <label>รหัสผ่าน</label>
            <input type="password" name="u_password" placeholder="กรุณากรอกรหัสผ่าน" required>
        </div>
        
        <div class="input-group">
            <label>ยืนยันรหัสผ่าน</label>
            <input type="password" name="confirm_password" placeholder="กรุณากรอกยืนยันรหัสผ่าน" required>
        </div>

        <div class="btn-group">
            <button type="button" class="btn btn-back" onclick="history.back()">ย้อนกลับ</button>
            <button type="submit" class="btn btn-confirm">ยืนยัน</button>
        </div>
    </form>
</div>

</body>
</html>