// Emulador Android: 'http://10.0.2.2:3000'
// Celular físico por USB (adb reverse tcp:3000 tcp:3000): 'http://localhost:3000'
// Celular físico por WiFi: IP de la PC en la red
const API_BASE_URL = 'http://localhost:3000';

// Función auxiliar central que arma la URL completa y hace el fetch a la API, normalizando headers y errores
async function request(path, options = {}) {
  // Se destructura headers antes del spread para no pisar Content-Type con extraHeaders (bug conocido, ver CLAUDE.md)
  const { headers: extraHeaders, ...rest } = options;
  const res = await fetch(`${API_BASE_URL}${path}`, {
    headers: { 'Content-Type': 'application/json', ...extraHeaders },
    ...rest,
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.error ?? 'Error de servidor');
  return data;
}

// Función para armar el header Authorization con el JWT del usuario/admin
function authHeaders(token) {
  return { Authorization: `Bearer ${token}` };
}

// ── Cotizaciones ──────────────────────────────────────────────
// Función para pedir a la API el ranking de billeteras para un monto dado
export function getCotizaciones(monto) {
  return request(`/api/cotizaciones?monto=${monto}`);
}

// Función para pedir el historial de tasas (evolución) de una billetera puntual, paginado
export function getHistorialCotizaciones(billetera_id, page = 1, limit = 10) {
  return request(`/api/cotizaciones/historial?billetera_id=${billetera_id}&page=${page}&limit=${limit}`);
}

// ── Billeteras ────────────────────────────────────────────────
// Función para listar billeteras activas, con filtro opcional por nombre y país resuelto en la API (WHERE dinámico en SQL)
export function getBilleteras(filtros = {}) {
  const params = new URLSearchParams();
  if (filtros.nombre) params.set('nombre', filtros.nombre);
  if (filtros.pais) params.set('pais', filtros.pais);
  const qs = params.toString();
  return request(`/api/billeteras${qs ? `?${qs}` : ''}`);
}

// Función para pedir el detalle completo de una billetera (comisiones, países, reseñas, etc.)
export function getBilletera(id) {
  return request(`/api/billeteras/${id}`);
}

// ── Auth backend ──────────────────────────────────────────────
// Función para loguear contra el backend propio (email/contraseña) y obtener el JWT
export function loginBackend(email, password) {
  return request('/api/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });
}

// Función para registrar un usuario nuevo en el backend (MySQL)
export function registerBackend(nombre, email, password) {
  return request('/api/auth/register', {
    method: 'POST',
    body: JSON.stringify({ nombre, email, password }),
  });
}

// Función para intercambiar un ID token de Firebase por el JWT propio del backend (puente Firebase↔backend)
export function firebaseLoginBackend(idToken) {
  return request('/api/auth/firebase-login', {
    method: 'POST',
    body: JSON.stringify({ idToken }),
  });
}

// ── Historial ─────────────────────────────────────────────────
// Función para traer el historial de consultas del usuario, paginado real (LIMIT/OFFSET en la DB)
export function getHistorial(token, page = 1, limit = 10) {
  return request(`/api/historial?page=${page}&limit=${limit}`, { headers: authHeaders(token) });
}

// Función para guardar una consulta de cotización en el historial del usuario
export function saveHistorial(token, { monto, moneda_destino, mejor_billetera_id, mejor_tasa, total_ars }) {
  return request('/api/historial', {
    method: 'POST',
    headers: authHeaders(token),
    body: JSON.stringify({ monto, moneda_destino, mejor_billetera_id, mejor_tasa, total_ars }),
  });
}

// ── Alertas ───────────────────────────────────────────────────
// Función para traer las alertas del usuario logueado
export function getAlertas(token) {
  return request('/api/alertas', { headers: authHeaders(token) });
}

// Función para crear una alerta de cotización (condición + valor objetivo)
export function createAlerta(token, { billetera_id, condicion, valor_objetivo }) {
  return request('/api/alertas', {
    method: 'POST',
    headers: authHeaders(token),
    body: JSON.stringify({ billetera_id, condicion, valor_objetivo }),
  });
}

// Función para activar o pausar una alerta existente
export function toggleAlerta(token, id, activa) {
  return request(`/api/alertas/${id}`, {
    method: 'PATCH',
    headers: authHeaders(token),
    body: JSON.stringify({ activa }),
  });
}

// ── Favoritos ─────────────────────────────────────────────────
// Función para traer las billeteras favoritas del usuario
export function getFavoritos(token) {
  return request('/api/favoritos', { headers: authHeaders(token) });
}

// Función para marcar una billetera como favorita
export function addFavorito(token, billetera_id) {
  return request('/api/favoritos', {
    method: 'POST',
    headers: authHeaders(token),
    body: JSON.stringify({ billetera_id }),
  });
}

// Función para quitar una billetera de favoritos
export function removeFavorito(token, billetera_id) {
  return request(`/api/favoritos/${billetera_id}`, {
    method: 'DELETE',
    headers: authHeaders(token),
  });
}

// ── Reseñas ───────────────────────────────────────────────────
// Función para crear una reseña de una billetera (dispara recalculo de rating en el backend)
export function createResena(token, { billetera_id, calificacion, comentario }) {
  return request('/api/resenas', {
    method: 'POST',
    headers: authHeaders(token),
    body: JSON.stringify({ billetera_id, calificacion, comentario }),
  });
}
