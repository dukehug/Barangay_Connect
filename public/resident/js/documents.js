(() => {
    document.getElementById('pageSize')?.addEventListener('change', event => event.target.form.requestSubmit());
    document.querySelectorAll('[data-confirm-cancel]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!window.confirm('Cancel this request? Any reserved resources will be released.')) event.preventDefault();
        });
    });
    const previewDialog = document.querySelector('[data-attachment-dialog]');
    let previewTrigger;
    document.querySelectorAll('[data-file-preview]').forEach(link => {
        link.addEventListener('click', event => {
            if (!previewDialog || typeof previewDialog.showModal !== 'function') return;
            event.preventDefault();
            previewTrigger = link;
            const viewer = document.createElement(link.dataset.fileType === 'image' ? 'img' : 'iframe');
            viewer.src = link.href;
            viewer.setAttribute(link.dataset.fileType === 'image' ? 'alt' : 'title', link.dataset.fileName);
            previewDialog.querySelector('[data-preview-content]').replaceChildren(viewer);
            previewDialog.querySelector('#attachment-preview-title').textContent = link.dataset.fileName;
            previewDialog.querySelector('[data-preview-fallback]').href = link.href;
            previewDialog.showModal();
        });
    });
    previewDialog?.querySelector('[data-close-preview]')?.addEventListener('click', () => previewDialog.close());
    previewDialog?.addEventListener('close', () => {
        previewDialog.querySelector('[data-preview-content]').replaceChildren();
        previewTrigger?.focus();
    });
    document.querySelectorAll('[data-delete-attachment]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!window.confirm('Delete this file? You may need to upload a replacement before resubmitting.')) event.preventDefault();
        });
    });
    const form = document.querySelector('[data-document-form]');
    if (!form) return;
    const type = form.querySelector('#document_type_id');
    const application = form.querySelector('#application_kind');
    const sync = () => {
        const category = type.selectedOptions[0]?.dataset.category;
        form.querySelectorAll('[data-category-panel]').forEach(panel => {
            panel.hidden = !panel.dataset.categoryPanel.split(' ').includes(category);
            panel.querySelectorAll('input, select').forEach(input => { input.disabled = panel.hidden; });
        });
        const requiredKinds = category === 'clearance'
            ? ['request_letter', application.value === 'renewal' ? 'previous_clearance' : 'registration']
            : category === 'certification' ? ['voters_id', 'voter_registration_record', 'nbi_clearance', 'photo_id']
            : category === 'use_closure' ? ['request_letter'] : [];
        const supportingDocuments = form.querySelector('[data-supporting-documents]');
        if (supportingDocuments) supportingDocuments.hidden = requiredKinds.length === 0;
        form.querySelectorAll('[data-attachment-kind]').forEach(panel => {
            panel.hidden = !requiredKinds.includes(panel.dataset.attachmentKind);
            panel.querySelectorAll('[data-upload-token]').forEach(input => { input.disabled = panel.hidden; });
        });
        form.querySelectorAll('[data-resource-row]').forEach(row => {
            const selected = Number(row.querySelector('[data-resource-quantity]').value) > 0;
            row.querySelectorAll('input[type="datetime-local"]').forEach(input => { input.required = selected && category === 'use_closure'; });
        });
    };
    form.addEventListener('change', sync);
    form.addEventListener('submit', sync);
    sync();
})();
