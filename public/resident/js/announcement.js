/** Label adjacent published announcements for keyboard navigation. */
(() => {
    document.querySelectorAll('.detail-pagination a').forEach(link => link.setAttribute('aria-label', link.textContent.trim()));
})();