/** Preview uploads without storing private resident files in browser storage. */
(() => {
  document.getElementById('pageSize')?.addEventListener('change', event => event.target.form.requestSubmit());
  const input = document.getElementById('attachment');
  const preview = document.getElementById('complaintImagePreview');
  const error = document.getElementById('complaintImageError');
  let url;
  function clear() {
    if (url) URL.revokeObjectURL(url);
    url = null;
    preview.hidden = true;
    error.textContent = '';
    input.setCustomValidity('');
  }
  input.addEventListener('change', () => {
    clear();
    const file = input.files[0];
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
      error.textContent = 'Choose a JPG, PNG, or WebP image up to 5 MB.';
      input.setCustomValidity(error.textContent);
      return;
    }
    url = URL.createObjectURL(file);
    document.getElementById('complaintImage').src = url;
    document.getElementById('complaintImageName').textContent = file.name;
    preview.hidden = false;
  });
  document.getElementById('removeComplaintImage').addEventListener('click', () => { clear(); input.value = ''; });
})();
