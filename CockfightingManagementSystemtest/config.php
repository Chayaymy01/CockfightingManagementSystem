<?php

$host = "localhost";
$user = "root";
$password = "";
$dbname = "cockfighting_system";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ : " . $conn->connect_error);
}

$conn->set_charset("utf8");

// ตั้งค่าจาก Environment ของ Apache/XAMPP ก่อนเปิดใช้ Google Login จริง
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');

// Google Apps Script สำหรับส่ง OTP ทางอีเมล
define('GOOGLE_OTP_WEB_APP_URL', getenv('GOOGLE_OTP_WEB_APP_URL') ?: 'https://script.google.com/macros/s/AKfycbyM--6moP0ObqFYs4Ipb1KLOSKo01XTMjktJnPTcb88rEfp3msDk9q_szkuv_58RnqE2g/exec');
define('GOOGLE_OTP_API_SECRET', getenv('GOOGLE_OTP_API_SECRET') ?: '0d95f8c45e2a7b634faa491bd733a8c9328350dcbb1cb909929d4728604a931e');
