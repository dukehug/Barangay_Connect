/** Shared SweetAlert2 notices for resident forms. */
(() => {
    const fire = options => {
        const target = options.target || document.querySelector('dialog[open]') || document.body;
        if (!window.Swal) {
            const notice = document.createElement('p');
            notice.className = 'portal-message';
            notice.setAttribute('role', 'alert');
            notice.textContent = `${options.title}: ${options.text || ''}`;
            target.appendChild(notice);
            return Promise.resolve({isConfirmed: false});
        }
        return window.Swal.fire({
            ...options,
            target,
            position: 'center',
            allowOutsideClick: false,
            timer: 5000,
            timerProgressBar: true,
            heightAuto: false,
            theme: 'light',
            confirmButtonColor: '#0000ff',
            cancelButtonColor: '#64748b',
            customClass: {container: 'resident-alert-container', popup: 'resident-alert-popup'},
        });
    };
    window.ResidentAlerts = {fire};

    document.addEventListener('DOMContentLoaded', () => {
        const notice = document.querySelector('[data-resident-alert]');
        if (notice && window.Swal) {
            notice.hidden = true;
            fire({icon: notice.dataset.icon, title: notice.dataset.title, text: notice.dataset.message});
        }
        document.querySelectorAll('[data-document-form], #complaintForm, #profileForm').forEach(form => {
            let showingValidation = false;
            form.addEventListener('invalid', event => {
                event.preventDefault();
                if (showingValidation) return;
                showingValidation = true;
                const field = event.target;
                const label = field.labels?.[0]?.textContent.trim();
                fire({
                    icon: 'warning', title: 'Please check the form',
                    text: `${label ? `${label}: ` : ''}${field.validationMessage}`,
                }).then(() => {
                    showingValidation = false;
                    field.focus();
                });
            }, true);
        });
    });
})();
