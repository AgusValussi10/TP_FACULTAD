let jugadoresView = 'lista'; // lista | ficha | alta
let jugadorSeleccionado = null;
let categoriasCache = [];

async function renderJugadores() {
  if (jugadoresView === 'lista') await renderJugadoresLista();
  else if (jugadoresView === 'ficha') await renderFichaJugador(jugadorSeleccionado);
  else await renderAltaJugador();
}

async function renderJugadoresLista() {
  const area = document.getElementById('content-area');
  try {
    categoriasCache = await apiFetch('/categorias');
    const catOpts = categoriasCache.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');

    area.innerHTML = `
      <div class="section-header">
        <h2>Jugadores</h2>
        <button class="btn btn-primary" id="btn-nuevo-jug">+ Nuevo jugador</button>
      </div>
      <div class="toolbar">
        <select id="filtro-cat"><option value="">Todas las categorías</option>${catOpts}</select>
        <input id="filtro-busq" placeholder="Buscar nombre o DNI..." style="width:220px">
        <button class="btn btn-secondary" id="btn-buscar">Buscar</button>
      </div>
      <div class="card">
        <div class="table-wrap" id="jug-table-wrap">
          <div class="loading"><div class="spinner"></div> Buscando...</div>
        </div>
      </div>`;

    const cargarJugadores = async () => {
      const catId = document.getElementById('filtro-cat').value;
      const busq = document.getElementById('filtro-busq').value;
      const params = new URLSearchParams();
      if (catId) params.append('categoriaId', catId);
      if (busq) params.append('busqueda', busq);
      const jugadores = await apiFetch('/jugadores?' + params);
      const wrap = document.getElementById('jug-table-wrap');
      wrap.innerHTML = renderTable(
        ['Nombre','DNI','Categoría','Fecha Nac.','Estado','Acciones'],
        jugadores,
        j => `<tr>
          <td><strong>${j.apellido}, ${j.nombre}</strong></td>
          <td>${j.dni}</td>
          <td>${j.categoriaNombre || '—'}</td>
          <td>${formatFecha(j.fechaNacimiento)}</td>
          <td>${badge(j.activo ? 'activo' : 'inactivo')}</td>
          <td>
            <button class="btn btn-sm btn-secondary btn-ver-ficha" data-id="${j.id}">👁️ Ficha</button>
            ${j.activo ? `<button class="btn btn-sm btn-danger btn-baja" data-id="${j.id}" style="margin-left:4px">Dar de baja</button>` : ''}
          </td>
        </tr>`
      );

      wrap.querySelectorAll('.btn-ver-ficha').forEach(btn =>
        btn.addEventListener('click', () => {
          jugadoresView = 'ficha';
          jugadorSeleccionado = btn.dataset.id;
          renderJugadores();
        }));
      wrap.querySelectorAll('.btn-baja').forEach(btn =>
        btn.addEventListener('click', () => confirmInline(btn, async () => {
          try {
            await apiFetch(`/jugadores/${btn.dataset.id}`, { method: 'DELETE' });
            showToast('Jugador dado de baja');
            cargarJugadores();
          } catch (e) { showToast(e.message, 'error'); }
        })));
    };

    document.getElementById('btn-buscar').addEventListener('click', cargarJugadores);
    document.getElementById('filtro-busq').addEventListener('keydown', e => { if (e.key === 'Enter') cargarJugadores(); });
    document.getElementById('btn-nuevo-jug').addEventListener('click', () => { jugadoresView = 'alta'; renderJugadores(); });
    cargarJugadores();
  } catch (err) {
    area.innerHTML = `<div class="alert alert-warning">⚠️ ${err.message}</div>`;
  }
}

async function renderFichaJugador(id) {
  const area = document.getElementById('content-area');
  try {
    const { jugador: j, cuotas, seguro, carnet, asistencias } = await apiFetch(`/jugadores/${id}`);
    area.innerHTML = `
      <div style="margin-bottom:16px">
        <button class="btn btn-secondary btn-sm" id="btn-volver-lista">← Volver</button>
      </div>
      <h2 style="margin-bottom:20px;font-size:20px">${j.apellido}, ${j.nombre} ${badge(j.activo ? 'activo' : 'inactivo')}</h2>
      <div class="ficha-grid">
        <div class="card">
          <div class="card-title">Datos personales</div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">DNI</span><span>${j.dni}</span></div>
            <div class="info-row"><span class="info-label">Nacimiento</span><span>${formatFecha(j.fechaNacimiento)}</span></div>
            <div class="info-row"><span class="info-label">Categoría</span><span>${j.categoriaNombre}</span></div>
            <div class="info-row"><span class="info-label">Alta</span><span>${formatFecha(j.fechaAlta)}</span></div>
          </div>
        </div>
        <div class="card">
          <div class="card-title">Grupo familiar</div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Contacto</span><span>${j.grupoFamiliarContacto || '—'}</span></div>
            <div class="info-row"><span class="info-label">Teléfono</span><span>${j.grupoFamiliarTelefono || '—'}</span></div>
            <div class="info-row"><span class="info-label">Email</span><span>${j.grupoFamiliarEmail || '—'}</span></div>
          </div>
        </div>
      </div>
      <div class="card" style="margin-bottom:16px">
        <div class="card-title">Seguro & Carnet</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div>
            <div style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px">Seguro</div>
            ${seguro ? `
              <div class="info-list">
                <div class="info-row"><span class="info-label">Póliza</span><span>${seguro.numeroPoliza}</span></div>
                <div class="info-row"><span class="info-label">Vigencia</span><span>${formatFecha(seguro.vigenteDesde)} — ${formatFecha(seguro.vigenteHasta)}</span></div>
                <div class="info-row"><span class="info-label">Estado</span><span>${badge(seguro.estado)}</span></div>
              </div>` : '<span style="color:var(--text-muted)">Sin seguro registrado</span>'}
          </div>
          <div>
            <div style="font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px">Carnet de liga</div>
            ${carnet ? `
              <div class="info-list">
                <div class="info-row"><span class="info-label">N° carnet</span><span>${carnet.numeroCarnet}</span></div>
                <div class="info-row"><span class="info-label">Temporada</span><span>${carnet.temporada}</span></div>
                <div class="info-row"><span class="info-label">Estado</span><span>${badge(carnet.estado)}</span></div>
                <div class="info-row"><span class="info-label">Vencimiento</span><span>${formatFecha(carnet.fechaVencimiento)}</span></div>
              </div>` : '<span style="color:var(--text-muted)">Sin carnet registrado</span>'}
          </div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="card">
          <div class="card-title">Últimas cuotas</div>
          <div class="table-wrap">
            ${renderTable(['Período','Monto','Dcto.','Estado'], cuotas,
              c => `<tr>
                <td>${c.periodoMes}/${c.periodoAnio}</td>
                <td>${formatPeso(c.monto)}</td>
                <td>${c.descuentoHermanos > 0 ? formatPeso(c.descuentoHermanos) : '—'}</td>
                <td>${badge(c.estado)}</td>
              </tr>`)}
          </div>
        </div>
        <div class="card">
          <div class="card-title">Últimas asistencias</div>
          <div class="table-wrap">
            ${renderTable(['Fecha','Estado'], asistencias,
              a => `<tr>
                <td>${formatFecha(a.fecha)}</td>
                <td>${badge(a.presente ? 'presente' : 'ausente')}</td>
              </tr>`)}
          </div>
        </div>
      </div>`;

    document.getElementById('btn-volver-lista').addEventListener('click', () => {
      jugadoresView = 'lista';
      renderJugadores();
    });
  } catch (err) {
    area.innerHTML = `<div class="alert alert-warning">⚠️ ${err.message}</div>`;
  }
}

