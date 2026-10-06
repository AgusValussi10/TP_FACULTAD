const sectionTitles = {
  reportes: 'Dashboard',
  categorias: 'Categorías',
  jugadores: 'Jugadores',
  cuotas: 'Cuotas',
  seguros: 'Seguros',
  carnets: 'Carnets de Liga',
  asistencia: 'Asistencia',
  partidos: 'Partidos y Resultados',
  ia: 'Recordatorio IA'
};

const sectionRenders = {
  reportes: renderReportes,
  categorias: renderCategorias,
  jugadores: renderJugadores,
  cuotas: renderCuotas,
  seguros: renderSeguros,
  carnets: renderCarnets,
  asistencia: renderAsistencia,
  partidos: renderPartidos,
  ia: renderIA
};

let currentSection = 'reportes';

function navigateTo(section) {
  if (!sectionRenders[section]) return;
  currentSection = section;

  document.querySelectorAll('.nav-item').forEach(a => {
    a.classList.toggle('active', a.dataset.section === section);
  });

  document.getElementById('topbar-title').textContent = sectionTitles[section] || section;
  document.getElementById('content-area').innerHTML = '<div class="loading"><div class="spinner"></div> Cargando...</div>';
  closeModal();
  sectionRenders[section]();
}

// Modal helpers
function openModal(title, bodyHtml) {
  document.getElementById('modal-title').textContent = title;
  document.getElementById('modal-body').innerHTML = bodyHtml;
  document.getElementById('modal-overlay').classList.add('active');
}
function closeModal() {
  document.getElementById('modal-overlay').classList.remove('active');
}

// Toast
function showToast(msg, type = 'success') {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}

// Confirm inline helper (replaces element content with confirm buttons)
function confirmInline(btnEl, onConfirm) {
  const original = btnEl.outerHTML;
  const parent = btnEl.parentNode;
  const wrapper = document.createElement('span');
  wrapper.innerHTML = `
    <span style="font-size:13px;font-weight:700;color:#c0392b;margin-right:6px;">¿Confirmar?</span>
    <button class="btn btn-sm btn-danger" id="confirm-yes">Sí</button>
    <button class="btn btn-sm btn-secondary" id="confirm-no" style="margin-left:4px;">No</button>
  `;
  parent.replaceChild(wrapper, btnEl);
  wrapper.querySelector('#confirm-yes').addEventListener('click', () => {
    wrapper.innerHTML = '<span class="spinner" style="display:inline-block"></span>';
    onConfirm();
  });
  wrapper.querySelector('#confirm-no').addEventListener('click', () => {
    wrapper.outerHTML = original;
  });
}

// Render table helper
function renderTable(columns, rows, rowFn) {
  if (!rows || rows.length === 0) {
    return `<table><thead><tr>${columns.map(c=>`<th>${c}</th>`).join('')}</tr></thead>
      <tbody><tr class="empty-row"><td colspan="${columns.length}">Sin datos</td></tr></tbody></table>`;
  }
  return `<table>
    <thead><tr>${columns.map(c=>`<th>${c}</th>`).join('')}</tr></thead>
    <tbody>${rows.map(rowFn).join('')}</tbody>
  </table>`;
}

// Init
document.addEventListener('DOMContentLoaded', () => {
  if (!getToken()) {
    window.location.href = 'login.html';
    return;
  }

  const nombre = localStorage.getItem('user_nombre') || 'Admin';
  document.getElementById('user-name-display').textContent = nombre;
  document.getElementById('user-chip').textContent = nombre;

  // Nav click handlers
  document.querySelectorAll('.nav-item').forEach(a => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      navigateTo(a.dataset.section);
    });
  });

  // Modal close
  document.getElementById('modal-close').addEventListener('click', closeModal);
  document.getElementById('modal-overlay').addEventListener('click', (e) => {
    if (e.target === document.getElementById('modal-overlay')) closeModal();
  });

  // Logout
  document.getElementById('btn-logout').addEventListener('click', () => {
    clearSession();
    window.location.href = 'login.html';
  });

  // Hash routing
  const hash = window.location.hash.replace('#', '');
  navigateTo(sectionRenders[hash] ? hash : 'reportes');
});
