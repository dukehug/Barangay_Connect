/** Keep navigation accessible while Laravel selects the active page. */
(() => {
  document.querySelectorAll('nav a.active').forEach(link => link.setAttribute('aria-current', 'page'));
})();
