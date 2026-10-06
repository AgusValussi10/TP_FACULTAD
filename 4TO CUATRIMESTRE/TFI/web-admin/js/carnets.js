async function renderCarnets() {
  const area = document.getElementById('content-area');
  try {
    const carnets = await apiFetch('/carnets');
    const temporadas = [...new Set(carnets.map(c => c.temporada))].sort().reverse();
    const tempOpts = ['<option value="">Todas</option>', ...temporadas.map(t => `<option>${t}</option>`)].join('');

    area.innerHTML = `
      <div class="section-header">
        <h2>Carnets de Liga (${carnets.length})</h2>
        <button class="btn btn-primary" id="btn-nuevo-carnet">+ Nuevo carnet</button>
      </div>
      <div class="toolbar">
        <select id="filtro-temp"><option value="">Todas las temporadas</option>
          ${temporadas.map(t=>`<option>${t}</option>`).join('')}
        </select>
      </div>
      <div class="card">
        <div class="table-wrap" id="carnet-table">
          ${renderTablaCarnets(carnets)}
        </div>
      </div>`;

    document.getElementById('btn-nuevo-carnet').addEventListener('click', () => abrirModalCarnet());
    document.getElementById('filtro-temp').addEventListener('change', (e) => {
      const val = e.target.value;
      const filtrados = val ? carnets.filter(c => c.temporada === val) : carnets;
      document.getElementById('carnet-table').innerHTML = renderTablaCarnets(filtrados);
      attachCarnetEvents(carnets);
    });
    attachCarnetEvents(carnets);
  } catch (err) {
    area.innerHTML = `<div class="alert alert-warning">⚠️ ${err.message}</div>`;
  }
}

function renderTablaCarnets(carnets) {
  return renderTable(
    ['Jugador','Categoría','N° Carnet','Temporada','Estado','Vencimiento','Acciones'],
    carnets,
    c => `<tr>
      <td><strong>${c.jugadorNombre}</strong></td>
      <td>${c.categoriaNombre}</td>
      <td>${c.numeroCarnet}</td>
      <td>${c.temporada}</td>
      <td>${badge(c.estado)}</td>
      <td>${formatFecha(c.fechaVencimiento)}</td>
      <td><button class="btn btn-sm btn-secondary btn-edit-carnet" data-id="${c.id}"
        data-jugador="${c.jugadorId}" data-numero="${c.numeroCarnet}"
        data-temporada="${c.temporada}" data-estado="${c.estado}"
        data-emision="${c.fechaEmision?.substring(0,10)||''}"
        data-vencimiento="${c.fechaVencimiento?.substring(0,10)||''}">✏️ Editar</button></td>
    </tr>`
  );
}

function attachCarnetEvents(allCarnets) {
  document.querySelectorAll('.btn-edit-carnet').forEach(btn =>
    btn.addEventListener('click', () => abrirModalCarnet({
      id: btn.dataset.id, jugadorId: btn.dataset.jugador,
      numeroCarnet: btn.dataset.numero, temporada: btn.dataset.temporada,
      estado: btn.dataset.estado, fechaEmision: btn.dataset.emision,
      fechaVencimiento: btn.dataset.vencimiento
    })));
}

async function abrirModalCarnet(c = null) {
  let jugadores = [];
  try { jugadores = await apiFetch('/jugadores'); } catch {}
  const jugOpts = jugadores.map(j => `<option value="${j.id}" ${c?.jugadorId == j.id ? 'selected' : ''}>${j.apellido}, ${j.nombre}</option>`).join('');

  openModal(c ? 'Editar carnet' : 'Nuevo carnet', `
    <form id="form-carnet">
      <div class="form-grid">
        <div class="field form-group-full">
          <label>Jugador *</label>
          <select name="jugadorId" required ${c ? 'disabled' : ''}><option value="">Seleccionar...</option>${jugOpts}</select>
        </div>
        <div class="field"><label>N° Carnet *</label><input name="numeroCarnet" required value="${c?.numeroCarnet||''}"></div>
        <div class="field"><label>Temporada *</label><input name="temporada" required placeholder="ej: 2026" value="${c?.temporada||new Date().getFullYear()}"></div>
        <div class="field"><label>Estado</label>
          <select name="estado">
            <option value="pendiente" ${c?.estado==='pendiente'?'selected':''}>Pendiente</option>
            <option value="activo" ${c?.estado==='activo'?'selected':''}>Activo</option>
            <option value="vencido" ${c?.estado==='vencido'?'selected':''}>Vencido</option>
          </select>
        </div>
        <div class="field"><label>Fecha emisión</label><input type="date" name="fechaEmision" value="${c?.fechaEmision||''}"></div>
        <div class="field"><label>Fecha vencimiento</label><input type="date" name="fechaVencimiento" value="${c?.fechaVencimiento||''}"></div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">${c ? 'Guardar' : 'Crear'}</button>
      </div>
    </form>`);

  document.getElementById('form-carnet').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const body = {
      jugadorId: c ? +c.jugadorId : +fd.get('jugadorId'),
      numeroCarnet: fd.get('numeroCarnet'), temporada: fd.get('temporada'),
      estado: fd.get('estado'),
      fechaEmision: fd.get('fechaEmision') || null,
      fechaVencimiento: fd.get('fechaVencimiento') || null
    };
    try {
      if (c) await apiFetch(`/carnets/${c.id}`, { method: 'PUT', body: JSON.stringify(body) });
      else await apiFetch('/carnets', { method: 'POST', body: JSON.stringify(body) });
      showToast(c ? 'Carnet actualizado' : 'Carnet creado');
      closeModal();
      renderCarnets();
    } catch (err) { showToast(err.message, 'error'); }
  });
}
