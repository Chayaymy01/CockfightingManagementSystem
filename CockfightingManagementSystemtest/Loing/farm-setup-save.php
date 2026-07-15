<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

function respond(int $status, bool $success, string $message, array $extra = []): void
{
    http_response_code($status);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, false, 'รองรับเฉพาะการส่งข้อมูลแบบ POST');
}

if (empty($_SESSION['user_id']) || !filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT)) {
    respond(401, false, 'กรุณาเข้าสู่ระบบก่อนเพิ่มข้อมูลฟาร์ม', ['redirect' => 'loing.PHP']);
}

require_once __DIR__ . '/../config.php';

$userId = (int) $_SESSION['user_id'];
$farmName = trim((string) ($_POST['farm_name'] ?? ''));
$farmPhone = trim((string) ($_POST['farm_phone'] ?? ''));
$farmAddress = trim((string) ($_POST['farm_address'] ?? ''));
$farmDescription = trim((string) ($_POST['farm_description'] ?? ''));
$bankName = trim((string) ($_POST['bank_name'] ?? ''));
$accountNumber = trim((string) ($_POST['account_number'] ?? ''));

$allowedBanks = ['กรุงไทย', 'กรุงเทพ', 'ไทยพาณิซย์', 'กสิกรไทย', 'กรุงศรีอยุธยา', 'ออมสิน'];

if (mb_strlen($farmName) < 2 || mb_strlen($farmName) > 50) {
    respond(422, false, 'ชื่อฟาร์มต้องมี 2-50 ตัวอักษร');
}

if (!preg_match('/^[0-9]{10}$/', $farmPhone)) {
    respond(422, false, 'กรุณากรอกเบอร์โทร 10 หลัก');
}

if (mb_strlen($farmAddress) < 10) {
    respond(422, false, 'กรุณากรอกที่อยู่ฟาร์มอย่างน้อย 10 ตัวอักษร');
}

if (mb_strlen($farmDescription) > 200) {
    respond(422, false, 'รายละเอียดฟาร์มต้องไม่เกิน 200 ตัวอักษร');
}

if (!in_array($bankName, $allowedBanks, true)) {
    respond(422, false, 'กรุณาเลือกธนาคารที่ถูกต้อง');
}

if (!preg_match('/^[0-9]{10,20}$/', $accountNumber)) {
    respond(422, false, 'เลขบัญชีธนาคารต้องเป็นตัวเลข 10-20 หลัก');
}

/**
 * ตรวจสอบและย้ายรูปไปยังโฟลเดอร์ uploads/farms/{user_id}
 * คืน path แบบ relative สำหรับเก็บใน frimage.frfile_path
 */
function storeImage(string $field, string $role, int $userId, array &$movedFiles): ?string
{
    if (!isset($_FILES[$field]) || (int) $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('อัปโหลดรูปภาพไม่สำเร็จ กรุณาเลือกไฟล์ใหม่');
    }

    if ((int) $file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('รูปภาพต้องมีขนาดไม่เกิน 2MB');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string) $file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime])) {
        throw new RuntimeException('รองรับเฉพาะรูป JPG, PNG หรือ WEBP');
    }

    $relativeDirectory = 'uploads/farms/user_' . $userId;
    $absoluteDirectory = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

    if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0755, true) && !is_dir($absoluteDirectory)) {
        throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์เก็บรูปภาพได้');
    }

    $filename = $role . '_' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('ไม่สามารถบันทึกไฟล์รูปภาพได้');
    }

    $movedFiles[] = $absolutePath;
    return $relativeDirectory . '/' . $filename;
}

$movedFiles = [];
$lockAcquired = false;

try {
    $profilePath = storeImage('farm_profile_image', 'profile', $userId, $movedFiles);
    $coverPath = storeImage('farm_cover_image', 'cover', $userId, $movedFiles);

    $lockResult = $conn->query("SELECT GET_LOCK('farm_setup_ids', 10) AS acquired");
    $lockRow = $lockResult ? $lockResult->fetch_assoc() : null;
    $lockAcquired = isset($lockRow['acquired']) && (int) $lockRow['acquired'] === 1;

    if (!$lockAcquired) {
        throw new RuntimeException('ระบบกำลังมีผู้ใช้งาน กรุณาลองบันทึกใหม่อีกครั้ง');
    }

    $conn->begin_transaction();

    $imagePaths = array_values(array_filter([$profilePath, $coverPath], static fn($path) => $path !== null));
    $imageIdsByPath = [];

    if ($imagePaths !== []) {
        $result = $conn->query('SELECT COALESCE(MAX(frimage_id), 0) AS max_id FROM frimage');
        $row = $result->fetch_assoc();
        $nextImageId = ((int) $row['max_id']) + 1;

        $imageStatement = $conn->prepare('INSERT INTO frimage (frimage_id, frfile_path) VALUES (?, ?)');
        foreach ($imagePaths as $imagePath) {
            $imageId = $nextImageId++;
            $imageStatement->bind_param('is', $imageId, $imagePath);
            if (!$imageStatement->execute()) {
                throw new RuntimeException('ไม่สามารถบันทึกข้อมูลรูปภาพได้');
            }
            $imageIdsByPath[$imagePath] = $imageId;
        }
        $imageStatement->close();
    }

    $mainImagePath = $profilePath ?? $coverPath;
    $mainImageId = $mainImagePath !== null ? $imageIdsByPath[$mainImagePath] : null;

    $result = $conn->query('SELECT COALESCE(MAX(farm_id), 0) AS max_id FROM farm');
    $row = $result->fetch_assoc();
    $farmId = ((int) $row['max_id']) + 1;

    $statement = $conn->prepare(
        'INSERT INTO farm '
        . '(farm_id, user_id, frimage_id, farm_name, farm_location, farm_contact, farm_details, bank_name, account_number) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->bind_param(
        'iiissssss',
        $farmId,
        $userId,
        $mainImageId,
        $farmName,
        $farmAddress,
        $farmPhone,
        $farmDescription,
        $bankName,
        $accountNumber
    );

    if (!$statement->execute()) {
        throw new RuntimeException('ไม่สามารถบันทึกข้อมูลฟาร์มได้');
    }

    $statement->close();
    $conn->commit();

    $conn->query("SELECT RELEASE_LOCK('farm_setup_ids')");
    $lockAcquired = false;

    respond(201, true, 'บันทึกข้อมูลฟาร์มสำเร็จ', [
        'farm_id' => $farmId,
        'uploaded_images' => count($imagePaths),
        'redirect' => 'dashboard.php',
    ]);
} catch (Throwable $error) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
        // ยังไม่ได้เริ่ม transaction
    }

    if ($lockAcquired) {
        $conn->query("SELECT RELEASE_LOCK('farm_setup_ids')");
    }

    foreach ($movedFiles as $filePath) {
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }

    error_log('Farm setup error: ' . $error->getMessage());
    respond(500, false, $error instanceof RuntimeException
        ? $error->getMessage()
        : 'เกิดข้อผิดพลาดขณะบันทึกข้อมูล กรุณาลองใหม่อีกครั้ง');
}
