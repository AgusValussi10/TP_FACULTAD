async function renderCategorias() {
  const area = document.getElementById('content-area');
  try {
    const cats = await apiFetch('/categorias');
    area.innerHTML = `
      <div class="section-header">
        <h2>Categorías (${cats.length})</h2>
        <button class="btn btn-primary" id="btn-nueva-cat">+ Nueva categoría</button>
      </div>
      <div class="card">
        <div class="table-wrap">
          ${renderTable(
            ['Nombre','Nacimientos','Entrenador','Jugadores','Acciones'],
            cats,
            c => `<tr>
              <td><strong>${c.nombre}</strong></td>
              <td>${c.anioNacimientoDesde} – ${c.anioNacimientoHasta}</td>
              <td>${c.entrenadorNombre || '<span style="color:var(--text-muted)">Sin asignar</span>'}</td>
              <td>${badge(c.cantJugadores + ' activos', {'0 activos':'badge-gris'})||c.cantJugadores}</td>
              <td>
                <button class="btn btn-sm btn-secondary btn-edit-cat" data-id="${c.id}" data-nombre="${c.nombre}"
                  data-desde="${c.anioNacimientoDesde}" data-hasta="${c.anioNacimientoHasta}"
                  data-entrenador="${c.entrenadorId||''}">✏️ Editar</button>
                <button class="btn btn-sm btn-danger btn-del-cat" data-id="${c.id}" style="margin-left:6px">🗑️</button>
              </td>
            </tr>`
          )}
        </div>
      </div>`;

    document.getElementById('btn-nueva-cat').addEventListener('click', () => abrirModalCategoria());

    area.querySelectorAll('.btn-edit-cat').forEach(btn => {
      btn.addEventListener('click', () => abrirModalCategoria({
        id: btn.dataset.id, nombre: btn.dataset.nombre,
        anioDesde: btn.dataset.desde, anioHasta: btn.dataset.hasta,
        entrenadorId: btn.dataset.entrenador
      }));
    });

    area.querySelectorAll('.btn-del-cat').forEach(btn => {
      btn.addEventListener('click', () => confirmInline(btn, async () => {
        try {
          await apiFetch(`/categorias/${btn.dataset.id}`, { method: 'DELETE' });
          showToast('Categoría eliminada');
          renderCategorias();
        } catch (e) { showToast(e.message, 'error'); }
      }));
    });
  } catch (err) {
    area.innerHTML = `<div class="alert alert-warning">⚠️ ${err.message}</div>`;
  }
}

function abrirModalCategoria(cat = null) {
  const titulo = cat ? 'Editar categoría' : 'Nueva categoría';
  openModal(titulo, `
    <form id="form-cat">
      <div class="form-grid">
        <div class="field form-group-full">
          <label>Nombre *</label>
          <input name="nombre" required value="${cat?.nombre||''}">
        </div>
        <div class="field">
          <label>Año nacimiento (desde) *</label>
          <input type="number" name="anioDesde" required value="${cat?.anioDesde||new Date().getFullYear()-12}" min="2000" max="2025">
        </div>
        <div class="field">
          <label>Año nacimiento (hasta) *</label>
          <input type="number" name="anioHasta" required value="${cat?.anioHasta||new Date().getFullYear()-10}" min="2000" max="2025">
        </div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">${cat ? 'Guardar cambios' : 'Crear'}</button>
      </div>
    </form>`);

  document.getElementById('form-cat').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const body = { nombre: fd.get('nombre'), anioDesde: +fd.get('anioDesde'), anioHasta: +fd.get('anioHasta'), entrenadorId: null };
    try {
      if (cat) await apiFetch(`/categorias/${cat.id}`, { method: 'PUT', body: JSON.stringify(body) });
      else await apiFetch('/categorias', { method: 'POST', body: JSON.stringify(body) });
      showToast(cat ? 'Categoría actualizada' : 'Categoría creada');
      closeModal();
      renderCategorias();
    } catch (err) { showToast(err.message, 'error'); }
  });
}
