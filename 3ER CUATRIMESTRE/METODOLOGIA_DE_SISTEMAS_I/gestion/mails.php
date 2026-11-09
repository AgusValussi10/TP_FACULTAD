<?php

/** UI admin: mails a las familias (modo simulación) con vista previa y "Ejecutar ahora (prueba)". RFGm07/RFGm08. */

require_once __DIR__ . '/layout_admin.php';
require_once __DIR__ . '/../lib/recordatorios.php';
layout_admin_inicio('Mails enviados', 'Fin de mes (total a abonar con factura adjunta) y día 20 (aviso de deuda)');
$hoy = new DateTime('today');
$sugerida = ultimo_dia_habil((int) $hoy->format('n'), (int) $hoy->format('Y'));
?>
  <div class="card">
    <div class="card-header"><span>▶️</span><h2>Ejecutar ahora (prueba)</h2></div>
    <div class="card-body">
      <p class="ayuda"><strong>Modo simulación:</strong> no hay SMTP configurado, así que los mails no salen; se arman igual y
        quedan guardados acá. Todos los días a las 9 un proceso automático (GitHub Actions) revisa si corresponde mandarlos.
        Con esta prueba se corre como si hoy fuera la fecha elegida. Un mismo tipo y mes no se genera dos veces para la misma familia.</p>
      <div class="form-row">
        <label for="tipo">Tipo
          <select id="tipo">
            <?php foreach (TIPOS_MAIL as $clave => $texto) : ?>
              <option value="<?= $clave ?>"><?= htmlspecialchars($texto) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label for="fecha">Fecha simulada<input type="date" id="fecha" value="<?= $sugerida ?>"></label>
        <label for="forzar" style="flex-direction:row;align-items:center"><input type="checkbox" id="forzar"> Generar igual si la fecha no corresponde</label>
        <button class="btn" id="btn-ejecutar">Ejecutar ahora (prueba)</button>
      </div>
      <div id="msg" class="msg" role="status"></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span>✉️</span><h2>Mails generados</h2></div>
    <div class="card-body">
      <table>
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Familia</th><th>Asunto</th><th class="num">Total</th><th>Adjuntos</th><th>Estado</th><th></th></tr></thead>
        <tbody id="tbody"><tr><td colspan="8" class="empty-msg">Cargando…</td></tr></tbody>
      </table>
      <h3 id="titulo-vista" class="ayuda" style="margin-top:1rem"></h3>
      <iframe id="vista" title="Vista previa del mail" sandbox="" style="width:100%;height:520px;border:1px solid #E5E7EB;border-radius:10px;display:none"></iframe>
    </div>
  </div>
<script src="js/admin.js"></script>
<script>
  const tbody = document.getElementById('tbody');
  let mails = [];

  async function cargar() {
    const r = await api('mails_listar.php');
    mails = r.success ? r.data : [];
    if (!mails.length) {
      tbody.innerHTML = `<tr><td colspan="8" class="empty-msg">${esc(r.success ? 'Todavía no se generó ningún mail.' : r.message)}</td></tr>`;
      return;
    }
    tbody.innerHTML = mails.map((m, i) => `<tr>
        <td>${esc(m.fecha)}</td><td>${esc(m.tipo)}</td>
        <td>${esc(m.padre)}<br><small>${esc(m.destinatario)}</small></td>
        <td>${esc(m.asunto)}</td><td class="num">${pesos(m.total)}</td>
        <td>${m.adjuntos.map((a) => `<a href="factura_pdf.php?factura_id=${a.id}" target="_blank" rel="noopener">${esc(a.numero)}.pdf</a>`).join('<br>') || '—'}</td>
        <td>${badge(m.estado === 'simulado' ? 'en_revision' : m.estado, m.estado === 'simulado' ? 'Simulado' : m.estado)}</td>
        <td><button class="btn-sm" data-i="${i}">Ver</button></td></tr>`).join('');
  }

  tbody.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('button[data-i]');
    if (!btn) return;
    const m = mails[btn.dataset.i];
    document.getElementById('titulo-vista').textContent = `Vista previa: ${m.asunto} (${m.padre})`;
    const vista = document.getElementById('vista');
    // El HTML se trae con la sesión del admin y se muestra en un iframe con sandbox (sin scripts).
    const res = await fetch('mail_ver.php?id=' + m.id).catch(() => null);
    vista.srcdoc = res && res.ok ? await res.text() : '<p>No se pudo cargar la vista previa.</p>';
    vista.style.display = 'block';
    vista.scrollIntoView({ behavior: 'smooth' });
  });

  document.getElementById('btn-ejecutar').addEventListener('click', async (ev) => {
    ev.target.disabled = true;
    const r = await api('mails_ejecutar.php', {
      tipo: document.getElementById('tipo').value,
      fecha: document.getElementById('fecha').value,
      forzar: document.getElementById('forzar').checked ? '1' : '0',
    });
    ev.target.disabled = false;
    mostrarMensaje(document.getElementById('msg'), r);
    cargar();
  });

  cargar();
</script>
<?php
layout_admin_fin();
