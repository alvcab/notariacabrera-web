document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const anio = params.get('anio') || '2026';

  document.querySelectorAll('.anio-block').forEach((el) => {
    el.style.display = el.dataset.anio === anio ? '' : 'none';
  });

  document.querySelectorAll('#yearLinks a').forEach((a) => {
    if (a.dataset.anio === anio) {
      a.classList.add('year-active');
    }
  });
});
