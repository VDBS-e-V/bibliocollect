// K11: Native fragment links handle navigation and focus; only mirror the active hash for AT.
function syncSectionIndices() {
  const hash = window.location.hash;
  if (!hash) return;

  document.querySelectorAll('[data-ui-section-index]').forEach((nav) => {
    nav.querySelectorAll('a[href^="#"]').forEach((link) => {
      if (link.getAttribute('href') === hash) {
        link.setAttribute('aria-current', 'location');
      } else {
        link.removeAttribute('aria-current');
      }
    });
  });
}

window.addEventListener('hashchange', syncSectionIndices);
document.addEventListener('DOMContentLoaded', syncSectionIndices);
