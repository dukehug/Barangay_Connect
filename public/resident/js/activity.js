/** Filter the latest authenticated activity without introducing sample records. */
(() => {
  const filter = document.getElementById('activityFilter');
  function render() {
    let count = 0;
    document.querySelectorAll('.activity-event').forEach(event => {
      event.hidden = !['all', event.dataset.kind, event.dataset.state].includes(filter.value);
      if (!event.hidden) count++;
    });
    document.getElementById('activityCount').textContent = `${count} activities`;
    document.getElementById('activityEmpty').hidden = count > 0;
  }
  filter.addEventListener('change', render);
  render();
})();