async function renderAltaJugador() {
  const area = document.getElementById('content-area');
  if (!categoriasCache.length) categoriasCache = await apiFetch('/categorias');
  const catOpts = categoriasCache.map(c =>
    `<option value="${c.id}">${c.nombre} (${c.anioNacimientoDesde}–${c.anioNacimientoHasta})</option>`).join('');

  area.innerHTML = `
    <div style="margin-bottom:16px">
      <button class="btn btn-secondary btn-sm" id="btn-volver-lista2">← Volver</button>
    </div>
    <h2 style="margin-bottom:20px">Nuevo jugador</h2>
    <div class="card">
      <form id="form-jugador">
        <div class="form-grid">
          <div class="field"><label>Nombre *</label><input name="nombre" required></div>
          <div class="field"><label>Apellido *</label><input name="apellido" required></div>
          <div class="field"><label>DNI *</label><input name="dni" required></div>
          <div class="field"><label>Fecha de nacimiento *</label><input type="date" name="fechaNacimiento" required></div>
          <div class="field"><label>Categoría *</label>
            <select name="categoriaId" required><option value="">Seleccionar...</option>${catOpts}</select>
          </div>
        </div>
        <hr style="margin:20px 0;border-color:var(--border)">
        <div style="font-size:13px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:14px">Grupo familiar</div>
        <div class="form-grid">
          <div class="field"><label>Nombre contacto</label><input name="gf_nombre"></div>
          <div class="field"><label>Teléfono</label><input name="gf_telefono" type="tel"></div>
          <div class="field form-group-full"><label>Email</label><input name="gf_email" type="email"></div>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" id="btn-cancelar-alta">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar jugador</button>
        </div>
      </form>
    </div>`;

  const volver = () => { jugadoresView = 'lista'; renderJugadores(); };
  document.getElementById('btn-volver-lista2').addEventListener('click', volver);
  document.getElementById('btn-cancelar-alta').addEventListener('click', volver);

  const form = document.getElementById('form-jugador');
  const inputFecha = form.elements['fechaNacimiento'];
  const selectCat = form.elements['categoriaId'];

  // Al elegir la fecha, preselecciona la categoría que corresponde a ese año de nacimiento
  inputFecha.addEventListener('change', () => {
    const anio = new Date(inputFecha.value).getUTCFullYear();
    const cat = categoriasCache.find(c => anio >= c.anioNacimientoDesde && anio <= c.anioNacimientoHasta);
    if (cat) selectCat.value = cat.id;
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const anioNac = new Date(fd.get('fechaNacimiento')).getUTCFullYear();
    const catSel = categoriasCache.find(c => c.id === +fd.get('categoriaId'));
    if (catSel && (anioNac < catSel.anioNacimientoDesde || anioNac > catSel.anioNacimientoHasta)) {
      showToast(`La categoría ${catSel.nombre} admite nacidos entre ${catSel.anioNacimientoDesde} y ${catSel.anioNacimientoHasta}`, 'error');
      return;
    }
    const body = {
      nombre: fd.get('nombre'), apellido: fd.get('apellido'),
      dni: fd.get('dni'), fechaNacimiento: fd.get('fechaNacimiento'),
      categoriaId: +fd.get('categoriaId'),
      grupoFamiliar: fd.get('gf_nombre') ? {
        nombreContacto: fd.get('gf_nombre'),
        telefono: fd.get('gf_telefono'),
        email: fd.get('gf_email')
      } : null
    };
    try {
      await apiFetch('/jugadores', { method: 'POST', body: JSON.stringify(body) });
      showToast('Jugador creado correctamente');
      jugadoresView = 'lista';
      renderJugadores();
    } catch (err) { showToast(err.message, 'error'); }
  });
}
