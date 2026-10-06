const API_BASE = 'http://localhost:5089/api';

function getToken() { return localStorage.getItem('jwt_token'); }
function clearSession() {
  localStorage.removeItem('jwt_token');
  localStorage.removeItem('user_nombre');
  localStorage.removeItem('user_rol');
}

async function apiFetch(endpoint, options = {}) {
  const token = getToken();
  const headers = { 'Content-Type': 'application/json' };
  if (token) headers['Authorization'] = `Bearer ${token}`;

  let res;
  try {
    res = await fetch(API_BASE + endpoint, { headers, ...options });
  } catch {
    throw new Error('No se puede conectar al servidor. Verificá que el backend esté corriendo en localhost:5089');
  }

  if (res.status === 401) {
    clearSession();
    if (window.location.pathname !== '/login.html') window.location.href = 'login.html';
    throw new Error('Sesión expirada');
  }
  if (res.status === 204) return null;
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.message || `Error ${res.status}`);
  }
  return res.json();
}

function formatFecha(isoStr) {
  if (!isoStr) return '—';
  const d = new Date(isoStr);
  return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
}

function formatPeso(n) {
  if (n == null) return '—';
  return '$ ' + Number(n).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function badge(estado, mapa = {}) {
  const defaults = {
    activo: 'badge-verde', vigente: 'badge-verde', pagada: 'badge-verde', jugado: 'badge-verde', presente: 'badge-verde',
    pendiente: 'badge-naranja', programado: 'badge-naranja',
    vencido: 'badge-rojo', vencida: 'badge-rojo', suspendido: 'badge-rojo',
    inactivo: 'badge-gris', ausente: 'badge-gris'
  };
  const cls = mapa[estado] || defaults[estado] || 'badge-gris';
  return `<span class="badge ${cls}">${estado}</span>`;
}
