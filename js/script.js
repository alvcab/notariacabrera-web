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

  const mapEl = document.getElementById('mapa-ubicacion');
  if (mapEl && window.L) {
    const officeCoords = [-30.60374, -71.20176]; // Miguel Aguirre Perry Nº 328 (punto de la notaría en OpenStreetMap)

    const map = L.map(mapEl, {
      center: officeCoords,
      zoom: 18,
      scrollWheelZoom: false,
    });

    // Mapa base de OpenStreetMap (gratis, sin API key). CARTO empezó a exigir API key.
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
      maxZoom: 19,
    }).addTo(map);

    const greenDotIcon = L.divIcon({
      className: 'map-marker',
      iconSize: [22, 22],
    });

    L.marker(officeCoords, { icon: greenDotIcon })
      .addTo(map)
      .bindPopup('Miguel Aguirre Perry Nº 328, centro de Ovalle');
  }

  // Aparición suave al bajar: cada bloque se muestra con un fundido al entrar en pantalla.
  // Los que entran juntos (tarjetas de una grilla) aparecen uno tras otro.
  if (document.documentElement.classList.contains('reveal')) {
    window.__revealOK = true;
    const targets = document.querySelectorAll([
      '.section > h2',
      '.section > h3',
      '.section > p',
      '.section > .card',
      '.section > .complaints-box',
      '.section > .links-grid',
      '.section > .resource-link',
      '.info-grid > *',
      '.cards-grid > *',
      '.map-section',
    ].join(','));

    // Lo que ya está en pantalla al abrir la página espera 1,5 s (mientras baja el título);
    // lo que aparece después al hacer scroll empieza de inmediato.
    let initialWait = 1.5;

    const observer = new IntersectionObserver((entries) => {
      const entering = entries.filter((entry) => entry.isIntersecting);
      const wait = initialWait;
      initialWait = 0;
      entering.forEach((entry, i) => {
        const el = entry.target;
        el.style.transitionDelay = `${wait + i * 0.3}s`;
        el.classList.add('is-in');
        // Quita el retraso al terminar, para que los hover respondan al instante
        el.addEventListener('transitionend', () => { el.style.transitionDelay = ''; }, { once: true });
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });

    targets.forEach((el) => observer.observe(el));
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
