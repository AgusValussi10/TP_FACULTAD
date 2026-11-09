// Utilidades de la vista: escape de HTML, formato de dinero y fechas.

const ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

/** Escapa texto para insertarlo en HTML. */
export function esc(valor) {
  return String(valor ?? '').replace(/[&<>"']/g, (c) => ESCAPES[c]);
}

/**
 * Template tag: html`<p>${texto}</p>` escapa cada valor interpolado.
 * Para insertar HTML ya armado (por ejemplo, otra plantilla), usar crudo().
 */
export function html(partes, ...valores) {
  return partes.reduce((salida, parte, i) => {
    if (i === 0) return parte;
    const v = valores[i - 1];
    const texto = v && v.__crudo ? v.valor : Array.isArray(v) ? v.map((x) => (x && x.__crudo ? x.valor : esc(x))).join('') : esc(v);
    return salida + texto + parte;
  }, '');
}

/** Marca un string como HTML seguro (ya escapado) para html``. */
export function crudo(valor) {
  return { __crudo: true, valor: String(valor ?? '') };
}

const PESOS = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' });
export function pesos(n) {
  return PESOS.format(Number(n) || 0);
}

/** Pone un mensaje accesible (role=alert/status) en un contenedor. */
export function mensaje(el, texto, tipo = 'error') {
  el.innerHTML = texto ? html`<div class="alerta alerta-${tipo}" role="${tipo === 'error' ? 'alert' : 'status'}">${texto}</div>` : '';
}
