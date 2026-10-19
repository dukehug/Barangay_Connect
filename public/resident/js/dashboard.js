/** Choose a greeting without replacing the authenticated resident's name. */
(() => {
  const greeting = document.getElementById('greet');
  const hour = new Date().getHours();
  greeting.textContent = `Magandang ${hour < 12 ? 'Umaga' : hour < 18 ? 'Hapon' : 'Gabi'}, ${greeting.dataset.name}!`;
})();
