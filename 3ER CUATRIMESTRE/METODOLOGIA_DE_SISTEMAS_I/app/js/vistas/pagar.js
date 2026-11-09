// Pagar (RFGm05): el padre marca los ítems pendientes de una factura, ve el total
// (con el recargo de una cuota vencida, RN11) y el sistema emite el comprobante de pago.
import { api } from '../api.js';
import { html, crudo, pesos, mensaje } from '../util.js';

export async function render(main, ctx) {
  const alumno = ctx.alumno();
  const facturaId = Number(ctx.params().get('factura'));
  if (ctx.estado.usuario.rol !== 'padre' || !alumno || !facturaId) {
    ctx.ir('facturas');
    return;
  }
  const r = await api('facturas.php?alumno_id=' + encodeURIComponent(alumno.id));
  if (!r.success) {
    main.innerHTML = '';
    mensaje(main, r.message);
    return;
  }
  const factura = r.data.find((f) => f.id === facturaId);
  if (!factura) {
    main.innerHTML = html`<p class="vacio tarjeta">La factura no es de ${alumno.nombre}. <a href="#/facturas">Volver a Facturas</a></p>`;
    return;
  }

  const filas = factura.items.map((it) => {
    const libre = it.estado === 'pendiente';
    const recargo = it.recargo_previsto > 0
      ? html`<br><span class="badge badge-rojo">Vencida: + ${pesos(it.recargo_previsto)} de recargo</span>` : '';
    return crudo(html`<li>
      <label class="check ${libre ? '' : 'check-deshabilitado'}">
        <input type="checkbox" name="item" value="${it.id}" data-importe="${it.importe + it.recargo_previsto}"
          ${crudo(libre ? '' : 'disabled')}>
        <span class="check-texto">${it.descripcion}${crudo(recargo)}
          ${crudo(libre ? '' : html`<br><span class="badge ${it.estado === 'pagado' ? 'badge-verde' : 'badge-azul'}">${it.estado_texto}</span>`)}</span>
        <span class="check-monto">${pesos(it.importe)}</span>
      </label>
    </li>`);
  });

  main.innerHTML = html`
    <a href="#/facturas" class="volver">← Facturas</a>
    <h1>Pagar ${factura.periodo}</h1>
    <p class="sub">${alumno.nombre} · Factura ${factura.numero} · vence el ${factura.vencimiento_ar}</p>
    <div id="msg"></div>
    <form id="form-pagar" class="tarjeta" novalidate>
      <fieldset class="sin-borde">
        <legend>Elegí qué querés pagar</legend>
        <ul class="lista">${filas}</ul>
      </fieldset>
      <div class="fila total"><span>Total a transferir</span><span id="total">${pesos(0)}</span></div>
      <p class="sub">Solo se paga por transferencia. Al confirmar te damos el número de pago y los datos de la cuenta.</p>
      <button class="btn" type="submit" id="btn-pagar" disabled>Generar comprobante de pago</button>
    </form>`;

  const form = document.getElementById('form-pagar');
  const btn = document.getElementById('btn-pagar');
  const elegidos = () => [...form.querySelectorAll('input[name="item"]:checked')];
  form.addEventListener('change', () => {
    const total = elegidos().reduce((s, c) => s + Number(c.dataset.importe), 0);
    document.getElementById('total').textContent = pesos(total);
    btn.disabled = total <= 0;
  });
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const datos = new URLSearchParams({ factura_id: String(factura.id) });
    elegidos().forEach((c) => datos.append('items[]', c.value));
    btn.disabled = true;
    btn.textContent = 'Generando…';
    const res = await api('pago_crear.php', datos);
    if (!res.success) {
      mensaje(document.getElementById('msg'), res.message);
      btn.disabled = false;
      btn.textContent = 'Generar comprobante de pago';
      return;
    }
    ctx.ir('pago?id=' + res.data.id);
  });
}
