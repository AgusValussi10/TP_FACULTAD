<?php

/** UI admin: generar la facturación de un mes y consultar facturas con filtros (Parte 3). */

require_once __DIR__ . '/layout_admin.php';
layout_admin_inicio('Facturación', 'Facturas mensuales por alumno: cuota, deportes, comedor, transporte y recargos');
$mes_sig = (int) date('n', strtotime('first day of next month'));
$anio_sig = (int) date('Y', strtotime('first day of next month'));
?>
  <div class="card">
    <div class="card-header"><span>🧾</span><h2>Generar facturación</h2></div>
    <div class="card-body">
      <p class="ayuda">Toma la cuota del mes según el nivel, los deportes inscriptos antes del día 1, el comedor y el
        transporte reservados para ese mes, y el recargo de cuotas anteriores vencidas. Se puede ejecutar más de una
        vez: las facturas que ya existen no se modifican.</p>
      <div class="form-row">
        <label for="gen-mes">Mes<select id="gen-mes"></select></label>
        <label for="gen-anio">Año<input id="gen-anio" type="number" min="2020" max="2100" value="<?= $anio_sig ?>"></label>
        <button class="btn" id="btn-generar">Generar facturación</button>
      </div>
      <div id="msg-gen" class="msg" role="status"></div>
      <ul id="avisos" class="ayuda"></ul>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span>📋</span><h2>Facturas</h2></div>
    <div class="card-body">
      <div class="form-row">
        <label for="f-mes">Mes<select id="f-mes"><option value="0">Todos</option></select></label>
        <label for="f-anio">Año<input id="f-anio" type="number" min="2020" max="2100" value="<?= (int) date('Y') ?>"></label>
        <label for="f-estado">Estado
          <select id="f-estado">
            <option value="">Todos</option><option value="pendiente">Pendiente</option>
            <option value="parcial">Pago parcial</option><option value="pagada">Pagada</option>
            <option value="vencida">Vencida (impaga)</option>
          </select>
        </label>
        <label for="f-q">Alumno<input id="f-q" type="search" placeholder="Nombre…"></label>
        <button class="btn-sm" id="btn-filtrar">Filtrar</button>
      </div>
      <div class="resumen" id="resumen"></div>
      <table>
        <thead><tr><th>N°</th><th>Alumno</th><th>Período</th><th>Vence</th><th class="num">Total</th><th class="num">Pagado</th><th>Estado</th><th></th></tr></thead>
        <tbody id="tbody"><tr><td colspan="8" class="empty-msg">Cargando…</td></tr></tbody>
      </table>
    </div>
  </div>
<script src="js/admin.js"></script>
<script>
  const MESES = <?= json_encode(MESES, JSON_UNESCAPED_UNICODE) ?>;
  for (const id of ['gen-mes', 'f-mes']) {
    const sel = document.getElementById(id);
    MESES.forEach((m, i) => sel.add(new Option(m, i + 1)));
  }
  document.getElementById('gen-mes').value = <?= $mes_sig ?>;

  const tbody = document.getElementById('tbody');
  let facturas = [];

  async function cargar() {
    const params = new URLSearchParams({
      mes: document.getElementById('f-mes').value, anio: document.getElementById('f-anio').value,
      estado: document.getElementById('f-estado').value, q: document.getElementById('f-q').value,
    });
    const r = await api('facturas_listar.php?' + params);
    facturas = r.success ? r.data : [];
    const total = facturas.reduce((s, f) => s + f.total, 0);
    const pagado = facturas.reduce((s, f) => s + f.pagado, 0);
    document.getElementById('resumen').innerHTML = `<div>Facturas<strong>${facturas.length}</strong></div>
      <div>Facturado<strong>${pesos(total)}</strong></div><div>Cobrado<strong>${pesos(pagado)}</strong></div>
      <div>Saldo<strong>${pesos(total - pagado)}</strong></div>`;
    if (!facturas.length) {
      tbody.innerHTML = `<tr><td colspan="8" class="empty-msg">${esc(r.success ? 'No hay facturas con esos filtros.' : r.message)}</td></tr>`;
      return;
    }
    tbody.innerHTML = facturas.map((f, i) => `<tr>
        <td>${esc(f.numero)}</td><td>${esc(f.alumno)}<br><small>${esc(f.curso || '')}</small></td>
        <td>${esc(f.periodo)}</td><td>${esc(f.vencimiento)}</td>
        <td class="num">${pesos(f.total)}</td><td class="num">${pesos(f.pagado)}</td>
        <td>${badge(f.estado, f.estado_texto)} ${f.vencida ? badge('vencida', 'Vencida') : ''}</td>
        <td><button class="btn-sm" data-i="${i}" aria-expanded="false">Ítems</button></td>
      </tr>`).join('');
  }

  tbody.addEventListener('click', (ev) => {
    const btn = ev.target.closest('button[data-i]');
    if (!btn) return;
    const fila = btn.closest('tr');
    if (fila.nextElementSibling?.classList.contains('detalle')) {
      fila.nextElementSibling.remove();
      btn.setAttribute('aria-expanded', 'false');
      return;
    }
    const f = facturas[btn.dataset.i];
    const filas = f.items.map(it => `<tr><td>${esc(it.tipo)}</td><td>${esc(it.descripcion)}</td>
      <td class="num">${pesos(it.importe)}</td><td>${badge(it.estado, it.estado.replace('_', ' '))}</td></tr>`).join('');
    fila.insertAdjacentHTML('afterend', `<tr class="detalle"><td colspan="8"><table>${filas}</table></td></tr>`);
    btn.setAttribute('aria-expanded', 'true');
  });

  document.getElementById('btn-filtrar').addEventListener('click', cargar);

  document.getElementById('btn-generar').addEventListener('click', async (ev) => {
    const mes = document.getElementById('gen-mes').value;
    const anio = document.getElementById('gen-anio').value;
    if (!confirm(`¿Generar la facturación de ${MESES[mes - 1]} ${anio}?`)) return;
    ev.target.disabled = true;
    const r = await api('facturacion_generar.php', { mes, anio });
    ev.target.disabled = false;
    mostrarMensaje(document.getElementById('msg-gen'), r);
    document.getElementById('avisos').innerHTML = (r.data?.avisos || []).map(a => `<li>⚠️ ${esc(a)}</li>`).join('');
    if (r.success) {
      document.getElementById('f-mes').value = mes;
      document.getElementById('f-anio').value = anio;
      cargar();
    }
  });

  cargar();
</script>
<?php
layout_admin_fin();
