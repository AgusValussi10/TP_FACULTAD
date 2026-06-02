<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Administrador');

require_once '../database/db_config.php';

$stats = ['total' => 0, 'pendiente' => 0, 'contactado' => 0, 'admitido' => 0, 'rechazado' => 0];
$res = $conn->query("SELECT estado, COUNT(*) AS cnt FROM solicitudes_inscripcion GROUP BY estado");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $stats[$r['estado']] = (int)$r['cnt'];
        $stats['total'] += (int)$r['cnt'];
    }
}

$solicitudes = [];
$res2 = $conn->query(
    "SELECT id, nombre_alumno, apellido_alumno, nivel_educativo, nombre_tutor, telefono, email, comentarios, estado,
            DATE_FORMAT(created_at,'%d/%m/%Y %H:%i') AS fecha
     FROM solicitudes_inscripcion
     ORDER BY FIELD(estado,'pendiente','contactado','admitido','rechazado'), created_at DESC"
);
if ($res2) {
    while ($r = $res2->fetch_assoc()) $solicitudes[] = $r;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Admin – Educar para Transformar</title>
  <link rel="icon" href="../assets/logo.avif">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Merriweather:wght@700&display=swap" rel="stylesheet">
  <style>
    :root {
      --verde:      #F97316;
      --verde-bg:   #FFF7ED;
      --azul:       #374151;
      --gris-texto: #111827;
      --blanco:     #FFFFFF;
      --borde:      #E5E7EB;
      --sombra:     0 4px 18px rgba(249,115,22,.13);
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
    .logo-text strong { display: block; font-size: .95rem; font-weight: 900; color: var(--gris-texto); }
    .logo-text span   { font-size: .7rem; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; }
    .user-info { display: flex; align-items: center; gap: 1rem; }
    .user-badge { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; border-radius: 20px; padding: .35rem .9rem; font-size: .85rem; font-weight: 700; }
    .btn-logout { background: #C2410C; color: #fff; border: none; border-radius: 20px; padding: .4rem 1rem; font-weight: 900; font-size: .85rem; cursor: pointer; text-decoration: none; transition: opacity .2s; }
    .btn-logout:hover { opacity: .85; }

    .welcome { background: linear-gradient(135deg, #1F2937 0%, #111827 100%); color: #fff; padding: 2.5rem 1.5rem; text-align: center; }
    .welcome h1 { font-family: 'Merriweather', serif; font-size: clamp(1.4rem, 4vw, 2rem); margin-bottom: .4rem; }
    .welcome p  { opacity: .85; font-size: .95rem; }
    .rol-badge  { display: inline-block; margin-top: .8rem; background: rgba(249,115,22,.25); border: 1px solid rgba(249,115,22,.5); color: #F97316; border-radius: 20px; padding: .25rem .9rem; font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; }

    .container { max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem 3rem; }

    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .stat-card { background: var(--blanco); border-radius: 14px; box-shadow: var(--sombra); padding: 1.2rem 1.5rem; text-align: center; }
    .stat-num  { font-size: 2.2rem; font-weight: 900; }
    .stat-label{ font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #6B7280; margin-top: .2rem; }
    .stat-card.total      .stat-num { color: var(--azul); }
    .stat-card.pendiente  .stat-num { color: #D97706; }
    .stat-card.contactado .stat-num { color: #2563EB; }
    .stat-card.admitido   .stat-num { color: #059669; }
    .stat-card.rechazado  .stat-num { color: #DC2626; }

    .card { background: var(--blanco); border-radius: 16px; box-shadow: var(--sombra); overflow: hidden; }
    .card-header { padding: 1.1rem 1.5rem; display: flex; align-items: center; gap: .7rem; border-bottom: 2px solid var(--borde); }
    .card-header .icon { font-size: 1.5rem; }
    .card-header h2 { font-size: 1rem; font-weight: 800; color: var(--azul); }
    .card-body { padding: 1.2rem 1.5rem; overflow-x: auto; }

    table { width: 100%; border-collapse: collapse; font-size: .86rem; }
    th { background: var(--verde-bg); color: var(--verde); font-weight: 800; padding: .6rem .8rem; text-align: left; font-size: .78rem; text-transform: uppercase; white-space: nowrap; }
    td { padding: .6rem .8rem; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr.row-admitido  { background: #F0FDF4; }
    tr.row-rechazado { background: #FFF1F2; }
    tr.row-contactado{ background: #EFF6FF; }

    .estado-badge { display: inline-block; border-radius: 20px; padding: .2rem .65rem; font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
    .estado-pendiente  { background: #FEF3C7; color: #92400E; }
    .estado-contactado { background: #DBEAFE; color: #1E40AF; }
    .estado-admitido   { background: #D1FAE5; color: #065F46; }
    .estado-rechazado  { background: #FEE2E2; color: #991B1B; }

    .acciones { display: flex; gap: .4rem; flex-wrap: wrap; }
    .btn-accion { border: none; border-radius: 8px; padding: .3rem .7rem; font-size: .78rem; font-weight: 800; cursor: pointer; font-family: inherit; transition: opacity .2s; white-space: nowrap; }
    .btn-accion:hover { opacity: .8; }
    .btn-accion:disabled { opacity: .4; cursor: default; }
    .btn-contactado { background: #DBEAFE; color: #1E40AF; }
    .btn-admitido   { background: #D1FAE5; color: #065F46; }
    .btn-rechazado  { background: #FEE2E2; color: #991B1B; }
    .btn-pendiente  { background: #F3F4F6; color: #374151; }

    .comentario-txt { font-size: .82rem; color: #6B7280; font-style: italic; max-width: 200px; }
    .empty-msg { color: #9CA3AF; font-size: .9rem; text-align: center; padding: 2rem 0; }

    /* Modal admisión */
    .overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:200; align-items:center; justify-content:center; }
    .overlay.open { display:flex; }
    .modal-box { background:#fff; border-radius:20px; padding:2rem; width:100%; max-width:420px; box-shadow:0 20px 60px rgba(0,0,0,.25); animation: popIn .2s ease; }
    @keyframes popIn { from{transform:scale(.92);opacity:0} to{transform:scale(1);opacity:1} }
    .modal-box h3 { font-size:1.1rem; font-weight:900; color:#059669; margin-bottom:.3rem; }
    .modal-box .modal-sub { font-size:.85rem; color:#6B7280; margin-bottom:1.2rem; }
    .modal-field { margin-bottom:.9rem; }
    .modal-field label { display:block; font-size:.83rem; font-weight:700; margin-bottom:.3rem; color:#374151; }
    .modal-field input { width:100%; padding:.65rem .9rem; border:2px solid #E5E7EB; border-radius:10px; font-size:.9rem; font-family:inherit; transition:border-color .2s; }
    .modal-field input:focus { outline:none; border-color:#059669; }
    .modal-error { color:#DC2626; font-size:.83rem; font-weight:700; margin-bottom:.8rem; min-height:1.1rem; }
    .modal-btns { display:flex; gap:.7rem; justify-content:flex-end; margin-top:1.2rem; }
    .btn-cancelar { background:#F3F4F6; color:#374151; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:800; cursor:pointer; font-family:inherit; }
    .btn-confirmar { background:#059669; color:#fff; border:none; border-radius:10px; padding:.6rem 1.4rem; font-weight:800; cursor:pointer; font-family:inherit; transition:opacity .2s; }
    .btn-confirmar:hover { opacity:.85; }
    .btn-confirmar:disabled { opacity:.5; cursor:default; }

    @media (max-width: 600px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }

    @keyframes nuevaFila {
      0%   { background-color: #FEF9C3; }
      70%  { background-color: #FFFBEB; }
      100% { background-color: transparent; }
    }
    .fila-nueva { animation: nuevaFila 4s ease-out; }

    .toast-notif {
      position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 300;
      background: #D97706; color: #fff; border-radius: 12px;
      padding: .75rem 1.2rem; font-weight: 700; font-size: .9rem;
      box-shadow: 0 4px 20px rgba(0,0,0,.2);
      opacity: 0; transform: translateY(10px); transition: opacity .3s ease, transform .3s ease;
      pointer-events: none;
    }
    .toast-notif.visible { opacity: 1; transform: translateY(0); }

    /* ── Tabs ── */
    .tabs-nav {
      display: flex; gap: .5rem;
      background: var(--blanco); border-bottom: 2px solid var(--borde);
      padding: 0 1.5rem; position: sticky; top: 65px; z-index: 90;
      box-shadow: 0 2px 8px rgba(0,0,0,.05);
    }
    .tab-btn {
      background: none; border: none; cursor: pointer; font-family: inherit;
      font-size: .9rem; font-weight: 700; color: #6B7280;
      padding: .9rem 1.2rem; border-bottom: 3px solid transparent;
      margin-bottom: -2px; transition: color .2s, border-color .2s;
      white-space: nowrap; display: flex; align-items: center; gap: .4rem;
    }
    .tab-btn:hover { color: var(--verde); }
    .tab-btn.active { color: var(--verde); border-bottom-color: var(--verde); }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
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
    <span class="user-badge">⚙️ Admin</span>
    <a href="../auth/logout.php" class="btn-logout">Cerrar sesión</a>
  </div>
</header>

<div class="welcome">
  <h1>Panel de Administración</h1>
  <p>Gestión integral del centro educativo</p>
  <span class="rol-badge">Administrador</span>
</div>

<!-- Navegación por pestañas -->
<nav class="tabs-nav">
  <button class="tab-btn active" data-tab="inscripciones">📋 Inscripciones</button>
  <button class="tab-btn" data-tab="opiniones">💬 Opiniones</button>
  <button class="tab-btn" data-tab="propuestas">💼 Propuestas de Trabajo</button>
</nav>

<div class="container">

  <!-- ══ TAB: INSCRIPCIONES ══ -->
  <div class="tab-panel active" id="panel-inscripciones">

  <!-- ESTADÍSTICAS -->
  <div class="stats-grid">
    <div class="stat-card total">
      <div class="stat-num"><?= $stats['total'] ?></div>
      <div class="stat-label">Total</div>
    </div>
    <div class="stat-card pendiente">
      <div class="stat-num"><?= $stats['pendiente'] ?></div>
      <div class="stat-label">Pendientes</div>
    </div>
    <div class="stat-card contactado">
      <div class="stat-num"><?= $stats['contactado'] ?></div>
      <div class="stat-label">Contactados</div>
    </div>
    <div class="stat-card admitido">
      <div class="stat-num"><?= $stats['admitido'] ?></div>
      <div class="stat-label">Admitidos</div>
    </div>
    <div class="stat-card rechazado">
      <div class="stat-num"><?= $stats['rechazado'] ?></div>
      <div class="stat-label">Rechazados</div>
    </div>
  </div>

  <!-- TABLA DE SOLICITUDES -->
  <div class="card">
    <div class="card-header">
      <span class="icon">📋</span>
      <h2>Solicitudes de Inscripción</h2>
    </div>
    <div class="card-body">
      <?php if (empty($solicitudes)): ?>
        <p class="empty-msg">No hay solicitudes registradas aún.</p>
      <?php else: ?>
      <table id="tabla-solicitudes">
        <thead>
          <tr>
            <th>#</th>
            <th>Alumno</th>
            <th>Nivel</th>
            <th>Tutor</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th>Comentarios</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($solicitudes as $s): ?>
          <tr id="row-<?= $s['id'] ?>" class="row-<?= $s['estado'] ?>" data-nombre="<?= htmlspecialchars($s['nombre_alumno']) ?>" data-apellido="<?= htmlspecialchars($s['apellido_alumno']) ?>">
            <td><?= $s['id'] ?></td>
            <td><strong><?= htmlspecialchars($s['apellido_alumno'] . ', ' . $s['nombre_alumno']) ?></strong></td>
            <td><?= htmlspecialchars($s['nivel_educativo']) ?></td>
            <td><?= htmlspecialchars($s['nombre_tutor']) ?></td>
            <td><?= htmlspecialchars($s['telefono']) ?></td>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td><span class="comentario-txt"><?= $s['comentarios'] ? htmlspecialchars(mb_strimwidth($s['comentarios'], 0, 60, '…')) : '—' ?></span></td>
            <td style="white-space:nowrap"><?= $s['fecha'] ?></td>
            <td id="estado-<?= $s['id'] ?>">
              <span class="estado-badge estado-<?= $s['estado'] ?>"><?= ucfirst($s['estado']) ?></span>
            </td>
            <td>
              <div class="acciones" id="acciones-<?= $s['id'] ?>">
                <?php if ($s['estado'] !== 'contactado'): ?>
                  <button class="btn-accion btn-contactado" onclick="cambiarEstado(<?= $s['id'] ?>,'contactado')">Contactado</button>
                <?php endif; ?>
                <?php if ($s['estado'] !== 'admitido'): ?>
                  <button class="btn-accion btn-admitido"   onclick="abrirModalAdmitir(<?= $s['id'] ?>)">Admitir</button>
                <?php endif; ?>
                <?php if ($s['estado'] !== 'rechazado'): ?>
                  <button class="btn-accion btn-rechazado"  onclick="cambiarEstado(<?= $s['id'] ?>,'rechazado')">Rechazar</button>
                <?php endif; ?>
                <?php if ($s['estado'] !== 'pendiente'): ?>
                  <button class="btn-accion btn-pendiente"  onclick="cambiarEstado(<?= $s['id'] ?>,'pendiente')">↩ Pendiente</button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  </div><!-- /panel-inscripciones -->

  <!-- ══ TAB: OPINIONES ══ -->
  <div class="tab-panel" id="panel-opiniones">
    <div class="card" style="margin-top:2rem;">
      <div class="card-header">
        <span class="icon">💬</span>
        <h2>Gestión de Opiniones</h2>
      </div>
      <div class="card-body">
        <p class="empty-msg">Próximamente: aprobación de opiniones para la sección de noticias.</p>
      </div>
    </div>
  </div><!-- /panel-opiniones -->

  <!-- ══ TAB: PROPUESTAS DE TRABAJO ══ -->
  <div class="tab-panel" id="panel-propuestas">
    <div class="card" style="margin-top:2rem;">
      <div class="card-header">
        <span class="icon">💼</span>
        <h2>Gestión de Propuestas de Trabajo</h2>
      </div>
      <div class="card-body">
        <p class="empty-msg">Próximamente: administración de puestos vacantes y postulaciones.</p>
      </div>
    </div>
  </div><!-- /panel-propuestas -->

</div>

<!-- Modal admisión -->
<div class="overlay" id="overlay-admitir" onclick="cerrarModal(event)">
  <div class="modal-box">
    <h3>✅ Admitir alumno</h3>
    <p class="modal-sub" id="modal-alumno-nombre"></p>
    <input type="hidden" id="modal-id">
    <div class="modal-field">
      <label>Nombre completo del alumno</label>
      <input type="text" id="modal-nombre-completo" readonly style="background:#F9FAFB;color:#6B7280;">
    </div>
    <div class="modal-field">
      <label>Usuario *</label>
      <input type="text" id="modal-usuario" placeholder="ej: garcia.ana" autocomplete="off">
    </div>
    <div class="modal-field">
      <label>Contraseña *</label>
      <input type="text" id="modal-password" placeholder="mínimo 4 caracteres" autocomplete="off">
    </div>
    <p class="modal-error" id="modal-error"></p>
    <div class="modal-btns">
      <button class="btn-cancelar" onclick="document.getElementById('overlay-admitir').classList.remove('open')">Cancelar</button>
      <button class="btn-confirmar" id="btn-confirmar" onclick="confirmarAdmision()">Crear usuario y admitir</button>
    </div>
  </div>
</div>

<script>
  // ── Navegación por tabs ──────────────────────────────────────────────
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
    });
  });

  const BOTONES = {
    contactado: '<button class="btn-accion btn-contactado" onclick="cambiarEstado(ID,\'contactado\')">Contactado</button>',
    admitido:   '<button class="btn-accion btn-admitido"   onclick="abrirModalAdmitir(ID)">Admitir</button>',
    rechazado:  '<button class="btn-accion btn-rechazado"  onclick="cambiarEstado(ID,\'rechazado\')">Rechazar</button>',
    pendiente:  '<button class="btn-accion btn-pendiente"  onclick="cambiarEstado(ID,\'pendiente\')">↩ Pendiente</button>',
  };

  function normalizar(str) {
    return str.toLowerCase()
      .normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/\s+/g, '.');
  }

  function abrirModalAdmitir(id) {
    const row      = document.getElementById(`row-${id}`);
    const nombre   = row.dataset.nombre;
    const apellido = row.dataset.apellido;
    document.getElementById('modal-id').value             = id;
    document.getElementById('modal-alumno-nombre').textContent = apellido + ', ' + nombre;
    document.getElementById('modal-nombre-completo').value = nombre + ' ' + apellido;
    document.getElementById('modal-usuario').value         = normalizar(apellido) + '.' + normalizar(nombre);
    document.getElementById('modal-password').value        = '';
    document.getElementById('modal-error').textContent     = '';
    document.getElementById('btn-confirmar').disabled      = false;
    document.getElementById('overlay-admitir').classList.add('open');
    document.getElementById('modal-password').focus();
  }

  function cerrarModal(e) {
    if (e.target === document.getElementById('overlay-admitir'))
      document.getElementById('overlay-admitir').classList.remove('open');
  }

  async function confirmarAdmision() {
    const id       = document.getElementById('modal-id').value;
    const usuario  = document.getElementById('modal-usuario').value.trim();
    const password = document.getElementById('modal-password').value.trim();
    const nombre   = document.getElementById('modal-nombre-completo').value.trim();
    const errorEl  = document.getElementById('modal-error');
    const btn      = document.getElementById('btn-confirmar');

    errorEl.textContent = '';
    if (!usuario || !password) { errorEl.textContent = 'Completá usuario y contraseña.'; return; }
    if (password.length < 4)   { errorEl.textContent = 'La contraseña debe tener al menos 4 caracteres.'; return; }

    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
      const body = new FormData();
      body.append('id',       id);
      body.append('usuario',  usuario);
      body.append('password', password);
      body.append('nombre',   nombre);
      const res  = await fetch('../inscripciones/admitir.php', { method: 'POST', body });
      const data = await res.json();

      if (data.success) {
        document.getElementById('overlay-admitir').classList.remove('open');

        // Actualizar fila
        document.getElementById(`estado-${id}`).innerHTML =
          `<span class="estado-badge estado-admitido">Admitido</span>`;
        const row = document.getElementById(`row-${id}`);
        row.className = 'row-admitido';
        const todos = ['contactado','rechazado','pendiente'];
        document.getElementById(`acciones-${id}`).innerHTML = todos
          .map(e => BOTONES[e].replaceAll('ID', id))
          .join('');

        actualizarStats();
      } else {
        errorEl.textContent = data.message || 'Error al guardar.';
        btn.disabled = false;
        btn.textContent = 'Crear usuario y admitir';
      }
    } catch {
      errorEl.textContent = 'No se pudo conectar con el servidor.';
      btn.disabled = false;
      btn.textContent = 'Crear usuario y admitir';
    }
  }

  async function cambiarEstado(id, estado) {
    const btns = document.querySelectorAll(`#acciones-${id} button`);
    btns.forEach(b => b.disabled = true);

    try {
      const body = new FormData();
      body.append('id', id);
      body.append('estado', estado);
      const res  = await fetch('../inscripciones/cambiar_estado.php', { method: 'POST', body });
      const data = await res.json();

      if (data.success) {
        // Actualizar badge de estado
        document.getElementById(`estado-${id}`).innerHTML =
          `<span class="estado-badge estado-${estado}">${estado.charAt(0).toUpperCase() + estado.slice(1)}</span>`;

        // Actualizar color de fila
        const row = document.getElementById(`row-${id}`);
        row.className = `row-${estado}`;

        // Reconstruir botones (todos excepto el estado actual)
        const todos = ['contactado','admitido','rechazado','pendiente'];
        document.getElementById(`acciones-${id}`).innerHTML = todos
          .filter(e => e !== estado)
          .map(e => BOTONES[e].replaceAll('ID', id))
          .join('');

        // Actualizar contador en stats
        actualizarStats();
      } else {
        alert(data.message || 'Error al actualizar.');
        btns.forEach(b => b.disabled = false);
      }
    } catch {
      alert('No se pudo conectar con el servidor.');
      btns.forEach(b => b.disabled = false);
    }
  }

  function actualizarStats() {
    const conteos = { total: 0, pendiente: 0, contactado: 0, admitido: 0, rechazado: 0 };
    document.querySelectorAll('#tabla-solicitudes tbody tr').forEach(row => {
      const estado = [...row.classList].find(c => c.startsWith('row-'))?.replace('row-','');
      if (estado) { conteos[estado]++; conteos.total++; }
    });
    ['total','pendiente','contactado','admitido','rechazado'].forEach(k => {
      const el = document.querySelector(`.stat-card.${k} .stat-num`);
      if (el) el.textContent = conteos[k];
    });
  }

  // ── Polling automático ──────────────────────────────────────────────

  function esc(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
  }

  function capitalizar(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
  }

  function buildRow(s) {
    const id = s.id;
    const todos = ['contactado', 'admitido', 'rechazado', 'pendiente'];
    const botones = todos
      .filter(e => e !== s.estado)
      .map(e => BOTONES[e].replaceAll('ID', id))
      .join('');
    const comentario = s.comentarios
      ? esc(s.comentarios.length > 60 ? s.comentarios.substring(0, 60) + '…' : s.comentarios)
      : '—';
    return `<tr id="row-${id}" class="row-${s.estado} fila-nueva"
                data-nombre="${esc(s.nombre_alumno)}" data-apellido="${esc(s.apellido_alumno)}">
      <td>${id}</td>
      <td><strong>${esc(s.apellido_alumno)}, ${esc(s.nombre_alumno)}</strong></td>
      <td>${esc(s.nivel_educativo)}</td>
      <td>${esc(s.nombre_tutor)}</td>
      <td>${esc(s.telefono)}</td>
      <td>${esc(s.email)}</td>
      <td><span class="comentario-txt">${comentario}</span></td>
      <td style="white-space:nowrap">${s.fecha}</td>
      <td id="estado-${id}"><span class="estado-badge estado-${s.estado}">${capitalizar(s.estado)}</span></td>
      <td><div class="acciones" id="acciones-${id}">${botones}</div></td>
    </tr>`;
  }

  function asegurarTabla() {
    if (!document.getElementById('tabla-solicitudes')) {
      document.querySelector('.card-body').innerHTML = `
        <table id="tabla-solicitudes">
          <thead><tr>
            <th>#</th><th>Alumno</th><th>Nivel</th><th>Tutor</th>
            <th>Teléfono</th><th>Email</th><th>Comentarios</th>
            <th>Fecha</th><th>Estado</th><th>Acciones</th>
          </tr></thead>
          <tbody></tbody>
        </table>`;
    }
  }

  function mostrarToast(msg) {
    const toast = document.createElement('div');
    toast.className = 'toast-notif';
    toast.textContent = msg;
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('visible'));
    setTimeout(() => {
      toast.classList.remove('visible');
      setTimeout(() => toast.remove(), 400);
    }, 4000);
  }

  async function pollingActualizar() {
    try {
      const res = await fetch('../inscripciones/polling.php', { credentials: 'same-origin' });
      if (!res.ok) return;
      const { stats, solicitudes } = await res.json();

      // Actualizar stats desde el servidor
      ['total','pendiente','contactado','admitido','rechazado'].forEach(k => {
        const el = document.querySelector(`.stat-card.${k} .stat-num`);
        if (el && el.textContent !== String(stats[k])) el.textContent = stats[k];
      });

      if (!solicitudes.length) return;

      asegurarTabla();
      const tbody = document.querySelector('#tabla-solicitudes tbody');
      let nuevas = 0;

      solicitudes.forEach(s => {
        const existingRow = document.getElementById(`row-${s.id}`);
        if (!existingRow) {
          // Fila nueva: insertar al inicio (pendiente, más reciente)
          const tpl = document.createElement('template');
          tpl.innerHTML = buildRow(s);
          tbody.insertBefore(tpl.content.firstChild, tbody.firstChild);
          nuevas++;
        } else {
          // Fila existente: actualizar si cambió el estado
          const estadoActual = [...existingRow.classList]
            .find(c => c.startsWith('row-'))?.replace('row-', '');
          if (estadoActual !== s.estado) {
            // No actualizar si el modal de admisión está abierto para esta fila
            const modalAbierto = document.getElementById('overlay-admitir')?.classList.contains('open');
            const modalId = document.getElementById('modal-id')?.value;
            if (modalAbierto && String(modalId) === String(s.id)) return;

            existingRow.className = `row-${s.estado}`;
            document.getElementById(`estado-${s.id}`).innerHTML =
              `<span class="estado-badge estado-${s.estado}">${capitalizar(s.estado)}</span>`;
            const todos = ['contactado','admitido','rechazado','pendiente'];
            document.getElementById(`acciones-${s.id}`).innerHTML = todos
              .filter(e => e !== s.estado)
              .map(e => BOTONES[e].replaceAll('ID', s.id))
              .join('');
          }
        }
      });

      if (nuevas > 0) {
        mostrarToast(nuevas === 1
          ? 'Nueva solicitud de inscripción recibida'
          : `${nuevas} nuevas solicitudes de inscripción`);
      }
    } catch {
      // Error de red — reintentar en el siguiente ciclo
    }
  }

  setInterval(pollingActualizar, 20000);
</script>

</body>
</html>
