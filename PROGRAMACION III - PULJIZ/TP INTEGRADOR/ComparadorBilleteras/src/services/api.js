// Emulador Android: 'http://10.0.2.2:3000'
// Celular físico por USB (adb reverse tcp:3000 tcp:3000): 'http://localhost:3000'
// Celular físico por WiFi: IP de la PC en la red
const API_BASE_URL = 'http://localhost:3000';

async function request(path, options = {}) {
  const { headers: extraHeaders, ...rest } = options;
  const res = await fetch(`${API_BASE_URL}${path}`, {
    headers: { 'Content-Type': 'application/json', ...extraHeaders },
    ...rest,
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.error ?? 'Error de servidor');
  return data;
}

function authHeaders(token) {
  return { Authorization: `Bearer ${token}` };
}

// ── Cotizaciones ──────────────────────────────────────────────
export function getCotizaciones(monto) {
  return request(`/api/cotizaciones?monto=${monto}`);
}

export function getHistorialCotizaciones(billetera_id, page = 1, limit = 10) {
  return request(`/api/cotizaciones/historial?billetera_id=${billetera_id}&page=${page}&limit=${limit}`);
}

// ── Billeteras ────────────────────────────────────────────────
export function getBilleteras() {
  return request('/api/billeteras');
}

export function getBilletera(id) {
  return request(`/api/billeteras/${id}`);
}

// ── Auth backend ──────────────────────────────────────────────
export function loginBackend(email, password) {
  return request('/api/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });
}

export function registerBackend(nombre, email, password) {
  return request('/api/auth/register', {
    method: 'POST',
    body: JSON.stringify({ nombre, email, password }),
  });
}

export function firebaseLoginBackend(idToken) {
  return request('/api/auth/firebase-login', {
    method: 'POST',
    body: JSON.stringify({ idToken }),
  });
}

// ── Historial ─────────────────────────────────────────────────
export function getHistorial(token, page = 1, limit = 10) {
  return request(`/api/historial?page=${page}&limit=${limit}`, { headers: authHeaders(token) });
}

export function saveHistorial(token, { monto, moneda_destino, mejor_billetera_id, mejor_tasa, total_ars }) {
  return request('/api/historial', {
    method: 'POST',
    headers: authHeaders(token),
    body: JSON.stringify({ monto, moneda_destino, mejor_billetera_id, mejor_tasa, total_ars }),
  });
}

// ── Alertas ───────────────────────────────────────────────────
export function getAlertas(token) {
  return request('/api/alertas', { headers: authHeaders(token) });
}

export function createAlerta(token, { billetera_id, condicion, valor_objetivo }) {
  return request('/api/alertas', {
    method: 'POST',
    headers: authHeaders(token),
    body: JSON.stringify({ billetera_id, condicion, valor_objetivo }),
  });
}

export function toggleAlerta(token, id, activa) {
  return request(`/api/alertas/${id}`, {
    method: 'PATCH',
    headers: authHeaders(token),
    body: JSON.stringify({ activa }),
  });
}

// ── Favoritos ─────────────────────────────────────────────────
export function getFavoritos(token) {
  return request('/api/favoritos', { headers: authHeaders(token) });
}

export function addFavorito(token, billetera_id) {
  return request('/api/favoritos', {
    method: 'POST',
    headers: authHeaders(token),
    body: JSON.stringify({ billetera_id }),
  });
}

export function removeFavorito(token, billetera_id) {
  return request(`/api/favoritos/${billetera_id}`, {
    method: 'DELETE',
    headers: authHeaders(token),
  });
}

// ── Reseñas ───────────────────────────────────────────────────
export function createResena(token, { billetera_id, calificacion, comentario }) {
  return request('/api/resenas', {
    method: 'POST',
    headers: authHeaders(token),
    body: JSON.stringify({ billetera_id, calificacion, comentario }),
  });
}
