(() => {
    document.querySelectorAll('#pageSize, #recordType').forEach(select => {
        select.addEventListener('change', event => event.target.form.requestSubmit());
    });
})();