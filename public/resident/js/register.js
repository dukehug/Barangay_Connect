/** Supplement server validation with immediate password confirmation feedback. */
(() => {
    const password = document.getElementById('password');
    const confirmation = document.getElementById('password_confirmation');

    // 找不到元素代表不是註冊頁,直接結束
    if (!password || !confirmation) return;

    const validate = () => confirmation.setCustomValidity(
        confirmation.value && confirmation.value !== password.value
            ? 'Passwords do not match.'
            : ''
    );

    password.addEventListener('input', validate);
    confirmation.addEventListener('input', validate);
})();