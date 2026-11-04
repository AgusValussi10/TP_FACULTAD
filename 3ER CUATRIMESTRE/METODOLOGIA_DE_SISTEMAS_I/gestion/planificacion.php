<?php
require_once '../auth/session.php';
$rol_sesion = $_SESSION['rol'] ?? '';
if (!in_array($rol_sesion, ['docente', 'admin'], true)) {
    header('Location: /');
    exit;
}
$es_admin   = $rol_sesion === 'admin';
$nombre     = htmlspecialchars($_SESSION['nombre'] ?? ($es_admin ? 'Administrador' : 'Docente'));
$volver_url = $es_admin ? '../portals/portal_admin.php' : '../portals/portal_docente.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Planificación Anual – Educar para Transformar</title>
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

    .container { max-width: 860px; margin: 2rem auto; padding: 0 1.5rem 3rem; }
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
    .form-row select, .form-row input[type="number"] {
      padding: .55rem .7rem; border: 2px solid var(--borde); border-radius: 8px;
      font-family: inherit; font-size: .9rem; min-width: 180px;
    }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-size: .82rem; font-weight: 800; color: var(--azul); margin-bottom: .4rem; }
    .form-group textarea {
      width: 100%; padding: .7rem; border: 2px solid var(--borde); border-radius: 8px;
      font-family: inherit; font-size: .9rem; resize: vertical; min-height: 160px;
    }
    .form-group textarea:focus { outline: none; border-color: var(--naranja); }
    .hint { font-size: .78rem; color: #6B7280; margin-top: .3rem; }
    .btn {
      background: var(--naranja); color: var(--blanco); border: none; border-radius: 10px;
      padding: .6rem 1.4rem; font-weight: 800; font-size: .9rem; cursor: pointer;
      font-family: inherit; transition: opacity .2s;
    }
    .btn:hover { opacity: .88; }
    .btn:disabled { opacity: .6; cursor: not-allowed; }
    .msg { margin: .8rem 0; font-weight: 700; font-size: .88rem; min-height: 1.2em; }

    .plan-existente {
      background: var(--naranja-bg); border-left: 4px solid var(--naranja);
      border-radius: 0 10px 10px 0; padding: .8rem 1rem;
      font-size: .85rem; margin-bottom: 1rem;
    }
    .plan-existente strong { color: var(--azul); }

    /* Vista de coordinación (admin) */
    .container.ancho { max-width: 1100px; }
    .resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .resumen .stat { background: var(--blanco); border-radius: 14px; box-shadow: var(--sombra); padding: 1rem 1.2rem; }
    .resumen .stat b { display: block; font-size: 1.6rem; font-weight: 900; color: var(--azul); }
    .resumen .stat span { font-size: .8rem; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: .05em; }
    .resumen .stat.ok b { color: #059669; }
    .resumen .stat.pend b { color: #DC2626; }
    .barra { height: 8px; background: var(--borde); border-radius: 6px; overflow: hidden; margin-top: .5rem; }
    .barra i { display: block; height: 100%; background: #059669; }
    table { width: 100%; border-collapse: collapse; font-size: .86rem; }
    th { text-align: left; padding: .6rem .7rem; background: #F3F4F6; font-weight: 800; color: var(--azul); font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; }
    td { padding: .6rem .7rem; border-bottom: 1px solid var(--borde); vertical-align: top; }
    .badge { display: inline-block; padding: .2rem .65rem; border-radius: 12px; font-size: .75rem; font-weight: 800; white-space: nowrap; }
    .badge-verde { background: #D1FAE5; color: #065F46; }
    .badge-rojo  { background: #FEE2E2; color: #991B1B; }
    .btn-sm { background: var(--blanco); color: var(--azul); border: 2px solid var(--borde); border-radius: 8px; padding: .3rem .8rem; font-weight: 800; font-size: .8rem; cursor: pointer; font-family: inherit; }
    .btn-sm:hover { border-color: var(--naranja); color: var(--naranja); }
    .detalle-plan td { background: var(--naranja-bg); line-height: 1.5; }
    .detalle-plan .texto { white-space: pre-wrap; }
    .detalle-plan h4 { font-size: .8rem; text-transform: uppercase; color: var(--azul); margin: .2rem 0 .3rem; }
    .empty-msg { color: #6B7280; font-style: italic; padding: .5rem 0; }
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
  <h1>Planificación Anual</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge"><?= $es_admin ? 'Coordinación · Administración' : 'Portal Docente' ?></span>
</div>

<?php if ($es_admin) : ?>
<div class="container ancho">
  <a href="<?= $volver_url ?>" class="volver">&larr; Volver al portal</a>

  <div class="card">
    <div class="card-header"><span class="icon">📁</span><h2>Control de planificaciones anuales</h2></div>
    <div class="card-body">
      <div class="form-row" style="margin-bottom:0;">
        <label>Año lectivo
          <input type="number" id="adm-anio" value="<?= date('Y') ?>" min="2024" max="2100">
        </label>
        <label>Curso
          <select id="adm-curso"><option value="0">Todos los cursos</option></select>
        </label>
        <label>Estado
          <select id="adm-estado">
            <option value="">Todas</option>
            <option value="cargada">Cargadas</option>
            <option value="pendiente">Pendientes</option>
          </select>
        </label>
        <button type="button" class="btn" id="adm-buscar">Ver</button>
      </div>
      <div class="msg" id="adm-msg"></div>
    </div>
  </div>

  <div class="resumen" id="adm-resumen" style="display:none;"></div>

  <div class="card" id="adm-tabla-card" style="display:none;">
    <div class="card-header"><span class="icon">📚</span><h2 id="adm-titulo">Materias</h2></div>
    <div class="card-body" style="overflow-x:auto;">
      <table>
        <thead><tr><th>Curso</th><th>Materia</th><th>Docente</th><th>Estado</th><th>Última carga</th><th></th></tr></thead>
        <tbody id="adm-tbody"></tbody>
      </table>
    </div>
  </div>
</div>
<?php else : ?>
<div class="container">
  <a href="<?= $volver_url ?>" class="volver">&larr; Volver al portal</a>

  <div class="card">
    <div class="card-header"><span class="icon">📁</span><h2>Cargar / Actualizar Planificación</h2></div>
    <div class="card-body">
      <div class="form-row">
        <label>Materia
          <select id="sel-materia"><option value="">Elegí una materia…</option></select>
        </label>
        <label>Año lectivo
          <input type="number" id="sel-anio" value="<?= date('Y') ?>" min="2024" max="2100">
        </label>
        <button type="button" class="btn" id="btn-cargar">Cargar</button>
      </div>
      <div class="msg" id="msg"></div>

      <div id="plan-existente" style="display:none;" class="plan-existente">
        <strong>Ya existe una planificación para esta materia y año</strong> (cargada el <span id="fecha-carga"></span><span id="fecha-act"></span>).
        Podés editarla y guardar nuevamente.
      </div>

      <div id="form-plan" style="display:none;">
        <div class="form-group">
          <label for="txt-contenidos">Contenidos anuales *</label>
          <textarea id="txt-contenidos" placeholder="Describí los contenidos de la planificación anual para esta materia…"></textarea>
          <p class="hint">Mínimo 20 caracteres.</p>
        </div>
        <div class="form-group">
          <label for="txt-obs">Observaciones (opcional)</label>
          <textarea id="txt-obs" style="min-height:80px;" placeholder="Recursos, metodología, adaptaciones, etc."></textarea>
        </div>
        <button type="button" class="btn" id="btn-guardar">💾 Guardar planificación</button>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<?php if ($es_admin) : ?>
<script>
  const admAnio = document.getElementById('adm-anio');
  const admCurso = document.getElementById('adm-curso');
  const admEstado = document.getElementById('adm-estado');
  const admMsg = document.getElementById('adm-msg');
  const admTbody = document.getElementById('adm-tbody');
  const admResumen = document.getElementById('adm-resumen');
  const admCard = document.getElementById('adm-tabla-card');
  let cursosCargados = false;
  let ultimaLista = [];

  function esc(str) {
    if (str === null || str === undefined) return '—';
    const d = document.createElement('div'); d.textContent = String(str); return d.innerHTML;
  }

  async function buscar() {
    admMsg.textContent = 'Cargando…'; admMsg.style.color = '';
    try {
      const res = await fetch(`planificacion_listar.php?anio=${encodeURIComponent(admAnio.value)}&curso_id=${encodeURIComponent(admCurso.value)}`);
      const data = await res.json();
      if (!data.success) { admMsg.textContent = data.message || 'Error al cargar.'; admMsg.style.color = '#DC2626'; return; }

      if (!cursosCargados) {
        data.cursos.forEach(c => { const o = document.createElement('option'); o.value = c.id; o.textContent = c.nombre; admCurso.appendChild(o); });
        cursosCargados = true;
      }
      ultimaLista = data.materias;
      const r = data.resumen;
      const pct = r.total ? Math.round(r.cargadas * 100 / r.total) : 0;
      admResumen.innerHTML = `
        <div class="stat"><b>${r.total}</b><span>Materias</span></div>
        <div class="stat ok"><b>${r.cargadas}</b><span>Con planificación</span></div>
        <div class="stat pend"><b>${r.pendientes}</b><span>Pendientes</span></div>
        <div class="stat"><b>${pct}%</b><span>Avance ${data.anio}</span><div class="barra"><i style="width:${pct}%"></i></div></div>`;
      admResumen.style.display = '';
      document.getElementById('adm-titulo').textContent = `Materias — ciclo lectivo ${data.anio}`;
      pintar();
      admCard.style.display = '';
      admMsg.textContent = '';
    } catch {
      admMsg.textContent = 'Error de conexión con el servidor.'; admMsg.style.color = '#DC2626';
    }
  }

  function pintar() {
    const filtro = admEstado.value;
    const filas = ultimaLista.filter(m => !filtro || (filtro === 'cargada') === (m.plan_id !== null));
    if (!filas.length) { admTbody.innerHTML = '<tr><td colspan="6" class="empty-msg">No hay materias para ese filtro.</td></tr>'; return; }
    admTbody.innerHTML = filas.map(m => {
      const tiene = m.plan_id !== null;
      const fecha = tiene ? (m.fecha_actualizacion ? `${m.fecha_actualizacion} (editada)` : m.fecha_carga) : '—';
      return `<tr>
        <td>${esc(m.curso)}</td>
        <td><strong>${esc(m.materia)}</strong></td>
        <td>${m.docente ? esc(m.docente) : '<span class="badge badge-rojo">Sin docente</span>'}</td>
        <td>${tiene ? '<span class="badge badge-verde">Cargada</span>' : '<span class="badge badge-rojo">Pendiente</span>'}</td>
        <td>${fecha}</td>
        <td>${tiene ? `<button class="btn-sm" data-ver="${m.materia_id}">Ver</button>` : ''}</td>
      </tr>
      ${tiene ? `<tr class="detalle-plan" id="det-${m.materia_id}" style="display:none;"><td colspan="6">
        <h4>Contenidos</h4><div class="texto">${esc(m.contenidos)}</div>
        ${m.observaciones ? `<h4 style="margin-top:.8rem;">Observaciones</h4><div class="texto">${esc(m.observaciones)}</div>` : ''}
      </td></tr>` : ''}`;
    }).join('');
  }

  admTbody.addEventListener('click', e => {
    const id = e.target.dataset.ver;
    if (!id) return;
    const fila = document.getElementById('det-' + id);
    const abierta = fila.style.display !== 'none';
    fila.style.display = abierta ? 'none' : '';
    e.target.textContent = abierta ? 'Ver' : 'Ocultar';
  });
  document.getElementById('adm-buscar').addEventListener('click', buscar);
  admEstado.addEventListener('change', () => { if (ultimaLista.length) pintar(); });
  buscar();
</script>
<?php else : ?>
<script>
  const selMateria  = document.getElementById('sel-materia');
  const selAnio     = document.getElementById('sel-anio');
  const btnCargar   = document.getElementById('btn-cargar');
  const btnGuardar  = document.getElementById('btn-guardar');
  const formPlan    = document.getElementById('form-plan');
  const planExist   = document.getElementById('plan-existente');
  const fechaCarga  = document.getElementById('fecha-carga');
  const txtContenidos = document.getElementById('txt-contenidos');
  const txtObs      = document.getElementById('txt-obs');
  const msg         = document.getElementById('msg');

  function setMsg(texto, color) {
    msg.textContent = texto;
    msg.style.color = color || 'var(--gris-texto)';
  }

  (async function cargarMaterias() {
    try {
      const res  = await fetch('materias_docente.php');
      const data = await res.json();
      if (!data.success) { return; }
      if (!data.materias.length) { setMsg('No tenés materias asignadas. Pedile al administrador que te asigne una.', '#DC2626'); return; }
      data.materias.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = `${m.nombre} – ${m.curso_nombre}`;
        selMateria.appendChild(opt);
      });
    } catch {}
  })();

  btnCargar.addEventListener('click', async () => {
    const materia_id = selMateria.value;
    const anio = selAnio.value;
    if (!materia_id || !anio) { setMsg('Elegí una materia y un año.', '#DC2626'); return; }

    setMsg('Cargando…');
    formPlan.style.display = 'none';
    planExist.style.display = 'none';

    try {
      const res  = await fetch(`planificacion_listar.php?materia_id=${encodeURIComponent(materia_id)}&anio=${encodeURIComponent(anio)}`);
      const data = await res.json();
      if (!data.success) { setMsg(data.message || 'Error al cargar.', '#DC2626'); return; }

      if (data.plan) {
        txtContenidos.value = data.plan.contenidos;
        txtObs.value = data.plan.observaciones || '';
        fechaCarga.textContent = data.plan.fecha_carga;
        document.getElementById('fecha-act').textContent = data.plan.fecha_actualizacion ? `, última edición ${data.plan.fecha_actualizacion}` : '';
        planExist.style.display = '';
      } else {
        txtContenidos.value = '';
        txtObs.value = '';
        planExist.style.display = 'none';
      }

      formPlan.style.display = '';
      setMsg('');
    } catch {
      setMsg('Error de conexión con el servidor.', '#DC2626');
    }
  });

  btnGuardar.addEventListener('click', async () => {
    const materia_id   = selMateria.value;
    const anio         = selAnio.value;
    const contenidos   = txtContenidos.value.trim();
    const observaciones = txtObs.value.trim();

    if (!materia_id || !anio) { setMsg('Elegí una materia y un año.', '#DC2626'); return; }
    if (contenidos.length < 20) { setMsg('Los contenidos deben tener al menos 20 caracteres.', '#DC2626'); return; }

    btnGuardar.disabled = true;
    setMsg('Guardando…');

    const body = new FormData();
    body.append('materia_id', materia_id);
    body.append('anio', anio);
    body.append('contenidos', contenidos);
    body.append('observaciones', observaciones);

    try {
      const res  = await fetch('planificacion_guardar.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        setMsg('✅ ' + data.message, '#059669');
        if (planExist.style.display === 'none') {
          fechaCarga.textContent = new Date().toLocaleDateString('es-AR');
          document.getElementById('fecha-act').textContent = '';
        } else {
          document.getElementById('fecha-act').textContent = `, última edición ${new Date().toLocaleDateString('es-AR')}`;
        }
        planExist.style.display = '';
      } else {
        setMsg(data.message || 'Error al guardar.', '#DC2626');
      }
    } catch {
      setMsg('Error de conexión al guardar.', '#DC2626');
    } finally {
      btnGuardar.disabled = false;
    }
  });
</script>
<?php endif; ?>

</body>
</html>
