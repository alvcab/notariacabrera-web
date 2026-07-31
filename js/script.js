document.addEventListener('DOMContentLoaded', () => {
  const navToggle = document.getElementById('navToggle');
  const navList = document.getElementById('navList');

  if (navToggle && navList) {
    navToggle.addEventListener('click', () => {
      const isOpen = navList.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', isOpen);
    });
  }

  const yearEl = document.getElementById('year');
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }

  document.querySelectorAll('.back-link[data-close-tab]').forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const fallbackHref = link.getAttribute('href');
      window.close();
      setTimeout(() => {
        window.location.href = fallbackHref;
      }, 300);
    });
  });
});
