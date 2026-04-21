<?php
session_start();

// ถ้าไม่มี Session ให้ดีดกลับไปหน้า Login ทันที
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>ยินดีต้อนรับคุณ <?php echo htmlspecialchars($_SESSION['real_name']); ?></h1>
    <p>นี่คือหน้าภายในระบบที่ปลอดภัย</p>
    <a href="logout.php">ออกจากระบบ</a>
</body>
</html>