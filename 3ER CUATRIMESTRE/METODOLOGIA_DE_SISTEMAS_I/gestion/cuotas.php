<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Administrador');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cuotas y Pagos – Educar para Transformar</title>
  <link rel="icon" href="../assets/logo.avif">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Merriweather:wght@700&display=swap" rel="stylesheet">
  <style>
    :root {
      --naranja:     #F97316;
      --naranja-bg:  #FFF7ED;
      --azul:        #374151;
      --gris-texto:  #111827;
      --blanco:      #FFFFFF;
      --borde:       #E5E7EB;
      --sombra:      0 4px 18px rgba(249,115,22,.13);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Nunito', sans-serif; color: var(--gris-texto); background: #F9FAFB; }
    header {
      background: var(--blanco); padding: .75rem 1.5rem;
      display: flex; align-items: center; justify-content: space-between;
      box-shadow: 0 2px 12px rgba(249,115,22,.12);
      border-bottom: 1px solid rgba(249,115,22,.15);
      position: sticky; top: 0; z-index: 100;
    }
    .logo-area { display: flex; align-items: center; gap: .7rem; text-decoration: none; color: inherit; }
    .logo-circle { height: 48px; width: auto; border-radius: 6px; object-fit: contain; }
    .logo-text strong { display: block; font-size: .95rem; font-weight: 900; }
    .logo-text span   { font-size: .7rem; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; }
    .user-info { display: flex; align-items: center; gap: 1rem; }
    .user-badge {
      background: var(--naranja-bg); color: var(--naranja);
      border: 1px solid var(--borde); border-radius: 20px;
      padding: .35rem .9rem; font-size: .85rem; font-weight: 700;
    }
    .btn-logout {
      background: #C2410C; color: #fff; border: none; border-radius: 20px;
      padding: .4rem 1rem; font-weight: 900; font-size: .85rem; cursor: pointer;
      text-decoration: none; transition: opacity .2s;
    }
    .btn-logout:hover { opacity: .85; }
    .welcome {
      background: linear-gradient(135deg, #374151 0%, #1F2937 45%, #111827 100%);
      color: var(--blanco); padding: 2.5rem 1.5rem; text-align: center;
    }
    .welcome h1 { font-family: 'Merriweather', serif; font-size: clamp(1.4rem, 4vw, 2rem); margin-bottom: .4rem; }
    .welcome p  { opacity: .85; font-size: .95rem; }
    .rol-badge  {
      display: inline-block; margin-top: .8rem;
      background: rgba(249,115,22,.25); border: 1px solid rgba(249,115,22,.5);
      color: var(--naranja); border-radius: 20px;
      padding: .25rem .9rem; font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em;
    }
    .container { max-width: 1000px; margin: 2rem auto; padding: 0 1.5rem 3rem; }
    .volver { display: inline-block; margin-bottom: 1rem; color: var(--azul); font-weight: 700; font-size: .85rem; text-decoration: none; }
    .volver:hover { text-decoration: underline; }
    .card { background: var(--blanco); border-radius: 16px; box-shadow: var(--sombra); overflow: hidden; margin-bottom: 1.5rem; }
    .card-header {
      padding: 1.1rem 1.5rem; display: flex; align-items: center; gap: .7rem;
      border-bottom: 2px solid var(--borde);
    }
    .card-header .icon { font-size: 1.5rem; }
    .card-header h2 { font-size: 1rem; font-weight: 800; color: var(--azul); }
    .card-body { padding: 1.2rem 1.5rem; overflow-x: auto; }
    .form-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; margin-bottom: 1rem; }
    .form-row label { display: flex; flex-direction: column; gap: .3rem; font-size: .82rem; font-weight: 800; color: var(--azul); }
    .form-row select, .form-row input {
      padding: .55rem .7rem; border: 2px solid var(--borde); border-radius: 8px;
      font-family: inherit; font-size: .9rem;
    }
    .btn {
      background: var(--naranja); color: var(--blanco); border: none; border-radius: 10px;
      padding: .55rem 1.2rem; font-weight: 800; font-size: .88rem; cursor: pointer;
      font-family: inherit; transition: opacity .2s;
    }
    .btn:hover { opacity: .88; }
    .btn:disabled { opacity: .5; cursor: not-allowed; }
    .btn-sm {
      background: var(--azul); color: var(--blanco); border: none; border-radius: 8px;
      padding: .35rem .8rem; font-weight: 800; font-size: .8rem; cursor: pointer;
      font-family: inherit; transition: opacity .2s; white-space: nowrap;
    }
    .btn-sm:hover { opacity: .85; }
    .btn-sm:disabled { opacity: .5; cursor: not-allowed; }
    table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    th { background: var(--naranja-bg); color: var(--naranja); font-weight: 800;
         padding: .5rem .7rem; text-align: left; font-size: .8rem; text-transform: uppercase; white-space: nowrap; }
    td { padding: .6rem .7rem; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    .badge {
      display: inline-block; border-radius: 12px;
      padding: .15rem .55rem; font-size: .78rem; font-weight: 800; white-space: nowrap;
    }
    .badge-verde  { background: #d1fae5; color: #065F46; }
    .badge-rojo   { background: #fee2e2; color: #991B1B; }
    .badge-amarillo { background: #fef3c7; color: #92400E; }
    .msg { margin: .6rem 0; font-weight: 700; font-size: .88rem; min-height: 1.2em; }
    .empty-msg { color: #9CA3AF; font-size: .9rem; text-align: center; padding: 1.5rem 0; }
    .pago-inline { display: flex; align-items: center; gap: .4rem; }
    .pago-inline input[type="date"] { padding: .35rem .5rem; border: 2px solid var(--borde); border-radius: 8px; font-family: inherit; font-size: .82rem; }
  </style>
</head>
<body>

<header>
  <a href="../" class="logo-area">
    <img src="../assets/logo.avif" alt="Educar para Transformar" class="logo-circle">
    <div class="logo-text">
      <strong>Educar para Transformar</strong>
      <span>Centro Educativo – Resistencia, Chaco</span>
    </div>
  </a>
  <div class="user-info">
    <span class="user-badge">🔑 Admin</span>
    <a href="../auth/logout.php" class="btn-logout">Cerrar sesión</a>
  </div>
</header>

<div class="welcome">
  <h1>Cuotas y Pagos</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge">Panel Administración</span>
</div>

<div class="container">
  <a href="../portals/portal_admin.php" class="volver">&larr; Volver al panel</a>

  <div class="card">
    <div class="card-header"><span class="icon">📊</span><h2>Resumen General</h2></div>
    <div class="card-body">
      <button type="button" class="btn" id="btn-recalcular" style="margin-bottom:1rem;">🔄 Recalcular condiciones de regularización</button>
      <div class="msg" id="msg-recalcular"></div>
      <table>
        <thead><tr><th>Alumno</th><th>Curso</th><th>Condición</th><th>Deuda pendiente</th><th></th></tr></thead>
        <tbody id="tbody-resumen"><tr><td colspan="5" class="empty-msg">Cargando…</td></tr></tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="icon">➕</span><h2>Generar Cuota</h2></div>
    <div class="card-body">
      <div class="form-row">
        <label>Alumno
          <select id="sel-alumno"><option value="">Elegí un alumno…</option></select>
        </label>
        <label>Concepto
          <select id="sel-concepto">
            <option value="matricula">Matrícula</option>
            <option value="cuota" selected>Cuota</option>
          </select>
        </label>
        <label>Mes
          <select id="sel-mes"></select>
        </label>
        <label>Año
          <select id="sel-anio"></select>
        </label>
        <label>Importe
          <input type="number" id="inp-importe" min="0" step="0.01" style="width:120px;">
        </label>
        <button type="button" class="btn" id="btn-generar">Generar</button>
      </div>
      <div class="msg" id="msg-generar"></div>
    </div>
  </div>

  <div class="card" id="card-cuotas" style="display:none;">
    <div class="card-header"><span class="icon">💳</span><h2>Cuotas de <span id="cuotas-alumno-nombre"></span></h2></div>
    <div class="card-body">
      <table>
        <thead>
          <tr><th>Concepto</th><th>Período</th><th>Importe</th><th>Recargo</th><th>Estado</th><th>Fecha de pago</th><th>Acción</th></tr>
        </thead>
        <tbody id="tbody-cuotas"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
  // Debe coincidir con PORCENTAJE_RECARGO_MORA en gestion/helpers.php (RFG12).
  const PORCENTAJE_RECARGO_MORA = 10;

  const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  const selMes = document.getElementById('sel-mes');
  MESES.forEach((m, i) => {
    const opt = document.createElement('option');
    opt.value = i + 1;
    opt.textContent = m;
    selMes.appendChild(opt);
  });

  const selAnio = document.getElementById('sel-anio');
  const anioActual = new Date().getFullYear();
  for (let a = anioActual - 1; a <= anioActual + 1; a++) {
    const opt = document.createElement('option');
    opt.value = a;
    opt.textContent = a;
    if (a === anioActual) opt.selected = true;
    selAnio.appendChild(opt);
  }
  selMes.value = new Date().getMonth() + 1;

  const selAlumno   = document.getElementById('sel-alumno');
  const tbodyResumen= document.getElementById('tbody-resumen');
  const tbodyCuotas = document.getElementById('tbody-cuotas');
  const cardCuotas  = document.getElementById('card-cuotas');
  const cuotasAlumnoNombre = document.getElementById('cuotas-alumno-nombre');
  const msgGenerar  = document.getElementById('msg-generar');
  const msgRecalcular = document.getElementById('msg-recalcular');
  const btnGenerar  = document.getElementById('btn-generar');
  const btnRecalcular = document.getElementById('btn-recalcular');

  let alumnosCache = [];

  function esc(str) {
    if (str === null || str === undefined) return '—';
    const d = document.createElement('div'); d.textContent = String(str); return d.innerHTML;
  }

  function setMsg(el, texto, color) {
    el.textContent = texto;
    el.style.color = color || 'var(--gris-texto)';
  }

  function badgeCondicion(condicion) {
    return condicion === 'pendiente_regularizacion'
      ? '<span class="badge badge-rojo">Pendiente de regularización</span>'
      : '<span class="badge badge-verde">Regular</span>';
  }

  async function cargarResumen() {
    tbodyResumen.innerHTML = '<tr><td colspan="5" class="empty-msg">Cargando…</td></tr>';
    try {
      const res  = await fetch('cuotas_listar.php');
      const data = await res.json();
      if (!data.success) { tbodyResumen.innerHTML = `<tr><td colspan="5" class="empty-msg">${esc(data.message)}</td></tr>`; return; }

      alumnosCache = data.alumnos;

      if (!alumnosCache.length) {
        tbodyResumen.innerHTML = '<tr><td colspan="5" class="empty-msg">No hay alumnos activos.</td></tr>';
      } else {
        tbodyResumen.innerHTML = alumnosCache.map(a => `
          <tr>
            <td><strong>${esc(a.nombre)}</strong></td>
            <td>${a.curso ? esc(a.curso) : '—'}</td>
            <td>${badgeCondicion(a.condicion)}</td>
            <td>$${a.deuda_pendiente.toFixed(2)}</td>
            <td><button type="button" class="btn-sm" onclick="verCuotas(${a.id})">Ver cuotas</button></td>
          </tr>
        `).join('');
      }

      selAlumno.innerHTML = '<option value="">Elegí un alumno…</option>' +
        alumnosCache.map(a => `<option value="${a.id}">${esc(a.nombre)}</option>`).join('');
    } catch {
      tbodyResumen.innerHTML = '<tr><td colspan="5" class="empty-msg">Error de conexión.</td></tr>';
    }
  }

  function verCuotas(alumnoId) {
    selAlumno.value = alumnoId;
    cargarCuotas(alumnoId);
  }

  async function cargarCuotas(alumnoId) {
    if (!alumnoId) { cardCuotas.style.display = 'none'; return; }

    const alumno = alumnosCache.find(a => String(a.id) === String(alumnoId));
    cuotasAlumnoNombre.textContent = alumno ? alumno.nombre : '';
    cardCuotas.style.display = '';
    tbodyCuotas.innerHTML = '<tr><td colspan="7" class="empty-msg">Cargando…</td></tr>';

    try {
      const res  = await fetch(`cuotas_listar.php?alumno_id=${encodeURIComponent(alumnoId)}`);
      const data = await res.json();
      if (!data.success) { tbodyCuotas.innerHTML = `<tr><td colspan="7" class="empty-msg">${esc(data.message)}</td></tr>`; return; }

      if (!data.cuotas.length) {
        tbodyCuotas.innerHTML = '<tr><td colspan="7" class="empty-msg">Sin cuotas generadas.</td></tr>';
        return;
      }

      const hoy = new Date().toISOString().slice(0, 10);
      tbodyCuotas.innerHTML = data.cuotas.map(c => {
        const estadoBadge = c.estado === 'pagada'
          ? '<span class="badge badge-verde">Pagada</span>'
          : '<span class="badge badge-amarillo">Pendiente</span>';
        const accion = c.estado === 'pendiente'
          ? `<div class="pago-inline">
               <input type="date" id="fecha-pago-${c.id}" value="${hoy}">
               <button type="button" class="btn-sm" id="btn-pagar-${c.id}"
                 onclick="prepararPago(${c.id}, ${c.importe}, '${c.fecha_vencimiento_iso}')">Registrar pago</button>
             </div>`
          : '—';
        return `
          <tr>
            <td>${c.concepto === 'matricula' ? 'Matrícula' : 'Cuota'}</td>
            <td>${MESES[c.mes - 1]} ${c.anio}</td>
            <td>$${Number(c.importe).toFixed(2)}</td>
            <td>$${Number(c.recargo).toFixed(2)}</td>
            <td>${estadoBadge}</td>
            <td>${c.fecha_pago || '—'}</td>
            <td>${accion}</td>
          </tr>
        `;
      }).join('');
    } catch {
      tbodyCuotas.innerHTML = '<tr><td colspan="7" class="empty-msg">Error de conexión.</td></tr>';
    }
  }

  // Primer clic: calcula y muestra el recargo estimado. Segundo clic: confirma el pago.
  function prepararPago(cuotaId, importe, fechaVencimientoIso) {
    const btn = document.getElementById(`btn-pagar-${cuotaId}`);
    const inputFecha = document.getElementById(`fecha-pago-${cuotaId}`);

    if (btn.dataset.confirmando === '1') {
      confirmarPago(cuotaId);
      return;
    }

    const fechaPago = inputFecha.value;
    const venc = new Date(fechaVencimientoIso + 'T00:00:00');
    const pago = new Date(fechaPago + 'T00:00:00');
    const recargo = pago > venc ? Math.round(importe * PORCENTAJE_RECARGO_MORA) / 100 : 0;

    btn.textContent = recargo > 0 ? `Confirmar (recargo $${recargo.toFixed(2)})` : 'Confirmar (sin recargo)';
    btn.dataset.confirmando = '1';
  }

  async function confirmarPago(cuotaId) {
    const btn = document.getElementById(`btn-pagar-${cuotaId}`);
    const inputFecha = document.getElementById(`fecha-pago-${cuotaId}`);

    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const body = new FormData();
    body.append('cuota_id', cuotaId);
    body.append('fecha_pago', inputFecha.value);

    try {
      const res  = await fetch('cuotas_pagar.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        cargarCuotas(selAlumno.value);
        cargarResumen();
      } else {
        alert(data.message || 'Error al registrar el pago.');
        btn.disabled = false;
        btn.textContent = 'Registrar pago';
        btn.dataset.confirmando = '0';
      }
    } catch {
      alert('Error de conexión.');
      btn.disabled = false;
      btn.textContent = 'Registrar pago';
      btn.dataset.confirmando = '0';
    }
  }

  selAlumno.addEventListener('change', () => cargarCuotas(selAlumno.value));

  btnGenerar.addEventListener('click', async () => {
    const alumno_id = selAlumno.value;
    const importe   = document.getElementById('inp-importe').value;

    if (!alumno_id) { setMsg(msgGenerar, 'Elegí un alumno.', '#DC2626'); return; }
    if (!importe || Number(importe) <= 0) { setMsg(msgGenerar, 'Ingresá un importe válido.', '#DC2626'); return; }

    btnGenerar.disabled = true;
    setMsg(msgGenerar, 'Generando...');

    const body = new FormData();
    body.append('alumno_id', alumno_id);
    body.append('concepto', document.getElementById('sel-concepto').value);
    body.append('mes', selMes.value);
    body.append('anio', selAnio.value);
    body.append('importe', importe);

    try {
      const res  = await fetch('cuotas_generar.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        setMsg(msgGenerar, '✅ Cuota generada.', '#059669');
        cargarResumen();
        cargarCuotas(alumno_id);
      } else {
        setMsg(msgGenerar, data.message || 'Error al generar.', '#DC2626');
      }
    } catch {
      setMsg(msgGenerar, 'Error de conexión.', '#DC2626');
    } finally {
      btnGenerar.disabled = false;
    }
  });

  btnRecalcular.addEventListener('click', async () => {
    btnRecalcular.disabled = true;
    setMsg(msgRecalcular, 'Recalculando...');
    try {
      const res  = await fetch('cuotas_recalcular.php', { method: 'POST' });
      const data = await res.json();
      if (data.success) {
        setMsg(msgRecalcular, `✅ ${data.actualizados} alumno(s) recalculado(s).`, '#059669');
        cargarResumen();
      } else {
        setMsg(msgRecalcular, data.message || 'Error al recalcular.', '#DC2626');
      }
    } catch {
      setMsg(msgRecalcular, 'Error de conexión.', '#DC2626');
    } finally {
      btnRecalcular.disabled = false;
    }
  });

  cargarResumen();
</script>

</body>
</html>
