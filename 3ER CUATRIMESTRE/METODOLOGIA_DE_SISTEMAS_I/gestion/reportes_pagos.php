<?php

/** UI admin: reportes de pagos con filtro de período y exportación a CSV (RFGm12). */

require_once __DIR__ . '/layout_admin.php';
require_once __DIR__ . '/../lib/reportes.php';
layout_admin_inicio('Reportes de pagos', 'Ingresos, pagos completos e incompletos, por deporte y por recorrido');
$desde = date('Y') . '-01';
$hasta = date('Y-m');
?>
  <div class="card">
    <div class="card-header"><span>📊</span><h2>Reporte</h2></div>
    <div class="card-body">
      <p class="ayuda">Los ingresos se cuentan por la fecha en que se aprobó el pago; el resto, por el mes facturado.</p>
      <div class="form-row">
        <label for="reporte">Reporte
          <select id="reporte">
            <?php foreach (REPORTES_PAGOS as $clave => $titulo) : ?>
              <option value="<?= $clave ?>"><?= htmlspecialchars($titulo) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label for="desde">Desde<input type="month" id="desde" value="<?= $desde ?>"></label>
        <label for="hasta">Hasta<input type="month" id="hasta" value="<?= $hasta ?>"></label>
        <button class="btn" id="btn-ver">Ver</button>
        <a class="btn-sm" id="btn-csv" href="#">⬇️ Exportar CSV</a>
      </div>
      <div id="msg" class="msg" role="status"></div>
      <p id="titulo" class="ayuda"><strong>Elegí un reporte y un período.</strong></p>
      <table>
        <thead id="thead"><tr><th scope="col">Reporte</th></tr></thead>
        <tbody id="tbody"></tbody>
      </table>
    </div>
  </div>
<script src="js/admin.js"></script>
<script>
  const campos = ['reporte', 'desde', 'hasta'].map((id) => document.getElementById(id));
  const params = () => new URLSearchParams(Object.fromEntries(campos.map((c) => [c.id, c.value])));

  function celda(valor, columna) {
    const esImporte = columna.startsWith('$');
    const texto = esImporte && valor !== '' ? pesos(valor) : valor;
    return `<td class="${typeof valor === 'number' ? 'num' : ''}">${esc(texto)}</td>`;
  }

  async function cargar() {
    document.getElementById('btn-csv').href = 'reportes_pagos_datos.php?formato=csv&' + params();
    const r = await api('reportes_pagos_datos.php?' + params());
    const msg = document.getElementById('msg');
    if (!r.success) {
      mostrarMensaje(msg, r);
      document.getElementById('thead').innerHTML = '';
      document.getElementById('tbody').innerHTML = '';
      return;
    }
    msg.textContent = '';
    const { titulo, columnas, filas, totales } = r.data;
    document.getElementById('titulo').textContent = titulo;
    document.getElementById('thead').innerHTML = '<tr>' + columnas.map((c) =>
      `<th class="${c.startsWith('$') ? 'num' : ''}">${esc(c.replace('$ ', ''))}</th>`).join('') + '</tr>';
    const cuerpo = filas.map((f) => '<tr>' + f.map((v, i) => celda(v, columnas[i])).join('') + '</tr>');
    if (totales) cuerpo.push('<tr class="grupo">' + totales.map((v, i) => celda(v, columnas[i])).join('') + '</tr>');
    document.getElementById('tbody').innerHTML = cuerpo.join('')
      || `<tr><td colspan="${columnas.length}" class="empty-msg">No hay datos en ese período.</td></tr>`;
  }

  document.getElementById('btn-ver').addEventListener('click', cargar);
  campos.forEach((c) => c.addEventListener('change', cargar));
  cargar();
</script>
<?php
layout_admin_fin();
