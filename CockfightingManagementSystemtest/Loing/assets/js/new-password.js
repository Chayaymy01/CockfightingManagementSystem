const password = document.querySelector("#new-password");
const confirmPassword = document.querySelector("#confirm-password");
const newPasswordForm = document.querySelector("#login-form");
const newPasswordError = document.querySelector("#new-password-error");

const ruleLength = document.querySelector("#rule-length");
const ruleLetter = document.querySelector("#rule-letter");
const ruleNumber = document.querySelector("#rule-number");

password.addEventListener("input", function () {
    const value = password.value;

    updateRule(ruleLength, value.length >= 8);
    updateRule(ruleLetter, /[a-zA-Z]/.test(value));
    updateRule(ruleNumber, /\d/.test(value));
});

if (newPasswordForm) {
    newPasswordForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const value = password.value;
        const confirmValue = confirmPassword.value;
        const submitButton = newPasswordForm.querySelector(".login-submit-btn");

        if (value.length < 8 || !/[a-zA-Z]/.test(value) || !/\d/.test(value)) {
            if (newPasswordError) {
                newPasswordError.textContent = "รหัสผ่านต้องมีอย่างน้อย 8 ตัว มีตัวอักษรภาษาอังกฤษ และมีตัวเลข";
            }
            return;
        }

        if (value !== confirmValue) {
            if (newPasswordError) {
                newPasswordError.textContent = "รหัสผ่านทั้งสองช่องไม่ตรงกัน";
            }
            return;
        }

        if (newPasswordError) {
            newPasswordError.textContent = "";
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const response = await fetch(newPasswordForm.action, {
                method: "POST",
                body: new FormData(newPasswordForm),
                headers: {
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            const contentType = response.headers.get("content-type") || "";
            const data = contentType.includes("application/json")
                ? await response.json()
                : { success: response.ok, redirect: "login.php" };

            if (!response.ok || data.success === false) {
                throw new Error(data.message || "เปลี่ยนรหัสผ่านไม่สำเร็จ");
            }

            window.location.href = newPasswordForm.dataset.successRedirect || data.redirect || "login.php";
        } catch (error) {
            if (newPasswordError) {
                newPasswordError.textContent = error.message || "ไม่สามารถเชื่อมต่อระบบเปลี่ยนรหัสผ่านได้";
            }
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    });
}

function updateRule(rule, valid) {
    const icon = rule.querySelector("i");

    if (valid) {
        rule.classList.add("valid");
        icon.className = "bi bi-check-circle-fill";
    } else {
        rule.classList.remove("valid");
        icon.className = "bi bi-circle";
    }
}
