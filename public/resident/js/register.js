/** Supplement server validation with immediate password confirmation feedback. */
(() => {
  const password = document.getElementById('password');
  const confirmation = document.getElementById('password_confirmation');
  const validate = () => confirmation.setCustomValidity(confirmation.value && confirmation.value !== password.value ? 'Passwords do not match.' : '');
  password.addEventListener('input', validate);
  confirmation.addEventListener('input', validate);
})();
