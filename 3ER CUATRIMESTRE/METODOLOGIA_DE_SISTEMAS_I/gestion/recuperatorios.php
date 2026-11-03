<?php
require_once '../auth/session.php';
$rol_sesion = $_SESSION['rol'] ?? '';
if (!in_array($rol_sesion, ['docente', 'admin'], true)) {
    header('Location: /');
    exit;
}
// El docente programa y califica; el admin ve todos los recuperatorios (solo lectura).
$es_admin   = $rol_sesion === 'admin';
$nombre     = htmlspecialchars($_SESSION['nombre'] ?? ($es_admin ? 'Administrador' : 'Docente'));
$volver_url = $es_admin ? '../portals/portal_admin.php' : '../portals/portal_docente.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exámenes Recuperatorios – Educar para Transformar</title>
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
      --rojo:        #DC2626;
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
    .container { max-width: 960px; margin: 2rem auto; padding: 0 1.5rem 3rem; }
    .volver { display: inline-block; margin-bottom: 1rem; color: var(--azul); font-weight: 700; font-size: .85rem; text-decoration: none; }
    .volver:hover { text-decoration: underline; }
    .card { background: var(--blanco); border-radius: 16px; box-shadow: var(--sombra); overflow: hidden; margin-bottom: 1.5rem; }
    .card-header {
      padding: 1.1rem 1.5rem; display: flex; align-items: center; gap: .7rem;
      border-bottom: 2px solid var(--borde);
    }
    .card-header .icon { font-size: 1.5rem; }
    .card-header h2 { font-size: 1rem; font-weight: 800; color: var(--azul); }
    .card-body { padding: 1.2rem 1.5rem; }
    .form-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; margin-bottom: 1.2rem; }
    .form-row label { display: flex; flex-direction: column; gap: .3rem; font-size: .82rem; font-weight: 800; color: var(--azul); }
    .form-row select { padding: .55rem .7rem; border: 2px solid var(--borde); border-radius: 8px; font-family: inherit; font-size: .9rem; min-width: 200px; }
    .btn {
      background: var(--naranja); color: var(--blanco); border: none; border-radius: 10px;
      padding: .55rem 1.2rem; font-weight: 800; font-size: .88rem; cursor: pointer;
      font-family: inherit; transition: opacity .2s;
    }
    .btn:hover { opacity: .88; }
    .btn:disabled { opacity: .6; cursor: not-allowed; }
    .btn-sm {
      background: var(--azul); color: var(--blanco); border: none; border-radius: 8px;
      padding: .35rem .8rem; font-weight: 800; font-size: .8rem; cursor: pointer;
      font-family: inherit; transition: opacity .2s;
    }
    .btn-sm:hover { opacity: .85; }
    table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    th { background: var(--naranja-bg); color: var(--naranja); font-weight: 800;
         padding: .5rem .7rem; text-align: left; font-size: .8rem; text-transform: uppercase; }
    td { padding: .6rem .7rem; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    .badge {
      display: inline-block; border-radius: 12px;
      padding: .15rem .55rem; font-size: .78rem; font-weight: 800;
    }
    .badge-rojo   { background: #fee2e2; color: #991B1B; }
    .badge-verde  { background: #d1fae5; color: #065F46; }
    .badge-gris   { background: #E5E7EB; color: #6B7280; }
    .badge-amarillo { background: #fef3c7; color: #92400E; }
    .input-fecha, .sel-turno, .sel-estado, .sel-periodo {
      padding: .35rem .5rem; border: 2px solid var(--borde); border-radius: 8px;
      font-family: inherit; font-size: .85rem;
    }
    .msg-global { margin: .6rem 0; font-weight: 700; font-size: .88rem; min-height: 1.2em; }
    .empty-msg { color: #9CA3AF; font-size: .9rem; text-align: center; padding: 1.5rem 0; }
    .alerta-nota { color: var(--rojo); font-weight: 800; }
    .container.ancho { max-width: 1100px; }
    .form-row input[type="date"], .form-row input[type="number"] { padding: .55rem .7rem; border: 2px solid var(--borde); border-radius: 8px; font-family: inherit; font-size: .9rem; }
    .form-row input[type="number"] { width: 90px; }
    .hint { font-size: .78rem; color: #6B7280; margin-top: -.6rem; margin-bottom: .8rem; }
    .btn-gris { background: #E5E7EB; color: var(--azul); }
    .btn-mini { background: none; border: 2px solid var(--borde); border-radius: 8px; padding: .25rem .6rem; font-weight: 800; font-size: .78rem; cursor: pointer; font-family: inherit; color: var(--azul); }
    .btn-mini:hover { border-color: var(--naranja); color: var(--naranja); }
    .btn-mini.peligro:hover { border-color: #DC2626; color: #DC2626; }
    .editando { background: var(--naranja-bg); border-left: 4px solid var(--naranja); padding: .6rem .9rem; border-radius: 0 10px 10px 0; font-size: .85rem; margin-bottom: 1rem; }
    .resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .resumen .stat { background: var(--blanco); border-radius: 14px; box-shadow: var(--sombra); padding: 1rem 1.2rem; }
    .resumen .stat b { display: block; font-size: 1.6rem; font-weight: 900; color: var(--azul); }
    .resumen .stat span { font-size: .78rem; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: .05em; }
    tr.vencido td { background: #FEF2F2; }
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
    <span class="user-badge"><?= $es_admin ? '🛡️ Administrador' : '🎓 Docente' ?></span>
    <a href="../auth/logout.php" class="btn-logout">Cerrar sesión</a>
  </div>
</header>

<div class="welcome">
  <h1>Exámenes Recuperatorios</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge"><?= $es_admin ? 'Administración' : 'Portal Docente' ?></span>
</div>

<div class="container ancho">
  <a href="<?= $volver_url ?>" class="volver">&larr; Volver al portal</a>

<?php if ($es_admin): ?>
  <div class="card">
    <div class="card-header"><span class="icon">🔎</span><h2>Recuperatorios de la institución</h2></div>
    <div class="card-body">
      <div class="form-row" style="margin-bottom:0;">
        <label>Curso <select id="f-curso"><option value="0">Todos</option></select></label>
        <label>Estado
          <select id="f-estado">
            <option value="">Todos</option>
            <option value="pendiente">Pendientes</option>
            <option value="aprobado">Aprobados</option>
            <option value="desaprobado">Desaprobados</option>
          </select>
        </label>
        <button type="button" class="btn" id="f-buscar">Ver</button>
      </div>
      <div class="msg-global" id="msg-global"></div>
    </div>
  </div>
  <div class="resumen" id="resumen" style="display:none;"></div>
  <div class="card" id="card-lista" style="display:none;">
    <div class="card-header"><span class="icon">🔄</span><h2>Listado</h2></div>
    <div class="card-body" style="overflow-x:auto;">
      <table>
        <thead><tr><th>Fecha</th><th>Turno</th><th>Alumno</th><th>Curso</th><th>Materia</th><th>Docente</th><th>Período</th><th>Nota</th><th>Estado</th></tr></thead>
        <tbody id="tbody-lista"></tbody>
      </table>
      <p class="hint" style="margin:.8rem 0 0;">Los recuperatorios los programa y califica el docente de cada materia. En rojo: pendientes con fecha ya pasada (falta cargar la nota).</p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-header"><span class="icon">📋</span><h2>Seleccionar Materia</h2></div>
    <div class="card-body">
      <div class="form-row" style="margin-bottom:0;">
        <label>Materia
          <select id="sel-materia"><option value="">Elegí una materia…</option></select>
        </label>
        <button type="button" class="btn" id="btn-cargar">Ver alumnos</button>
      </div>
      <div class="msg-global" id="msg-global"></div>
    </div>
  </div>

  <div id="seccion" style="display:none;">
    <div class="card" id="card-form">
      <div class="card-header"><span class="icon">🗓️</span><h2 id="titulo-form">Programar recuperatorio</h2></div>
      <div class="card-body">
        <div class="editando" id="aviso-edit" style="display:none;"></div>
        <div class="form-row">
          <label>Alumno <select id="r-alumno"></select></label>
          <label>Período
            <select id="r-periodo">
              <option>1er Trimestre</option><option>2do Trimestre</option><option>3er Trimestre</option><option>Anual</option>
            </select>
          </label>
          <label>Fecha <input type="date" id="r-fecha"></label>
          <label>Turno
            <select id="r-turno"><option value="">—</option><option value="mañana">Mañana</option><option value="tarde">Tarde</option></select>
          </label>
          <label>Nota <input type="number" id="r-nota" min="1" max="10" step="1" placeholder="—"></label>
        </div>
        <p class="hint">Dejá la nota vacía al programarlo. Cuando el alumno rinda, cargá la nota: con <span id="nota-min"></span> o más queda aprobado. Al padre/tutor le llega una notificación.</p>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
          <button type="button" class="btn" id="btn-guardar">💾 Guardar</button>
          <button type="button" class="btn btn-gris" id="btn-cancelar" style="display:none;">Cancelar edición</button>
        </div>
        <div class="msg-global" id="msg-form"></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="icon">🔄</span><h2 id="titulo-recups">Recuperatorios programados</h2></div>
      <div class="card-body" style="overflow-x:auto;">
        <table>
          <thead><tr><th>Alumno</th><th>Período</th><th>Fecha</th><th>Turno</th><th>Nota</th><th>Estado</th><th></th></tr></thead>
          <tbody id="tbody-recups"></tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="icon">👥</span><h2 id="titulo-materia">Alumnos del curso</h2></div>
      <div class="card-body" style="overflow-x:auto;">
        <table>
          <thead><tr><th>Alumno</th><th>Promedio</th><th>Evaluaciones</th><th>Situación</th><th>Recuperatorios</th><th></th></tr></thead>
          <tbody id="tbody-alumnos"></tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>
</div>

<script>
  const msgGlobal = document.getElementById('msg-global');
  function setMsg(el, texto, color) { el.textContent = texto; el.style.color = color || 'var(--gris-texto)'; }
  function esc(str) {
    if (str === null || str === undefined) return '—';
    const d = document.createElement('div'); d.textContent = String(str); return d.innerHTML;
  }
  function badgeEstado(r) {
    if (r.estado === 'aprobado')    return '<span class="badge badge-verde">Aprobado</span>';
    if (r.estado === 'desaprobado') return '<span class="badge badge-rojo">Desaprobado</span>';
    return '<span class="badge badge-amarillo">Pendiente</span>';
  }
  const turnoTxt = t => t === 'mañana' ? 'Mañana' : (t === 'tarde' ? 'Tarde' : '—');
</script>

<?php if ($es_admin): ?>
<script>
  const fCurso = document.getElementById('f-curso');
  let cursosOk = false;

  async function buscar() {
    setMsg(msgGlobal, 'Cargando…');
    try {
      const res  = await fetch(`recuperatorio_listar.php?curso_id=${encodeURIComponent(fCurso.value)}&estado=${encodeURIComponent(document.getElementById('f-estado').value)}`);
      const data = await res.json();
      if (!data.success) { setMsg(msgGlobal, data.message || 'Error.', '#DC2626'); return; }
      if (!cursosOk) {
        data.cursos.forEach(c => { const o = document.createElement('option'); o.value = c.id; o.textContent = c.nombre; fCurso.appendChild(o); });
        cursosOk = true;
      }
      const r = data.resumen;
      document.getElementById('resumen').innerHTML = `
        <div class="stat"><b>${r.pendiente + r.aprobado + r.desaprobado}</b><span>Total</span></div>
        <div class="stat"><b style="color:#92400E">${r.pendiente}</b><span>Pendientes</span></div>
        <div class="stat"><b style="color:#065F46">${r.aprobado}</b><span>Aprobados</span></div>
        <div class="stat"><b style="color:#991B1B">${r.desaprobado}</b><span>Desaprobados</span></div>`;
      document.getElementById('resumen').style.display = '';
      const tb = document.getElementById('tbody-lista');
      tb.innerHTML = data.recuperatorios.length ? data.recuperatorios.map(x => {
        const vencido = x.estado === 'pendiente' && x.fecha && x.fecha < data.hoy;
        return `<tr class="${vencido ? 'vencido' : ''}">
          <td>${x.fecha_ar ? esc(x.fecha_ar) : '<span class="badge badge-gris">A confirmar</span>'}</td>
          <td>${turnoTxt(x.turno)}</td><td><strong>${esc(x.alumno)}</strong></td><td>${esc(x.curso)}</td>
          <td>${esc(x.materia)}</td><td>${esc(x.docente)}</td><td>${esc(x.periodo)}</td>
          <td>${x.nota ?? '—'}</td><td>${badgeEstado(x)}</td></tr>`;
      }).join('') : '<tr><td colspan="9" class="empty-msg">No hay recuperatorios para ese filtro.</td></tr>';
      document.getElementById('card-lista').style.display = '';
      setMsg(msgGlobal, '');
    } catch {
      setMsg(msgGlobal, 'Error de conexión con el servidor.', '#DC2626');
    }
  }
  document.getElementById('f-buscar').addEventListener('click', buscar);
  document.getElementById('f-estado').addEventListener('change', buscar);
  fCurso.addEventListener('change', buscar);
  buscar();
</script>
<?php else: ?>
<script>
  const selMateria = document.getElementById('sel-materia');
  const msgForm    = document.getElementById('msg-form');
  const rAlumno = document.getElementById('r-alumno'), rPeriodo = document.getElementById('r-periodo'),
        rFecha  = document.getElementById('r-fecha'),  rTurno   = document.getElementById('r-turno'),
        rNota   = document.getElementById('r-nota');
  let materiaActual = null, datos = null, editandoId = 0;

  (async function cargarMaterias() {
    try {
      const res  = await fetch('materias_docente.php');
      const data = await res.json();
      if (!data.success) { setMsg(msgGlobal, data.message || 'No se pudieron cargar tus materias.', '#DC2626'); return; }
      if (!data.materias.length) { setMsg(msgGlobal, 'No tenés materias asignadas.', '#DC2626'); return; }
      data.materias.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id; opt.textContent = `${m.nombre} – ${m.curso_nombre}`;
        selMateria.appendChild(opt);
      });
    } catch { setMsg(msgGlobal, 'Error de conexión con el servidor.', '#DC2626'); }
  })();

  async function cargar() {
    const materia_id = selMateria.value;
    if (!materia_id) { setMsg(msgGlobal, 'Elegí una materia.', '#DC2626'); return; }
    setMsg(msgGlobal, 'Cargando…');
    try {
      const res  = await fetch(`recuperatorio_listar.php?materia_id=${encodeURIComponent(materia_id)}`);
      const data = await res.json();
      if (!data.success) { setMsg(msgGlobal, data.message || 'Error.', '#DC2626'); return; }
      materiaActual = materia_id; datos = data;
      document.getElementById('nota-min').textContent = data.nota_aprobacion;
      document.getElementById('titulo-materia').textContent = `Alumnos del curso — ${data.materia} ${data.curso} (aprueba con ${data.nota_aprobacion})`;
      document.getElementById('titulo-recups').textContent = `Recuperatorios programados — ${data.materia} ${data.curso}`;
      pintarAlumnos(); pintarRecups(); limpiarForm();
      document.getElementById('seccion').style.display = '';
      setMsg(msgGlobal, '');
    } catch { setMsg(msgGlobal, 'Error de conexión con el servidor.', '#DC2626'); }
  }

  function pintarAlumnos() {
    rAlumno.innerHTML = '<option value="">Elegí un alumno…</option>' + datos.alumnos.map(a =>
      `<option value="${a.alumno_id}">${esc(a.nombre)}${a.necesita_recup ? ' ⚠ (prom. ' + a.promedio + ')' : ''}</option>`).join('');
    const tb = document.getElementById('tbody-alumnos');
    if (!datos.alumnos.length) { tb.innerHTML = '<tr><td colspan="6" class="empty-msg">No hay alumnos en el curso.</td></tr>'; return; }
    tb.innerHTML = datos.alumnos.map(a => {
      const sit = a.promedio === null ? '<span class="badge badge-gris">Sin notas</span>'
        : (a.necesita_recup ? '<span class="badge badge-rojo">Debe recuperar</span>' : '<span class="badge badge-verde">Aprobando</span>');
      const rec = a.recups_total ? `${a.recups_total}${a.recups_pendientes ? ` (${a.recups_pendientes} pendiente${a.recups_pendientes > 1 ? 's' : ''})` : ''}` : '—';
      return `<tr><td><strong>${esc(a.nombre)}</strong></td>
        <td class="${a.necesita_recup ? 'alerta-nota' : ''}">${a.promedio ?? '—'}</td><td>${a.evaluaciones}</td>
        <td>${sit}</td><td>${rec}</td>
        <td><button class="btn-mini" data-programar="${a.alumno_id}">+ Programar</button></td></tr>`;
    }).join('');
  }

  function pintarRecups() {
    const tb = document.getElementById('tbody-recups');
    if (!datos.recuperatorios.length) { tb.innerHTML = '<tr><td colspan="7" class="empty-msg">Todavía no programaste recuperatorios en esta materia.</td></tr>'; return; }
    tb.innerHTML = datos.recuperatorios.map(r => {
      const vencido = r.estado === 'pendiente' && r.fecha && r.fecha < datos.hoy;
      return `<tr class="${vencido ? 'vencido' : ''}">
        <td><strong>${esc(r.alumno)}</strong></td><td>${esc(r.periodo)}</td>
        <td>${r.fecha_ar ? esc(r.fecha_ar) : '<span class="badge badge-gris">A confirmar</span>'}${vencido ? '<br><small style="color:#991B1B;font-weight:700;">Falta cargar la nota</small>' : ''}</td>
        <td>${turnoTxt(r.turno)}</td><td>${r.nota ?? '—'}</td><td>${badgeEstado(r)}</td>
        <td style="white-space:nowrap;"><button class="btn-mini" data-editar="${r.id}">Editar</button>
            <button class="btn-mini peligro" data-eliminar="${r.id}">Eliminar</button></td></tr>`;
    }).join('');
  }

  function limpiarForm() {
    editandoId = 0;
    rAlumno.disabled = false; rAlumno.value = '';
    rPeriodo.value = datos ? datos.periodo_actual : 'Anual';
    rFecha.value = ''; rTurno.value = ''; rNota.value = '';
    rFecha.min = datos ? datos.hoy : '';
    document.getElementById('titulo-form').textContent = 'Programar recuperatorio';
    document.getElementById('aviso-edit').style.display = 'none';
    document.getElementById('btn-cancelar').style.display = 'none';
  }

  function editar(id) {
    const r = datos.recuperatorios.find(x => String(x.id) === String(id));
    if (!r) return;
    editandoId = r.id;
    rAlumno.value = r.alumno_id; rAlumno.disabled = true;
    rPeriodo.value = r.periodo; rFecha.value = r.fecha || ''; rTurno.value = r.turno || ''; rNota.value = r.nota ?? '';
    rFecha.min = '';
    document.getElementById('titulo-form').textContent = 'Editar recuperatorio';
    const aviso = document.getElementById('aviso-edit');
    aviso.innerHTML = `Editando el recuperatorio de <strong>${esc(r.alumno)}</strong>. Para cerrarlo, cargá la nota que sacó.`;
    aviso.style.display = '';
    document.getElementById('btn-cancelar').style.display = '';
    setMsg(msgForm, '');
    document.getElementById('card-form').scrollIntoView({ behavior: 'smooth' });
  }

  document.getElementById('seccion').addEventListener('click', async e => {
    const t = e.target;
    if (t.dataset.editar) editar(t.dataset.editar);
    if (t.dataset.programar) {
      limpiarForm(); rAlumno.value = t.dataset.programar; setMsg(msgForm, '');
      document.getElementById('card-form').scrollIntoView({ behavior: 'smooth' });
      rFecha.focus();
    }
    if (t.dataset.eliminar) {
      const r = datos.recuperatorios.find(x => String(x.id) === t.dataset.eliminar);
      if (!t.dataset.confirmar) {   // doble click de confirmación (sin confirm(), que bloquea la UI)
        t.dataset.confirmar = '1'; t.textContent = '¿Seguro?';
        setTimeout(() => { delete t.dataset.confirmar; t.textContent = 'Eliminar'; }, 3000);
        return;
      }
      const body = new FormData(); body.append('id', t.dataset.eliminar);
      try {
        const res = await fetch('recuperatorio_eliminar.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success) { await cargar(); setMsg(msgForm, `🗑️ Se eliminó el recuperatorio de ${r ? r.alumno : 'alumno'}.`, '#059669'); }
        else setMsg(msgForm, data.message, '#DC2626');
      } catch { setMsg(msgForm, 'Error de conexión con el servidor.', '#DC2626'); }
    }
  });

  document.getElementById('btn-guardar').addEventListener('click', async e => {
    if (!editandoId && !rAlumno.value) { setMsg(msgForm, 'Elegí un alumno.', '#DC2626'); return; }
    const btn = e.target; btn.disabled = true; setMsg(msgForm, 'Guardando…');
    const body = new FormData();
    if (editandoId) body.append('id', editandoId);
    body.append('alumno_id', rAlumno.value); body.append('materia_id', materiaActual);
    body.append('periodo', rPeriodo.value); body.append('fecha', rFecha.value);
    body.append('turno', rTurno.value);     body.append('nota', rNota.value);
    try {
      const res = await fetch('recuperatorio_guardar.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) { await cargar(); setMsg(msgForm, '✅ ' + data.message, '#059669'); }
      else setMsg(msgForm, data.message || 'Error al guardar.', '#DC2626');
    } catch { setMsg(msgForm, 'Error de conexión con el servidor.', '#DC2626'); }
    finally { btn.disabled = false; }
  });
  document.getElementById('btn-cancelar').addEventListener('click', () => { limpiarForm(); setMsg(msgForm, ''); });
  document.getElementById('btn-cargar').addEventListener('click', cargar);
  selMateria.addEventListener('change', () => { if (selMateria.value) cargar(); });
</script>
<?php endif; ?>

</body>
</html>
