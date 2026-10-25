/** Shared interactions for server-rendered resident records. */
(() => {
    if (document.body.classList.contains('dash-page')) {
        window.addEventListener('pagehide', event => {
            if (event.persisted) {
                document.documentElement.style.visibility = 'hidden';
            }
        });
        window.addEventListener('pageshow', event => {
            if (event.persisted) {
                window.location.reload();
            }
        });
    }

    document.querySelectorAll('[data-open-dialog]').forEach(button => {
        button.addEventListener('click', event => {
            event.preventDefault();
            document.getElementById(button.dataset.openDialog)?.showModal();
        });
    });
    document.querySelectorAll('[data-close-dialog]').forEach(button => {
        button.addEventListener('click', () => button.closest('dialog').close());
    });
    document.querySelector('dialog[data-reopen]')?.showModal();
    document.querySelectorAll('[data-resident-uploads]').forEach(form => {
        let processing = 0;
        form.addEventListener('form-processing-started', () => {
            processing += 1;
        });
        form.addEventListener('form-processing-finished', () => {
            processing = Math.max(0, processing - 1);
        });
        form.addEventListener('submit', event => {
            if (processing > 0 || form.querySelector('[data-upload-busy]')) {
                event.preventDefault();
                window.alert('Please wait until your attachments are ready to submit.');
            }
        });
    });
})();
