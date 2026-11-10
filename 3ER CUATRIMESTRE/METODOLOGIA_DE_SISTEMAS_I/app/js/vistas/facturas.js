// Facturas (RFGm04): filtro por rango de períodos, cada factura con sus ítems, estado,
// pagos y comprobantes, descarga en PDF; y la pestaña "Deuda por concepto".
import { api } from '../api.js';
import { html, crudo, pesos, mensaje } from '../util.js';

const BADGE = {
  pendiente: 'badge-gris', parcial: 'badge-amarillo', pagada: 'badge-verde', pagado: 'badge-verde',
  en_revision: 'badge-azul', pendiente_transferencia: 'badge-amarillo', aprobado: 'badge-verde',
  rechazado: 'badge-rojo', anulado: 'badge-gris',
};

/** 'AAAA-MM' desplazado n meses desde hoy. */
function mesRelativo(n) {
  const d = new Date();
  d.setDate(1);
  d.setMonth(d.getMonth() + n);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
}

// Filtros y pestaña elegidos (se conservan al cambiar de hijo o de pantalla).
const filtro = { desde: mesRelativo(-11), hasta: mesRelativo(1), pestana: 'facturas' };

function badge(estado, texto) {
  return html`<span class="badge ${BADGE[estado] || 'badge-gris'}">${texto}</span>`;
}

function tarjetaFactura(f, esPadre) {
  const items = f.items.map((it) => crudo(html`<li class="item">
      <span>${it.descripcion}</span>
      <span class="item-der">${pesos(it.importe)}<br>${crudo(badge(it.estado, it.estado_texto))}</span>
    </li>`));
  const pagos = f.pagos.map((p) => crudo(html`<li class="item">
      <span><strong>${crudo(esPadre ? html`<a href="#/pago?id=${p.id}">${p.numero}</a>` : html`${p.numero}`)}</strong> · ${p.fecha}<br>${crudo(badge(p.estado, p.estado_texto))}
        ${crudo(p.motivo_rechazo ? html`<br><span class="sub">Motivo: ${p.motivo_rechazo}</span>` : '')}
        ${crudo(p.comprobantes.length ? html`<br><span class="sub">Comprobantes:</span> ${p.comprobantes.map((c) => crudo(esPadre
          ? html`<a href="/gestion/comprobante_ver.php?id=${c.id}" target="_blank" rel="noopener">${c.nombre}</a> (${pesos(c.monto)}) `
          : html`${c.nombre} (${pesos(c.monto)}) `))}` : '')}
      </span>
      <span class="item-der">${pesos(p.total)}</span>
    </li>`));

  return html`<li class="tarjeta factura">
    <div class="cuota-cabecera">
      <strong>${f.periodo}</strong>
      <span>${crudo(badge(f.estado, f.estado_texto))} ${crudo(f.vencida ? html`<span class="badge badge-rojo">Vencida</span>` : '')}</span>
    </div>
    <p class="sub">Factura ${f.numero} · vence el ${f.vencimiento_ar}</p>
    <div class="fila"><span>Total</span><span>${pesos(f.total)}</span></div>
    <div class="fila"><span>Pagado</span><span>${pesos(f.pagado)}</span></div>
    ${crudo(f.en_revision > 0 ? html`<div class="fila"><span>En proceso de pago</span><span>${pesos(f.en_revision)}</span></div>` : '')}
    <div class="fila total"><span>Saldo</span><span>${pesos(f.saldo)}</span></div>
    <details class="plegable">
      <summary>Ver detalle (${f.items.length} ${f.items.length === 1 ? 'ítem' : 'ítems'}${f.pagos.length ? `, ${f.pagos.length} ${f.pagos.length === 1 ? 'pago' : 'pagos'}` : ''})</summary>
      <ul class="lista">${items}</ul>
      ${crudo(f.pagos.length ? html`<h3>Pagos</h3><ul class="lista">${pagos}</ul>` : '')}
    </details>
    ${crudo(esPadre && f.saldo > 0 ? html`<a class="btn" href="#/pagar?factura=${f.id}">💸 Pagar</a>` : '')}
    <a class="btn btn-sec" href="/app/api/factura_pdf.php?factura_id=${f.id}" target="_blank" rel="noopener">📄 Descargar PDF</a>
  </li>`;
}

