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
  <title>Vacantes por Curso – Educar para Transformar</title>
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
    table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    th { background: var(--naranja-bg); color: var(--naranja); font-weight: 800;
         padding: .5rem .7rem; text-align: left; font-size: .8rem; text-transform: uppercase; }
    td { padding: .6rem .7rem; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    .badge {
      display: inline-block; border-radius: 12px;
      padding: .15rem .55rem; font-size: .78rem; font-weight: 800;
    }
    .badge-verde  { background: #d1fae5; color: #065F46; }
    .badge-rojo   { background: #fee2e2; color: #991B1B; }
    .badge-amarillo { background: #fef3c7; color: #92400E; }
    .input-cap {
      width: 70px; padding: .35rem .5rem; border: 2px solid var(--borde); border-radius: 8px;
      font-family: inherit; font-size: .9rem; text-align: center;
    }
    .btn-sm {
      background: var(--azul); color: var(--blanco); border: none; border-radius: 8px;
      padding: .35rem .8rem; font-weight: 800; font-size: .8rem; cursor: pointer;
      font-family: inherit; transition: opacity .2s;
    }
    .btn-sm:hover { opacity: .85; }
    .btn-sm:disabled { opacity: .5; cursor: not-allowed; }
    .msg-global { margin: .6rem 0; font-weight: 700; font-size: .88rem; min-height: 1.2em; }
    .empty-msg { color: #9CA3AF; font-size: .9rem; text-align: center; padding: 1.5rem 0; }
    .bar-wrap { display: flex; align-items: center; gap: .5rem; }
    .bar-bg { flex: 1; background: #E5E7EB; border-radius: 20px; height: 10px; overflow: hidden; }
    .bar-fill { height: 100%; border-radius: 20px; background: var(--naranja); }
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
  <h1>Vacantes por Curso</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge">Panel Administración</span>
</div>

<div class="container">
  <a href="../portals/portal_admin.php" class="volver">&larr; Volver al panel</a>

  <div class="card">
    <div class="card-header"><span class="icon">🏫</span><h2>Disponibilidad de Vacantes</h2></div>
    <div class="card-body">
      <div class="msg-global" id="msg-global"></div>
      <table>
        <thead>
          <tr>
            <th>Curso</th>
            <th>Nivel</th>
            <th>Inscriptos</th>
            <th>Capacidad</th>
            <th>Vacantes</th>
            <th>Ocupación</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="tbody-cursos"><tr><td colspan="7" class="empty-msg">Cargando…</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script>
  const tbody    = document.getElementById('tbody-cursos');
  const msgGlobal= document.getElementById('msg-global');

  function setMsg(txt, color) { msgGlobal.textContent = txt; msgGlobal.style.color = color || 'var(--gris-texto)'; }

  async function cargar() {
    try {
      const res  = await fetch('vacantes_listar.php');
      const data = await res.json();
      if (!data.success) { tbody.innerHTML = `<tr><td colspan="7" class="empty-msg">${data.message}</td></tr>`; return; }

      if (!data.cursos.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-msg">No hay cursos registrados.</td></tr>';
        return;
      }

      tbody.innerHTML = '';
      data.cursos.forEach(c => {
        const pct   = Math.round((c.inscriptos / c.capacidad) * 100);
        const bColor = pct >= 100 ? '#DC2626' : pct >= 80 ? '#D97706' : '#059669';
        let badgeVac;
        if (c.vacantes_disponibles <= 0) badgeVac = '<span class="badge badge-rojo">Sin vacantes</span>';
        else if (c.vacantes_disponibles <= 5) badgeVac = `<span class="badge badge-amarillo">${c.vacantes_disponibles}</span>`;
        else badgeVac = `<span class="badge badge-verde">${c.vacantes_disponibles}</span>`;

        const tr = document.createElement('tr');
        tr.dataset.cursoId = c.id;
        tr.innerHTML = `
          <td><strong>${esc(c.nombre)}</strong></td>
          <td>${esc(c.nivel_educativo)}</td>
          <td>${c.inscriptos}</td>
          <td><input type="number" class="input-cap" value="${c.capacidad}" min="1" max="100"></td>
          <td>${badgeVac}</td>
          <td>
            <div class="bar-wrap">
              <div class="bar-bg"><div class="bar-fill" style="width:${Math.min(pct,100)}%;background:${bColor};"></div></div>
              <span style="font-size:.78rem;font-weight:700;min-width:36px;">${pct}%</span>
            </div>
          </td>
          <td><button class="btn-sm btn-guardar">Guardar</button></td>
        `;
        tbody.appendChild(tr);
      });
    } catch {
      tbody.innerHTML = '<tr><td colspan="7" class="empty-msg">Error de conexión.</td></tr>';
    }
  }

  tbody.addEventListener('click', async (e) => {
    if (!e.target.classList.contains('btn-guardar')) return;
    const tr         = e.target.closest('tr');
    const curso_id   = tr.dataset.cursoId;
    const capacidad  = tr.querySelector('.input-cap').value;

    e.target.disabled = true; e.target.textContent = '…';
    const body = new FormData();
    body.append('curso_id', curso_id);
    body.append('capacidad', capacidad);

    try {
      const res  = await fetch('vacantes_actualizar.php', { method: 'POST', body });
      const data = await res.json();
      setMsg(data.success ? '✅ ' + data.message : data.message || 'Error.', data.success ? '#059669' : '#DC2626');
      if (data.success) cargar();
    } catch {
      setMsg('Error de conexión.', '#DC2626');
    } finally {
      e.target.disabled = false; e.target.textContent = 'Guardar';
    }
  });

  function esc(str) { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

  cargar();
</script>

</body>
</html>
