<?php

// ป้องกัน Session
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_start();

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
$is_json_request =
    (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

function respond_login_json($success, $message = "", $redirect = "")
{
    header("Content-Type: application/json; charset=utf-8");
    http_response_code($success ? 200 : 401);
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "redirect" => $redirect
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// --- 2. ตรวจสอบการส่งค่าจาก Form ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_input = trim($_POST['user_name'] ?? '');
    $pass_input = trim($_POST['u_password'] ?? '');

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
            $stored_password = $row['u_password'];
            // รองรับทั้งรหัสใหม่ที่บันทึกแบบ password_hash() และข้อมูลเก่าที่เป็น plaintext
            $password_ok = password_verify($pass_input, $stored_password) || hash_equals($stored_password, $pass_input);

            if ($password_ok) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['real_name'] = $row['real_name'];

                // รีเซ็ตจำนวนครั้งที่ Login ผิด
                $_SESSION['login_attempt'] = 0;

                if ($is_json_request) {
                    respond_login_json(true, "เข้าสู่ระบบสำเร็จ", "dashboard.php");
                }

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

    if ($is_json_request && $error) {
        respond_login_json(false, $error);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Super Kaichon</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="../css/login.css">
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Share+Tech&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/motion.css">
</head>



<body class="login-page">
    

    <a href="../html/index.html" class="back-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="rgba" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
  <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" />
</svg>
</a>

    <main class="min-h-screen flex items-center justify-center px-4">
    <section class="login-card reveal-scale w-full max-w-[90%] sm:max-w-[430px] md:max-w-[520px]">

        

        <h1 class="text-2xl font-bold text-center mb-6">
            เข้าสู่ระบบเพื่อใช้งาน
        </h1>

        <form
            id="login-form"
            method="POST"
            action="loing.php"
        >
            <div class="mb-4">
                <label class="login-label">ชื่อผู้เข้าใช้งาน</label>

                <div class="input-group">
                    <i class="bi bi-person input-icon"></i>
                    <input
                        type="text"
                        id="username"
                        name="user_name"
                        class="login-input"
                        placeholder="กรุณากรอกชื่อผู้เข้าใช้งาน"
                        required
                    >
                </div>
            </div>

            <div class="mb-2">
                <label class="login-label">รหัสผ่าน</label>

                <div class="input-group">
                    <i class="bi bi-lock input-icon"></i>
                    <input
                        type="password"
                        id="password"
                        name="u_password"
                        class="login-input"
                        placeholder="กรุณากรอกรหัสผ่าน"
                        required
                    >
                </div>
            </div>

            <p id="login-error" class="text-sm font-semibold text-red-600 mb-3 min-h-[20px]"><?php echo htmlspecialchars($error); ?></p>

            <div class="text-right mb-6">
                <a href="Newpw/pw.php" class="text-xs font-semibold text-gray-700 hover:text-emerald-600">
                    ลืมรหัสผ่าน?
                </a>
            </div>

            <button type="submit" class="login-submit-btn">
                เข้าสู่ระบบ
            </button>

            <p class="text-center text-sm mt-6">
                ยังไม่มีบัญชีใช่ไหม?
                <a href="Apply/apply.php" class="font-bold text-emerald-700">
                    สมัครสมาชิก
                </a>
            </p>
        </form>
    </section>
</main>

    <script src="../Js/login.js"></script>
    <script src="../Js/motion.js"></script>
</body>
</html>
