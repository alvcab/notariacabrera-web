const BIMESTRE_ORDER = { Primer: 1, Segundo: 2, Tercer: 3, Cuarto: 4, Quinto: 5, Sexto: 6 };

function parseNumero(str) {
  if (!str) return 0;
  return parseFloat(String(str).replace(/\./g, '').replace(',', '.')) || 0;
}

// Fecha como dd/mm/aaaa (los datos traen "09/01/2026 10:59 AM", "27/01/2025" o "01-06-2022")
function fechaCorta(str) {
  const m = String(str || '').match(/(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})/);
  return m ? `${m[1].padStart(2, '0')}/${m[2].padStart(2, '0')}/${m[3]}` : '';
}

// Para ordenar por fecha: aaaammdd
function fechaOrden(str) {
  const f = fechaCorta(str);
  return f ? f.slice(6) + f.slice(3, 5) + f.slice(0, 2) : '';
}

const REGISTRO_CONFIG = {
  publicos: {
    title: 'Registro de Índices de Instrumentos Públicos',
    columns: [
      { label: 'Fecha', get: r => fechaCorta(r.fechaindice) || '--', sort: r => fechaOrden(r.fechaindice) },
      { label: 'N° Repertorio', get: r => String(r.numero || '').replace(/\./g, ''), sort: r => parseNumero(r.numero) },
      { label: 'Bimestre', get: r => r.bimestre, sort: r => BIMESTRE_ORDER[r.bimestre] || 0 },
      { label: 'Materia', get: r => r.Materia, sort: r => (r.Materia || '').toLowerCase() },
      { label: 'Otorgante 1', get: r => [r.nom1, r.appaterno1, r.apmaterno1].filter(Boolean).join(' '), sort: r => (r.appaterno1 || '').toLowerCase() },
      { label: 'Otorgante 2', get: r => [r.nom2, r.appaterno2, r.apmaterno2].filter(Boolean).join(' '), sort: r => (r.appaterno2 || '').toLowerCase() },
    ],
  },
  minero: {
    title: 'Registro de Índices Mineros',
    columns: [
      { label: 'Fecha', get: r => fechaCorta(r.fechadoc) || '--', sort: r => fechaOrden(r.fechadoc) },
      { label: 'Fojas', get: r => [r.Fojas, r.fojasdigito].filter(Boolean).join(' '), sort: r => parseNumero(r.Fojas) },
      { label: 'N° Repertorio', get: r => r.Repertorio, sort: r => parseNumero(r.Repertorio) },
      { label: 'Registro', get: r => r.Registro, sort: r => (r.Registro || '').toLowerCase() },
      { label: 'Nombre', get: r => r.Nombre, sort: r => (r.Nombre || '').toLowerCase() },
      { label: 'Materia', get: r => r.Materia, sort: r => (r.Materia || '').toLowerCase() },
    ],
  },
};

const PAGE_SIZE = 50;

