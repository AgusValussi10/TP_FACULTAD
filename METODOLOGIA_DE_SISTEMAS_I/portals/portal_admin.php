<?php
session_start();
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

    @media (max-width: 600px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
  </style>
</head>
<body>

<header>
  <a href="../index.html" class="logo-area">
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
  <p>Gestión de solicitudes de inscripción</p>
  <span class="rol-badge">Administrador</span>
</div>

<div class="container">

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
          <tr id="row-<?= $s['id'] ?>" class="row-<?= $s['estado'] ?>">
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
                  <button class="btn-accion btn-admitido"   onclick="cambiarEstado(<?= $s['id'] ?>,'admitido')">Admitir</button>
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

</div>

<script>
  const BOTONES = {
    contactado: '<button class="btn-accion btn-contactado" onclick="cambiarEstado(ID,\'contactado\')">Contactado</button>',
    admitido:   '<button class="btn-accion btn-admitido"   onclick="cambiarEstado(ID,\'admitido\')">Admitir</button>',
    rechazado:  '<button class="btn-accion btn-rechazado"  onclick="cambiarEstado(ID,\'rechazado\')">Rechazar</button>',
    pendiente:  '<button class="btn-accion btn-pendiente"  onclick="cambiarEstado(ID,\'pendiente\')">↩ Pendiente</button>',
  };

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
</script>

</body>
</html>
