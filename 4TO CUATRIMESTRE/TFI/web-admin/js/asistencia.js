let asistenciaRegistros = {};

async function renderAsistencia() {
  const area = document.getElementById('content-area');
  let categorias = [];
  try { categorias = await apiFetch('/categorias'); } catch {}
  const catOpts = categorias.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
  const hoy = new Date().toISOString().substring(0, 10);

  area.innerHTML = `
    <div class="section-header"><h2>Asistencia</h2></div>
    <div class="toolbar">
      <select id="asist-cat"><option value="">Seleccionar categoría *</option>${catOpts}</select>
      <input type="date" id="asist-fecha" value="${hoy}">
      <button class="btn btn-secondary" id="btn-cargar-asist">Cargar / Ver</button>
    </div>
    <div id="asist-content"></div>`;

  document.getElementById('btn-cargar-asist').addEventListener('click', async () => {
    const catId = document.getElementById('asist-cat').value;
    const fecha = document.getElementById('asist-fecha').value;
    if (!catId) { showToast('Seleccioná una categoría', 'error'); return; }
    if (!fecha) { showToast('Seleccioná una fecha', 'error'); return; }

    const content = document.getElementById('asist-content');
    content.innerHTML = '<div class="loading"><div class="spinner"></div> Cargando...</div>';

    try {
      const registros = await apiFetch(`/asistencia?categoriaId=${catId}&fecha=${fecha}`);
      asistenciaRegistros = {};
      registros.forEach(r => { asistenciaRegistros[r.jugadorId] = r.presente; });

      content.innerHTML = `
        <div class="card">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <span style="font-weight:700">${registros.length} jugadores</span>
            <div style="display:flex;gap:8px">
              <button class="btn btn-sm btn-secondary" id="btn-todos-pres">✅ Todos presentes</button>
              <button class="btn btn-sm btn-secondary" id="btn-todos-ause">❌ Todos ausentes</button>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Jugador</th><th>Asistencia</th></tr></thead>
              <tbody id="asist-tbody">
                ${registros.map(r => `
                  <tr>
                    <td><strong>${r.jugadorNombre}</strong></td>
                    <td>
                      <div class="asist-toggle" id="toggle-${r.jugadorId}">
                        <button class="asist-btn ${r.presente === true ? 'active-pres' : ''}" data-id="${r.jugadorId}" data-val="true">✅ Presente</button>
                        <button class="asist-btn ${r.presente === false ? 'active-ause' : ''}" data-id="${r.jugadorId}" data-val="false">❌ Ausente</button>
                      </div>
                    </td>
                  </tr>`).join('')}
              </tbody>
            </table>
          </div>
          <div style="margin-top:20px;text-align:right">
            <button class="btn btn-primary" id="btn-guardar-asist">Guardar asistencia</button>
          </div>
        </div>`;

      const actualizarToggle = (jugId, val) => {
        asistenciaRegistros[+jugId] = val;
        const toggle = document.getElementById(`toggle-${jugId}`);
        toggle.querySelectorAll('.asist-btn').forEach(b => {
          const bVal = b.dataset.val === 'true';
          b.className = 'asist-btn' + (bVal === val ? (val ? ' active-pres' : ' active-ause') : '');
        });
      };

      content.querySelectorAll('.asist-btn').forEach(btn =>
        btn.addEventListener('click', () => actualizarToggle(btn.dataset.id, btn.dataset.val === 'true')));

      document.getElementById('btn-todos-pres').addEventListener('click', () =>
        registros.forEach(r => actualizarToggle(r.jugadorId, true)));
      document.getElementById('btn-todos-ause').addEventListener('click', () =>
        registros.forEach(r => actualizarToggle(r.jugadorId, false)));

      document.getElementById('btn-guardar-asist').addEventListener('click', async () => {
        const regs = registros.map(r => ({
          jugadorId: r.jugadorId,
          presente: asistenciaRegistros[r.jugadorId] === true
        }));
        try {
          await apiFetch('/asistencia', {
            method: 'POST',
            body: JSON.stringify({ categoriaId: +catId, fecha, registros: regs })
          });
          showToast('Asistencia guardada correctamente');
        } catch (err) { showToast(err.message, 'error'); }
      });
    } catch (err) {
      content.innerHTML = `<div class="alert alert-warning">⚠️ ${err.message}</div>`;
    }
  });
}
