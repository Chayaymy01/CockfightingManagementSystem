const registerForm = document.querySelector("#register-form");

const username = document.querySelector("#username");
const fullname = document.querySelector("#fullname");
const phone = document.querySelector("#phone");
const email = document.querySelector("#email");
const password = document.querySelector("#password");
const confirmPassword = document.querySelector("#confirm-password");
const terms = document.querySelector("#terms");
const registerBtn = document.querySelector("#register-btn");

const usernameError = document.querySelector("#username-error");
const fullnameError = document.querySelector("#fullname-error");
const phoneError = document.querySelector("#phone-error");
const emailError = document.querySelector("#email-error");
const passwordError = document.querySelector("#password-error");
const confirmPasswordError = document.querySelector("#confirm-password-error");
const termsError = document.querySelector("#terms-error");
const registerServerError = document.querySelector("#register-server-error");

const ruleLength = document.querySelector("#rule-length");
const ruleLetter = document.querySelector("#rule-letter");
const ruleNumber = document.querySelector("#rule-number");
const ruleMatch = document.querySelector("#rule-match");

const openTermsBtn = document.querySelector("#open-terms");
const openPrivacyBtn = document.querySelector("#open-privacy");
const policyModal = document.querySelector("#policy-modal");
const policyTitle = document.querySelector("#policy-title");
const policyContent = document.querySelector("#policy-content");
const policyClose = document.querySelector("#policy-close");
const policyAccept = document.querySelector("#policy-accept");

const togglePassword = document.querySelector("#toggle-password");
const toggleConfirmPassword = document.querySelector("#toggle-confirm-password");
const successModal = document.querySelector("#success-modal");
const goLoginBtn = document.querySelector("#go-login-btn");

const address = document.querySelector("#address");
const latitudeInput = document.querySelector("#latitude");
const longitudeInput = document.querySelector("#longitude");
const addressError = document.querySelector("#address-error");

const openMapBtn = document.querySelector("#open-map-btn");
const closeMapBtn = document.querySelector("#close-map-btn");
const mapModal = document.querySelector("#map-modal");
const useLocationBtn = document.querySelector("#use-location-btn");
const selectedLocationText = document.querySelector("#selected-location-text");

address.addEventListener("input", validateAddress);

username.addEventListener("input", validateUsername);
fullname.addEventListener("input", validateFullname);
phone.addEventListener("input", validatePhone);
email.addEventListener("input", validateEmail);
password.addEventListener("input", function () {
    validatePassword();
    validateConfirmPassword();
    checkPasswordRules();
});
confirmPassword.addEventListener("input", function () {
    validateConfirmPassword();
    checkPasswordRules();
});
terms.addEventListener("change", validateTerms);

function validateUsername() {
    const value = username.value.trim();
    const pattern = /^[a-zA-Z][a-zA-Z0-9_]{3,9}$/;

    if (value === "") {
        setError(username, usernameError, "*กรุณากรอกชื่อผู้ใช้งาน");
        return false;
    }

    if (!pattern.test(value)) {
        setError(username, usernameError, "*ต้องขึ้นต้นด้วยอังกฤษ ใช้ a-z, 0-9, _ จำนวน 4-20 ตัว");
        return false;
    }

    setSuccess(username, usernameError);
    return true;
}

function validateFullname() {
    const value = fullname.value.trim();
    const pattern = /^[ก-๙a-zA-Z\s]{2,100}$/;

    if (value === "") {
        setError(fullname, fullnameError, "*กรุณากรอกชื่อ-นามสกุล");
        return false;
    }

    if (!pattern.test(value)) {
        setError(fullname, fullnameError, "*ใช้ได้เฉพาะภาษาไทย อังกฤษ และเว้นวรรค");
        return false;
    }

    setSuccess(fullname, fullnameError);
    return true;
}

function validatePhone() {
    const value = phone.value.trim();
    const pattern = /^[0-9]{10}$/;

    if (value === "") {
        setError(phone, phoneError, "*กรุณากรอกเบอร์โทรศัพท์");
        return false;
    }

    if (!pattern.test(value)) {
        setError(phone, phoneError, "*กรอกเบอร์ 10 หลัก เช่น 0912345678");
        return false;
    }

    setSuccess(phone, phoneError);
    return true;
}

function validateEmail() {
    const value = email.value.trim();
    const pattern = /^\S+@\S+\.\S+$/;

    if (value === "") {
        setError(email, emailError, "*กรุณากรอกอีเมล");
        return false;
    }

    if (!pattern.test(value)) {
        setError(email, emailError, "*รูปแบบอีเมลไม่ถูกต้อง");
        return false;
    }

    setSuccess(email, emailError);
    return true;
}

