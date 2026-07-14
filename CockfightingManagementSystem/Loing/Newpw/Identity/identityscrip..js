document.querySelectorAll('.otp-field').forEach((field, index, fields) => {
    field.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '').slice(0, 1);

        if (e.target.value.length >= 1 && index < fields.length - 1) {
            fields[index + 1].focus(); // ไปช่องถัดไป
        }
    });

    field.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
            fields[index - 1].focus(); // ย้อนกลับเมื่อกดลบ
        }
    });

    field.addEventListener('paste', (e) => {
        const digits = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, fields.length);

        if (digits.length < 2) {
            return;
        }

        e.preventDefault();
        digits.split('').forEach((digit, digitIndex) => {
            if (fields[digitIndex]) {
                fields[digitIndex].value = digit;
            }
        });

        fields[Math.min(digits.length, fields.length) - 1].focus();
    });
});
