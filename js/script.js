// Página de inicio (html.oscurece): el título principal es siempre verde bosque; el resto parte crema
// y cada sección se va poniendo verde a medida que se baja (y vuelve a crema al subir).
//   - Bajando: la sección se pone negra cuando su borde superior sube del 65 % de la pantalla.
//   - Subiendo: vuelve a crema apenas su borde superior baja del 15 %, o sea, al salir de ella hacia arriba.
//   - Arriba del todo, todo crema.
(function inicioOscurece() {
  const root = document.documentElement;
  if (!root.classList.contains('oscurece')) return;

  // Vista de prueba ?vista=franjas: franjas fijas crema / verde alternadas, sin cambios al hacer scroll
  if (new URLSearchParams(location.search).get('vista') === 'franjas') {
    const fijar = () => ['#servicios', '#nosotros', '#contacto', '.site-footer']
      .forEach((q) => document.querySelector(q)?.classList.add('negra'));
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fijar);
    else fijar();
    return;
  }
  // Ubicación y el mapa cambian juntos; Contacto arrastra al pie de página
  const grupos = [['#ubicacion', '.map-section'], ['#servicios'], ['#funciones'],
    ['#nosotros'], ['#recursos'], ['#contacto', '.site-footer']]
    .map((sel) => sel.map((q) => document.querySelector(q)).filter(Boolean))
    .filter((g) => g.length);

  // Cada sección pasa de crema a verde con una animación que siempre termina (nunca queda a medias):
  //   - Bajando: se pone verde cuando su borde superior sube del 65 % de la pantalla.
  //   - Subiendo: vuelve a crema apenas su borde superior baja del 15 %, al salir de ella hacia arriba.
  //   - Arriba del todo, todo crema.
  // El efecto de las esquinas hacia el centro lo hace el CSS (ver .negra en css/style.css).
  let ultimoY = window.scrollY;
  const revisar = () => {
    const y = window.scrollY;
    const subiendo = y < ultimoY;
    ultimoY = y;
    const alto = window.innerHeight;
    grupos.forEach((grupo) => {
      const top = grupo[0].getBoundingClientRect().top;
      let negra = grupo[0].classList.contains('negra');
      if (y <= 40) negra = false;
      else if (subiendo) { if (top > alto * 0.15) negra = false; }
      else negra = top < alto * 0.65;
      grupo.forEach((el) => el.classList.toggle('negra', negra));
    });
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', revisar);
  else revisar();
  window.addEventListener('scroll', revisar, { passive: true });
  window.addEventListener('resize', revisar);
})();


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
    // Si se llega a una sección con #ancla (por ejemplo al volver desde Trámites), sin espera
    let initialWait = location.hash ? 0 : 1.5;

    const observer = new IntersectionObserver((entries) => {
      const entering = entries.filter((entry) => entry.isIntersecting);
      const wait = initialWait;
      initialWait = 0;
      entering.forEach((entry, i) => {
        const el = entry.target;
        // La espera se aplica solo a la primera transición de la lista (el fundido de opacidad);
        // las demás (borde, color, elevación y sombra al pasar el mouse) quedan sin espera.
        // Un valor por transición: si faltan, CSS repite la lista y la espera volvería a aparecer.
        el.style.transitionDelay = `${wait + i * 0.3}s, 0s, 0s, 0s, 0s`;
        el.classList.add('is-in');
        // Quita el retraso al terminar, para que los hover respondan al instante
        el.addEventListener('transitionend', (e) => {
          if (e.target === el && e.propertyName === 'opacity') el.style.transitionDelay = '';
        });
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });

    targets.forEach((el) => observer.observe(el));
  }

  mostrarTurno();

  // Al volver desde Trámites (#ver-tramites), dejar el botón centrado en la pantalla
  if (location.hash === '#ver-tramites') {
    const boton = document.getElementById('ver-tramites');
    const centrar = () => boton && boton.scrollIntoView({ block: 'center', behavior: 'instant' });
    centrar();
    window.addEventListener('load', centrar, { once: true });
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

// ── Notaría de turno ────────────────────────────────────────────────────
// Cada turno es un periodo (normalmente un mes). Fechas en formato AAAA-MM-DD.
//   - Sin "dias": durante todo el periodo dice "Estamos de turno".
//   - Con dias (0 = domingo, 1 = lunes … 6 = sábado) y horario desde/hasta:
//       · el día de turno dice "Estamos de turno · 9:00 a 14:00 hrs.";
//       · de lunes a viernes avisa el próximo día de turno de la semana ("De turno el sábado 10").
//   - Fuera del periodo muestra los meses de turno del año ("Turno notarial 2026: mayo y noviembre").
// Todo se calcula con la hora de Chile. Para probar cómo se ve un día cualquiera:
// abrir la página con ?fecha=2026-10-07 (simula ese día).
const TURNOS = [
  // Fuente: calendario de turnos de la Asociación de Notarios y Conservadores (marzo 2026).
  // En Ovalle el turno es los sábados de 9:00 a 12:00 (no se atiende los sábados feriados).
  // Pendiente: resto de 2026 y 2027 según el calendario de la notaría.
  { inicio: '2026-03-01', fin: '2026-03-31', dias: [6], desde: '9:00', hasta: '12:00' },
];

const DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
const DIAS_CORTOS = ['dom.', 'lun.', 'mar.', 'mié.', 'jue.', 'vie.', 'sáb.'];
const MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const MESES_CORTOS = ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'sept.', 'oct.', 'nov.', 'dic.'];

// "mayo y noviembre", "marzo, julio y noviembre"
const unirConY = (lista) => (lista.length > 1 ? `${lista.slice(0, -1).join(', ')} y ${lista[lista.length - 1]}` : lista[0] || '');

// Meses de turno del año, según el mes en que empieza cada periodo
function mesesDeTurno(anio) {
  const meses = [...new Set(TURNOS.filter((t) => t.inicio.startsWith(anio)).map((t) => Number(t.inicio.slice(5, 7)) - 1))];
  return meses.sort((a, b) => a - b);
}

// Fechas como texto AAAA-MM-DD, tratadas en UTC para que sumar días no dependa de la zona horaria
const aFecha = (texto) => new Date(`${texto}T00:00:00Z`);
const aTexto = (fecha) => fecha.toISOString().slice(0, 10);

function buscarTurno(hoyTexto) {
  const hoy = aFecha(hoyTexto);
  const diaSemana = hoy.getUTCDay();

  for (const turno of TURNOS) {
    if (hoyTexto < turno.inicio || hoyTexto > turno.fin) continue;

    // Sin calendario de días: todo el periodo cuenta como turno
    if (!turno.dias) return { turno, fecha: hoy, esHoy: true };

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
  const aviso = document.createElement('span');

  // Fuera del turno: aviso fijo con los meses de turno del año
  if (!resultado) {
    const anio = hoy.slice(0, 4);
    const meses = mesesDeTurno(anio);
    if (!meses.length) return;
    aviso.className = 'turno-badge turno-badge--proximo';
    aviso.innerHTML =
      '<span class="turno-dot" aria-hidden="true"></span>' +
      `<span class="turno-largo">Turno notarial ${anio}: ${unirConY(meses.map((m) => MESES[m]))}</span>` +
      `<span class="turno-corto">Turno: ${unirConY(meses.map((m) => MESES_CORTOS[m]))}</span>`;
    logo.after(aviso);
    return;
  }

  const { turno, fecha, esHoy } = resultado;
  const dia = fecha.getUTCDay();
  const numero = fecha.getUTCDate();
  const largo = esHoy ? 'Estamos de turno' : `De turno el ${DIAS[dia]} ${numero}`;
  const corto = esHoy ? 'De turno' : `Turno ${DIAS_CORTOS[dia]} ${numero}`;
  const horario = turno.desde && turno.hasta ? `<span class="turno-horario"> · ${turno.desde} a ${turno.hasta} hrs.</span>` : '';

  // Es solo informativo: un <span>, no un link, así que hacer click no hace nada
  aviso.className = esHoy ? 'turno-badge' : 'turno-badge turno-badge--proximo';
  aviso.innerHTML =
    '<span class="turno-dot" aria-hidden="true"></span>' +
    `<span class="turno-largo">${largo}</span><span class="turno-corto">${corto}</span>` +
    horario;
  logo.after(aviso);
}