function validatePassword() {
    const value = password.value;

    if (value === "") {
        setError(password, passwordError, "*กรุณากรอกรหัสผ่าน");
        return false;
    }

    if (value.length < 8) {
        setError(password, passwordError, "*รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร");
        return false;
    }

    if (!/[a-zA-Z]/.test(value)) {
        setError(password, passwordError, "*รหัสผ่านต้องมีตัวอักษรภาษาอังกฤษ");
        return false;
    }

    if (!/\d/.test(value)) {
        setError(password, passwordError, "*รหัสผ่านต้องมีตัวเลขอย่างน้อย 1 ตัว");
        return false;
    }

    setSuccess(password, passwordError);
    return true;
}

function validateConfirmPassword() {
    const passwordValue = password.value;
    const confirmValue = confirmPassword.value;

    if (confirmValue === "") {
        setError(confirmPassword, confirmPasswordError, "*กรุณายืนยันรหัสผ่าน");
        return false;
    }

    if (passwordValue !== confirmValue) {
        setError(confirmPassword, confirmPasswordError, "*รหัสผ่านทั้งสองช่องไม่ตรงกัน");
        return false;
    }

    setSuccess(confirmPassword, confirmPasswordError);
    return true;
}

function validateTerms() {
    if (!terms.checked) {
        termsError.textContent = "*กรุณายอมรับเงื่อนไขการใช้งาน";
        return false;
    }

    termsError.textContent = "";
    return true;
}

function setError(input, errorElement, message) {
    const inputGroup = input.closest(".input-group");

    inputGroup.classList.remove("success");
    inputGroup.classList.add("error");

    errorElement.textContent = message;
}

function setSuccess(input, errorElement) {
    const inputGroup = input.closest(".input-group");

    inputGroup.classList.remove("error");
    inputGroup.classList.add("success");

    errorElement.textContent = "";
}

function checkPasswordRules() {
    const passwordValue = password.value;
    const confirmValue = confirmPassword.value;

    updateRule(ruleLength, passwordValue.length >= 8);
    updateRule(ruleLetter, /[a-zA-Z]/.test(passwordValue));
    updateRule(ruleNumber, /\d/.test(passwordValue));
    updateRule(ruleMatch, passwordValue !== "" && passwordValue === confirmValue);
}

function updateRule(rule, isValid) {
    const icon = rule.querySelector("i");

    if (isValid) {
        rule.classList.add("valid");
        icon.className = "bi bi-check-circle-fill";
    } else {
        rule.classList.remove("valid");
        icon.className = "bi bi-circle";
    }
}

function setLoading(isLoading) {
    if (isLoading) {
        registerBtn.classList.add("loading");
        registerBtn.disabled = true;
        registerBtn.innerHTML = `<span class="spinner"></span>กำลังสมัคร...`;
    } else {
        registerBtn.classList.remove("loading");
        registerBtn.disabled = false;
        registerBtn.innerHTML = "สมัครสมาชิก";
    }
}

function setServerError(message) {
    if (registerServerError) {
        registerServerError.textContent = message || "";
    }
}

registerForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const isUsernameValid = validateUsername();
    const isFullnameValid = validateFullname();
    const isPhoneValid = validatePhone();
    const isEmailValid = validateEmail();
    const isAddressValid = validateAddress();
    const isPasswordValid = validatePassword();
    const isConfirmValid = validateConfirmPassword();
    const isTermsValid = validateTerms();

    if (
        !isUsernameValid ||
        !isFullnameValid ||
        !isPhoneValid ||
        !isEmailValid ||
        !isAddressValid ||
        !isPasswordValid ||
        !isConfirmValid ||
        !isTermsValid
    ) {
        return;
    }

    setLoading(true);
    setServerError("");

    try {
        const formData = new FormData(registerForm);

        // Backend PHP รับ field จาก form นี้โดยตรง:
        // user_name, real_name, phone, email, address, latitude, longitude, u_password, confirm_password
        const response = await fetch(registerForm.action, {
            method: "POST",
            body: formData,
            headers: {
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            }
        });

        const contentType = response.headers.get("content-type") || "";
        const data = contentType.includes("application/json")
            ? await response.json()
            : { success: response.ok };

        if (!response.ok || data.success === false) {
            throw new Error(data.message || "สมัครสมาชิกไม่สำเร็จ กรุณาลองใหม่อีกครั้ง");
        }

        successModal.classList.add("show");
    } catch (error) {
        setServerError(error.message || "ไม่สามารถเชื่อมต่อระบบสมัครสมาชิกได้");
    } finally {
        setLoading(false);
    }
});

function validateAddress() {
    const value = address.value.trim();

    if (value === "") {
        setError(address, addressError, "กรุณากรอกที่อยู่");
        return false;
    }

    if (value.length < 10) {
        setError(address, addressError, "ที่อยู่ เช่น บ้านเลขที่ ซอย ถนน ตำบล อำเภอ จังหวัด รหัสไปรษณีย์");
        return false;
    }

    setSuccess(address, addressError);
    return true;
}

