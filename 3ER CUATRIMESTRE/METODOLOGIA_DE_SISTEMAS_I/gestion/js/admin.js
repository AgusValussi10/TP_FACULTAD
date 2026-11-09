// Utilidades compartidas de las pantallas del admin de la Parte 3.
// Los endpoints responden {success, data, message}.

/** Escapa texto para insertarlo en HTML. */
function esc(valor) {
  return String(valor ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

const formatoPesos = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' });
function pesos(n) {
  return formatoPesos.format(Number(n) || 0);
}

/** GET (sin datos) o POST (con datos) a un endpoint JSON. Nunca lanza: devuelve {success, data, message}. */
async function api(url, datos) {
  try {
    const opciones = datos ? { method: 'POST', body: new URLSearchParams(datos) } : {};
    const res = await fetch(url, opciones);
    const json = await res.json();
    return json && typeof json === 'object' ? json : { success: false, message: 'Respuesta inválida.' };
  } catch (e) {
    return { success: false, message: 'No se pudo conectar con el servidor.' };
  }
}

function mostrarMensaje(el, json) {
  el.textContent = json.message || (json.success ? 'Listo.' : 'Ocurrió un error.');
  el.className = 'msg ' + (json.success ? 'ok' : 'error');
}

const BADGES = {
  pendiente: 'badge-gris', parcial: 'badge-amarillo', pagada: 'badge-verde', pagado: 'badge-verde',
  en_revision: 'badge-azul', pendiente_transferencia: 'badge-gris', aprobado: 'badge-verde',
  rechazado: 'badge-rojo', anulado: 'badge-gris', vencida: 'badge-rojo',
};
function badge(estado, texto) {
  return `<span class="badge ${BADGES[estado] || 'badge-gris'}">${esc(texto || estado)}</span>`;
}
