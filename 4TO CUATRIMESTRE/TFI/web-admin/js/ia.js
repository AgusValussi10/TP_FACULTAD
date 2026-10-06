async function renderIA() {
  const area = document.getElementById('content-area');

  let jugadores = [], stats = {};
  try {
    [jugadores, stats] = await Promise.all([
      apiFetch('/jugadores'),
      apiFetch('/reportes/dashboard').catch(() => ({}))
    ]);
  } catch {}

  const jugOpts = jugadores.map(j =>
    `<option value="${j.id}">${j.apellido}, ${j.nombre} (${j.categoriaNombre})</option>`
  ).join('');

  area.innerHTML = `
    <div class="section-header"><h2>Recordatorio IA</h2></div>
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">

      <!-- Panel generador -->
      <div class="card">
        <div class="card-title">Generador de recordatorios</div>
        <div class="field" style="margin-bottom:16px">
          <label>Tipo de recordatorio</label>
          <select id="ia-tipo">
            <option value="morosidad">💰 Morosidad — cuotas pendientes</option>
            <option value="seguro">🛡️ Seguro vencido o por vencer</option>
            <option value="carnet">🎫 Carnet de liga pendiente/vencido</option>
          </select>
        </div>
        <div class="field" style="margin-bottom:16px">
          <label>Jugadores a incluir</label>
          <select id="ia-jugadores" multiple size="8" style="padding:8px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:13px">
            ${jugOpts}
          </select>
          <span style="font-size:11px;color:var(--text-muted)">Ctrl+click para seleccionar varios</span>
        </div>
        <div style="display:flex;gap:8px;margin-bottom:16px">
          <button class="btn btn-primary" id="btn-generar-msg">🤖 Generar mensaje</button>
          <button class="btn btn-secondary" id="btn-sel-todos">Seleccionar todos</button>
        </div>
        <div id="ia-loading" style="display:none" class="loading">
          <div class="spinner"></div> Generando con IA...
        </div>
        <div id="ia-resultado" style="display:none">
          <div style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px">Mensaje generado</div>
          <textarea id="ia-mensaje-area" style="width:100%;min-height:180px;padding:12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:14px;line-height:1.6;resize:vertical"></textarea>
          <div style="margin-top:8px;display:flex;gap:8px">
            <button class="btn btn-secondary btn-sm" id="btn-copiar">📋 Copiar al portapapeles</button>
            <button class="btn btn-secondary btn-sm" id="btn-whatsapp">📱 Enviar por WhatsApp</button>
          </div>
        </div>
      </div>

      <!-- Panel estado -->
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card">
          <div class="card-title">Estado del club</div>
          <div style="display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:var(--bg);border-radius:8px">
              <div>
                <div style="font-size:22px;font-weight:800;color:var(--warning)">${stats.cuotasPendientes || 0}</div>
                <div style="font-size:12px;color:var(--text-muted)">Cuotas pendientes</div>
              </div>
              <button class="btn btn-sm btn-secondary" onclick="navigateTo('cuotas')">Ver →</button>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:var(--bg);border-radius:8px">
              <div>
                <div style="font-size:22px;font-weight:800;color:var(--danger)">${stats.jugadoresSinSeguro || 0}</div>
                <div style="font-size:12px;color:var(--text-muted)">Sin seguro vigente</div>
              </div>
              <button class="btn btn-sm btn-secondary" onclick="navigateTo('seguros')">Ver →</button>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:var(--bg);border-radius:8px">
              <div>
                <div style="font-size:22px;font-weight:800;color:var(--text)">${jugadores.length}</div>
                <div style="font-size:12px;color:var(--text-muted)">Jugadores activos</div>
              </div>
              <button class="btn btn-sm btn-secondary" onclick="navigateTo('jugadores')">Ver →</button>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-title" style="font-size:13px">💡 Consejos de uso</div>
          <p style="font-size:13px;color:var(--text-muted);line-height:1.6">
            Seleccioná los jugadores que necesitan el recordatorio, elegí el tipo y hacé click en "Generar mensaje".
            La IA redactará un mensaje personalizado para enviar a las familias por WhatsApp.
          </p>
        </div>
      </div>
    </div>`;

  document.getElementById('btn-sel-todos').addEventListener('click', () => {
    const sel = document.getElementById('ia-jugadores');
    Array.from(sel.options).forEach(o => o.selected = true);
  });

  document.getElementById('btn-generar-msg').addEventListener('click', async () => {
    const tipo = document.getElementById('ia-tipo').value;
    const sel = document.getElementById('ia-jugadores');
    const ids = Array.from(sel.selectedOptions).map(o => +o.value);
    if (ids.length === 0) { showToast('Seleccioná al menos un jugador', 'error'); return; }

    const loading = document.getElementById('ia-loading');
    const resultado = document.getElementById('ia-resultado');
    loading.style.display = 'flex';
    resultado.style.display = 'none';

    try {
      const res = await apiFetch('/ia/recordatorio', {
        method: 'POST',
        body: JSON.stringify({ tipo, jugadorIds: ids })
      });
      document.getElementById('ia-mensaje-area').value = res.mensaje ||
        `Estimada familia,\n\nLe recordamos que tiene ${tipo === 'morosidad' ? 'cuotas pendientes de pago' : tipo === 'seguro' ? 'el seguro deportivo vencido o próximo a vencer' : 'el carnet de liga pendiente o vencido'}.\n\nPor favor, acérquese a la secretaría del club para regularizar su situación.\n\nMuchas gracias,\nFundación Unión del Sur`;
      loading.style.display = 'none';
      resultado.style.display = 'block';
      showToast('Mensaje generado');
    } catch {
      // Fallback with template message
      const selJugadores = Array.from(sel.selectedOptions).map(o => o.text.split(' (')[0]).join(', ');
      document.getElementById('ia-mensaje-area').value =
        `Estimada familia,\n\nLe recordamos que el jugador ${selJugadores} tiene ${tipo === 'morosidad' ? 'cuotas pendientes de pago' : tipo === 'seguro' ? 'el seguro deportivo vencido o próximo a vencer' : 'el carnet de liga pendiente o vencido'}.\n\nPor favor, acérquese a la secretaría del club para regularizar su situación a la brevedad.\n\nMuchas gracias,\nFundación Unión del Sur`;
      loading.style.display = 'none';
      resultado.style.display = 'block';
      showToast('Mensaje generado (sin IA — conectá el backend para mensajes personalizados)', 'info');
    }
  });

  document.getElementById('btn-copiar').addEventListener('click', async () => {
    const texto = document.getElementById('ia-mensaje-area').value;
    try {
      await navigator.clipboard.writeText(texto);
      showToast('Copiado al portapapeles');
    } catch { showToast('No se pudo copiar', 'error'); }
  });

  document.getElementById('btn-whatsapp').addEventListener('click', () => {
    const texto = encodeURIComponent(document.getElementById('ia-mensaje-area').value);
    window.open(`https://wa.me/?text=${texto}`, '_blank');
  });
}
