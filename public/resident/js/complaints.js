(() => {
    document.getElementById('pageSize')?.addEventListener('change', event => event.target.form.requestSubmit());
})();
