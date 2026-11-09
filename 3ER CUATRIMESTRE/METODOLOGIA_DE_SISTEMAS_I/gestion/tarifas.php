<?php

/** UI admin: valores de cuota, matrícula, comedor, deportes y transporte (Parte 3). */

require_once __DIR__ . '/layout_admin.php';
layout_admin_inicio('Tarifas', 'Valores de cuota, matrícula, comedor, deportes y transporte');
?>
  <div class="card">
    <div class="card-header"><span>💲</span><h2>Valores vigentes</h2></div>
    <div class="card-body">
      <p class="ayuda">Solo la administración fija los valores. Cada deporte y cada recorrido tiene su propio valor.
        Un cambio se aplica a la próxima facturación: lo ya facturado conserva el importe con que se emitió.</p>
      <div id="msg" class="msg" role="status"></div>
      <table>
        <thead><tr><th>Concepto</th><th>Detalle</th><th class="num">Valor</th><th>Última modificación</th><th></th></tr></thead>
        <tbody id="tbody"><tr><td colspan="5" class="empty-msg">Cargando…</td></tr></tbody>
      </table>
    </div>
  </div>
<script src="js/admin.js"></script>
<script>
  const tbody = document.getElementById('tbody');
  const msg = document.getElementById('msg');

  async function cargar() {
    const r = await api('tarifas_listar.php');
    if (!r.success) { mostrarMensaje(msg, r); return; }
    let grupo = '';
    tbody.innerHTML = r.data.map((t, i) => {
      const cab = t.grupo !== grupo ? `<tr class="grupo"><td colspan="5">${esc(t.grupo)}</td></tr>` : '';
      grupo = t.grupo;
      const falta = t.valor === null ? ' <span class="badge badge-rojo">Sin valor</span>' : '';
      return `${cab}<tr>
        <td>${esc(t.descripcion)}${falta}</td>
        <td>${esc(t.detalle)}</td>
        <td class="num"><input id="v${i}" type="number" min="1" step="0.01" value="${t.valor ?? ''}"
          aria-label="Valor de ${esc(t.descripcion)}" style="width:130px"></td>
        <td>${t.actualizado_en ? esc(t.actualizado_en) : '—'}</td>
        <td><button class="btn-sm" data-clave="${esc(t.clave)}" data-input="v${i}">Guardar</button></td>
      </tr>`;
    }).join('');
  }

  tbody.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('button[data-clave]');
    if (!btn) return;
    btn.disabled = true;
    const r = await api('tarifas_guardar.php', { clave: btn.dataset.clave, valor: document.getElementById(btn.dataset.input).value });
    mostrarMensaje(msg, r);
    btn.disabled = false;
    if (r.success) cargar();
  });

  cargar();
</script>
<?php
layout_admin_fin();
