<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันตัวตน OTP</title>
    <link rel="stylesheet" href="identitystyle.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>

<div class="container">
    <div class="otp-card">
        <h2>ยืนยันตัวตนของคุณ</h2>
        <p>กรุณากรอกรหัส OTP ผ่าน SMS เพื่อดำเนินการต่อไป <br>
        เบอร์โทรศัพท์ : <span class="phone">0962564674</span> 
        <a href="#" class="change-num">เปลี่ยนหมายเลข</a>
        </p>

        <form action="/verify" method="POST" id="otp-form">
            <div class="otp-inputs">
                <input type="text" name="otp[]" maxlength="1" class="otp-field">
                <input type="text" name="otp[]" maxlength="1" class="otp-field">
                <input type="text" name="otp[]" maxlength="1" class="otp-field">
                <input type="text" name="otp[]" maxlength="1" class="otp-field">
                <input type="text" name="otp[]" maxlength="1" class="otp-field">
                <input type="text" name="otp[]" maxlength="1" class="otp-field">
            </div>

            <p class="timer">ส่ง OTP อีกครั้งใน 60 วินาที</p>

            <div class="btn-group">
                <button type="button" class="btn btn-back">ย้อนกลับ</button>
                <button type="submit" class="btn btn-confirm">ยืนยัน</button>
            </div>
        </form>
    </div>
</div>

<script src="identityscrip..js"></script>
</body>
</html>