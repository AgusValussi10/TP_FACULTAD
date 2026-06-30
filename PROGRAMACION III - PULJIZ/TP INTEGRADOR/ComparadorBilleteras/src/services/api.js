// Cambiar a la IP de LAN cuando se prueba en dispositivo físico (ej: 'http://192.168.1.X:3000')
const API_BASE_URL = 'http://10.0.2.2:3000';

async function request(path, options = {}) {
  const res = await fetch(`${API_BASE_URL}${path}`, {
    headers: { 'Content-Type': 'application/json', ...options.headers },
    ...options,
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

// ── Historial ─────────────────────────────────────────────────
export function getHistorial(token) {
  return request('/api/historial', { headers: authHeaders(token) });
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
