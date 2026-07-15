document.querySelectorAll('.otp-field').forEach((field, index, fields) => {
    field.addEventListener('input', (e) => {
        if (e.target.value.length >= 1 && index < fields.length - 1) {
            fields[index + 1].focus(); // ไปช่องถัดไป
        }
    });

    field.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
            fields[index - 1].focus(); // ย้อนกลับเมื่อกดลบ
        }
    });
});