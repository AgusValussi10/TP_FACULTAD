async function renderPartidos() {
  const area = document.getElementById('content-area');
  let categorias = [];
  try { categorias = await apiFetch('/categorias'); } catch {}
  const catOpts = categorias.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');

  area.innerHTML = `
    <div class="section-header">
      <h2>Partidos y Resultados</h2>
      <button class="btn btn-primary" id="btn-nuevo-partido">+ Nuevo partido</button>
    </div>
    <div class="toolbar">
      <select id="f-cat-partido"><option value="">Todas las categorías</option>${catOpts}</select>
      <select id="f-estado-partido">
        <option value="">Todos los estados</option>
        <option value="programado">Programados</option>
        <option value="jugado">Jugados</option>
        <option value="suspendido">Suspendidos</option>
      </select>
      <button class="btn btn-secondary" id="btn-buscar-partidos">Buscar</button>
    </div>
    <div class="card">
      <div class="table-wrap" id="partidos-table">
        <div class="loading"><div class="spinner"></div> Cargando...</div>
      </div>
    </div>`;

  const cargar = async () => {
    const params = new URLSearchParams();
    const cat = document.getElementById('f-cat-partido').value;
    const est = document.getElementById('f-estado-partido').value;
    if (cat) params.append('categoriaId', cat);
    if (est) params.append('estado', est);
    const partidos = await apiFetch('/partidos?' + params);
    const wrap = document.getElementById('partidos-table');
    wrap.innerHTML = renderTable(
      ['Categoría','Rival','Fecha','Hora','Lugar','Resultado','Estado','Acciones'],
      partidos,
      p => `<tr>
        <td>${p.categoriaNombre}</td>
        <td><strong>${p.rival}</strong></td>
        <td>${formatFecha(p.fecha)}</td>
        <td>${p.hora}</td>
        <td>${p.lugar === 'local' ? '🏠 Local' : '✈️ Visitante'}</td>
        <td>${p.estado === 'jugado' ? `<strong>${p.resultadoLocal} - ${p.resultadoVisitante}</strong>` : '—'}</td>
        <td>${badge(p.estado)}</td>
        <td>${p.estado === 'programado' ?
          `<button class="btn btn-sm btn-success btn-cargar-res" data-id="${p.id}" data-rival="${p.rival}">⚽ Resultado</button>` :
          ''}</td>
      </tr>`
    );
    wrap.querySelectorAll('.btn-cargar-res').forEach(btn =>
      btn.addEventListener('click', () => abrirModalResultado(btn.dataset.id, btn.dataset.rival)));
  };

  document.getElementById('btn-buscar-partidos').addEventListener('click', cargar);
  document.getElementById('btn-nuevo-partido').addEventListener('click', () => abrirModalNuevoPartido(catOpts));
  cargar();
}

function abrirModalNuevoPartido(catOpts) {
  openModal('Nuevo partido', `
    <form id="form-partido">
      <div class="form-grid">
        <div class="field form-group-full">
          <label>Categoría *</label>
          <select name="categoriaId" required><option value="">Seleccionar...</option>${catOpts}</select>
        </div>
        <div class="field form-group-full"><label>Rival *</label><input name="rival" required placeholder="Nombre del equipo rival"></div>
        <div class="field"><label>Fecha *</label><input type="date" name="fecha" required></div>
        <div class="field"><label>Hora *</label><input name="hora" required placeholder="ej: 16:00"></div>
        <div class="field form-group-full"><label>Lugar</label>
          <select name="lugar"><option value="local">Local</option><option value="visitante">Visitante</option></select>
        </div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">Crear partido</button>
      </div>
    </form>`);

  document.getElementById('form-partido').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      await apiFetch('/partidos', {
        method: 'POST',
        body: JSON.stringify({
          categoriaId: +fd.get('categoriaId'), rival: fd.get('rival'),
          fecha: fd.get('fecha'), hora: fd.get('hora'), lugar: fd.get('lugar')
        })
      });
      showToast('Partido creado');
      closeModal();
      renderPartidos();
    } catch (err) { showToast(err.message, 'error'); }
  });
}

function abrirModalResultado(id, rival) {
  openModal(`Resultado — vs ${rival}`, `
    <form id="form-resultado">
      <div class="form-grid">
        <div class="field"><label>Goles Unión del Sur</label><input type="number" name="local" min="0" value="0" required></div>
        <div class="field"><label>Goles ${rival}</label><input type="number" name="visitante" min="0" value="0" required></div>
      </div>
      <div id="goleadores-area" style="margin-top:16px">
        <div style="font-size:13px;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px">Goleadores (opcional)</div>
        <div id="gol-list"></div>
        <button type="button" class="btn btn-sm btn-secondary" id="btn-add-gol" style="margin-top:8px">+ Agregar goleador</button>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn btn-success">Confirmar resultado</button>
      </div>
    </form>`);

  let goleadorIdx = 0;
  const addGoleador = () => {
    const row = document.createElement('div');
    row.style.cssText = 'display:flex;gap:8px;margin-bottom:6px;align-items:center';
    row.innerHTML = `<input placeholder="Nombre jugador" style="flex:1;padding:7px 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px" name="gol_nombre_${goleadorIdx}">
      <input type="number" min="1" value="1" style="width:60px;padding:7px 10px;border:1.5px solid var(--border);border-radius:6px;font-family:inherit;font-size:13px" name="gol_cant_${goleadorIdx}">
      <button type="button" style="background:none;border:none;cursor:pointer;color:var(--danger);font-size:16px" onclick="this.parentNode.remove()">✕</button>`;
    document.getElementById('gol-list').appendChild(row);
    goleadorIdx++;
  };
  document.getElementById('btn-add-gol').addEventListener('click', addGoleador);

  document.getElementById('form-resultado').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const goleadores = [];
    for (let i = 0; i < goleadorIdx; i++) {
      const nombre = fd.get(`gol_nombre_${i}`);
      const cant = fd.get(`gol_cant_${i}`);
      if (nombre) goleadores.push({ jugadorId: 0, cantidad: +cant });
    }
    try {
      await apiFetch(`/partidos/${id}/resultado`, {
        method: 'PUT',
        body: JSON.stringify({ local: +fd.get('local'), visitante: +fd.get('visitante'), goleadores })
      });
      showToast('Resultado cargado');
      closeModal();
      renderPartidos();
    } catch (err) { showToast(err.message, 'error'); }
  });
}
