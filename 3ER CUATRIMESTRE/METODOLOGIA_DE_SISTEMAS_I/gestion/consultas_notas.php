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
  <title>Consultas sobre Notas – Educar para Transformar</title>
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
      .consulta-item { border-left: 4px solid var(--naranja); background: #F9FAFB; border-radius: 8px; padding: .8rem 1rem; margin-bottom: .8rem; font-size: .9rem; }
    .consulta-item.resp-ok { border-left-color: #059669; }
    .consulta-item textarea { width: 100%; min-height: 60px; margin-top: .5rem; padding: .5rem; border: 2px solid var(--borde); border-radius: 8px; font-family: inherit; font-size: .88rem; resize: vertical; }
    .respuesta { margin-top: .5rem; padding: .4rem .7rem; background: #d1fae5; color: #065F46; border-radius: 6px; }
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
  <h1>Consultas sobre Notas</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge">Portal Docente</span>
</div>

<div class="container">
  <a href="../portals/portal_docente.php" class="volver">&larr; Volver al portal</a>

  <div class="card">
    <div class="card-header"><span class="icon">💬</span><h2>Consultas de padres/tutores</h2></div>
    <div class="card-body">
      <div class="msg-global" id="msg-global"></div>
      <div id="lista"><p class="empty-msg">Cargando…</p></div>
    </div>
  </div>
</div>

<script>
  const lista     = document.getElementById('lista');
  const msgGlobal = document.getElementById('msg-global');

  function esc(str) { const d = document.createElement('div'); d.textContent = str ?? ''; return d.innerHTML; }
  function setMsg(t, c) { msgGlobal.textContent = t; msgGlobal.style.color = c || 'var(--gris-texto)'; }

  async function cargar() {
    try {
      const res  = await fetch('consulta_nota_listar.php');
      const data = await res.json();
      if (!data.success) { lista.innerHTML = `<p class="empty-msg">${esc(data.message)}</p>`; return; }
      if (!data.consultas.length) { lista.innerHTML = '<p class="empty-msg">No hay consultas sobre tus materias.</p>'; return; }
      lista.innerHTML = data.consultas.map(q => `
        <div class="consulta-item ${q.estado === 'respondida' ? 'resp-ok' : ''}" data-id="${q.id}">
          <strong>${esc(q.alumno)}</strong> · ${esc(q.materia)} – ${esc(q.evaluacion)} (nota ${q.nota})
          <span class="badge ${q.estado === 'respondida' ? 'badge-verde' : 'badge-amarillo'}">${q.estado === 'respondida' ? 'Respondida' : 'Pendiente'}</span>
          <div style="color:#6B7280;font-size:.8rem;">${q.fecha}</div>
          <div style="margin-top:.4rem;">${esc(q.mensaje)}</div>
          ${q.estado === 'respondida'
            ? `<div class="respuesta"><strong>Tu respuesta:</strong> ${esc(q.respuesta)}</div>`
            : `<textarea maxlength="1000" placeholder="Escribí tu respuesta…"></textarea>
               <div style="margin-top:.4rem;"><button type="button" class="btn-sm btn-responder">Responder</button></div>`}
        </div>`).join('');
    } catch {
      lista.innerHTML = '<p class="empty-msg">Error al cargar las consultas.</p>';
    }
  }

  lista.addEventListener('click', async (e) => {
    if (!e.target.classList.contains('btn-responder')) return;
    const item = e.target.closest('.consulta-item');
    const body = new FormData();
    body.append('id', item.dataset.id);
    body.append('respuesta', item.querySelector('textarea').value);
    e.target.disabled = true;
    try {
      const res  = await fetch('consulta_nota_responder.php', { method: 'POST', body });
      const data = await res.json();
      setMsg(data.success ? '✅ ' + data.message : data.message || 'Error.', data.success ? '#059669' : '#DC2626');
      if (data.success) cargar();
    } catch {
      setMsg('Error de conexión.', '#DC2626');
    } finally {
      e.target.disabled = false;
    }
  });

  cargar();
</script>

</body>
</html>
