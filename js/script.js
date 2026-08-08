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
    const officeCoords = [-30.6040, -71.2015]; // Miguel Aguirre Perry Nº 328, centro de Ovalle

    const map = L.map(mapEl, {
      center: officeCoords,
      zoom: 16,
      scrollWheelZoom: false,
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
      maxZoom: 19,
    }).addTo(map);

    const greenDotIcon = L.divIcon({
      className: 'map-marker',
      iconSize: [16, 16],
    });

    L.marker(officeCoords, { icon: greenDotIcon })
      .addTo(map)
      .bindPopup('Miguel Aguirre Perry Nº 328, centro de Ovalle');
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
