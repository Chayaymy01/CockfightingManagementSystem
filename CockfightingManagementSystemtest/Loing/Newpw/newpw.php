<?php
session_start();
require_once __DIR__ . "/../../config.php";

$msg = "";
$is_json_request =
    (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

function new_password_response($success, $message, $redirect = "")
{
    global $is_json_request;

    if ($is_json_request) {
        header("Content-Type: application/json; charset=utf-8");
        http_response_code($success ? 200 : 400);
        echo json_encode([
            "success" => $success,
            "message" => $message,
            "redirect" => $redirect
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    return "<script>alert('" . addslashes($message) . "');</script>";
}

function get_reset_columns($conn)
{
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM member");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }

    return $columns;
}

function reset_has_column($columns, $name)
{
    return in_array($name, $columns, true);
}

function bind_new_password_params($stmt, $types, &$values)
{
    $refs = [];
    foreach ($values as $key => &$value) {
        $refs[$key] = &$value;
    }

    return $stmt->bind_param($types, ...$refs);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_SESSION['otp_verified'])) {
        $msg = new_password_response(false, "กรุณายืนยัน OTP ก่อนตั้งรหัสผ่านใหม่");
    } else {
        $u_password = $_POST['u_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($u_password !== $confirm_password) {
            $msg = new_password_response(false, "รหัสผ่านไม่ตรงกัน");
        } elseif (strlen($u_password) < 8) {
            $msg = new_password_response(false, "รหัสผ่านต้องมีอย่างน้อย 8 ตัว");
        } else {
            $hashed_password = password_hash($u_password, PASSWORD_DEFAULT);
            $columns = get_reset_columns($conn);
            $conditions = [];
            $values = [$hashed_password];

            if (!empty($_SESSION['reset_user_id']) && reset_has_column($columns, "user_id")) {
                $conditions[] = "user_id = ?";
                $values[] = $_SESSION['reset_user_id'];
                $types = "si";
            } else {
                $reset_account = $_SESSION['reset_account'] ?? '';
                $types = "s";

                if ($reset_account !== "" && reset_has_column($columns, "u_email")) {
                    $conditions[] = "u_email = ?";
                    $values[] = $reset_account;
                    $types .= "s";
                }

                if ($reset_account !== "" && reset_has_column($columns, "user_line")) {
                    $conditions[] = "user_line = ?";
                    $values[] = $reset_account;
                    $types .= "s";
                }
            }

            if (empty($conditions)) {
                $msg = new_password_response(false, "ไม่พบข้อมูลบัญชีสำหรับเปลี่ยนรหัสผ่าน");
            } else {
                $sql = "UPDATE member SET u_password = ? WHERE " . implode(" OR ", $conditions) . " LIMIT 1";
                $stmt = $conn->prepare($sql);
                bind_new_password_params($stmt, $types, $values);

                if ($stmt->execute() && $stmt->affected_rows >= 0) {
                    unset($_SESSION['generated_otp'], $_SESSION['otp_verified'], $_SESSION['reset_account'], $_SESSION['reset_user_id']);
                    new_password_response(true, "เปลี่ยนรหัสผ่านสำเร็จ", "../loing.php");
                    echo "<script>alert('เปลี่ยนรหัสผ่านสำเร็จ!'); window.location.href='../loing.php';</script>";
                    exit();
                } else {
                    $msg = new_password_response(false, "เปลี่ยนรหัสผ่านไม่สำเร็จ: " . $conn->error);
                }

                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Super Kaichon</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="../../css/New-password.css">
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/motion.css">
</head>

<body class="login-page">
    

    <a href="Identity/identity.php" class="back-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="rgba" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
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
            action="newpw.php" data-success-redirect="../loing.php"
        >
            

            <div class="mb-4">
                <label class="login-label">รหัสผ่านใหม่</label>

                <div class="input-group"> 
                    <i class="bi bi-lock input-icon"></i>
                    <input
                        type="password"
                        id="new-password"
                        name="u_password"
                        class="login-input"
                        placeholder="กรุณากรอกรหัสผ่านใหม่"
                        required
                    >
                </div>
            </div>

            <div class="mb-4">
                <label class="login-label">ยืนยันรหัสผ่านใหม่</label>

                <div class="input-group"> 
                    <i class="bi bi-lock input-icon"></i>
                    <input
                        type="password"
                        id="confirm-password"
                        name="confirm_password"
                        class="login-input"
                        placeholder="กรุณากรอกยืนยันรหัสผ่านใหม่"
                        required
                    >
                </div>
            </div>

                <p id="rule-length" class="password-rule">
                    <i class="bi bi-circle"></i>
                    รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร
                </p>

                <p id="rule-letter" class="password-rule">
                    <i class="bi bi-circle"></i>
                    มีตัวอักษรภาษาอังกฤษ
                </p>

                <p id="rule-number" class="password-rule">
                    <i class="bi bi-circle"></i>
                    มีตัวเลขอย่างน้อย 1 ตัว
                </p>

                <p id="new-password-error" class="text-sm font-semibold text-red-600 my-3 min-h-[20px]"><?php echo strip_tags($msg); ?></p>

            <button type="submit" class="login-submit-btn">
                เปลี่ยนรหัสผ่าน
            </button>

            </p>
        </form>
    </section>
</main>

    <script src="../../Js/New-password.js"></script>
    <script src="../../Js/motion.js"></script>
</body>
</html>
