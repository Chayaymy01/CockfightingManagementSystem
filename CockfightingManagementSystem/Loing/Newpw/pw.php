<?php
session_start();
require_once "../../config.php"; 

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
                $_SESSION['otp_expires_at'] = time() + 300;
                $_SESSION['otp_attempts'] = 0;
                unset($_SESSION['otp_verified']);

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
                        header("Location: Identity/identity.php");
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
    <title>ลืมรหัสผ่าน?</title>
    <link rel="stylesheet" href="./pwstyle.css?v=<?php echo filemtime(__DIR__ . '/pwstyle.css'); ?>">
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>ลืมรหัสผ่าน?</h2>
            <p class="subtitle">กรุณาระบุบัญชีที่คุณต้องการรีเซ็ตรหัสผ่าน<br>หมายเลขโทรศัพท์หรืออีเมล</p>
            
            <?php if(!empty($message)): ?>
                <div class="alert <?php echo htmlspecialchars($message_class, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="pw.php" method="POST">
                <div class="input-group">
                    <input type="text" name="account_input" placeholder="กรุณากรอกหมายเลขโทรศัพท์หรืออีเมล" required autocomplete="off">
                </div>
                <div class="button-group">
                    <button type="button" class="btn-back" onclick="window.location.href='../loing.php';">ย้อนกลับ</button>
                    <button type="submit" class="btn-confirm">ยืนยัน</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
