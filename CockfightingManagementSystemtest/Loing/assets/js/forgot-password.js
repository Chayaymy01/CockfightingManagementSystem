
const forgotForm = document.querySelector("#forgot-form");
const forgotError = document.querySelector("#forgot-error");

forgotForm.addEventListener("submit", async function (event) {

    event.preventDefault();

    const account = document.querySelector("#account").value.trim();
    const submitButton = forgotForm.querySelector(".login-submit-btn");

    if (account === "") {

        if (forgotError) {
            forgotError.textContent = "กรุณากรอกหมายเลขโทรศัพท์หรืออีเมล";
        } else {
            alert("กรุณากรอกหมายเลขโทรศัพท์หรืออีเมล");
        }

        return;
    }

    if (forgotError) {
        forgotError.textContent = "";
    }

    if (submitButton) {
        submitButton.disabled = true;
    }

    try {
        const response = await fetch(forgotForm.action, {
            method: "POST",
            body: new FormData(forgotForm),
            headers: {
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            }
        });

        const contentType = response.headers.get("content-type") || "";
        const isBackendOtpRedirect = response.redirected && response.url.includes("/verify-otp.php");
        const data = contentType.includes("application/json")
            ? await response.json()
            : { success: isBackendOtpRedirect, redirect: isBackendOtpRedirect ? (forgotForm.dataset.successRedirect || "verify-otp.php") : "" };

        if (!response.ok || data.success === false) {
            throw new Error(data.message || "ส่งรหัส OTP ไม่สำเร็จ");
        }

        const redirectUrl = data.redirect
            ? new URL(data.redirect, window.location.href).href
            : response.url;

        window.location.href = redirectUrl;
    } catch (error) {
        if (forgotError) {
            forgotError.textContent = error.message || "ไม่สามารถเชื่อมต่อระบบลืมรหัสผ่านได้";
        } else {
            alert(error.message || "ไม่สามารถเชื่อมต่อระบบลืมรหัสผ่านได้");
        }
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
        }
    }

});
