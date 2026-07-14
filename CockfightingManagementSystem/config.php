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
