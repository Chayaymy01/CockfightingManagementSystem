<?php
session_start();

// ถ้าไม่มี Session ให้ดีดกลับไปหน้า Login ทันที
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Super Kaichon</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/motion.css">
</head>
<body class="dashboard-page">
    <main class="dashboard-shell reveal-scale">
        <section class="dashboard-hero">
            <div class="dashboard-card reveal-left">
                <p class="dashboard-copy">Super Kaichon Dashboard</p>
                <h1 class="dashboard-title">ยินดีต้อนรับคุณ <?php echo htmlspecialchars($_SESSION['real_name']); ?></h1>
                <p class="dashboard-copy">นี่คือหน้าภายในระบบสำหรับจัดการข้อมูลฟาร์ม ไก่ชน และข้อมูลผู้ใช้ของคุณ</p>

                <div class="dashboard-actions">
                    <a class="dashboard-action" href="farm-setup.html?v=20260715-2">จัดการข้อมูลฟาร์ม</a>
                    <a class="dashboard-logout" href="login.php">ออกจากระบบ</a>
                </div>
            </div>

            <aside class="dashboard-card dashboard-stat reveal-right">
                <span class="dashboard-copy">สถานะบัญชี</span>
                <strong>พร้อมใช้งาน</strong>
                <p class="dashboard-copy">Session เข้าสู่ระบบทำงานอยู่</p>
            </aside>
        </section>

        <section class="dashboard-grid">
            <article class="dashboard-card stagger-item reveal">
                <h2 class="dashboard-mini-title">ข้อมูลฟาร์ม</h2>
                <p class="dashboard-mini-copy">เพิ่มหรือแก้ไขข้อมูลฟาร์มเพื่อให้ผู้ซื้อรู้จักฟาร์มของคุณมากขึ้น</p>
            </article>

            <article class="dashboard-card stagger-item reveal">
                <h2 class="dashboard-mini-title">รายการไก่ชน</h2>
                <p class="dashboard-mini-copy">เตรียมพื้นที่สำหรับจัดการข้อมูลไก่ชน รูปภาพ และสถานะประกาศขาย</p>
            </article>

            <article class="dashboard-card stagger-item reveal">
                <h2 class="dashboard-mini-title">ตลาดออนไลน์</h2>
                <p class="dashboard-mini-copy">รองรับการเชื่อมต่อ marketplace เมื่อ backend เพิ่ม endpoint ในอนาคต</p>
            </article>
        </section>
    </main>

    <script src="assets/js/motion.js"></script>
</body>
</html>
