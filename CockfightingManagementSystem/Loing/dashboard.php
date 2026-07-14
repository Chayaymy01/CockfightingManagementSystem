<?php
session_start();

// ถ้าไม่มี Session ให้ดีดกลับไปหน้า Login ทันที
if (!isset($_SESSION['user_id'])) {
    header("Location: loing.php");
    exit();
}

$farm_registration_success = !empty($_SESSION['farm_registration_success']);
unset($_SESSION['farm_registration_success']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <style>
        body { min-height: 100vh; margin: 0; display: grid; place-content: center; gap: 14px; text-align: center; font-family: Tahoma, sans-serif; color: #111; background: linear-gradient(rgba(230,248,255,.22), rgba(230,248,255,.22)), url('Newpw/Identity/Setnewpw/assets/rooster-meadow.png') center / cover no-repeat fixed; }
        h1, p, a { margin: 0; }
        a { display: inline-block; margin: 12px auto 0; padding: 10px 20px; border-radius: 999px; color: #111; background: #f1c40f; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <?php if ($farm_registration_success): ?>
        <p>สมัครฟาร์มและบันทึกข้อมูลสำเร็จแล้ว</p>
    <?php endif; ?>
    <h1>ยินดีต้อนรับคุณ <?php echo htmlspecialchars($_SESSION['real_name']); ?></h1>
    <p>นี่คือหน้าภายในระบบที่ปลอดภัย</p>
    <a href="Apply/Farm/rgfarm.php">สมัครฟาร์ม</a>
    <a href="loing.php">ออกจากระบบ</a>
</body>
</html>
