async function renderSeguros() {
  const area = document.getElementById('content-area');
  try {
    const [seguros, proxVencer] = await Promise.all([
      apiFetch('/seguros'),
      apiFetch('/seguros?vencimientoProximo=true')
    ]);

    const alertHtml = proxVencer.length
      ? `<div class="alert alert-warning">⚠️ <strong>${proxVencer.length}</strong> seguro(s) vencen en los próximos 15 días.</div>`
      : '';

    area.innerHTML = `
      <div class="section-header">
        <h2>Seguros (${seguros.length})</h2>
        <button class="btn btn-primary" id="btn-nuevo-seguro">+ Nuevo seguro</button>
      </div>
      ${alertHtml}
      <div class="toolbar">
        <select id="filtro-seg-estado">
          <option value="">Todos</option>
          <option value="vigente">Vigentes</option>
          <option value="vencido">Vencidos</option>
          <option value="pendiente">Pendientes</option>
        </select>
      </div>
      <div class="card">
        <div class="table-wrap" id="seg-table">
          ${renderTablasSeguros(seguros)}
        </div>
      </div>`;

    document.getElementById('btn-nuevo-seguro').addEventListener('click', () => abrirModalSeguro());
    document.getElementById('filtro-seg-estado').addEventListener('change', async (e) => {
      const val = e.target.value;
      const params = val ? `?estado=${val}` : '';
      // Filter client-side for simplicity
      const filtrados = val ? seguros.filter(s => s.estado === val) : seguros;
      document.getElementById('seg-table').innerHTML = renderTablasSeguros(filtrados);
      attachSeguroEvents();
    });
    attachSeguroEvents();
  } catch (err) {
    area.innerHTML = `<div class="alert alert-warning">⚠️ ${err.message}</div>`;
  }
}

function renderTablasSeguros(seguros) {
  return renderTable(
    ['Jugador','Categoría','N° Póliza','Vigente desde','Vigente hasta','Estado','Acciones'],
    seguros,
    s => `<tr>
      <td><strong>${s.jugadorNombre}</strong></td>
      <td>${s.categoriaNombre}</td>
      <td>${s.numeroPoliza}</td>
      <td>${formatFecha(s.vigenteDesde)}</td>
      <td>${formatFecha(s.vigenteHasta)}</td>
      <td>${badge(s.estado)}</td>
      <td><button class="btn btn-sm btn-secondary btn-edit-seg"
        data-id="${s.id}" data-jugador="${s.jugadorId}"
        data-poliza="${s.numeroPoliza}"
        data-desde="${s.vigenteDesde?.substring(0,10)||''}"
        data-hasta="${s.vigenteHasta?.substring(0,10)||''}">✏️ Editar</button></td>
    </tr>`
  );
}

function attachSeguroEvents() {
  document.querySelectorAll('.btn-edit-seg').forEach(btn =>
    btn.addEventListener('click', () => abrirModalSeguro({
      id: btn.dataset.id, jugadorId: btn.dataset.jugador,
      numeroPoliza: btn.dataset.poliza,
      vigenteDesde: btn.dataset.desde, vigenteHasta: btn.dataset.hasta
    })));
}

async function abrirModalSeguro(seg = null) {
  let jugadores = [];
  try { const all = await apiFetch('/jugadores'); jugadores = all; } catch {}
  const jugOpts = jugadores.map(j => `<option value="${j.id}" ${seg?.jugadorId == j.id ? 'selected' : ''}>${j.apellido}, ${j.nombre}</option>`).join('');

  openModal(seg ? 'Editar seguro' : 'Nuevo seguro', `
    <form id="form-seg">
      <div class="form-grid">
        <div class="field form-group-full">
          <label>Jugador *</label>
          <select name="jugadorId" required ${seg ? 'disabled' : ''}><option value="">Seleccionar...</option>${jugOpts}</select>
        </div>
        <div class="field form-group-full"><label>N° Póliza *</label><input name="numeroPoliza" required value="${seg?.numeroPoliza||''}"></div>
        <div class="field"><label>Vigente desde *</label><input type="date" name="vigenteDesde" required value="${seg?.vigenteDesde||''}"></div>
        <div class="field"><label>Vigente hasta *</label><input type="date" name="vigenteHasta" required value="${seg?.vigenteHasta||''}"></div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">${seg ? 'Guardar' : 'Crear'}</button>
      </div>
    </form>`);

  document.getElementById('form-seg').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const body = {
      jugadorId: seg ? +seg.jugadorId : +fd.get('jugadorId'),
      numeroPoliza: fd.get('numeroPoliza'),
      vigenteDesde: fd.get('vigenteDesde'),
      vigenteHasta: fd.get('vigenteHasta')
    };
    try {
      if (seg) await apiFetch(`/seguros/${seg.id}`, { method: 'PUT', body: JSON.stringify(body) });
      else await apiFetch('/seguros', { method: 'POST', body: JSON.stringify(body) });
      showToast(seg ? 'Seguro actualizado' : 'Seguro creado');
      closeModal();
      renderSeguros();
    } catch (err) { showToast(err.message, 'error'); }
  });
}