function tarjetaConcepto(c) {
  // Un bloque por mes (una tabla de 4 columnas no entra bien en un celular).
  const meses = c.meses.map((m) => crudo(html`<li class="mes-deuda">
      <strong>${m.periodo}</strong>${crudo(m.detalle ? html` <span class="sub">· ${m.detalle}</span>` : '')}
      <div class="fila"><span>Facturado</span><span>${pesos(m.facturado)}</span></div>
      <div class="fila"><span>Pagado</span><span>${pesos(m.pagado)}</span></div>
      ${crudo(m.en_revision > 0 ? html`<div class="fila"><span>En proceso de pago</span><span>${pesos(m.en_revision)}</span></div>` : '')}
      <div class="fila total"><span>Debe</span><span>${pesos(m.adeudado)}</span></div>
    </li>`));
  return html`<li class="tarjeta">
    <h3>${c.concepto}</h3>
    <div class="fila"><span>Facturado</span><span>${pesos(c.facturado)}</span></div>
    <div class="fila"><span>Pagado</span><span>${pesos(c.pagado)}</span></div>
    ${crudo(c.en_revision > 0 ? html`<div class="fila"><span>En proceso de pago</span><span>${pesos(c.en_revision)}</span></div>` : '')}
    <div class="fila total"><span>Debe</span><span>${pesos(c.adeudado)}</span></div>
    <details class="plegable">
      <summary>Por mes</summary>
      <ul class="lista">${meses}</ul>
    </details>
  </li>`;
}

async function pintarListado(cont, ctx) {
  const alumno = ctx.alumno();
  const q = new URLSearchParams({ alumno_id: alumno.id, desde: filtro.desde, hasta: filtro.hasta });
  cont.innerHTML = '<p class="cargando">Cargando…</p>';
  if (filtro.pestana === 'facturas') {
    const r = await api('facturas.php?' + q);
    if (!r.success) { cont.innerHTML = ''; mensaje(cont, r.message); return; }
    const esPadre = ctx.estado.usuario.rol === 'padre';
    cont.innerHTML = r.data.length
      ? html`<ul class="lista-tarjetas">${r.data.map((f) => crudo(tarjetaFactura(f, esPadre)))}</ul>`
      : html`<p class="vacio tarjeta">No hay facturas en ese período.</p>`;
    return;
  }
  const r = await api('deuda.php?' + q);
  if (!r.success) { cont.innerHTML = ''; mensaje(cont, r.message); return; }
  const t = r.data.totales;
  cont.innerHTML = html`
    <section class="tarjeta" aria-label="Totales del período">
      <div class="fila"><span>Facturado</span><span>${pesos(t.facturado)}</span></div>
      <div class="fila"><span>Pagado</span><span>${pesos(t.pagado)}</span></div>
      ${crudo(t.en_revision > 0 ? html`<div class="fila"><span>En proceso de pago</span><span>${pesos(t.en_revision)}</span></div>` : '')}
      <div class="fila total"><span>Debe</span><span>${pesos(t.adeudado)}</span></div>
    </section>
    ${crudo(r.data.conceptos.length
      ? html`<ul class="lista-tarjetas">${r.data.conceptos.map((c) => crudo(tarjetaConcepto(c)))}</ul>`
      : html`<p class="vacio tarjeta">No hay deuda facturada en ese período.</p>`)}`;
}

export async function render(main, ctx) {
  const alumno = ctx.alumno();
  if (!alumno) {
    main.innerHTML = html`<p class="vacio">No hay alumnos vinculados.</p>`;
    return;
  }
  const pestana = (id, texto) => html`<button type="button" role="tab" id="tab-${id}" data-pestana="${id}"
    aria-selected="${filtro.pestana === id}" aria-controls="panel">${texto}</button>`;

  main.innerHTML = html`
    <h1>Facturas</h1>
    <p class="sub">${alumno.nombre}${alumno.curso ? ' · ' + alumno.curso : ''}</p>
    <div class="tabs" role="tablist" aria-label="Vista">
      ${crudo(pestana('facturas', 'Facturas'))}${crudo(pestana('deuda', 'Deuda por concepto'))}
    </div>
    <form class="filtro tarjeta" id="form-filtro">
      <div class="campo"><label for="desde">Desde</label><input type="month" id="desde" value="${filtro.desde}" required></div>
      <div class="campo"><label for="hasta">Hasta</label><input type="month" id="hasta" value="${filtro.hasta}" required></div>
      <button class="btn" type="submit">Ver</button>
    </form>
    <div id="panel" role="tabpanel" aria-labelledby="tab-${filtro.pestana}"></div>`;

  const panel = document.getElementById('panel');
  main.querySelectorAll('[data-pestana]').forEach((b) => b.addEventListener('click', () => {
    filtro.pestana = b.dataset.pestana;
    main.querySelectorAll('[data-pestana]').forEach((x) => x.setAttribute('aria-selected', String(x === b)));
    panel.setAttribute('aria-labelledby', b.id);
    pintarListado(panel, ctx).catch(() => mensaje(panel, 'No se pudo cargar.'));
  }));
  document.getElementById('form-filtro').addEventListener('submit', (e) => {
    e.preventDefault();
    const desde = document.getElementById('desde').value;
    const hasta = document.getElementById('hasta').value;
    if (desde && hasta && desde > hasta) {
      mensaje(panel, 'El mes "desde" no puede ser posterior a "hasta".');
      return;
    }
    filtro.desde = desde;
    filtro.hasta = hasta;
    pintarListado(panel, ctx).catch(() => mensaje(panel, 'No se pudo cargar.'));
  });
  await pintarListado(panel, ctx);
}
