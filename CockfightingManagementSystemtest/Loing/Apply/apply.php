<?php
session_start();
require_once __DIR__ . "/../../config.php";

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$is_json_request =
    (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

$msg = "";

function register_response($success, $message, $redirect = "")
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

    return $message;
}

function get_member_columns($conn)
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

function has_column($columns, $name)
{
    return in_array($name, $columns, true);
}

function bind_dynamic_params($stmt, $types, &$values)
{
    $refs = [];
    foreach ($values as $key => &$value) {
        $refs[$key] = &$value;
    }

    return $stmt->bind_param($types, ...$refs);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$is_json_request && (!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token']))) {
        die("Invalid request");
    }

    $user_name = trim($_POST['user_name'] ?? '');
    $real_name = trim($_POST['real_name'] ?? $_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $password_input = $_POST['u_password'] ?? $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? $_POST['Confirm password'] ?? $password_input;

    if ($user_name === "" && $email !== "") {
        $user_name = preg_replace('/[^a-zA-Z0-9_]/', '', explode('@', $email)[0]);
    }

    if ($real_name === "") {
        $real_name = $user_name;
    }

    if ($user_name === "" || $real_name === "" || $phone === "" || $email === "" || $password_input === "") {
        $msg = register_response(false, "กรุณากรอกข้อมูลให้ครบถ้วน");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = register_response(false, "อีเมลไม่ถูกต้อง");
    } elseif ($password_input !== $confirm_password) {
        $msg = register_response(false, "รหัสผ่านทั้งสองช่องไม่ตรงกัน");
    } elseif (strlen($password_input) < 8) {
        $msg = register_response(false, "รหัสผ่านต้องมีอย่างน้อย 8 ตัว");
    } else {
        $columns = get_member_columns($conn);

        if (!has_column($columns, "user_name") || !has_column($columns, "u_password")) {
            $msg = register_response(false, "ตาราง member ยังไม่มีคอลัมน์ user_name หรือ u_password");
        } else {
            $conditions = [];
            $check_values = [];

            if (has_column($columns, "user_name")) {
                $conditions[] = "user_name = ?";
                $check_values[] = $user_name;
            }

            if ($email !== "" && has_column($columns, "user_line")) {
                $conditions[] = "user_line = ?";
                $check_values[] = $email;
            }

            if ($email !== "" && has_column($columns, "u_email")) {
                $conditions[] = "u_email = ?";
                $check_values[] = $email;
            }

            if ($phone !== "" && has_column($columns, "u_phonenumber")) {
                $conditions[] = "u_phonenumber = ?";
                $check_values[] = $phone;
            }

            if (!empty($conditions)) {
                $check_sql = "SELECT user_id FROM member WHERE " . implode(" OR ", $conditions) . " LIMIT 1";
                $check = $conn->prepare($check_sql);
                $check_types = str_repeat("s", count($check_values));
                bind_dynamic_params($check, $check_types, $check_values);
                $check->execute();
                $res = $check->get_result();

                if ($res->num_rows > 0) {
                    $msg = register_response(false, "ชื่อผู้ใช้ อีเมล หรือเบอร์โทรนี้ถูกใช้แล้ว");
                }

                $check->close();
            }

            if ($msg === "") {
                $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);

                $field_map = [
                    "user_name" => $user_name,
                    "real_name" => $real_name,
                    "u_phonenumber" => $phone,
                    "user_line" => $email,
                    "u_email" => $email,
                    "u_address" => $address,
                    "latitude" => $latitude,
                    "longitude" => $longitude,
                    "u_password" => $hashed_password
                ];

                $insert_fields = [];
                $insert_values = [];

                foreach ($field_map as $field => $value) {
                    if (has_column($columns, $field)) {
                        $insert_fields[] = $field;
                        $insert_values[] = $value;
                    }
                }

                $placeholders = implode(",", array_fill(0, count($insert_fields), "?"));
                $insert_sql = "INSERT INTO member (" . implode(",", $insert_fields) . ") VALUES ($placeholders)";
                $stmt = $conn->prepare($insert_sql);
                $insert_types = str_repeat("s", count($insert_values));
                bind_dynamic_params($stmt, $insert_types, $insert_values);

                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $conn->insert_id;
                    $_SESSION['real_name'] = $real_name;
                    $msg = register_response(true, "สมัครสมาชิกสำเร็จ", "../../html/farm-setup.html");
                } else {
                    $msg = register_response(false, "สมัครไม่สำเร็จ: " . $conn->error);
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
    <title>สมัครสมาชิก - Super Kaichon</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <link rel="stylesheet" href="../../css/Register.css">

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&family=Prompt:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/motion.css">
</head>

<body class="login-page">

    <a href="../../html/index.html" class="back-btn">
        <i class="bi bi-arrow-left"></i>
    </a>

    <main class="min-h-screen flex items-center justify-center px-4 py-8">
        <section class="login-card reveal-scale w-full max-w-[90%] sm:max-w-[430px] md:max-w-[560px]">

            <h1 class="text-2xl font-bold text-center mb-2">
                สมัครสมาชิก
            </h1>

            <p class="text-center text-sm text-gray-500 mb-6">
                สร้างบัญชีเพื่อใช้งานระบบ Super Kaichon
            </p>

            <form
                id="register-form"
                method="POST"
                action="apply.php" data-success-redirect="../../html/farm-setup.html"
            >

                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token']); ?>">

                <!-- Username -->
                <div class="mb-1">
                    <label class="login-label">ชื่อผู้ใช้งาน</label>

                    <div class="input-group">
                        <i class="bi bi-person input-icon"></i>
                        <input
                            type="text"
                            id="username"
                            name="user_name"
                            class="login-input"
                            placeholder="กรอกชื่อผู้ใช้งาน"
                        >
                    </div>

                    <p class="input-help">
                        ใช้ภาษาอังกฤษ, ตัวเลข จำนวน 4-10 ตัว
                    </p>

                    <p id="username-error" class="input-error"></p>
                </div>

                <!-- Fullname -->
                <div class="mb-1">
                    <label class="login-label">ชื่อ-นามสกุล</label>

                    <div class="input-group">
                        <i class="bi bi-person-badge input-icon"></i>
                        <input
                            type="text"
                            id="fullname"
                            name="real_name"
                            class="login-input"
                            placeholder="กรอกชื่อ-นามสกุล"
                        >
                    </div>

                    <p id="fullname-error" class="input-error"></p>
                </div>

                <!-- Phone -->
                <div class="mb-1">
                    <label class="login-label">เบอร์โทรศัพท์</label>

                    <div class="input-group">
                        <i class="bi bi-telephone input-icon"></i>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            class="login-input"
                            placeholder="09xxxxxxxx"
                        >
                    </div>

                    <p id="phone-error" class="input-error"></p>
                </div>

                <!-- Email -->
                <div class="mb-1">
                    <label class="login-label">อีเมล</label>

                    <div class="input-group">
                        <i class="bi bi-envelope input-icon"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="login-input"
                            placeholder="example@gmail.com"
                        >
                    </div>

                    <p id="email-error" class="input-error"></p>
                </div>

                <!-- Address -->
                <div class="mb-1">
                    <div class="address-label-row">
                        <label class="login-label">ที่อยู่สำหรับติดต่อ</label>

                
                    </div>

                    <div class="input-group">
                        <i class="bi bi-geo-alt input-icon"></i>
                        <input
                            type="text"
                            id="address"
                            name="address"
                            class="login-input"
                            placeholder="กรอกที่อยู่ หรือเลือกจากแผนที่"
                        >
                    </div>

                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">

                    <p id="address-error" class="input-error"></p>
                </div>

                <!-- Password -->
                <div class="mb-1">
                    <label class="login-label">รหัสผ่าน</label>

                    <div class="input-group">
                        <i class="bi bi-lock input-icon"></i>

                        <input
                            type="password"
                            id="password"
                            name="u_password"
                            class="login-input"
                            placeholder="กรอกรหัสผ่าน"
                        >

                        <i class="bi bi-eye-slash eye-icon" id="toggle-password"></i>
                    </div>

                    <p id="password-error" class="input-error"></p>
                </div>

                <!-- Confirm Password -->
                <div class="mb-0">
                    <label class="login-label">ยืนยันรหัสผ่าน</label>

                    <div class="input-group">
                        <i class="bi bi-lock-fill input-icon"></i>

                        <input
                            type="password"
                            id="confirm-password"
                            name="confirm_password"
                            class="login-input"
                            placeholder="กรอกรหัสผ่านอีกครั้ง"
                        >

                        <i class="bi bi-eye-slash eye-icon" id="toggle-confirm-password"></i>
                    </div>

                    <p id="confirm-password-error" class="input-error"></p>
                </div>

                <!-- Password Rules -->
                <div class="password-rules">
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

                    <p id="rule-match" class="password-rule">
                        <i class="bi bi-circle"></i>
                        รหัสผ่านทั้งสองช่องตรงกัน
                    </p>
                </div>

                <!-- Terms -->
                <div class="terms-row">
                    <input type="checkbox" id="terms" name="terms" value="1">

                    <label for="terms" class="terms-text">
                        ฉันยอมรับ
                        <button type="button" class="terms-link" id="open-terms">
                            เงื่อนไขการใช้งาน
                        </button>
                        และ
                        <button type="button" class="terms-link" id="open-privacy">
                            นโยบายความเป็นส่วนตัว
                        </button>
                    </label>
                </div>

                <p id="terms-error" class="input-error mb-1"></p>
                <p id="register-server-error" class="input-error mb-1"><?php echo htmlspecialchars($msg); ?></p>

                <!-- Fixed Button -->
                <div class="register-action-bar">
                    <button type="submit" id="register-btn" class="login-submit-btn">
                        สมัครสมาชิก
                    </button>
                </div>

                <p class="text-center text-sm mt-3">
                    มีบัญชีอยู่แล้ว?
                    <a href="../loing.php" class="font-bold text-emerald-700">
                        เข้าสู่ระบบ
                    </a>
                </p>

            </form>
        </section>
    </main>


    <!-- Policy Modal -->
    <div class="policy-modal" id="policy-modal">
        <div class="policy-box reveal-scale">

            <div class="policy-header">
                <h2 id="policy-title">เงื่อนไขการใช้งาน</h2>

                <button type="button" class="policy-close" id="policy-close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="policy-content" id="policy-content"></div>

            <div class="policy-footer">
                <button type="button" class="policy-accept" id="policy-accept">
                    เข้าใจแล้ว
                </button>
            </div>

        </div>
    </div>

    <!-- Success Modal -->
    <div class="success-modal" id="success-modal">
        <div class="success-box reveal-scale">
            <i class="bi bi-check-circle-fill success-icon"></i>

            <h2>สมัครสมาชิกสำเร็จ</h2>

            <p>
                บัญชีของคุณถูกสร้างเรียบร้อยแล้ว<br>
                กรุณาเพิ่มข้อมูลฟาร์มเพื่อเริ่มใช้งาน
            </p>

            <button type="button" id="go-login-btn" class="success-btn">
                ไปหน้าเพิ่มข้อมูลฟาร์ม
            </button>
        </div>
    </div>

    <script src="../../Js/Register.js"></script>
    <script src="../../Js/motion.js"></script>
</body>
</html>
