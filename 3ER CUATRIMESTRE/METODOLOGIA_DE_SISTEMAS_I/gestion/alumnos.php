<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Administrador');

require_once '../gestion/helpers.php';
require_once '../database/db_config.php';

$alumnos = [];
$res = $conn->query(
    "SELECT u.id, u.nombre, u.usuario, u.activo,
            c.nombre AS curso, ac.curso_id
     FROM usuarios u
     LEFT JOIN alumno_curso ac ON ac.alumno_id = u.id
     LEFT JOIN cursos c ON c.id = ac.curso_id
     WHERE u.rol = 'alumno'
     ORDER BY u.activo DESC, u.nombre"
);
if ($res) while ($r = $res->fetch_assoc()) $alumnos[] = $r;

$cursos = [];
$res2 = $conn->query("SELECT id, nombre FROM cursos ORDER BY nombre");
if ($res2) while ($r = $res2->fetch_assoc()) $cursos[] = $r;

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Alumnos – Educar para Transformar</title>
  <link rel="icon" href="../assets/logo.avif">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Merriweather:wght@700&display=swap" rel="stylesheet">
  <style>
    :root {
      --naranja:    #F97316;
      --naranja-bg: #FFF7ED;
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
    .logo-text strong { display: block; font-size: .95rem; font-weight: 900; }
    .logo-text span   { font-size: .7rem; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; }
    .user-info { display: flex; align-items: center; gap: 1rem; }
    .user-badge { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; border-radius: 20px; padding: .35rem .9rem; font-size: .85rem; font-weight: 700; }
    .btn-logout { background: #C2410C; color: #fff; border: none; border-radius: 20px; padding: .4rem 1rem; font-weight: 900; font-size: .85rem; cursor: pointer; text-decoration: none; transition: opacity .2s; }
    .btn-logout:hover { opacity: .85; }

    .welcome { background: linear-gradient(135deg, #374151 0%, #1F2937 45%, #111827 100%); color: #fff; padding: 2.5rem 1.5rem; text-align: center; }
    .welcome h1 { font-family: 'Merriweather', serif; font-size: clamp(1.4rem, 4vw, 2rem); margin-bottom: .4rem; }
    .welcome p  { opacity: .85; font-size: .95rem; }
    .rol-badge  { display: inline-block; margin-top: .8rem; background: rgba(249,115,22,.25); border: 1px solid rgba(249,115,22,.5); color: var(--naranja); border-radius: 20px; padding: .25rem .9rem; font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; }

    .container { max-width: 1100px; margin: 2rem auto; padding: 0 1.5rem 3rem; }
    .volver { display: inline-block; margin-bottom: 1rem; color: var(--azul); font-weight: 700; font-size: .85rem; text-decoration: none; }
    .volver:hover { text-decoration: underline; }

    .card { background: var(--blanco); border-radius: 16px; box-shadow: var(--sombra); overflow: hidden; margin-bottom: 1.5rem; }
    .card-header { padding: 1.1rem 1.5rem; display: flex; align-items: center; gap: .7rem; border-bottom: 2px solid var(--borde); justify-content: space-between; }
    .card-header-left { display: flex; align-items: center; gap: .7rem; }
    .card-header .icon { font-size: 1.5rem; }
    .card-header h2 { font-size: 1rem; font-weight: 800; color: var(--azul); }
    .card-body { padding: 1.2rem 1.5rem; overflow-x: auto; }

    table { width: 100%; border-collapse: collapse; font-size: .86rem; }
    th { background: var(--naranja-bg); color: var(--naranja); font-weight: 800; padding: .6rem .8rem; text-align: left; font-size: .78rem; text-transform: uppercase; white-space: nowrap; }
    td { padding: .6rem .8rem; border-bottom: 1px solid #F3F4F6; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }

    .estado-badge { display: inline-block; border-radius: 20px; padding: .2rem .65rem; font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
    .al-activo   { background: #D1FAE5; color: #065F46; }
    .al-inactivo { background: #FEE2E2; color: #991B1B; }

    .acciones { display: flex; gap: .4rem; flex-wrap: wrap; }
    .btn-accion { border: none; border-radius: 8px; padding: .3rem .7rem; font-size: .78rem; font-weight: 800; cursor: pointer; font-family: inherit; transition: opacity .2s; white-space: nowrap; }
    .btn-accion:hover { opacity: .8; }
    .btn-editar    { background: #DBEAFE; color: #1E40AF; }
    .btn-suspender { background: #FEF3C7; color: #92400E; }
    .btn-reactivar { background: #D1FAE5; color: #065F46; }
    .btn-eliminar  { background: #FEE2E2; color: #991B1B; }

    .btn-nuevo { background: var(--naranja); color: #fff; border: none; border-radius: 10px; padding: .5rem 1.2rem; font-weight: 800; font-size: .88rem; cursor: pointer; font-family: inherit; transition: opacity .2s; }
    .btn-nuevo:hover { opacity: .88; }

    .busqueda { padding: .55rem .9rem; border: 2px solid var(--borde); border-radius: 10px; font-family: inherit; font-size: .9rem; width: 100%; max-width: 320px; margin-bottom: 1rem; }
    .busqueda:focus { outline: none; border-color: var(--naranja); }

    .empty-msg { color: #9CA3AF; font-size: .9rem; text-align: center; padding: 2rem 0; }

    /* Overlay + modal */
    .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 200; align-items: center; justify-content: center; }
    .overlay.open { display: flex; }
    @keyframes popIn { from{transform:scale(.92);opacity:0} to{transform:scale(1);opacity:1} }

    .modal-box { background: #fff; border-radius: 20px; padding: 2rem; width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,.25); animation: popIn .2s ease; }
    .modal-box h3 { font-size: 1.1rem; font-weight: 900; color: var(--azul); margin-bottom: 1.1rem; }
    .modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    .modal-grid .span2 { grid-column: 1/-1; }
    .modal-grid label { display: block; font-size: .82rem; font-weight: 700; margin-bottom: .25rem; color: #374151; }
    .modal-grid input,
    .modal-grid select { width: 100%; padding: .6rem .8rem; border: 2px solid var(--borde); border-radius: 10px; font-size: .88rem; font-family: inherit; transition: border-color .2s; }
    .modal-grid input:focus,
    .modal-grid select:focus { outline: none; border-color: var(--naranja); }
    .modal-error { color: #DC2626; font-size: .83rem; font-weight: 700; min-height: 1.2rem; margin-top: .5rem; }
    .modal-btns { display: flex; gap: .7rem; justify-content: flex-end; margin-top: 1.2rem; }
    .btn-cancelar  { background: #F3F4F6; color: #374151; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 800; cursor: pointer; font-family: inherit; }
    .btn-confirmar { background: var(--naranja); color: #fff; border: none; border-radius: 10px; padding: .6rem 1.4rem; font-weight: 800; cursor: pointer; font-family: inherit; transition: opacity .2s; }
    .btn-confirmar:hover { opacity: .85; }
    .btn-confirmar:disabled { opacity: .5; cursor: default; }

    .stats-bar { display: flex; gap: 1rem; margin-bottom: 1.2rem; flex-wrap: wrap; }
    .stat-pill { background: var(--naranja-bg); border: 1px solid var(--borde); border-radius: 20px; padding: .3rem .9rem; font-size: .82rem; font-weight: 700; color: var(--azul); }
    .stat-pill span { color: var(--naranja); font-weight: 900; }
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
  <h1>Gestión de Alumnos</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge">Panel Administración</span>
</div>

<div class="container">
  <a href="../portals/portal_admin.php" class="volver">&larr; Volver al portal</a>

  <?php
    $total    = count($alumnos);
    $activos  = count(array_filter($alumnos, fn($a) => $a['activo']));
    $inactivos = $total - $activos;
  ?>

  <div class="card">
    <div class="card-header">
      <div class="card-header-left">
        <span class="icon">👩‍🎓</span>
        <h2>Alumnos registrados</h2>
      </div>
      <button class="btn-nuevo" onclick="abrirModal(null)">+ Nuevo alumno</button>
    </div>
    <div class="card-body">

      <div class="stats-bar">
        <div class="stat-pill">Total: <span><?= $total ?></span></div>
        <div class="stat-pill">Activos: <span><?= $activos ?></span></div>
        <?php if ($inactivos > 0): ?>
        <div class="stat-pill">Suspendidos: <span><?= $inactivos ?></span></div>
        <?php endif; ?>
      </div>

      <input type="text" class="busqueda" id="busqueda" placeholder="Buscar por nombre o usuario…" oninput="filtrar()">

      <?php if (empty($alumnos)): ?>
        <p class="empty-msg">No hay alumnos registrados aún.</p>
      <?php else: ?>
      <div style="overflow-x:auto;">
      <table id="tabla-alumnos">
        <thead>
          <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Usuario</th>
            <th>Curso</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($alumnos as $al): ?>
          <tr id="row-<?= $al['id'] ?>"
              data-nombre="<?= strtolower(htmlspecialchars($al['nombre'])) ?>"
              data-usuario="<?= strtolower(htmlspecialchars($al['usuario'])) ?>">
            <td><?= $al['id'] ?></td>
            <td><strong><?= htmlspecialchars($al['nombre']) ?></strong></td>
            <td><?= htmlspecialchars($al['usuario']) ?></td>
            <td><?= $al['curso'] ? htmlspecialchars($al['curso']) : '<span style="color:#9CA3AF">—</span>' ?></td>
            <td id="estado-<?= $al['id'] ?>">
              <span class="estado-badge <?= $al['activo'] ? 'al-activo' : 'al-inactivo' ?>">
                <?= $al['activo'] ? 'Activo' : 'Suspendido' ?>
              </span>
            </td>
            <td>
              <div class="acciones" id="acc-<?= $al['id'] ?>">
                <button class="btn-accion btn-editar"
                  onclick='abrirModal(<?= json_encode(['id'=>(int)$al['id'],'nombre'=>$al['nombre'],'usuario'=>$al['usuario'],'curso_id'=>(int)($al['curso_id'] ?? 0)]) ?>)'>
                  Editar
                </button>
                <?php if ($al['activo']): ?>
                <button class="btn-accion btn-suspender" onclick="accion(<?= $al['id'] ?>,'suspender')">Suspender</button>
                <?php else: ?>
                <button class="btn-accion btn-reactivar" onclick="accion(<?= $al['id'] ?>,'reactivar')">Reactivar</button>
                <?php endif; ?>
                <button class="btn-accion btn-eliminar" onclick="accion(<?= $al['id'] ?>,'eliminar')">Eliminar</button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal crear / editar -->
<div class="overlay" id="overlay" onclick="cerrarOverlay(event)">
  <div class="modal-box" onclick="event.stopPropagation()">
    <h3 id="modal-titulo">Nuevo alumno</h3>
    <input type="hidden" id="al-id">
    <div class="modal-grid">
      <div class="span2">
        <label>Nombre completo</label>
        <input type="text" id="al-nombre" placeholder="Ej: García, Ana Laura">
      </div>
      <div>
        <label>Usuario (para login)</label>
        <input type="text" id="al-usuario" placeholder="ana.garcia">
      </div>
      <div>
        <label id="al-pass-label">Contraseña</label>
        <input type="password" id="al-password" placeholder="Mínimo 4 caracteres">
      </div>
      <div class="span2">
        <label>Curso</label>
        <select id="al-curso">
          <option value="0">— Sin asignar —</option>
          <?php foreach ($cursos as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-error" id="al-error"></div>
    <div class="modal-btns">
      <button class="btn-cancelar" onclick="document.getElementById('overlay').classList.remove('open')">Cancelar</button>
      <button class="btn-confirmar" id="btn-guardar" onclick="guardar()">Guardar</button>
    </div>
  </div>
</div>

<script>
  function filtrar() {
    const q = (document.getElementById('busqueda').value || '').toLowerCase().trim();
    document.querySelectorAll('#tabla-alumnos tbody tr').forEach(tr => {
      tr.style.display = (!q || tr.dataset.nombre.includes(q) || tr.dataset.usuario.includes(q)) ? '' : 'none';
    });
  }

  function abrirModal(al) {
    const nuevo = !al;
    document.getElementById('modal-titulo').textContent  = nuevo ? 'Nuevo alumno' : 'Editar alumno';
    document.getElementById('al-id').value               = nuevo ? '' : al.id;
    document.getElementById('al-nombre').value           = nuevo ? '' : al.nombre;
    document.getElementById('al-usuario').value          = nuevo ? '' : al.usuario;
    document.getElementById('al-password').value         = '';
    document.getElementById('al-pass-label').textContent = nuevo ? 'Contraseña' : 'Nueva contraseña (dejar vacío para no cambiar)';
    document.getElementById('al-curso').value            = nuevo ? '0' : String(al.curso_id || 0);
    document.getElementById('al-error').textContent      = '';
    document.getElementById('overlay').classList.add('open');
  }

  function cerrarOverlay(e) {
    if (e.target === document.getElementById('overlay'))
      document.getElementById('overlay').classList.remove('open');
  }

  async function guardar() {
    const id       = document.getElementById('al-id').value;
    const nombre   = document.getElementById('al-nombre').value.trim();
    const usuario  = document.getElementById('al-usuario').value.trim();
    const password = document.getElementById('al-password').value.trim();
    const curso_id = document.getElementById('al-curso').value;
    const errorEl  = document.getElementById('al-error');
    errorEl.textContent = '';

    if (!nombre)  { errorEl.textContent = 'El nombre es obligatorio.'; return; }
    if (!usuario) { errorEl.textContent = 'El usuario es obligatorio.'; return; }
    if (!id && !password) { errorEl.textContent = 'La contraseña es obligatoria para crear un alumno.'; return; }
    if (password && password.length < 4) { errorEl.textContent = 'La contraseña debe tener al menos 4 caracteres.'; return; }

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;

    const fd = new FormData();
    fd.append('id', id);
    fd.append('nombre', nombre);
    fd.append('usuario', usuario);
    fd.append('password', password);
    fd.append('curso_id', curso_id);

    try {
      const res  = await fetch('alumno_guardar.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.success) { errorEl.textContent = data.message; btn.disabled = false; return; }
      location.reload();
    } catch {
      errorEl.textContent = 'Error de conexión.';
      btn.disabled = false;
    }
  }

  async function accion(id, accionStr) {
    const msgs = {
      suspender: '¿Suspender este alumno? No podrá iniciar sesión.',
      reactivar: '¿Reactivar este alumno?',
      eliminar:  '¿Eliminar este alumno permanentemente?\nEsta acción no se puede deshacer.'
    };
    if (!confirm(msgs[accionStr])) return;

    const fd = new FormData();
    fd.append('id', id);
    fd.append('accion', accionStr);

    try {
      const res  = await fetch('alumno_eliminar.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.success) { alert(data.message); return; }

      if (accionStr === 'eliminar') {
        const row = document.getElementById(`row-${id}`);
        if (row) row.remove();
        return;
      }

      const activo   = accionStr === 'reactivar';
      const estadoEl = document.getElementById(`estado-${id}`);
      const accEl    = document.getElementById(`acc-${id}`);

      if (estadoEl) estadoEl.innerHTML = `<span class="estado-badge ${activo ? 'al-activo' : 'al-inactivo'}">${activo ? 'Activo' : 'Suspendido'}</span>`;
      if (accEl) {
        const btn = accEl.querySelector('.btn-suspender, .btn-reactivar');
        if (btn) {
          btn.className   = `btn-accion ${activo ? 'btn-suspender' : 'btn-reactivar'}`;
          btn.textContent = activo ? 'Suspender' : 'Reactivar';
          btn.onclick     = () => accion(id, activo ? 'suspender' : 'reactivar');
        }
      }
    } catch {
      alert('Error de conexión.');
    }
  }
</script>

</body>
</html>
