<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'enfermeria') {
    header('Location: /');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Enfermería');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrar Atención – Educar para Transformar</title>
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

    .container { max-width: 700px; margin: 2rem auto; padding: 0 1.5rem 3rem; }
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

    .campo { margin-bottom: 1rem; }
    .campo label { display: block; font-size: .82rem; font-weight: 800; color: var(--azul); margin-bottom: .35rem; }
    .campo select, .campo input, .campo textarea {
      width: 100%; padding: .6rem .8rem; border: 2px solid var(--borde); border-radius: 8px;
      font-family: inherit; font-size: .9rem;
    }
    .campo select:focus, .campo input:focus, .campo textarea:focus { outline: none; border-color: var(--verde); }
    .fila-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

    .btn {
      background: var(--verde); color: var(--blanco); border: none; border-radius: 10px;
      padding: .6rem 1.2rem; font-weight: 800; font-size: .88rem; cursor: pointer;
      font-family: inherit; transition: opacity .2s;
    }
    .btn:hover { opacity: .88; }
    .btn:disabled { opacity: .6; cursor: not-allowed; }

    .msg { margin: .6rem 0; font-weight: 700; font-size: .88rem; min-height: 1.2em; }

    @media (max-width: 500px) { .fila-2 { grid-template-columns: 1fr; } }
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
    <span class="user-badge">⚕️ Enfermería</span>
    <a href="../auth/logout.php" class="btn-logout">Cerrar sesión</a>
  </div>
</header>

<div class="welcome">
  <h1>Registrar Atención</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge">Portal Enfermería</span>
</div>

<div class="container">
  <a href="../portals/portal_enfermeria.php" class="volver">&larr; Volver al portal</a>
  <div class="card">
    <div class="card-header"><span class="icon">🩺</span><h2>Atención de enfermería</h2></div>
    <div class="card-body">
      <div class="campo">
        <label>Alumno/a *</label>
        <input type="text" id="sel-alumno-buscar" list="dl-alumnos" placeholder="Escribí para buscar…" autocomplete="off">
        <datalist id="dl-alumnos"></datalist>
        <input type="hidden" id="sel-alumno">
      </div>
      <div class="fila-2">
        <div class="campo">
          <label>Motivo *</label>
          <input type="text" id="motivo" placeholder="ej: Dolor de cabeza" maxlength="200">
        </div>
        <div class="campo">
          <label>Hora *</label>
          <input type="time" id="hora">
        </div>
      </div>
      <div class="campo">
        <label>Observaciones</label>
        <textarea id="observaciones" rows="3" placeholder="Detalles adicionales, tratamiento aplicado, etc."></textarea>
      </div>
      <button type="button" class="btn" id="btn-guardar">💾 Registrar atención</button>
      <div class="msg" id="msg"></div>
    </div>
  </div>
</div>

<script>
  const selAlumno = document.getElementById('sel-alumno');
  const buscarAlumno = document.getElementById('sel-alumno-buscar');
  const dlAlumnos = document.getElementById('dl-alumnos');
  let alumnosCache = [];
  const inpMotivo = document.getElementById('motivo');
  const inpHora   = document.getElementById('hora');
  const inpObs    = document.getElementById('observaciones');
  const btnGuardar = document.getElementById('btn-guardar');
  const msg = document.getElementById('msg');

  function pad(n) { return String(n).padStart(2, '0'); }
  const ahora = new Date();
  inpHora.value = `${pad(ahora.getHours())}:${pad(ahora.getMinutes())}`;

  function setMsg(texto, color) {
    msg.textContent = texto;
    msg.style.color = color || 'var(--gris-texto)';
  }

  (async function cargarAlumnos() {
    try {
      const res  = await fetch('enfermeria_alumnos.php');
      const data = await res.json();
      if (!data.success) { setMsg(data.message || 'Error al cargar alumnos.', '#DC2626'); return; }
      alumnosCache = data.alumnos;
      dlAlumnos.innerHTML = alumnosCache.map(a => `<option value="${esc(a.nombre)}">`).join('');
    } catch {
      setMsg('Error de conexión al cargar alumnos.', '#DC2626');
    }
  })();

  function esc(str) { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

  function normalizar(s) {
    return (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
  }

  buscarAlumno.addEventListener('input', () => {
    const texto = normalizar(buscarAlumno.value);
    if (!texto) { selAlumno.value = ''; return; }
    let match = alumnosCache.find(a => normalizar(a.nombre) === texto);
    if (!match) {
      const candidatos = alumnosCache.filter(a => normalizar(a.nombre).includes(texto));
      if (candidatos.length === 1) match = candidatos[0];
    }
    selAlumno.value = match ? match.id : '';
  });

  btnGuardar.addEventListener('click', async () => {
    const alumno_id = selAlumno.value;
    const motivo = inpMotivo.value.trim();
    const hora = inpHora.value;

    if (!alumno_id || !motivo || !hora) { setMsg('Completá alumno, motivo y hora.', '#DC2626'); return; }

    btnGuardar.disabled = true;
    setMsg('Guardando...');

    const body = new FormData();
    body.append('alumno_id', alumno_id);
    body.append('motivo', motivo);
    body.append('hora', hora);
    body.append('observaciones', inpObs.value.trim());

    try {
      const res  = await fetch('enfermeria_guardar.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        if (data.sin_contacto) {
          setMsg('⚠️ Atención registrada, pero no hay un padre/tutor vinculado. Avisar a administración.', '#D97706');
        } else {
          setMsg('✅ Atención registrada. Se notificó al padre/tutor.', '#059669');
        }
        selAlumno.value = '';
        buscarAlumno.value = '';
        inpMotivo.value = '';
        inpObs.value = '';
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
