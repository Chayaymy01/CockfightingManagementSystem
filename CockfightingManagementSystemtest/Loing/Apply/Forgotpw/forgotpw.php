<?php
$conn = new mysqli("localhost","root","","cockfighting_system");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("SELECT user_id FROM member WHERE u_phonenumber = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        // à¸ªà¸¸à¹ˆà¸¡ OTP
        $otp = rand(100000, 999999);
        $expire = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        // à¸šà¸±à¸™à¸—à¸¶à¸ OTP
        $stmt2 = $conn->prepare("UPDATE member SET otp_code=?, otp_expire=? WHERE user_id=?");
        $stmt2->bind_param("ssi", $otp, $expire, $row['user_id']);
        $stmt2->execute();

        // (à¸•à¸­à¸™à¸™à¸µà¹‰à¸¢à¸±à¸‡à¹„à¸¡à¹ˆà¸ªà¹ˆà¸‡ SMS â†’ à¹à¸ªà¸”à¸‡ OTP à¸Šà¸±à¹ˆà¸§à¸„à¸£à¸²à¸§)
        echo "OTP à¸‚à¸­à¸‡à¸„à¸¸à¸“à¸„à¸·à¸­: $otp";

        header("Location: verify.php?phone=$phone");
        exit();

    } else {
        echo "à¹„à¸¡à¹ˆà¸žà¸šà¹€à¸šà¸­à¸£à¹Œà¸™à¸µà¹‰";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="forgotpwstyle.css">
    <link rel="stylesheet" href="../../../css/motion.css">
</head>
<body>
    <div class="forgotpw-container">
        <h1>à¸¥à¸·à¸¡à¸£à¸«à¸±à¸ªà¸œà¹ˆà¸²à¸™?</h1>
        <h3>à¸à¸£à¸¸à¸“à¸²à¸£à¸°à¸šà¸¸à¸šà¸±à¸à¸Šà¸µà¸—à¸µà¹ˆà¸„à¸¸à¸“à¸•à¹‰à¸­à¸‡à¸à¸²à¸£à¸£à¸µà¹€à¸Šà¹‡à¸•à¸£à¸«à¸±à¸ªà¸œà¹ˆà¸²à¸™</h3>
        <h2>à¸«à¸¡à¸²à¸¢à¹€à¸¥à¸‚à¹‚à¸—à¸£à¸¨à¸±à¸žà¸—à¹Œà¸«à¸£à¸·à¸­à¸­à¸µà¹€à¸¡à¸¥à¸¥à¹Œ</h2>

    <form method="POST">
        <input type="text" name="phone" placeholder="à¸à¸£à¸¸à¸“à¸²à¸à¸£à¸­à¸à¸«à¸¡à¸²à¸¢à¹€à¸¥à¸‚à¹‚à¸—à¸£à¸¨à¸±à¸žà¸—à¹Œà¸«à¸£à¸·à¸­à¸­à¸µà¹€à¸¡à¸¥" required>

        <div class="form-buttons">
            <button type="button" class="btn-back " onclick="window.location.href='loing.PHP'">
    à¸¢à¹‰à¸­à¸™à¸à¸¥à¸±à¸š
</button>
            <button type="submit" class="btn-confirm">à¸¢à¸·à¸™à¸¢à¸±à¸™</button>
        </div>
    </form>
    </div>
    <script src="../../../Js/motion.js"></script>
</body>
</html>

