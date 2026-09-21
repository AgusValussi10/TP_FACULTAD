<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'docente') {
    header('Location: /');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Docente');
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
    <span class="user-badge">🎓 Docente</span>
    <a href="../auth/logout.php" class="btn-logout">Cerrar sesión</a>
  </div>
</header>

<div class="welcome">
  <h1>Exámenes Recuperatorios</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge">Portal Docente</span>
</div>

<div class="container">
  <a href="../portals/portal_docente.php" class="volver">&larr; Volver al portal</a>

  <div class="card">
    <div class="card-header"><span class="icon">📋</span><h2>Seleccionar Materia</h2></div>
    <div class="card-body">
      <div class="form-row">
        <label>Materia
          <select id="sel-materia"><option value="">Elegí una materia…</option></select>
        </label>
        <button type="button" class="btn" id="btn-cargar">Ver alumnos</button>
      </div>
      <div class="msg-global" id="msg-global"></div>
    </div>
  </div>

  <div id="seccion-alumnos" style="display:none;">
    <div class="card">
      <div class="card-header"><span class="icon">🔄</span><h2 id="titulo-materia">Alumnos</h2></div>
      <div class="card-body" style="overflow-x:auto;">
        <table>
          <thead>
            <tr>
              <th>Alumno</th>
              <th>Promedio</th>
              <th>Período</th>
              <th>Fecha examen</th>
              <th>Turno</th>
              <th>Estado</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="tbody-alumnos"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  const selMateria     = document.getElementById('sel-materia');
  const btnCargar      = document.getElementById('btn-cargar');
  const seccionAlumnos = document.getElementById('seccion-alumnos');
  const tituloMateria  = document.getElementById('titulo-materia');
  const tbodyAlumnos   = document.getElementById('tbody-alumnos');
  const msgGlobal      = document.getElementById('msg-global');

  let materiaActual = null;

  function setMsg(texto, color) {
    msgGlobal.textContent = texto;
    msgGlobal.style.color = color || 'var(--gris-texto)';
  }

  (async function cargarMaterias() {
    try {
      const res  = await fetch('materias_docente.php');
      const data = await res.json();
      if (!data.success) return;
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
    if (!materia_id) { setMsg('Elegí una materia.', '#DC2626'); return; }
    setMsg('Cargando…');
    seccionAlumnos.style.display = 'none';
    materiaActual = materia_id;

    try {
      const res  = await fetch(`recuperatorio_listar.php?materia_id=${encodeURIComponent(materia_id)}`);
      const data = await res.json();
      if (!data.success) { setMsg(data.message || 'Error.', '#DC2626'); return; }

      tituloMateria.textContent = `Alumnos – ${data.materia} (nota mínima: ${data.nota_aprobacion})`;
      tbodyAlumnos.innerHTML = '';

      if (data.alumnos.length === 0) {
        tbodyAlumnos.innerHTML = '<tr><td colspan="7" class="empty-msg">No hay alumnos en el curso.</td></tr>';
      } else {
        data.alumnos.forEach(a => {
          const necesita = a.necesita_recup;
          const promStr  = a.promedio !== null ? a.promedio : '—';
          const promClass = necesita ? 'alerta-nota' : '';

          const tr = document.createElement('tr');
          tr.dataset.alumnoId = a.alumno_id;

          tr.innerHTML = `
            <td>${escHtml(a.nombre)}</td>
            <td class="${promClass}">${promStr}</td>
            <td>
              <select class="sel-periodo">
                <option value="1er Trimestre" ${a.periodo === '1er Trimestre' ? 'selected' : ''}>1er Trimestre</option>
                <option value="2do Trimestre" ${a.periodo === '2do Trimestre' ? 'selected' : ''}>2do Trimestre</option>
                <option value="3er Trimestre" ${a.periodo === '3er Trimestre' ? 'selected' : ''}>3er Trimestre</option>
                <option value="Anual" ${(a.periodo === 'Anual' || !a.periodo) ? 'selected' : ''}>Anual</option>
              </select>
            </td>
            <td><input type="date" class="input-fecha" value="${a.recup_fecha ? isoFromAr(a.recup_fecha) : ''}"></td>
            <td>
              <select class="sel-turno">
                <option value="">—</option>
                <option value="mañana" ${a.turno === 'mañana' ? 'selected' : ''}>Mañana</option>
                <option value="tarde"  ${a.turno === 'tarde'  ? 'selected' : ''}>Tarde</option>
              </select>
            </td>
            <td>
              <select class="sel-estado">
                <option value="pendiente"   ${(a.recup_estado === 'pendiente'   || !a.recup_estado) ? 'selected' : ''}>Pendiente</option>
                <option value="aprobado"    ${a.recup_estado === 'aprobado'    ? 'selected' : ''}>Aprobado</option>
                <option value="desaprobado" ${a.recup_estado === 'desaprobado' ? 'selected' : ''}>Desaprobado</option>
              </select>
            </td>
            <td><button class="btn-sm btn-guardar-recup">Guardar</button></td>
          `;
          tbodyAlumnos.appendChild(tr);
        });
      }

      seccionAlumnos.style.display = '';
      setMsg('');
    } catch {
      setMsg('Error de conexión.', '#DC2626');
    }
  });

  tbodyAlumnos.addEventListener('click', async (e) => {
    if (!e.target.classList.contains('btn-guardar-recup')) return;
    const tr        = e.target.closest('tr');
    const alumno_id = tr.dataset.alumnoId;
    const periodo   = tr.querySelector('.sel-periodo').value;
    const fecha     = tr.querySelector('.input-fecha').value;
    const turno     = tr.querySelector('.sel-turno').value;
    const estado    = tr.querySelector('.sel-estado').value;

    e.target.disabled = true;
    e.target.textContent = '…';

    const body = new FormData();
    body.append('alumno_id',  alumno_id);
    body.append('materia_id', materiaActual);
    body.append('periodo',    periodo);
    body.append('fecha',      fecha);
    body.append('turno',      turno);
    body.append('estado',     estado);

    try {
      const res  = await fetch('recuperatorio_guardar.php', { method: 'POST', body });
      const data = await res.json();
      setMsg(data.success ? '✅ ' + data.message : data.message || 'Error.', data.success ? '#059669' : '#DC2626');
    } catch {
      setMsg('Error de conexión.', '#DC2626');
    } finally {
      e.target.disabled = false;
      e.target.textContent = 'Guardar';
    }
  });

  function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  function isoFromAr(dateAr) {
    if (!dateAr) return '';
    const [d, m, y] = dateAr.split('/');
    return `${y}-${m}-${d}`;
  }
</script>

</body>
</html>
