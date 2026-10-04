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

  mostrarTurno();

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

// ── Notaría de turno ────────────────────────────────────────────────────
// Cada turno es un periodo (normalmente un mes) en que la notaría atiende ciertos días
// de la semana. Fechas en formato AAAA-MM-DD; dias: 0 = domingo, 1 = lunes … 6 = sábado.
//   - De lunes a viernes avisa el próximo día de turno de esa semana ("De turno el sábado 10").
//   - El mismo día de turno avisa "De turno hoy".
//   - Fuera del periodo no muestra nada.
// Todo se calcula con la hora de Chile. Para probar cómo se ve un día cualquiera:
// abrir la página con ?fecha=2026-10-07 (simula ese día).
const TURNOS = [
  // Ejemplo (turno de octubre, los sábados de 9:00 a 14:00):
  // { inicio: '2026-10-01', fin: '2026-10-31', dias: [6], desde: '9:00', hasta: '14:00' },
];

const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const DIAS_CORTOS = ['dom.', 'lun.', 'mar.', 'mié.', 'jue.', 'vie.', 'sáb.'];

// Fechas como texto AAAA-MM-DD, tratadas en UTC para que sumar días no dependa de la zona horaria
const aFecha = (texto) => new Date(`${texto}T00:00:00Z`);
const aTexto = (fecha) => fecha.toISOString().slice(0, 10);

function buscarTurno(hoyTexto) {
  const hoy = aFecha(hoyTexto);
  const diaSemana = hoy.getUTCDay();

  for (const turno of TURNOS) {
    if (hoyTexto < turno.inicio || hoyTexto > turno.fin) continue;

    // ¿Hoy es día de turno?
    if (turno.dias.includes(diaSemana)) return { turno, fecha: hoy, esHoy: true };

    // Si no: el próximo día de turno dentro de esta misma semana (de lunes a domingo).
    // El domingo no anuncia nada: el sábado siguiente ya es otra semana.
    if (diaSemana === 0) continue;
    for (let i = 1; diaSemana + i <= 7; i++) {
      const fecha = new Date(hoy.getTime() + i * 86400000);
      const texto = aTexto(fecha);
      if (texto > turno.fin) break;
      if (turno.dias.includes(fecha.getUTCDay())) return { turno, fecha, esHoy: false };
    }
  }
  return null;
}

function mostrarTurno() {
  const header = document.querySelector('.header-inner');
  const logo = header && header.querySelector('.logo');
  if (!logo) return;

  const simulada = new URLSearchParams(location.search).get('fecha');
  const hoy = /^\d{4}-\d{2}-\d{2}$/.test(simulada || '')
    ? simulada
    : new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Santiago' }).format(new Date());

  const resultado = buscarTurno(hoy);
  if (!resultado) return;

  const { turno, fecha, esHoy } = resultado;
  const dia = fecha.getUTCDay();
  const numero = fecha.getUTCDate();
  const largo = esHoy ? 'De turno hoy' : `De turno el ${DIAS[dia]} ${numero}`;
  const corto = esHoy ? 'De turno hoy' : `Turno ${DIAS_CORTOS[dia]} ${numero}`;

  // Es solo informativo: un <span>, no un link, así que hacer click no hace nada
  const aviso = document.createElement('span');
  aviso.className = esHoy ? 'turno-badge' : 'turno-badge turno-badge--proximo';
  aviso.innerHTML =
    '<span class="turno-dot" aria-hidden="true"></span>' +
    `<span class="turno-largo">${largo}</span><span class="turno-corto">${corto}</span>` +
    `<span class="turno-horario"> · ${turno.desde} a ${turno.hasta} hrs.</span>`;
  logo.after(aviso);
}
