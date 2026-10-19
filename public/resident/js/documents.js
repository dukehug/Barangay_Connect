/** Page-size changes preserve the current server-side search. */
(() => {
  document.getElementById('pageSize')?.addEventListener('change', event => event.target.form.requestSubmit());
})();
