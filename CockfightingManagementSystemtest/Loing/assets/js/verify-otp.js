
const otpInputs = document.querySelectorAll(".otp-input");
const otpForm = document.querySelector("#otp-form");
const otpError = document.querySelector("#otp-error");

otpInputs.forEach((input,index)=>{

    input.addEventListener("input",function(){

        this.value=this.value.replace(/\D/g,"");

        if(this.value!="" && index<otpInputs.length-1){

            otpInputs[index+1].focus();

        }

    });

    input.addEventListener("keydown",function(e){

        if(e.key==="Backspace" && this.value==="" && index>0){

            otpInputs[index-1].focus();

        }

    });

});


// =====================
// OTP Modal
// =====================

const resendBtn=document.querySelector("#resend-btn");

const modal=document.querySelector("#otpModal");

const closeModal=document.querySelector("#closeModal");

const timer=document.querySelector("#timer");

let seconds=60;

function startTimer(){

    resendBtn.disabled=true;

    const interval=setInterval(()=>{

        seconds--;

        let min=Math.floor(seconds/60);

        let sec=seconds%60;

        timer.innerHTML=`สามารถส่งใหม่ได้ใน ${String(min).padStart(2,"0")}:${String(sec).padStart(2,"0")}`;

        if(seconds<=0){

            clearInterval(interval);

            resendBtn.disabled=false;

            timer.innerHTML="สามารถส่งรหัสใหม่ได้";

            seconds=60;

        }

    },1000);

}

resendBtn.addEventListener("click",()=>{

    modal.classList.add("active");

    startTimer();

});

closeModal.addEventListener("click",()=>{

    modal.classList.remove("active");

});

if (otpForm) {
    otpForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const otpCode = Array.from(otpInputs).map(input => input.value.trim()).join("");
        const submitButton = otpForm.querySelector(".login-submit-btn");

        if (otpCode.length !== 6) {
            if (otpError) {
                otpError.textContent = "กรุณากรอกรหัส OTP ให้ครบ 6 หลัก";
            }
            return;
        }

        if (otpError) {
            otpError.textContent = "";
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const response = await fetch(otpForm.action, {
                method: "POST",
                body: new FormData(otpForm),
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
                throw new Error(data.message || "รหัส OTP ไม่ถูกต้อง");
            }

            const targetRedirect = otpForm.dataset.successRedirect || data.redirect;
            const redirectUrl = targetRedirect
                ? new URL(targetRedirect, window.location.href).href
                : response.url;

            window.location.href = redirectUrl;
        } catch (error) {
            if (otpError) {
                otpError.textContent = error.message || "ไม่สามารถยืนยัน OTP ได้";
            }
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    });
}
