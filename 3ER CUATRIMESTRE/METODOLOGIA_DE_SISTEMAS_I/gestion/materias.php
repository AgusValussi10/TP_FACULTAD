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
  <title>Asignar Materias – Educar para Transformar</title>
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
    table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    th { background: var(--naranja-bg); color: var(--naranja); font-weight: 800;
         padding: .5rem .7rem; text-align: left; font-size: .8rem; text-transform: uppercase; white-space: nowrap; }
    td { padding: .55rem .7rem; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    .sel-docente { padding: .4rem .6rem; border: 2px solid var(--borde); border-radius: 8px; font-family: inherit; font-size: .85rem; min-width: 200px; }
    .msg { margin: .6rem 0; font-weight: 700; font-size: .88rem; min-height: 1.2em; }
    .empty-msg { color: #9CA3AF; font-size: .9rem; text-align: center; padding: 1.5rem 0; }
    .sin-docente { color: #DC2626; font-weight: 700; font-size: .82rem; }
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
  <h1>Asignar Materias</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge">Panel Administración</span>
</div>

<div class="container">
  <a href="../portals/portal_admin.php" class="volver">&larr; Volver al panel</a>

  <div class="card">
    <div class="card-header"><span class="icon">➕</span><h2>Nueva Materia</h2></div>
    <div class="card-body">
      <div class="form-row">
        <label>Nombre
          <input type="text" id="inp-nombre" placeholder="ej: Historia" maxlength="100" style="min-width:200px;">
        </label>
        <label>Curso
          <select id="sel-curso"><option value="">Elegí un curso…</option></select>
        </label>
        <label>Docente (opcional)
          <select id="sel-docente-nuevo"><option value="">Sin asignar</option></select>
        </label>
        <button type="button" class="btn" id="btn-crear">Crear materia</button>
      </div>
      <div class="msg" id="msg-crear"></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="icon">📖</span><h2>Materias y Docentes Asignados</h2></div>
    <div class="card-body">
      <div class="msg" id="msg-global"></div>
      <table>
        <thead>
          <tr><th>Materia</th><th>Curso</th><th>Docente asignado</th><th></th></tr>
        </thead>
        <tbody id="tbody-materias"><tr><td colspan="4" class="empty-msg">Cargando…</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script>
  const selCurso = document.getElementById('sel-curso');
  const selDocenteNuevo = document.getElementById('sel-docente-nuevo');
  const tbodyMaterias = document.getElementById('tbody-materias');
  const msgGlobal = document.getElementById('msg-global');
  const msgCrear = document.getElementById('msg-crear');
  const btnCrear = document.getElementById('btn-crear');

  let docentesCache = [];

  function esc(str) {
    if (str === null || str === undefined) return '—';
    const d = document.createElement('div'); d.textContent = String(str); return d.innerHTML;
  }

  function setMsg(el, texto, color) {
    el.textContent = texto;
    el.style.color = color || 'var(--gris-texto)';
  }

  function opcionesDocentes(seleccionadoId) {
    let html = `<option value="">Sin asignar</option>`;
    docentesCache.forEach(d => {
      html += `<option value="${d.id}" ${String(d.id) === String(seleccionadoId) ? 'selected' : ''}>${esc(d.nombre)}</option>`;
    });
    return html;
  }

  async function cargar() {
    tbodyMaterias.innerHTML = '<tr><td colspan="4" class="empty-msg">Cargando…</td></tr>';
    try {
      const res  = await fetch('materias_listar.php');
      const data = await res.json();
      if (!data.success) { tbodyMaterias.innerHTML = `<tr><td colspan="4" class="empty-msg">${esc(data.message)}</td></tr>`; return; }

      docentesCache = data.docentes;

      if (!selCurso.options.length) {
        selCurso.innerHTML = '<option value="">Elegí un curso…</option>' +
          data.cursos.map(c => `<option value="${c.id}">${esc(c.nombre)} — Nivel ${esc(c.nivel_educativo)}</option>`).join('');
      }
      selDocenteNuevo.innerHTML = opcionesDocentes('');

      if (!data.materias.length) {
        tbodyMaterias.innerHTML = '<tr><td colspan="4" class="empty-msg">No hay materias creadas todavía.</td></tr>';
        return;
      }

      tbodyMaterias.innerHTML = data.materias.map(m => `
        <tr data-materia-id="${m.id}">
          <td><strong>${esc(m.nombre)}</strong></td>
          <td>${esc(m.curso_nombre)}</td>
          <td>
            <select class="sel-docente">${opcionesDocentes(m.docente_id)}</select>
            ${!m.docente_id ? '<div class="sin-docente">Sin docente</div>' : ''}
          </td>
          <td><button type="button" class="btn btn-guardar-materia">Guardar</button></td>
        </tr>
      `).join('');
    } catch {
      tbodyMaterias.innerHTML = '<tr><td colspan="4" class="empty-msg">Error de conexión.</td></tr>';
    }
  }

  tbodyMaterias.addEventListener('click', async (e) => {
    if (!e.target.classList.contains('btn-guardar-materia')) return;
    const tr = e.target.closest('tr');
    const materia_id = tr.dataset.materiaId;
    const docente_id = tr.querySelector('.sel-docente').value;

    e.target.disabled = true;
    setMsg(msgGlobal, 'Guardando...');

    const body = new FormData();
    body.append('materia_id', materia_id);
    body.append('docente_id', docente_id || '0');

    try {
      const res  = await fetch('materias_asignar.php', { method: 'POST', body });
      const data = await res.json();
      setMsg(msgGlobal, data.success ? '✅ ' + data.message : data.message || 'Error.', data.success ? '#059669' : '#DC2626');
      if (data.success) cargar();
    } catch {
      setMsg(msgGlobal, 'Error de conexión.', '#DC2626');
    } finally {
      e.target.disabled = false;
    }
  });

  btnCrear.addEventListener('click', async () => {
    const nombreVal = document.getElementById('inp-nombre').value.trim();
    const curso_id  = selCurso.value;
    const docente_id = selDocenteNuevo.value;

    if (!nombreVal || !curso_id) { setMsg(msgCrear, 'Completá el nombre y el curso.', '#DC2626'); return; }

    btnCrear.disabled = true;
    setMsg(msgCrear, 'Creando...');

    const body = new FormData();
    body.append('nombre', nombreVal);
    body.append('curso_id', curso_id);
    body.append('docente_id', docente_id || '0');

    try {
      const res  = await fetch('materias_crear.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        setMsg(msgCrear, '✅ Materia creada.', '#059669');
        document.getElementById('inp-nombre').value = '';
        selCurso.value = '';
        selDocenteNuevo.value = '';
        cargar();
      } else {
        setMsg(msgCrear, data.message || 'Error al crear.', '#DC2626');
      }
    } catch {
      setMsg(msgCrear, 'Error de conexión.', '#DC2626');
    } finally {
      btnCrear.disabled = false;
    }
  });

  cargar();
</script>

</body>
</html>
