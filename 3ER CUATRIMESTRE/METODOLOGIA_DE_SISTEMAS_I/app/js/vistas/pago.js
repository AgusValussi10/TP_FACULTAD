// Pago (RFGm05/RFGm06): estado del pago, datos para transferir, PDF del comprobante de
// pago y carga de comprobantes de transferencia (foto o PDF; se pueden cargar varios).
import { api } from '../api.js';
import { html, crudo, pesos, mensaje } from '../util.js';

const BADGE = {
  pendiente_transferencia: 'badge-amarillo', en_revision: 'badge-azul', aprobado: 'badge-verde',
  rechazado: 'badge-rojo', anulado: 'badge-gris',
};
const LADO_MAXIMO = 1600; // px: una foto de comprobante se lee bien así y pesa poco

/**
 * Las fotos del celular pesan varios MB y la base admite ~1 MB por archivo: se achican
 * en el teléfono (JPEG, lado mayor 1600 px) antes de subirlas. Los PDF van como están.
 */
async function prepararArchivo(archivo, maxBytes) {
  if (archivo.type === 'application/pdf') {
    if (archivo.size > maxBytes) throw new Error(`El PDF pesa ${Math.ceil(archivo.size / 1024)} KB y el máximo es ${Math.floor(maxBytes / 1024)} KB.`);
    return archivo;
  }
  if (!archivo.type.startsWith('image/')) throw new Error('Elegí una foto o un PDF.');
  const chico = (archivo.type === 'image/jpeg' || archivo.type === 'image/png') && archivo.size <= Math.min(maxBytes, 500 * 1024);
  if (chico) return archivo;
  const imagen = await createImageBitmap(archivo);
  const escala = Math.min(1, LADO_MAXIMO / Math.max(imagen.width, imagen.height));
  const canvas = document.createElement('canvas');
  canvas.width = Math.round(imagen.width * escala);
  canvas.height = Math.round(imagen.height * escala);
  canvas.getContext('2d').drawImage(imagen, 0, 0, canvas.width, canvas.height);
  for (const calidad of [0.8, 0.6, 0.45]) {
    const blob = await new Promise((ok) => canvas.toBlob(ok, 'image/jpeg', calidad));
    if (blob && blob.size <= maxBytes) {
      return new File([blob], archivo.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' });
    }
  }
  throw new Error('La foto sigue siendo muy pesada. Probá sacarla de más cerca o subí un PDF.');
}

function datoCopiable(etiqueta, valor) {
  return html`<div class="fila"><span>${etiqueta}</span>
    <span class="copiable"><strong>${valor}</strong>
      <button type="button" class="btn-chico" data-copiar="${valor}" aria-label="Copiar ${etiqueta}">Copiar</button></span></div>`;
}

export async function render(main, ctx) {
  const pagoId = Number(ctx.params().get('id'));
  if (ctx.estado.usuario.rol !== 'padre' || !pagoId) {
    ctx.ir('facturas');
    return;
  }
  const r = await api('pago.php?pago_id=' + pagoId);
  if (!r.success) {
    main.innerHTML = html`<a href="#/facturas" class="volver">← Facturas</a>`;
    main.insertAdjacentHTML('beforeend', '<div id="msg"></div>');
    mensaje(document.getElementById('msg'), r.message);
    return;
  }
  const p = r.data;
  const t = p.transferencia;
  const hoy = new Date().toISOString().slice(0, 10);
  const falta = Math.max(0, Math.round((p.total - p.declarado) * 100) / 100);

  const comprobantes = p.comprobantes.length
    ? html`<ul class="lista">${p.comprobantes.map((c) => crudo(html`<li class="item">
        <span><a href="/gestion/comprobante_ver.php?id=${c.id}" target="_blank" rel="noopener">${c.nombre}</a><br>
          <span class="sub">${c.fecha} · ${c.kb} KB</span></span>
        <span class="item-der">${pesos(c.monto)}</span></li>`))}</ul>`
    : html`<p class="vacio">Todavía no cargaste ningún comprobante.</p>`;

  const formulario = p.admite_comprobantes ? html`
    <section class="tarjeta" aria-labelledby="t-subir">
      <h2 id="t-subir"><span aria-hidden="true">📎</span> Cargar comprobante de transferencia</h2>
      <div id="msg-subir"></div>
      <form id="form-subir" novalidate>
        <div class="campo"><label for="monto">Monto transferido</label>
          <input id="monto" type="number" inputmode="decimal" min="1" step="0.01" value="${falta || p.total}" required></div>
        <div class="campo"><label for="fecha">Fecha de la transferencia</label>
          <input id="fecha" type="date" max="${hoy}" value="${hoy}" required></div>
        <div class="campo">
          <span class="etiqueta">Comprobante (foto o PDF, máximo ${p.max_kb} KB después de achicar la foto)</span>
          <label class="btn btn-sec" for="foto">📷 Sacar foto</label>
          <input id="foto" class="oculto" type="file" accept="image/*" capture="environment">
          <label class="btn btn-sec" for="archivo">📁 Elegir foto o PDF</label>
          <input id="archivo" class="oculto" type="file" accept="image/*,application/pdf">
          <p class="sub" id="elegido" aria-live="polite">Ningún archivo elegido.</p>
        </div>
        <button class="btn" type="submit" id="btn-subir" disabled>Enviar comprobante</button>
      </form>
    </section>` : '';

  main.innerHTML = html`
    <a href="#/facturas" class="volver">← Facturas</a>
    <h1>Pago ${p.numero}</h1>
    <p class="sub">${p.alumno} · Factura ${p.factura} (${p.periodo}) · ${p.fecha}</p>
    <div id="msg"></div>

    <section class="tarjeta" aria-labelledby="t-estado">
      <h2 id="t-estado">Estado: <span class="badge ${BADGE[p.estado]}">${p.estado_texto}</span></h2>
      ${crudo(p.motivo_rechazo ? html`<div class="alerta alerta-error">Motivo del rechazo: ${p.motivo_rechazo}</div>` : '')}
      <ul class="lista">${p.items.map((it) => crudo(html`<li class="item"><span>${it.descripcion}</span><span class="item-der">${pesos(it.importe)}</span></li>`))}</ul>
      <div class="fila total"><span>Total a transferir</span><span>${pesos(p.total)}</span></div>
      ${crudo(p.declarado > 0 ? html`<div class="fila"><span>Declarado en comprobantes</span><span>${pesos(p.declarado)}</span></div>` : '')}
      <a class="btn btn-sec" href="/app/api/pago_pdf.php?pago_id=${p.id}" target="_blank" rel="noopener">📄 Comprobante de pago (PDF)</a>
    </section>

    ${crudo(p.admite_comprobantes ? html`<section class="tarjeta" aria-labelledby="t-transf">
      <h2 id="t-transf"><span aria-hidden="true">🏦</span> Datos para transferir</h2>
      <div class="fila"><span>Banco</span><span>${t.banco}</span></div>
      <div class="fila"><span>Titular</span><span>${t.titular}</span></div>
      ${crudo(datoCopiable('CBU', t.cbu))}
      ${crudo(datoCopiable('Alias', t.alias))}
      ${crudo(datoCopiable('Referencia', p.numero))}
      <p class="sub">Poné el número de pago como referencia. Datos bancarios de demostración.</p>
    </section>` : '')}

    ${crudo(formulario)}

    <section class="tarjeta" aria-labelledby="t-comp">
      <h2 id="t-comp"><span aria-hidden="true">🧾</span> Comprobantes cargados</h2>
      ${crudo(comprobantes)}
    </section>

    ${crudo(p.estado === 'pendiente_transferencia' ? html`<button class="btn btn-sec" type="button" id="btn-anular">Anular este pago</button>` : '')}`;

  main.querySelectorAll('[data-copiar]').forEach((b) => b.addEventListener('click', () => {
    navigator.clipboard?.writeText(b.dataset.copiar)
      .then(() => { b.textContent = '¡Copiado!'; })
      .catch(() => { b.textContent = 'No se pudo'; });
  }));

  document.getElementById('btn-anular')?.addEventListener('click', async () => {
    if (!confirm('¿Anular el pago? Los ítems vuelven a quedar pendientes.')) return;
    const res = await api('pago_anular.php', { pago_id: String(p.id) });
    if (res.success) ctx.refrescar();
    else mensaje(document.getElementById('msg'), res.message);
  });

  if (!p.admite_comprobantes) return;
  let archivo = null;
  const btn = document.getElementById('btn-subir');
  const msg = document.getElementById('msg-subir');
  for (const id of ['foto', 'archivo']) {
    document.getElementById(id).addEventListener('change', (e) => {
      archivo = e.target.files[0] || null;
      document.getElementById('elegido').textContent = archivo
        ? `Elegiste: ${archivo.name} (${Math.ceil(archivo.size / 1024)} KB)` : 'Ningún archivo elegido.';
      btn.disabled = !archivo;
    });
  }
  document.getElementById('form-subir').addEventListener('submit', async (e) => {
    e.preventDefault();
    const monto = document.getElementById('monto').value;
    const fecha = document.getElementById('fecha').value;
    if (!(Number(monto) > 0) || !fecha) {
      mensaje(msg, 'Completá el monto y la fecha de la transferencia.');
      return;
    }
    btn.disabled = true;
    btn.textContent = 'Enviando…';
    try {
      const listo = await prepararArchivo(archivo, p.max_kb * 1024);
      const datos = new FormData();
      datos.append('pago_id', String(p.id));
      datos.append('monto', monto);
      datos.append('fecha', fecha);
      datos.append('archivo', listo, listo.name);
      const res = await api('comprobante_subir.php', datos);
      if (!res.success) throw new Error(res.message);
      ctx.refrescar();
    } catch (err) {
      mensaje(msg, err.message || 'No se pudo enviar el comprobante.');
      btn.disabled = false;
      btn.textContent = 'Enviar comprobante';
    }
  });
}
