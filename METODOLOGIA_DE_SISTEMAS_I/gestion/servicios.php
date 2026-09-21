<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'padre') {
    header('Location: /');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Padre/Tutor');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reservar Servicios – Educar para Transformar</title>
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

    .form-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; margin-bottom: 1.2rem; }
    .form-row label { display: flex; flex-direction: column; gap: .3rem; font-size: .82rem; font-weight: 800; color: var(--azul); }
    .form-row select {
      padding: .55rem .7rem; border: 2px solid var(--borde); border-radius: 8px;
      font-family: inherit; font-size: .9rem; min-width: 200px;
    }

    .servicios-opciones { display: flex; flex-direction: column; gap: .8rem; margin-bottom: 1.2rem; }
    .servicio-item {
      display: flex; align-items: center; gap: .8rem;
      padding: .9rem 1rem; border: 2px solid var(--borde); border-radius: 12px;
    }
    .servicio-item input[type="checkbox"] { width: 20px; height: 20px; flex-shrink: 0; }
    .servicio-item strong { display: block; font-size: .92rem; }
    .servicio-item span { font-size: .8rem; color: #6B7280; }

    .btn {
      background: var(--verde); color: var(--blanco); border: none; border-radius: 10px;
      padding: .6rem 1.2rem; font-weight: 800; font-size: .88rem; cursor: pointer;
      font-family: inherit; transition: opacity .2s;
    }
    .btn:hover { opacity: .88; }
    .btn:disabled { opacity: .6; cursor: not-allowed; }

    .msg { margin: .6rem 0; font-weight: 700; font-size: .88rem; min-height: 1.2em; }
    .plazo-vencido {
      background: #FEE2E2; color: #991B1B; border-radius: 10px;
      padding: .8rem 1rem; font-size: .85rem; font-weight: 700; margin-bottom: 1.2rem;
    }
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
    <span class="user-badge">👨‍👩‍👧 Familia</span>
    <a href="../auth/logout.php" class="btn-logout">Cerrar sesión</a>
  </div>
</header>

<div class="welcome">
  <h1>Reservar Servicios</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge">Portal Familias</span>
</div>

<div class="container">
  <a href="../portals/portal_padre.php" class="volver">&larr; Volver al portal</a>
  <div class="card">
    <div class="card-header"><span class="icon">🍽️</span><h2>Comedor y transporte</h2></div>
    <div class="card-body">
      <div class="form-row">
        <label>Alumno/a
          <select id="sel-alumno"></select>
        </label>
        <label>Mes
          <select id="sel-mes"></select>
        </label>
        <label>Año
          <select id="sel-anio"></select>
        </label>
      </div>

      <div id="plazo-aviso" class="plazo-vencido" style="display:none;">
        ⚠️ El plazo para reservar este mes venció. Comunicate con administración para gestionarlo.
      </div>

      <div class="servicios-opciones">
        <label class="servicio-item">
          <input type="checkbox" id="chk-comedor">
          <div><strong>🍽️ Comedor</strong><span>Servicio de almuerzo mensual</span></div>
        </label>
        <label class="servicio-item">
          <input type="checkbox" id="chk-transporte">
          <div><strong>🚌 Transporte</strong><span>Traslado escolar mensual</span></div>
        </label>
      </div>

      <button type="button" class="btn" id="btn-guardar">💾 Confirmar reserva</button>
      <div class="msg" id="msg"></div>
    </div>
  </div>
</div>

<script>
  const selAlumno = document.getElementById('sel-alumno');
  const selMes    = document.getElementById('sel-mes');
  const selAnio   = document.getElementById('sel-anio');
  const chkComedor    = document.getElementById('chk-comedor');
  const chkTransporte = document.getElementById('chk-transporte');
  const btnGuardar = document.getElementById('btn-guardar');
  const plazoAviso = document.getElementById('plazo-aviso');
  const msg = document.getElementById('msg');

  const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  MESES.forEach((m, i) => {
    const opt = document.createElement('option');
    opt.value = i + 1;
    opt.textContent = m;
    selMes.appendChild(opt);
  });

  const anioActual = new Date().getFullYear();
  for (let a = anioActual; a <= anioActual + 1; a++) {
    const opt = document.createElement('option');
    opt.value = a;
    opt.textContent = a;
    selAnio.appendChild(opt);
  }

  function setMsg(texto, color) {
    msg.textContent = texto;
    msg.style.color = color || 'var(--gris-texto)';
  }

  function actualizarPlazo(plazoVencido) {
    plazoAviso.style.display = plazoVencido ? '' : 'none';
    chkComedor.disabled = plazoVencido;
    chkTransporte.disabled = plazoVencido;
    btnGuardar.disabled = plazoVencido;
  }

  async function cargarEstado() {
    const params = new URLSearchParams();
    if (selAlumno.value) params.set('alumno_id', selAlumno.value);
    if (selMes.value)    params.set('mes', selMes.value);
    if (selAnio.value)   params.set('anio', selAnio.value);

    setMsg('Cargando...');
    try {
      const res  = await fetch('servicios_listar.php?' + params.toString());
      const data = await res.json();
      if (!data.success) { setMsg(data.message || 'Error al cargar.', '#DC2626'); return; }

      if (!selAlumno.options.length) {
        data.alumnos.forEach(a => {
          const opt = document.createElement('option');
          opt.value = a.id;
          opt.textContent = a.nombre;
          selAlumno.appendChild(opt);
        });
      }
      selAlumno.value = data.alumno_id;
      selMes.value = data.mes;
      selAnio.value = data.anio;

      chkComedor.checked = data.comedor;
      chkTransporte.checked = data.transporte;
      actualizarPlazo(data.plazo_vencido);
      setMsg('');
    } catch {
      setMsg('Error de conexión al cargar.', '#DC2626');
    }
  }

  selAlumno.addEventListener('change', cargarEstado);
  selMes.addEventListener('change', cargarEstado);
  selAnio.addEventListener('change', cargarEstado);

  btnGuardar.addEventListener('click', async () => {
    const servicios = [];
    if (chkComedor.checked) servicios.push('comedor');
    if (chkTransporte.checked) servicios.push('transporte');

    if (servicios.length === 0) { setMsg('Marcá al menos un servicio.', '#DC2626'); return; }

    btnGuardar.disabled = true;
    setMsg('Guardando...');

    const body = new FormData();
    body.append('alumno_id', selAlumno.value);
    body.append('mes', selMes.value);
    body.append('anio', selAnio.value);
    servicios.forEach(s => body.append('servicios[]', s));

    try {
      const res  = await fetch('servicios_guardar.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        setMsg('✅ Reserva confirmada.', '#059669');
      } else {
        setMsg(data.message || 'Error al guardar.', '#DC2626');
      }
    } catch {
      setMsg('Error de conexión al guardar.', '#DC2626');
    } finally {
      btnGuardar.disabled = false;
    }
  });

  cargarEstado();
</script>

</body>
</html>
