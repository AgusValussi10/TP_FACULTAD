// Cliente de la API de la app (/app/api/). Mismo origen: viaja la cookie de sesión.
// Siempre devuelve {success, data, message, status}; nunca lanza.
// Si el servidor responde 401 (sesión vencida), avisa con el evento 'sesion-vencida'.

export async function api(ruta, datos) {
  const opciones = { credentials: 'same-origin', headers: { Accept: 'application/json' } };
  if (datos instanceof FormData) {
    opciones.method = 'POST';
    opciones.body = datos;
  } else if (datos) {
    opciones.method = 'POST';
    opciones.body = new URLSearchParams(datos);
  }
  let res;
  try {
    res = await fetch('/app/api/' + ruta, opciones);
  } catch (e) {
    return { success: false, data: null, status: 0, offline: true, message: 'No hay conexión. Revisá tu internet y probá de nuevo.' };
  }
  let json = null;
  try {
    json = await res.json();
  } catch (e) {
    json = null;
  }
  if (res.status === 401) {
    window.dispatchEvent(new CustomEvent('sesion-vencida'));
  }
  if (!json || typeof json !== 'object') {
    return { success: false, data: null, status: res.status, message: 'El servidor respondió algo inesperado. Probá de nuevo en un rato.' };
  }
  return { success: !!json.success, data: json.data ?? null, message: json.message || '', status: res.status };
}