document.addEventListener('DOMContentLoaded', async () => {
  const params = new URLSearchParams(window.location.search);
  const tipo = params.get('tipo');
  const anio = params.get('anio');
  const config = REGISTRO_CONFIG[tipo];

  const titleEl = document.getElementById('registroTitle');
  const countEl = document.getElementById('registroCount');
  const searchEl = document.getElementById('registroSearch');
  const headEl = document.getElementById('registroHead');
  const bodyEl = document.getElementById('registroBody');
  const prevBtn = document.getElementById('prevPage');
  const nextBtn = document.getElementById('nextPage');
  const pageInfoEl = document.getElementById('pageInfo');

  if (!config || !anio) {
    titleEl.textContent = 'Registro no encontrado';
    countEl.textContent = 'Falta indicar el tipo y el año en la URL (ej: registro.html?tipo=publicos&anio=2026).';
    return;
  }

  titleEl.textContent = `${config.title} — ${anio}`;

  // Índices mineros: enlace a la versión en texto del año (indice-minero-AAAA.html), que es la que lee Google
  if (tipo === 'minero') {
    const texto = document.createElement('a');
    texto.href = `indice-minero-${anio}.html`;
    texto.className = 'registro-texto-link';
    texto.textContent = 'Ver índice en texto';
    // Abajo y discreto, después de la paginación: es para Google más que para las personas
    document.querySelector('.registro-pager').after(texto);
  }
  document.title = `${config.title} ${anio} — Segunda Notaría Pública de Ovalle`;

  let sortIndex = null;
  let sortDir = 1;

  function renderHead() {
    headEl.innerHTML = config.columns.map((col, i) => {
      const arrow = sortIndex === i ? (sortDir === 1 ? ' ▲' : ' ▼') : '';
      const narrowClass = col.label === 'Materia' ? ' col-materia' : col.label === 'N° Repertorio' ? ' col-repertorio' : col.label === 'Fecha' ? ' col-fecha' : '';
      return `<th data-index="${i}" class="sortable${narrowClass}">${col.label}${arrow}</th>`;
    }).join('');

    headEl.querySelectorAll('th').forEach(th => {
      th.addEventListener('click', () => {
        const i = Number(th.dataset.index);
        if (sortIndex === i) {
          sortDir *= -1;
        } else {
          sortIndex = i;
          sortDir = 1;
        }
        currentPage = 1;
        applySortAndRender();
      });
    });
  }

  let allRows = [];
  let filteredRows = [];
  let currentPage = 1;

  // Primero los datos en vivo del Sistema Notaría (indices.php); si no responde o no tiene ese año, el JSON guardado
  async function cargar(url) {
    const res = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error('not found');
    const filas = await res.json();
    if (!Array.isArray(filas)) throw new Error('formato');
    return filas;
  }
  const consulta = `tipo=${encodeURIComponent(tipo)}&anio=${encodeURIComponent(anio)}`;
  try {
    allRows = await cargar(`indices.php?${consulta}`);
  } catch (err) {
    try {
      allRows = await cargar(`assets/data/${tipo}-${anio}.json`);
    } catch (err2) {
      countEl.textContent = 'No se pudo cargar la información de este año todavía.';
      return;
    }
  }

  const DEFAULT_SORT_OVERRIDES = {
    'publicos-2022': 'Bimestre',
  };
  const defaultSortLabel = DEFAULT_SORT_OVERRIDES[`${tipo}-${anio}`] || 'N° Repertorio';

  filteredRows = allRows;
  sortIndex = config.columns.findIndex(col => col.label === defaultSortLabel);
  sortDir = -1;

  function applySortAndRender() {
    if (sortIndex !== null) {
      const sortFn = config.columns[sortIndex].sort;
      filteredRows = [...filteredRows].sort((a, b) => {
        const va = sortFn(a);
        const vb = sortFn(b);
        if (va < vb) return -1 * sortDir;
        if (va > vb) return 1 * sortDir;
        return 0;
      });
    }
    renderHead();
    renderPage();
  }

  function renderPage() {
    const start = (currentPage - 1) * PAGE_SIZE;
    const pageRows = filteredRows.slice(start, start + PAGE_SIZE);

    bodyEl.innerHTML = pageRows.map(row => {
      const cells = config.columns.map(col => {
        const value = col.get(row) || '';
        const isMateria = col.label === 'Materia';
        const isRepertorio = col.label === 'N° Repertorio';
        const attrs = isMateria
          ? ` class="col-materia" title="${value.replace(/"/g, '&quot;')}"`
          : isRepertorio
            ? ' class="col-repertorio"'
            : col.label === 'Fecha'
              ? ' class="col-fecha"'
              : '';
        return `<td${attrs}>${value}</td>`;
      }).join('');
      return `<tr>${cells}</tr>`;
    }).join('');

    const totalPages = Math.max(1, Math.ceil(filteredRows.length / PAGE_SIZE));
    pageInfoEl.textContent = `Página ${currentPage} de ${totalPages}`;
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
    countEl.textContent = `${filteredRows.length} de ${allRows.length} registros`;
  }

  // Botón X: aparece cuando hay texto y borra la búsqueda
  const clearEl = document.getElementById('registroSearchClear');
  if (clearEl) {
    clearEl.addEventListener('click', () => {
      searchEl.value = '';
      searchEl.dispatchEvent(new Event('input'));
      searchEl.focus();
    });
  }

  searchEl.addEventListener('input', () => {
    if (clearEl) clearEl.hidden = !searchEl.value;
    const term = searchEl.value.trim().toLowerCase();
    filteredRows = !term
      ? allRows
      : allRows.filter(row =>
          config.columns.some(col => String(col.get(row) || '').toLowerCase().includes(term))
        );
    currentPage = 1;
    applySortAndRender();
  });

  prevBtn.addEventListener('click', () => {
    if (currentPage > 1) {
      currentPage -= 1;
      renderPage();
    }
  });

  nextBtn.addEventListener('click', () => {
    const totalPages = Math.ceil(filteredRows.length / PAGE_SIZE);
    if (currentPage < totalPages) {
      currentPage += 1;
      renderPage();
    }
  });

  applySortAndRender();
});
