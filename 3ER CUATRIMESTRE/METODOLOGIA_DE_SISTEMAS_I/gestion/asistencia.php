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
  <title>Cargar Asistencia – Educar para Transformar</title>
  <link rel="icon" href="../assets/logo.avif">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Merriweather:wght@700&display=swap" rel="stylesheet">
  <style>
    :root {
      --verde:       #F97316;
      --verde-claro: #FB923C;
      --verde-bg:    #FFF7ED;
      --azul:        #374151;
      --amarillo:    #F97316;
      --naranja:     #EA580C;
      --gris-texto:  #111827;
      --blanco:      #FFFFFF;
      --borde:       #E5E7EB;
      --sombra:      0 4px 18px rgba(249,115,22,.13);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Nunito', sans-serif; color: var(--gris-texto); background: #F9FAFB; }

    header {
      background: var(--blanco);
      color: var(--gris-texto);
      padding: .75rem 1.5rem;
      display: flex; align-items: center; justify-content: space-between;
      box-shadow: 0 2px 12px rgba(249,115,22,.12);
      border-bottom: 1px solid rgba(249,115,22,.15);
      position: sticky; top: 0; z-index: 100;
    }
    .logo-area { display: flex; align-items: center; gap: .7rem; text-decoration: none; color: inherit; }
    .logo-circle { height: 48px; width: auto; border-radius: 6px; object-fit: contain; }
    .logo-text strong { display: block; font-size: .95rem; font-weight: 900; color: var(--gris-texto); }
    .logo-text span   { font-size: .7rem; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; }
    .user-info { display: flex; align-items: center; gap: 1rem; }
    .user-badge {
      background: var(--verde-bg); color: var(--verde);
      border: 1px solid var(--borde); border-radius: 20px;
      padding: .35rem .9rem; font-size: .85rem; font-weight: 700;
    }
    .btn-logout {
      background: #C2410C; color: #FFFFFF;
      border: none; border-radius: 20px; padding: .4rem 1rem;
      font-weight: 900; font-size: .85rem; cursor: pointer;
      text-decoration: none; transition: opacity .2s;
      box-shadow: 0 2px 8px rgba(194,65,12,.25);
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
      color: var(--amarillo); border-radius: 20px;
      padding: .25rem .9rem; font-size: .78rem; font-weight: 800;
      text-transform: uppercase; letter-spacing: .1em;
    }

    .container { max-width: 800px; margin: 2rem auto; padding: 0 1.5rem 3rem; }
    .card { background: var(--blanco); border-radius: 16px; box-shadow: var(--sombra); overflow: hidden; }
    .card-header {
      padding: 1.1rem 1.5rem; display: flex; align-items: center; gap: .7rem;
      border-bottom: 2px solid var(--borde);
    }
    .card-header .icon { font-size: 1.5rem; }
    .card-header h2 { font-size: 1rem; font-weight: 800; color: var(--azul); }
    .card-body { padding: 1.2rem 1.5rem; }

    .volver { display: inline-block; margin-bottom: 1rem; color: var(--azul); font-weight: 700; font-size: .85rem; text-decoration: none; }
    .volver:hover { text-decoration: underline; }

    .form-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; margin-bottom: 1rem; }
    .form-row label { display: flex; flex-direction: column; gap: .3rem; font-size: .82rem; font-weight: 800; color: var(--azul); }
    .form-row select, .form-row input[type="date"] {
      padding: .55rem .7rem; border: 2px solid var(--borde); border-radius: 8px;
      font-family: inherit; font-size: .9rem; min-width: 220px;
    }
    .btn {
      background: var(--verde); color: var(--blanco); border: none; border-radius: 10px;
      padding: .6rem 1.2rem; font-weight: 800; font-size: .88rem; cursor: pointer;
      font-family: inherit; transition: opacity .2s;
    }
    .btn:hover { opacity: .88; }
    .btn:disabled { opacity: .6; cursor: not-allowed; }

    table { width: 100%; border-collapse: collapse; font-size: .88rem; margin-top: .5rem; }
    th { background: var(--verde-bg); color: var(--verde); font-weight: 800;
         padding: .5rem .7rem; text-align: left; font-size: .8rem; text-transform: uppercase; }
    td { padding: .6rem .7rem; border-bottom: 1px solid #F3F4F6; }
    tr:last-child td { border-bottom: none; }
    .estado-opciones label { margin-right: .9rem; font-weight: 600; font-size: .85rem; cursor: pointer; }

    .msg { margin: .6rem 0; font-weight: 700; font-size: .88rem; min-height: 1.2em; }
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
  <h1>Cargar Asistencia</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge">Portal Docente</span>
</div>

<div class="container">
  <a href="../portals/portal_docente.php" class="volver">&larr; Volver al portal</a>
  <div class="card">
    <div class="card-header"><span class="icon">📋</span><h2>Asistencia diaria</h2></div>
    <div class="card-body">
      <div class="form-row">
        <label>Materia
          <select id="sel-materia"><option value="">Elegí una materia…</option></select>
        </label>
        <label>Fecha
          <input type="date" id="sel-fecha">
        </label>
        <button type="button" class="btn" id="btn-cargar">Cargar listado</button>
      </div>
      <div class="msg" id="msg"></div>
      <table id="tabla-asistencia" style="display:none;">
        <thead><tr><th>Alumno</th><th>Estado</th></tr></thead>
        <tbody id="tbody-asistencia"></tbody>
      </table>
      <button type="button" class="btn" id="btn-guardar" style="display:none; margin-top:1rem;">💾 Guardar asistencia</button>
    </div>
  </div>
</div>

<script>
  const selMateria = document.getElementById('sel-materia');
  const selFecha   = document.getElementById('sel-fecha');
  const btnCargar  = document.getElementById('btn-cargar');
  const btnGuardar = document.getElementById('btn-guardar');
  const tabla      = document.getElementById('tabla-asistencia');
  const tbody      = document.getElementById('tbody-asistencia');
  const msg        = document.getElementById('msg');

  const hoy = new Date().toISOString().slice(0, 10);
  selFecha.value = hoy;
  selFecha.max   = hoy;

  const ESTADOS       = ['presente', 'ausente', 'tarde'];
  const ESTADO_LABEL  = { presente: 'Presente', ausente: 'Ausente', tarde: 'Tarde' };

  let materiaActual = null;
  let yaCargada = false;

  function setMsg(texto, color) {
    msg.textContent = texto;
    msg.style.color = color || 'var(--gris-texto)';
  }

  (async function cargarMaterias() {
    try {
      const res  = await fetch('materias_docente.php');
      const data = await res.json();
      if (!data.success) { setMsg(data.message || 'Error al cargar materias.', '#DC2626'); return; }
      data.materias.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = `${m.nombre} – ${m.curso_nombre}`;
        selMateria.appendChild(opt);
      });
    } catch {
      setMsg('Error de conexión al cargar materias.', '#DC2626');
    }
  })();

  btnCargar.addEventListener('click', async () => {
    const materia_id = selMateria.value;
    const fecha = selFecha.value;
    if (!materia_id || !fecha) { setMsg('Elegí materia y fecha.', '#DC2626'); return; }

    setMsg('Cargando...');
    tabla.style.display = 'none';
    btnGuardar.style.display = 'none';

    try {
      const res  = await fetch(`asistencia_listar.php?materia_id=${encodeURIComponent(materia_id)}&fecha=${encodeURIComponent(fecha)}`);
      const data = await res.json();
      if (!data.success) { setMsg(data.message || 'Error al cargar el listado.', '#DC2626'); return; }

      materiaActual = materia_id;
      yaCargada = !!data.ya_cargada;
      tbody.innerHTML = '';
      data.alumnos.forEach(a => {
        const tr = document.createElement('tr');
        tr.dataset.alumnoId = a.alumno_id;

        const tdNombre = document.createElement('td');
        tdNombre.textContent = a.nombre;

        const tdEstado = document.createElement('td');
        tdEstado.className = 'estado-opciones';
        tdEstado.innerHTML = ESTADOS.map(e =>
          `<label><input type="radio" name="estado-${a.alumno_id}" value="${e}" ${a.estado === e ? 'checked' : ''}> ${ESTADO_LABEL[e]}</label>`
        ).join('');

        tr.appendChild(tdNombre);
        tr.appendChild(tdEstado);
        tbody.appendChild(tr);
      });

      tabla.style.display = '';
      btnGuardar.style.display = '';
      if (yaCargada) {
        setMsg('⚠️ Ya existe asistencia cargada para esta fecha. Guardar sobreescribirá los datos existentes.', '#D97706');
      } else {
        setMsg('');
      }
    } catch {
      setMsg('Error de conexión al cargar el listado.', '#DC2626');
    }
  });

  btnGuardar.addEventListener('click', async () => {
    const estados = {};
    tbody.querySelectorAll('tr').forEach(tr => {
      const checked = tr.querySelector('input[type="radio"]:checked');
      if (checked) estados[tr.dataset.alumnoId] = checked.value;
    });

    if (Object.keys(estados).length === 0) { setMsg('Marcá al menos un estado.', '#DC2626'); return; }

    if (yaCargada && !confirm('Ya existe asistencia cargada para esta fecha. ¿Querés sobreescribirla?')) {
      return;
    }

    btnGuardar.disabled = true;
    setMsg('Guardando...');

    const body = new FormData();
    body.append('materia_id', materiaActual);
    body.append('fecha', selFecha.value);
    body.append('estados', JSON.stringify(estados));

    try {
      const res  = await fetch('asistencia_guardar.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        const accion = yaCargada ? 'actualizada' : 'guardada';
        setMsg(`✅ Asistencia ${accion} (${data.guardados} alumno${data.guardados === 1 ? '' : 's'}).`, '#059669');
        yaCargada = true;
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

</body>
</html>
