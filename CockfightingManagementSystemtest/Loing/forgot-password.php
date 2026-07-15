<?php
session_start();
require_once __DIR__ . "/../config.php"; 

$message = "";
$message_class = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $account_input = trim($_POST['account_input']);

    if (!empty($account_input)) {

        if (filter_var($account_input, FILTER_VALIDATE_EMAIL)) {

            // ตรวจสอบฐานข้อมูล
            $stmt = $conn->prepare("SELECT user_id, u_email FROM member WHERE u_email = ? LIMIT 1");
            $stmt->bind_param("s", $account_input);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                $message = "ไม่พบบัญชีอีเมลนี้ในระบบ";
                $message_class = "error";
            } else {
                $row = $result->fetch_assoc();

                // สุ่ม OTP
                $otp_code = random_int(100000, 999999);

                $_SESSION['reset_account'] = $row['u_email'];
                $_SESSION['generated_otp'] = $otp_code;

                // ==========================================
                // ตั้งค่า RESEND API
                // ==========================================
                $api_key = "re_QUxgTSFX_6spA3raf7j9CJ3ZCFXgHBgg8"; // 1. นำ API Key ที่ก๊อปมาจาก Resend มาวางตรงนี้แทน
                
                // 2. ถ้าใช้บัญชีฟรีและยังไม่ได้ต่อ Domain ให้ใช้ onboarding@resend.dev ไปก่อน
                $from_email = "ระบบรีเซ็ตรหัสผ่าน <onboarding@resend.dev>"; 
                $to_email = $row['u_email']; 

                $subject = "รหัส OTP สำหรับรีเซ็ตรหัสผ่าน";
                $body = "รหัส OTP ของคุณคือ : " . $otp_code . "\n\nรหัสนี้มีอายุการใช้งาน 5 นาที";

                // จัดเตรียม Payload ตามโครงสร้าง JSON ของ Resend
                $data = array(
                    "from" => $from_email,
                    "to" => array($to_email),
                    "subject" => $subject,
                    "text" => $body
                );

                // เริ่มทำงานด้วย cURL
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api.resend.com/emails");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

                // ส่ง Header ที่จำเป็นตามคู่มือของ Resend
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Authorization: Bearer " . $api_key,
                    "Content-Type: application/json"
                ));

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if (curl_errno($ch)) {
                    $message = "ระบบเชื่อมต่อเครือข่ายล้มเหลว: " . curl_error($ch);
                    $message_class = "error";
                } else {
                    // Resend หากส่งสำเร็จจะตอบกลับมาด้วย HTTP Code 200
                    if ($http_code == 200) {
                        $message = "ส่งรหัส OTP ไปยังอีเมลเรียบร้อยแล้ว!";
                        $message_class = "success";
                        header("Location: verify-otp.php");
                        exit();
                    } else {
                        // เกิดข้อผิดพลาดจากทาง Resend
                        $err_res = json_decode($response, true);
                        $err_msg = isset($err_res['message']) ? $err_res['message'] : 'ไม่ทราบสาเหตุ';
                        
                        $message = "ส่ง OTP ไม่สำเร็จ (Resend Error: " . $err_msg . ")";
                        $message_class = "error";
                    }
                }
                curl_close($ch);
            }
            $stmt->close();

        } else {
            $message = "รูปแบบอีเมลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง";
            $message_class = "error";
        }
    } else {
        $message = "กรุณากรอกที่อยู่อีเมล";
        $message_class = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน? - Super Kaichon</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="assets/css/forgot-password.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="assets/css/motion.css">
</head>


    
<body class="login-page">
    

    <a href="login.php" class="back-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="rgba" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
  <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" />
</svg>
</a>

    <main class="min-h-screen flex items-center justify-center px-4">
        <section class="login-card reveal-scale w-full max-w-[90%] sm:max-w-[430px] md:max-w-[520px]">
            <h1 class="text-2xl font-bold text-center mb-6">
                ลืมรหัสผ่าน?
            </h1>

            <form
                id="forgot-form"
                method="POST"
                action="forgot-password.php" data-success-redirect="verify-otp.php"
            >
                <div class="mb-4">
                    <label class="login-label mb-2">กรุณากรอกหมายเลขโทรศัพท์หรืออีเมลที่ใช้สมัครสมาชิก</label>
                    <label class="login-label ">เพื่อรับรหัสยืนยันสำหรับตั้งรหัสผ่านใหม่</label>
                    
                    <div class="input-group">
                    <input
                        type="text"
                        id="account"
                        name="account_input"
                        class="login-input"
                        placeholder="กรุณากรอกหมายเลขโทรศัพท์หรืออีเมล"
                        required>

                    </div>
                </div>

                <p id="forgot-error" class="text-sm font-semibold text-red-600 mb-3 min-h-[20px]"><?php echo htmlspecialchars($message); ?></p>

                <button type="submit" class="login-submit-btn">
                    ส่งรหัสยืนยัน
                </button>

            </form>
        </section>
    </main>

    <script src="assets/js/forgot-password.js"></script>
    <script src="assets/js/motion.js"></script>
</body>
</html>