const termsText = `
    <h3>เว็บไซต์นี้จัดทำขึ้นเพื่อการศึกษาเท่านั้น</h3>
    <p>
        เป็นส่วนหนึ่งของปริญญานิพนธ์ สาขาเทคโนโลยีสารสนเทศ มหาวิทยาลัยมหาสารคาม
        ไม่มีวัตถุประสงค์เพื่อการค้า หรือแสวงหาผลกำไรใดๆ ทั้งสิ้น
    </p>
    <p>
        ผู้พัฒนา: นายกฤษณะ เทพาฤทธิ์ & นายชยางกูร หมอยา
    </p>
    <p>
        ที่ปรึกษา: ผศ.ธนันชัย คำเกตุ
        
    </p>
    <h3>เงื่อนไขการใช้งานนี้กำหนดขึ้นเพื่อให้ผู้ใช้งานเข้าใจขอบเขต</h3>
    <h3>1. การใช้งานบัญชี</h3>
    <ul>
        <li>ผู้ใช้งานต้องกรอกข้อมูลที่ถูกต้องและเป็นความจริง</li>
        <li>ผู้ใช้งานต้องรักษารหัสผ่านของตนเองเป็นความลับ</li>
        <li>ห้ามนำบัญชีไปใช้ในทางที่ผิดหรือสร้างความเสียหายต่อผู้อื่น</li>
    </ul>

    <h3>2. การประกาศขายและซื้อขาย</h3>
    <ul>
        <li>ข้อมูลประกาศขายต้องตรงกับความเป็นจริง</li>
        <li>ห้ามลงข้อมูลหลอกลวง หรือทำให้ผู้ซื้อเข้าใจผิด</li>
        <li>ผู้ใช้งานต้องรับผิดชอบต่อข้อมูลที่ตนเองเผยแพร่</li>
    </ul>

    <h3>3. การระงับบัญชี</h3>
    <ul>
        <li>ระบบสามารถระงับบัญชีที่มีพฤติกรรมผิดเงื่อนไขได้</li>
        <li>เช่น การหลอกลวง การใช้ข้อมูลเท็จ หรือการรบกวนผู้ใช้งานอื่น</li>
    </ul>
`;

const privacyText = `
    <p>
        นโยบายความเป็นส่วนตัวนี้อธิบายวิธีที่ระบบ Super Kaichon เก็บ ใช้ และดูแลข้อมูลของผู้ใช้งาน
    </p>

    <h3>1. ข้อมูลที่ระบบจัดเก็บ</h3>
    <ul>
        <li>ชื่อผู้ใช้งาน</li>
        <li>ชื่อ-นามสกุล</li>
        <li>เบอร์โทรศัพท์</li>
        <li>อีเมล</li>
        <li>ข้อมูลฟาร์มและข้อมูลไก่ชนที่ผู้ใช้งานเพิ่มเข้าระบบ</li>
    </ul>

    <h3>2. การนำข้อมูลไปใช้</h3>
    <ul>
        <li>ใช้สำหรับสมัครสมาชิกและเข้าสู่ระบบ</li>
        <li>ใช้ติดต่อเกี่ยวกับคำสั่งซื้อ การขาย และการแจ้งเตือน</li>
        <li>ใช้ปรับปรุงการให้บริการของระบบ</li>
    </ul>

    <h3>3. การปกป้องข้อมูล</h3>
    <ul>
        <li>ระบบจะไม่เปิดเผยข้อมูลส่วนตัวแก่บุคคลภายนอกโดยไม่จำเป็น</li>
        <li>ผู้ใช้งานควรเก็บรหัสผ่านเป็นความลับ</li>
        <li>ผู้ใช้งานสามารถแก้ไขข้อมูลของตนเองได้ภายหลัง</li>
    </ul>
`;

function openPolicyModal(title, content) {
    policyTitle.textContent = title;
    policyContent.innerHTML = content;
    policyModal.classList.add("show");
}

function closePolicyModal() {
    policyModal.classList.remove("show");
}

openTermsBtn.addEventListener("click", function () {
    openPolicyModal("เงื่อนไขการใช้งาน", termsText);
});

openPrivacyBtn.addEventListener("click", function () {
    openPolicyModal("นโยบายความเป็นส่วนตัว", privacyText);
});

policyClose.addEventListener("click", closePolicyModal);
policyAccept.addEventListener("click", closePolicyModal);

policyModal.addEventListener("click", function (event) {
    if (event.target === policyModal) {
        closePolicyModal();
    }
});

togglePassword.addEventListener("click", function () {
    togglePasswordVisibility(password, togglePassword);
});

toggleConfirmPassword.addEventListener("click", function () {
    togglePasswordVisibility(confirmPassword, toggleConfirmPassword);
});

function togglePasswordVisibility(input, icon) {
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    } else {
        input.type = "password";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    }
}
goLoginBtn.addEventListener("click", function () {
    window.location.href = registerForm?.dataset.successRedirect || "farm-setup.html";
});
