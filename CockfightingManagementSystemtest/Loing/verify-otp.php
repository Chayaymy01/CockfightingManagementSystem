<?php
session_start();

$is_json_request =
    (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

function identity_response($success, $message, $redirect = "")
{
    header("Content-Type: application/json; charset=utf-8");
    http_response_code($success ? 200 : 400);
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "redirect" => $redirect
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// ตรวจสอบว่ามีข้อมูล Session หรือไม่ ถ้าไม่มีให้ดีดกลับไปหน้าแรกป้องกันคนแอบเข้าลัดขั้นตอน
if (!isset($_SESSION['generated_otp'])) {
    if ($is_json_request) {
        identity_response(false, "ยังไม่มีรหัส OTP กรุณาขอรหัสใหม่");
    }

    header("Location: forgot-password.php");
    exit();
}

$display_account = $_SESSION['reset_account'] ?? 'ไม่ระบุข้อมูล';
$alert_message = "";
$alert_class = "";

// ตรวจสอบว่ามาจากฝั่งเบอร์โทรศัพท์ที่ต้องจำลองเลขโค้ดขึ้นหน้าจอเพื่อใช้ส่งงานหรือไม่
if (isset($_GET['status']) && $_GET['status'] == 'simulated') {
    $alert_message = "📢 [โหมดจำลองระบบ SMS ฟรี] รหัส OTP ของคุณคือ: " . $_SESSION['generated_otp'];
    $alert_class = "info";
}

// เมื่อผู้ใช้กรอกรหัสครบ 6 ช่องแล้วกดปุ่ม "ยืนยัน"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp'])) {
    // รวมรหัสแยกกล่องจาก otp[] มารวมเป็นข้อความยาวตัวเดียว เช่น [1,2,3,4,5,6] -> 123456
    $user_otp = implode('', $_POST['otp']);

    if ($user_otp == $_SESSION['generated_otp']) {
        $alert_message = "✅ ยืนยันรหัสถูกต้อง! กำลังพาท่านไปหน้าเปลี่ยนรหัสผ่านใหม่...";
        $alert_class = "success";
        $_SESSION['otp_verified'] = true;

        if ($is_json_request) {
            identity_response(true, "ยืนยัน OTP สำเร็จ", "new-password.php");
        }

        header("Location: new-password.php");
        exit();
    } else {
        $alert_message = "❌ รหัส OTP ไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง";
        $alert_class = "error";

        if ($is_json_request) {
            identity_response(false, "รหัส OTP ไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันรหัส OTP</title>

    <script src="https://cdn.tailwindcss.com"></script>
    

    <link rel="stylesheet" href="assets/css/verify-otp.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/motion.css">
</head>


<div id="otpModal" class="modal">

    <div class="modal-box">

        <i class="bi bi-check-circle-fill modal-icon"></i>

        <h2>ส่งรหัสสำเร็จ</h2>

        <p>
            ระบบได้ส่งรหัส OTP ใหม่แล้ว<br>
            กรุณาตรวจสอบข้อความ SMS
        </p>

        <button id="closeModal" class="login-submit-btn">
            ตกลง
        </button>

    </div>

</div>

<body class="login-page">

    <a href="home.html" class="back-btn">
        <i class="bi bi-arrow-left"></i>
    </a>

        <a href="forgot-password.php" class="back-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
  <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" />
</svg>
</a>

    <main class="min-h-screen flex items-center justify-center px-4">
        <section class="login-card reveal-scale w-full max-w-[90%] sm:max-w-[430px] md:max-w-[520px]">
            <h1 class="text-2xl font-bold text-center mb-6">
                ยืนยันรหัส OTP
            </h1>

            <form
                id="otp-form"
                method="POST"
                action="verify-otp.php" data-success-redirect="new-password.php"
            >
                <div class="mb-4">
                    <label class="login-label">
                        กรุณากรอกรหัส OTP จำนวน 6 หลัก
                        ที่ส่งไปยังหมายเลขโทรศัพท์
                    </label>

                    <div class="flex justify-between items-center mt-2">
                        <span class="login-label">เบอร์โทรศัพท์ : 08x-xxx-1234</span>
                        <a href="forgot-password.php" class="text-sm font-medium text-rgba-600 hover:underline">
                            เปลี่ยนเบอร์โทรศัพท์?
                        </a>
                    </div>

                    <div class="otp-group">
                        <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" required>
                        <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" required>
                        <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" required>
                        <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" required>
                        <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" required>
                        <input type="text" name="otp[]" maxlength="1" class="otp-input" inputmode="numeric" required>
                    </div>

                    <p id="otp-error" class="text-center text-sm font-semibold text-red-600 mt-2 min-h-[20px]"><?php echo htmlspecialchars($alert_message); ?></p>

                    <p class="text-center text-sm text-gray-500 mt-2">
                            ไม่ได้รับรหัส?

                        <button
                            type="button"
                            id="resend-btn"
                            class="font-semibold text-emerald-600 hover:underline">
                            ส่งรหัสใหม่
                        </button>

                        <br>

                        <span id="timer" class="text-gray-400 text-xs">
                            สามารถส่งใหม่ได้ใน 01:00
                        </span>

                    </p>

                <button type="submit" class="login-submit-btn mt-6">
                    ยืนยัน
                </button>
            </form>
        </section>
    </main>

    <script src="assets/js/verify-otp.js"></script>
    <script src="assets/js/motion.js"></script>
</body>
</html>
