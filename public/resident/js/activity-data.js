/** Shared interactions for server-rendered resident records. */
(() => {
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
})();
