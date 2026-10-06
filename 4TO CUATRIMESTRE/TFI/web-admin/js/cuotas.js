async function renderCuotas() {
  const area = document.getElementById('content-area');
  const now = new Date();
  const mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  const mesesOpts = Array.from({length:12},(_,i)=>`<option value="${i+1}" ${i+1===now.getMonth()+1?'selected':''}>${mesesNombres[i+1]}</option>`).join('');

  let categorias = [];
  try { categorias = await apiFetch('/categorias'); } catch {}
  const catOpts = categorias.map(c=>`<option value="${c.id}">${c.nombre}</option>`).join('');

  area.innerHTML = `
    <div class="section-header">
      <h2>Cuotas</h2>
      <button class="btn btn-primary" id="btn-generar-cuotas">⚡ Generar cuotas del período</button>
    </div>
    <div class="toolbar">
      <select id="f-mes">${mesesOpts}</select>
      <input type="number" id="f-anio" value="${now.getFullYear()}" style="width:90px" min="2020" max="2030">
      <select id="f-cat"><option value="">Todas las categorías</option>${catOpts}</select>
      <button class="btn btn-secondary" id="btn-buscar-cuotas">Buscar</button>
      <span class="spacer"></span>
    </div>
    <div class="card">
      <div class="table-wrap" id="cuotas-wrap">
        <div class="loading"><div class="spinner"></div> Cargando...</div>
      </div>
    </div>`;

  const cargar = async () => {
    const params = new URLSearchParams({
      mes: document.getElementById('f-mes').value,
      anio: document.getElementById('f-anio').value
    });
    const cat = document.getElementById('f-cat').value;
    if (cat) params.append('categoriaId', cat);
    const cuotas = await apiFetch('/cuotas?' + params);
    const wrap = document.getElementById('cuotas-wrap');
    wrap.innerHTML = renderTable(
      ['Jugador','Categoría','Monto','Dcto. hermanos','Total','Estado','Acciones'],
      cuotas,
      c => {
        const total = c.monto - (c.descuentoHermanos || 0);
        return `<tr>
          <td><strong>${c.jugadorNombre}</strong></td>
          <td>${c.categoriaNombre}</td>
          <td>${formatPeso(c.monto)}</td>
          <td>${c.descuentoHermanos > 0 ? `<span style="color:var(--success)">-${formatPeso(c.descuentoHermanos)}</span>` : '—'}</td>
          <td><strong>${formatPeso(total)}</strong></td>
          <td>${badge(c.estado)}</td>
          <td>${c.estado !== 'pagada' ? `<button class="btn btn-sm btn-success btn-pagar" data-id="${c.id}" data-nombre="${c.jugadorNombre}">💳 Pagar</button>` : ''}</td>
        </tr>`;
      }
    );
    wrap.querySelectorAll('.btn-pagar').forEach(btn =>
      btn.addEventListener('click', () => abrirModalPago(btn.dataset.id, btn.dataset.nombre)));
  };

  document.getElementById('btn-buscar-cuotas').addEventListener('click', cargar);
  document.getElementById('btn-generar-cuotas').addEventListener('click', () => abrirModalGenerarCuotas(mesesOpts));
  cargar();
}

function abrirModalGenerarCuotas(mesesOpts) {
  openModal('Generar cuotas del período', `
    <form id="form-generar">
      <div class="form-grid">
        <div class="field"><label>Mes *</label><select name="mes">${mesesOpts}</select></div>
        <div class="field"><label>Año *</label><input type="number" name="anio" value="${new Date().getFullYear()}" required></div>
        <div class="field form-group-full"><label>Monto base ($ ARS) *</label><input type="number" name="monto" placeholder="ej: 5000" required step="100"></div>
      </div>
      <p style="font-size:13px;color:var(--text-muted);margin:12px 0">Se genera una cuota por cada jugador activo. Si tiene hermanos en el club, se aplica 10% de descuento automáticamente.</p>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">Generar</button>
      </div>
    </form>`);

  document.getElementById('form-generar').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      const res = await apiFetch('/cuotas/generar', {
        method: 'POST',
        body: JSON.stringify({ mes: +fd.get('mes'), anio: +fd.get('anio'), monto: +fd.get('monto') })
      });
      showToast(res.message);
      closeModal();
      renderCuotas();
    } catch (err) { showToast(err.message, 'error'); }
  });
}

function abrirModalPago(id, nombre) {
  openModal(`Registrar pago — ${nombre}`, `
    <form id="form-pago">
      <div class="form-grid col-1">
        <div class="field"><label>Método de pago *</label>
          <select name="metodo"><option>Efectivo</option><option>Transferencia</option><option>Tarjeta</option></select>
        </div>
        <div class="field"><label>Monto pagado *</label><input type="number" name="monto" required step="0.01" placeholder="0.00"></div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn btn-success">Confirmar pago</button>
      </div>
    </form>`);

  document.getElementById('form-pago').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      await apiFetch(`/cuotas/${id}/pagar`, {
        method: 'POST',
        body: JSON.stringify({ metodo: fd.get('metodo'), monto: +fd.get('monto') })
      });
      showToast('Pago registrado correctamente');
      closeModal();
      renderCuotas();
    } catch (err) { showToast(err.message, 'error'); }
  });
}
