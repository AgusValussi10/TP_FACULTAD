// Cuotas (RFGm03): pendientes (vencidas en rojo, por vencer en amarillo) y pagadas, con
// vencimiento, importe, recargo y estado. Mismos datos que la web (cuotas_de_alumno, RFG19).
import { api } from '../api.js';
import { html, crudo, pesos, mensaje } from '../util.js';

const BADGE = { impaga: 'badge-rojo', proxima_a_vencer: 'badge-amarillo', pendiente: 'badge-gris', pagada: 'badge-verde' };

function filaCuota(c, conRecibo) {
  const recargo = c.recargo > 0 ? html`<div class="fila"><span>Recargo por mora</span><span>${pesos(c.recargo)}</span></div>` : '';
  const pago = c.estado_visual === 'pagada'
    ? html`<p class="sub">Pagada el ${c.fecha_pago_ar || '—'}${c.metodo_pago ? ' · ' + c.metodo_pago : ''}</p>
      ${crudo(conRecibo ? html`<a class="btn btn-sec" href="/gestion/recibo_pdf.php?cuota_id=${c.id}" target="_blank" rel="noopener">📄 Recibo</a>` : '')}`
    : '';
  return html`<li class="cuota cuota-${c.estado_visual}">
    <div class="cuota-cabecera">
      <strong>${c.concepto} ${c.periodo}</strong>
      <span class="badge ${BADGE[c.estado_visual]}">${c.estado_texto}</span>
    </div>
    <div class="fila"><span>Vencimiento</span><span>${c.vencimiento_ar}</span></div>
    <div class="fila"><span>Importe</span><span>${pesos(c.importe)}</span></div>
    ${crudo(recargo)}
    <div class="fila total"><span>Total</span><span>${pesos(c.total)}</span></div>
    ${crudo(pago)}
  </li>`;
}

export async function render(main, ctx) {
  const alumno = ctx.alumno();
  if (!alumno) {
    main.innerHTML = html`<p class="vacio">No hay alumnos vinculados.</p>`;
    return;
  }
  const r = await api('cuotas.php?alumno_id=' + encodeURIComponent(alumno.id));
  if (!r.success) {
    main.innerHTML = '';
    mensaje(main, r.message);
    return;
  }
  const { cuotas, resumen } = r.data;
  // Las impagas primero, después las próximas a vencer y las pendientes (por vencimiento).
  const orden = { impaga: 0, proxima_a_vencer: 1, pendiente: 2 };
  const pendientes = cuotas.filter((c) => c.estado_visual !== 'pagada')
    .sort((a, b) => orden[a.estado_visual] - orden[b.estado_visual] || a.vencimiento.localeCompare(b.vencimiento));
  const pagadas = cuotas.filter((c) => c.estado_visual === 'pagada');
  const conRecibo = ctx.estado.usuario.rol === 'padre'; // el recibo lo baja el padre (RFG19)

  main.innerHTML = html`
    <h1>Cuotas</h1>
    <p class="sub">${alumno.nombre}${alumno.curso ? ' · ' + alumno.curso : ''}</p>

    <section class="tarjeta" aria-labelledby="t-res">
      <h2 id="t-res"><span aria-hidden="true">💳</span> Resumen</h2>
      <p class="cifra">${pesos(resumen.total_adeudado)}</p>
      <p class="sub">Total adeudado en cuotas${resumen.impagas ? ' (incluye el recargo de las vencidas)' : ''}</p>
      ${crudo(resumen.impagas ? html`<p><span class="badge badge-rojo">${resumen.impagas} vencida${resumen.impagas > 1 ? 's' : ''}: ${pesos(resumen.total_vencido)}</span></p>` : '')}
      ${crudo(resumen.proximas_a_vencer ? html`<p><span class="badge badge-amarillo">${resumen.proximas_a_vencer} por vencer</span></p>` : '')}
      ${crudo(resumen.proximo_vencimiento_ar ? html`<p class="sub">Próximo vencimiento: <strong>${resumen.proximo_vencimiento_ar}</strong></p>` : '')}
      <p class="sub">Deportes, comedor y transporte se ven en <a href="#/facturas">Facturas</a>, donde también se paga.</p>
    </section>

    <section aria-labelledby="t-pend">
      <h2 id="t-pend">Pendientes</h2>
      ${crudo(pendientes.length
        ? html`<ul class="lista-tarjetas">${pendientes.map((c) => crudo(filaCuota(c, false)))}</ul>`
        : html`<p class="vacio tarjeta">No hay cuotas pendientes. 🎉</p>`)}
    </section>

    <details class="tarjeta plegable">
      <summary>Pagadas (${pagadas.length})</summary>
      ${crudo(pagadas.length
        ? html`<ul class="lista-tarjetas">${pagadas.map((c) => crudo(filaCuota(c, conRecibo)))}</ul>`
        : html`<p class="vacio">Todavía no hay cuotas pagadas.</p>`)}
    </details>`;
}
