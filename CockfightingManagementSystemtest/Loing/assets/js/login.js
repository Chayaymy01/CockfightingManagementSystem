const loginForm = document.querySelector("#login-form");
const loginError = document.querySelector("#login-error");

loginForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const username = document.querySelector("#username").value.trim();
    const password = document.querySelector("#password").value.trim();
    const submitButton = loginForm.querySelector(".login-submit-btn");

    if (username === "" || password === "") {
        if (loginError) {
            loginError.textContent = "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
        } else {
            alert("กรุณากรอกชื่อผู้ใช้และรหัสผ่าน");
        }
        return;
    }

    if (loginError) {
        loginError.textContent = "";
    }

    if (submitButton) {
        submitButton.disabled = true;
    }

    try {
        const response = await fetch(loginForm.action, {
            method: "POST",
            body: new FormData(loginForm),
            headers: {
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            }
        });

        const contentType = response.headers.get("content-type") || "";
        const data = contentType.includes("application/json")
            ? await response.json()
            : { success: response.ok, redirect: response.url };

        if (!response.ok || data.success === false) {
            throw new Error(data.message || "เข้าสู่ระบบไม่สำเร็จ");
        }

        const redirectUrl = data.redirect
            ? new URL(data.redirect, response.url || loginForm.action).href
            : response.url;

        window.location.href = redirectUrl;
    } catch (error) {
        if (loginError) {
            loginError.textContent = error.message || "ไม่สามารถเชื่อมต่อระบบเข้าสู่ระบบได้";
        } else {
            alert(error.message || "ไม่สามารถเชื่อมต่อระบบเข้าสู่ระบบได้");
        }
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
        }
    }
});
