<?php

/** UI admin: revisar los pagos por transferencia con sus comprobantes; aprobar o rechazar con motivo (Parte 3). */

require_once __DIR__ . '/layout_admin.php';
require_once __DIR__ . '/../lib/facturacion.php';
layout_admin_inicio('Pagos a revisar', 'Comprobantes de transferencia cargados por las familias desde la app');
?>
  <div class="card">
    <div class="card-header"><span>🔎</span><h2>Pagos</h2></div>
    <div class="card-body">
      <p class="ayuda">Al aprobar, los ítems quedan pagados, la cuota se marca pagada (la ven igual la web y la app)
        y la familia recibe un aviso. Al rechazar, los ítems vuelven a quedar pendientes y se informa el motivo.</p>
      <div class="form-row">
        <label for="f-estado">Estado
          <select id="f-estado">
            <option value="">Todos</option>
            <?php foreach (ESTADOS_PAGO as $clave => $texto) : ?>
              <option value="<?= $clave ?>" <?= $clave === 'en_revision' ? 'selected' : '' ?>><?= htmlspecialchars($texto) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <div id="msg" class="msg" role="status"></div>
      <table>
        <thead><tr><th>Pago</th><th>Alumno / familia</th><th>Ítems</th><th class="num">Total</th><th>Comprobantes</th><th>Estado</th><th>Acción</th></tr></thead>
        <tbody id="tbody"><tr><td colspan="7" class="empty-msg">Cargando…</td></tr></tbody>
      </table>
    </div>
  </div>
<script src="js/admin.js"></script>
<script>
  const tbody = document.getElementById('tbody');
  const msg = document.getElementById('msg');

  function comprobantes(p) {
    if (!p.comprobantes.length) return '<em>Sin comprobante</em>';
    const lista = p.comprobantes.map(c => `<a href="comprobante_ver.php?id=${c.id}" target="_blank" rel="noopener">
      ${esc(c.nombre)}</a> · ${pesos(c.monto)} · ${esc(c.fecha)}`).join('<br>');
    const dif = Math.round((p.declarado - p.total) * 100) / 100;
    const aviso = dif === 0 ? '' : `<br>${badge(dif < 0 ? 'rechazado' : 'parcial', (dif < 0 ? 'Falta ' : 'Sobra ') + pesos(Math.abs(dif)))}`;
    return lista + aviso;
  }

  function acciones(p) {
    if (p.estado !== 'en_revision') {
      return esc(p.revisado || '') + (p.motivo_rechazo ? `<br><small>Motivo: ${esc(p.motivo_rechazo)}</small>` : '');
    }
    return `<div class="acciones">
      <button class="btn-sm btn-ok" data-accion="aprobar" data-id="${p.id}">Aprobar</button>
      <input id="motivo-${p.id}" type="text" maxlength="255" placeholder="Motivo del rechazo" aria-label="Motivo del rechazo de ${esc(p.numero)}">
      <button class="btn-sm btn-no" data-accion="rechazar" data-id="${p.id}">Rechazar</button></div>`;
  }

  async function cargar() {
    const r = await api('pagos_listar.php?estado=' + encodeURIComponent(document.getElementById('f-estado').value));
    if (!r.success || !r.data.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="empty-msg">${esc(r.success ? 'No hay pagos con ese estado.' : r.message)}</td></tr>`;
      return;
    }
    tbody.innerHTML = r.data.map(p => `<tr>
        <td><strong>${esc(p.numero)}</strong><br><small>${esc(p.factura)} · ${esc(p.periodo)}<br>${esc(p.creado)}</small></td>
        <td>${esc(p.alumno)}<br><small>${esc(p.padre)}</small></td>
        <td><small>${p.items.map(it => `${esc(it.descripcion)}: ${pesos(it.importe)}`).join('<br>')}</small></td>
        <td class="num">${pesos(p.total)}</td>
        <td><small>${comprobantes(p)}</small></td>
        <td>${badge(p.estado, p.estado_texto)}</td>
        <td>${acciones(p)}</td>
      </tr>`).join('');
  }

  tbody.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('button[data-accion]');
    if (!btn) return;
    const datos = { pago_id: btn.dataset.id, accion: btn.dataset.accion };
    if (datos.accion === 'rechazar') {
      datos.motivo = document.getElementById('motivo-' + btn.dataset.id).value.trim();
      if (datos.motivo.length < 5) { mostrarMensaje(msg, { success: false, message: 'Escribí el motivo del rechazo.' }); return; }
    } else if (!confirm('¿Aprobar el pago? Los ítems quedan pagados.')) {
      return;
    }
    btn.disabled = true;
    const r = await api('pago_revisar.php', datos);
    mostrarMensaje(msg, r);
    cargar();
  });

  document.getElementById('f-estado').addEventListener('change', cargar);
  cargar();
</script>
<?php
layout_admin_fin();
