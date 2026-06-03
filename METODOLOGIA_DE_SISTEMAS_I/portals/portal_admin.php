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

$opiniones = [];
$res3 = $conn->query(
    "SELECT id, nombre, texto, mes, anio, estado,
            DATE_FORMAT(created_at,'%d/%m/%Y %H:%i') AS fecha,
            UNIX_TIMESTAMP(created_at) AS ts
     FROM opiniones
     ORDER BY FIELD(estado,'pendiente','aprobado','rechazado'), created_at DESC"
);
if ($res3) {
    while ($r = $res3->fetch_assoc()) $opiniones[] = $r;
}

$puestos = [];
$res4 = $conn->query("SELECT id, titulo, descripcion, tipo, urgente, activo FROM puestos_vacantes ORDER BY created_at ASC");
if ($res4) while ($r = $res4->fetch_assoc()) $puestos[] = $r;

$postulaciones = [];
$res5 = $conn->query(
    "SELECT p.id, p.nombre, p.apellido, p.dni, p.email, p.telefono,
            p.experiencia_anios, p.experiencia_descripcion, p.estado,
            DATE_FORMAT(p.created_at,'%d/%m/%Y %H:%i') AS fecha,
            v.titulo AS puesto_titulo
     FROM postulaciones p
     JOIN puestos_vacantes v ON v.id = p.puesto_id
     ORDER BY FIELD(p.estado,'pendiente','revisado','seleccionado','rechazado'), p.created_at DESC"
);
if ($res5) while ($r = $res5->fetch_assoc()) $postulaciones[] = $r;

$consultas = [];
$res6 = $conn->query(
    "SELECT id, nombre, email, asunto, mensaje, estado,
            DATE_FORMAT(created_at,'%d/%m/%Y %H:%i') AS fecha,
            UNIX_TIMESTAMP(created_at) AS ts
     FROM consultas
     ORDER BY FIELD(estado,'pendiente','leida','respondida','archivada'), created_at DESC"
);
if ($res6) while ($r = $res6->fetch_assoc()) $consultas[] = $r;

$con_stats = ['total'=>0,'pendiente'=>0,'leida'=>0,'respondida'=>0,'archivada'=>0];
foreach ($consultas as $c) { $con_stats[$c['estado']]++; $con_stats['total']++; }

$noticias = [];
$res7 = $conn->query(
    "SELECT id, titulo, resumen, contenido, categoria, imagen_url, estado,
            DATE_FORMAT(fecha_pub,'%d/%m/%Y') AS fecha_pub_fmt,
            fecha_pub AS fecha_pub_raw,
            DATE_FORMAT(created_at,'%d/%m/%Y %H:%i') AS fecha,
            UNIX_TIMESTAMP(created_at) AS ts
     FROM noticias
     ORDER BY FIELD(estado,'publicada','borrador','archivada'), created_at DESC"
);
if ($res7) while ($r = $res7->fetch_assoc()) $noticias[] = $r;

$not_stats = ['total'=>0,'borrador'=>0,'publicada'=>0,'archivada'=>0];
foreach ($noticias as $n) { $not_stats[$n['estado']]++; $not_stats['total']++; }

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

    /* ── Texto largo truncado ── */
    .texto-opinion { font-size:.84rem; color:#4B5563; max-width:320px; line-height:1.5; }
    .nombre-opinion { font-weight:800; font-size:.88rem; }
    .mes-anio { font-size:.78rem; color:#9CA3AF; }

    /* ── Toggle ON/OFF ── */
    .toggle-wrap { display:flex; align-items:center; gap:.5rem; }
    .toggle-switch { position:relative; display:inline-block; width:46px; height:26px; flex-shrink:0; }
    .toggle-switch input { opacity:0; width:0; height:0; }
    .toggle-track { position:absolute; cursor:pointer; inset:0; background:#D1D5DB; border-radius:26px; transition:.25s; }
    .toggle-track::before { content:''; position:absolute; width:20px; height:20px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.25s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
    .toggle-switch input:checked + .toggle-track { background:#059669; }
    .toggle-switch input:checked + .toggle-track::before { transform:translateX(20px); }
    .toggle-switch input:disabled + .toggle-track { opacity:.6; cursor:default; }
    .toggle-lbl { font-size:.82rem; font-weight:800; min-width:28px; }
    .toggle-lbl.on  { color:#059669; }
    .toggle-lbl.off { color:#9CA3AF; }

    /* ── Sort activo ── */
    .sort-btn.activo { background:#DBEAFE; color:#1E40AF; }

    /* ── Propuestas ── */
    .puesto-tag { display:inline-block; border-radius:20px; padding:.15rem .6rem; font-size:.73rem; font-weight:800; margin-right:.3rem; }
    .puesto-tag.full     { background:#EDE9FE; color:#5B21B6; }
    .puesto-tag.part     { background:#DBEAFE; color:#1E40AF; }
    .puesto-tag.medio    { background:#FEF3C7; color:#92400E; }
    .puesto-tag.urgente  { background:#FEE2E2; color:#991B1B; }
    .puesto-tag.activo   { background:#D1FAE5; color:#065F46; }
    .puesto-tag.inactivo { background:#F3F4F6; color:#6B7280; }
    .add-puesto-form { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-top:1.2rem; padding-top:1.2rem; border-top:2px solid var(--borde); }
    .add-puesto-form input, .add-puesto-form select, .add-puesto-form textarea { width:100%; padding:.6rem .8rem; border:2px solid #E5E7EB; border-radius:10px; font-size:.88rem; font-family:inherit; }
    .add-puesto-form input:focus, .add-puesto-form select:focus, .add-puesto-form textarea:focus { outline:none; border-color:var(--verde); }
    .add-puesto-form .span2 { grid-column: 1 / -1; }
    .btn-agregar { background:var(--verde); color:#fff; border:none; border-radius:10px; padding:.6rem 1.4rem; font-weight:800; cursor:pointer; font-family:inherit; font-size:.88rem; transition:opacity .2s; }
    .btn-agregar:hover { opacity:.85; }
    .estado-revisado    { background:#EDE9FE; color:#5B21B6; }
    .estado-seleccionado{ background:#D1FAE5; color:#065F46; }
    .exp-txt { font-size:.82rem; color:#4B5563; max-width:280px; line-height:1.4; }

    /* ── Tabs ── */
    .tabs-nav {
      display: flex; gap: .5rem;
      background: var(--blanco); border-bottom: 2px solid var(--borde);
      padding: 0 1.5rem; position: sticky; top: 65px; z-index: 90;
      box-shadow: 0 2px 8px rgba(0,0,0,.05);
      overflow-x: auto;
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

    /* ── Noticias ── */
    .estado-borrador  { background:#FEF3C7; color:#92400E; }
    .estado-publicada { background:#D1FAE5; color:#065F46; }
    .estado-archivada { background:#F3F4F6; color:#6B7280; }
    .cat-tag { display:inline-block; border-radius:20px; padding:.15rem .6rem; font-size:.73rem; font-weight:800; }
    .cat-institucional { background:#DBEAFE; color:#1E40AF; }
    .cat-academica     { background:#EDE9FE; color:#5B21B6; }
    .cat-deportiva     { background:#D1FAE5; color:#065F46; }
    .cat-cultural      { background:#FEF3C7; color:#92400E; }
    .cat-general       { background:#F3F4F6; color:#374151; }
    .add-noticia-form { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-top:1.2rem; padding-top:1.2rem; border-top:2px solid var(--borde); }
    .add-noticia-form input,
    .add-noticia-form select,
    .add-noticia-form textarea { width:100%; padding:.6rem .8rem; border:2px solid #E5E7EB; border-radius:10px; font-size:.88rem; font-family:inherit; }
    .add-noticia-form input:focus,
    .add-noticia-form select:focus,
    .add-noticia-form textarea:focus { outline:none; border-color:var(--verde); }
    .add-noticia-form .span2 { grid-column:1/-1; }
    .noticia-titulo { font-weight:800; font-size:.88rem; max-width:240px; line-height:1.4; }
    .noticia-resumen { font-size:.82rem; color:#4B5563; max-width:300px; line-height:1.4; }

    /* Modal editar noticia */
    .modal-not-box { background:#fff; border-radius:20px; padding:2rem; width:100%; max-width:620px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.25); animation:popIn .2s ease; }
    .modal-not-box h3 { font-size:1.1rem; font-weight:900; color:var(--azul); margin-bottom:1rem; }
    .modal-not-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
    .modal-not-grid .span2 { grid-column:1/-1; }
    .modal-not-grid input,
    .modal-not-grid select,
    .modal-not-grid textarea { width:100%; padding:.6rem .8rem; border:2px solid #E5E7EB; border-radius:10px; font-size:.88rem; font-family:inherit; }
    .modal-not-grid input:focus,
    .modal-not-grid select:focus,
    .modal-not-grid textarea:focus { outline:none; border-color:var(--verde); }
    .modal-not-grid label { display:block; font-size:.82rem; font-weight:700; margin-bottom:.25rem; color:#374151; }

    /* Modal confirmar eliminar */
    .modal-del-box { background:#fff; border-radius:20px; padding:2rem; width:100%; max-width:380px; box-shadow:0 20px 60px rgba(0,0,0,.25); animation:popIn .2s ease; text-align:center; }
    .modal-del-box h3 { font-size:1.05rem; font-weight:900; color:#DC2626; margin-bottom:.5rem; }
    .modal-del-box p { font-size:.88rem; color:#4B5563; margin-bottom:1.4rem; }
    .stat-card.borrador   .stat-num { color:#D97706; }
    .stat-card.publicada  .stat-num { color:#059669; }
    .stat-card.archivada  .stat-num { color:#9CA3AF; }
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
  <button class="tab-btn" data-tab="consultas">📩 Consultas</button>
  <button class="tab-btn" data-tab="noticias">📰 Noticias</button>
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

    <!-- Stats opiniones -->
    <?php
      $op_stats = ['total'=>0,'pendiente'=>0,'aprobado'=>0,'rechazado'=>0];
      foreach ($opiniones as $o) {
          $op_stats[$o['estado']]++;
          $op_stats['total']++;
      }
    ?>
    <div class="stats-grid" style="margin-top:2rem;">
      <div class="stat-card total">
        <div class="stat-num"><?= $op_stats['total'] ?></div>
        <div class="stat-label">Total</div>
      </div>
      <div class="stat-card pendiente">
        <div class="stat-num"><?= $op_stats['pendiente'] ?></div>
        <div class="stat-label">Pendientes</div>
      </div>
      <div class="stat-card admitido">
        <div class="stat-num"><?= $op_stats['aprobado'] ?></div>
        <div class="stat-label">Aprobadas</div>
      </div>
      <div class="stat-card rechazado">
        <div class="stat-num"><?= $op_stats['rechazado'] ?></div>
        <div class="stat-label">Rechazadas</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="icon">💬</span>
        <h2>Opiniones recibidas</h2>
      </div>
      <div class="card-body">
        <?php if (empty($opiniones)): ?>
          <p class="empty-msg">No hay opiniones registradas aún.</p>
        <?php else: ?>

        <!-- Barra de ordenamiento -->
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:1rem;flex-wrap:wrap;">
          <span style="font-size:.83rem;font-weight:700;color:#6B7280;">Ordenar:</span>
          <button class="btn-accion sort-btn activo" id="sort-desc" onclick="sortOpiniones('desc')">↓ Más nuevas</button>
          <button class="btn-accion sort-btn" id="sort-asc"  onclick="sortOpiniones('asc')">↑ Más antiguas</button>
        </div>

        <table id="tabla-opiniones">
          <thead>
            <tr>
              <th>#</th>
              <th>Nombre</th>
              <th>Opinión</th>
              <th>Período</th>
              <th>Fecha envío</th>
              <th>Visible en web</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $meses_es = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                         'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            foreach ($opiniones as $o):
              $isOn    = $o['estado'] === 'aprobado';
              $periodo = $meses_es[(int)$o['mes']] . ' ' . $o['anio'];
              $rowCls  = $isOn ? 'row-admitido' : ($o['estado'] === 'rechazado' ? 'row-rechazado' : '');
            ?>
            <tr id="op-row-<?= $o['id'] ?>" class="<?= $rowCls ?>" data-ts="<?= $o['ts'] ?>">
              <td><?= $o['id'] ?></td>
              <td><span class="nombre-opinion"><?= htmlspecialchars($o['nombre']) ?></span></td>
              <td><span class="texto-opinion"><?= htmlspecialchars(mb_strimwidth($o['texto'], 0, 120, '…')) ?></span></td>
              <td><span class="mes-anio"><?= $periodo ?></span></td>
              <td style="white-space:nowrap"><?= $o['fecha'] ?></td>
              <td>
                <div class="toggle-wrap">
                  <label class="toggle-switch">
                    <input type="checkbox" id="op-chk-<?= $o['id'] ?>"
                           <?= $isOn ? 'checked' : '' ?>
                           onchange="toggleOpinion(<?= $o['id'] ?>, this.checked)">
                    <span class="toggle-track"></span>
                  </label>
                  <span class="toggle-lbl <?= $isOn ? 'on' : 'off' ?>" id="op-lbl-<?= $o['id'] ?>">
                    <?= $isOn ? 'ON' : 'OFF' ?>
                  </span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /panel-opiniones -->

  <!-- ══ TAB: PROPUESTAS DE TRABAJO ══ -->
  <div class="tab-panel" id="panel-propuestas">

    <!-- ── Puestos vacantes ── -->
    <div class="card" style="margin-top:2rem;">
      <div class="card-header">
        <span class="icon">💼</span>
        <h2>Puestos Vacantes</h2>
      </div>
      <div class="card-body">
        <table id="tabla-puestos">
          <thead>
            <tr><th>#</th><th>Puesto</th><th>Descripción</th><th>Tipo</th><th>Visible en web</th></tr>
          </thead>
          <tbody>
          <?php foreach ($puestos as $p): ?>
            <tr id="puesto-row-<?= $p['id'] ?>" class="<?= $p['activo'] ? 'row-admitido' : '' ?>">
              <td><?= $p['id'] ?></td>
              <td>
                <strong><?= htmlspecialchars($p['titulo']) ?></strong>
                <?php if ($p['urgente']): ?><span class="puesto-tag urgente">Urgente</span><?php endif; ?>
              </td>
              <td><span class="exp-txt"><?= htmlspecialchars($p['descripcion']) ?></span></td>
              <td>
                <?php $tipoCls = $p['tipo'] === 'Part-time' ? 'part' : ($p['tipo'] === 'Medio turno' ? 'medio' : 'full'); ?>
                <span class="puesto-tag <?= $tipoCls ?>"><?= htmlspecialchars($p['tipo']) ?></span>
              </td>
              <td>
                <div class="toggle-wrap">
                  <label class="toggle-switch">
                    <input type="checkbox" id="puesto-chk-<?= $p['id'] ?>"
                           <?= $p['activo'] ? 'checked' : '' ?>
                           onchange="togglePuesto(<?= $p['id'] ?>, this.checked)">
                    <span class="toggle-track"></span>
                  </label>
                  <span class="toggle-lbl <?= $p['activo'] ? 'on' : 'off' ?>" id="puesto-lbl-<?= $p['id'] ?>">
                    <?= $p['activo'] ? 'ON' : 'OFF' ?>
                  </span>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Formulario agregar puesto -->
        <div class="add-puesto-form">
          <div>
            <label style="font-size:.82rem;font-weight:700;display:block;margin-bottom:.3rem;">Título del puesto *</label>
            <input type="text" id="new-titulo" placeholder="ej: Docente de Matemáticas" maxlength="150">
          </div>
          <div>
            <label style="font-size:.82rem;font-weight:700;display:block;margin-bottom:.3rem;">Tipo *</label>
            <select id="new-tipo">
              <option value="Tiempo completo">Tiempo completo</option>
              <option value="Part-time">Part-time</option>
              <option value="Medio turno">Medio turno</option>
            </select>
          </div>
          <div class="span2">
            <label style="font-size:.82rem;font-weight:700;display:block;margin-bottom:.3rem;">Descripción / Requisitos *</label>
            <textarea id="new-desc" rows="2" placeholder="Requisitos y descripción del puesto..." maxlength="500"></textarea>
          </div>
          <div style="display:flex;align-items:center;gap:.5rem;">
            <input type="checkbox" id="new-urgente" style="width:auto;">
            <label for="new-urgente" style="font-size:.84rem;font-weight:700;">Marcar como urgente</label>
          </div>
          <div style="display:flex;align-items:flex-end;">
            <button class="btn-agregar" onclick="agregarPuesto()">+ Agregar puesto</button>
          </div>
          <p id="new-puesto-msg" class="span2" style="font-size:.84rem;font-weight:700;min-height:1.1rem;"></p>
        </div>
      </div>
    </div>

    <!-- ── Postulaciones recibidas ── -->
    <div class="card" style="margin-top:1.5rem;">
      <div class="card-header">
        <span class="icon">📨</span>
        <h2>Postulaciones recibidas</h2>
      </div>
      <div class="card-body">
        <?php if (empty($postulaciones)): ?>
          <p class="empty-msg">No hay postulaciones registradas aún.</p>
        <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th><th>Puesto</th><th>Nombre</th><th>DNI</th>
              <th>Email</th><th>Teléfono</th><th>Exp.</th>
              <th>Descripción exp.</th><th>Fecha</th><th>Estado</th><th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($postulaciones as $p):
            $rowCls = match($p['estado']) {
                'seleccionado' => 'row-admitido',
                'rechazado'    => 'row-rechazado',
                'revisado'     => 'row-contactado',
                default        => ''
            };
          ?>
            <tr id="post-row-<?= $p['id'] ?>" class="<?= $rowCls ?>">
              <td><?= $p['id'] ?></td>
              <td style="white-space:nowrap"><strong><?= htmlspecialchars($p['puesto_titulo']) ?></strong></td>
              <td><?= htmlspecialchars($p['apellido'].', '.$p['nombre']) ?></td>
              <td><?= htmlspecialchars($p['dni']) ?></td>
              <td><?= htmlspecialchars($p['email']) ?></td>
              <td><?= htmlspecialchars($p['telefono']) ?></td>
              <td style="text-align:center"><?= $p['experiencia_anios'] ?> año<?= $p['experiencia_anios'] !== '1' ? 's' : '' ?></td>
              <td><span class="exp-txt"><?= htmlspecialchars(mb_strimwidth($p['experiencia_descripcion'], 0, 100, '…')) ?></span></td>
              <td style="white-space:nowrap"><?= $p['fecha'] ?></td>
              <td id="post-estado-<?= $p['id'] ?>">
                <span class="estado-badge estado-<?= $p['estado'] === 'seleccionado' ? 'admitido' : ($p['estado'] === 'revisado' ? 'contactado' : $p['estado']) ?>">
                  <?= ucfirst($p['estado']) ?>
                </span>
              </td>
              <td>
                <div class="acciones" id="post-acc-<?= $p['id'] ?>">
                  <?php if ($p['estado'] !== 'revisado'):    ?><button class="btn-accion btn-contactado" onclick="postAccion(<?= $p['id'] ?>,'revisado')">Revisado</button><?php endif; ?>
                  <?php if ($p['estado'] !== 'seleccionado'):?><button class="btn-accion btn-admitido"   onclick="postAccion(<?= $p['id'] ?>,'seleccionado')">Seleccionar</button><?php endif; ?>
                  <?php if ($p['estado'] !== 'rechazado'):   ?><button class="btn-accion btn-rechazado"  onclick="postAccion(<?= $p['id'] ?>,'rechazado')">Rechazar</button><?php endif; ?>
                  <?php if ($p['estado'] !== 'pendiente'):   ?><button class="btn-accion btn-pendiente"  onclick="postAccion(<?= $p['id'] ?>,'pendiente')">↩ Pendiente</button><?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /panel-propuestas -->

  <!-- ══ TAB: CONSULTAS ══ -->
  <div class="tab-panel" id="panel-consultas">

    <div class="stats-grid" style="margin-top:2rem;">
      <div class="stat-card total">
        <div class="stat-num" id="con-stat-total"><?= $con_stats['total'] ?></div>
        <div class="stat-label">Total</div>
      </div>
      <div class="stat-card pendiente">
        <div class="stat-num" id="con-stat-pendiente"><?= $con_stats['pendiente'] ?></div>
        <div class="stat-label">Pendientes</div>
      </div>
      <div class="stat-card contactado">
        <div class="stat-num" id="con-stat-leida"><?= $con_stats['leida'] ?></div>
        <div class="stat-label">Leídas</div>
      </div>
      <div class="stat-card admitido">
        <div class="stat-num" id="con-stat-respondida"><?= $con_stats['respondida'] ?></div>
        <div class="stat-label">Respondidas</div>
      </div>
      <div class="stat-card rechazado">
        <div class="stat-num" id="con-stat-archivada"><?= $con_stats['archivada'] ?></div>
        <div class="stat-label">Archivadas</div>
      </div>
    </div>

    <div class="card" style="margin-top:1.5rem;">
      <div class="card-header">
        <span class="icon">📩</span>
        <h2>Consultas recibidas</h2>
      </div>
      <div class="card-body" id="consultas-card-body">
        <?php if (empty($consultas)): ?>
          <p class="empty-msg" id="consultas-empty">No hay consultas registradas aún.</p>
        <?php else: ?>
        <table id="tabla-consultas">
          <thead>
            <tr>
              <th>#</th>
              <th>Nombre</th>
              <th>Email</th>
              <th>Asunto</th>
              <th>Mensaje</th>
              <th>Fecha</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($consultas as $c):
              $rowCls = match($c['estado']) {
                'leida'      => 'row-contactado',
                'respondida' => 'row-admitido',
                'archivada'  => 'row-rechazado',
                default      => ''
              };
            ?>
            <tr id="con-row-<?= $c['id'] ?>" class="<?= $rowCls ?>">
              <td><?= $c['id'] ?></td>
              <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
              <td><?= htmlspecialchars($c['email']) ?></td>
              <td><?= $c['asunto'] ? htmlspecialchars(mb_strimwidth($c['asunto'], 0, 50, '…')) : '—' ?></td>
              <td><span class="comentario-txt"><?= htmlspecialchars(mb_strimwidth($c['mensaje'], 0, 80, '…')) ?></span></td>
              <td style="white-space:nowrap"><?= $c['fecha'] ?></td>
              <td id="con-estado-<?= $c['id'] ?>">
                <span class="estado-badge estado-<?= $c['estado'] === 'leida' ? 'contactado' : ($c['estado'] === 'respondida' ? 'admitido' : ($c['estado'] === 'archivada' ? 'rechazado' : 'pendiente')) ?>"><?= ucfirst($c['estado']) ?></span>
              </td>
              <td>
                <div class="acciones" id="con-acc-<?= $c['id'] ?>">
                  <?php if ($c['estado'] !== 'leida'):      ?><button class="btn-accion btn-contactado" onclick="conAccion(<?= $c['id'] ?>,'leida')">Leída</button><?php endif; ?>
                  <?php if ($c['estado'] !== 'respondida'): ?><button class="btn-accion btn-admitido"   onclick="conAccion(<?= $c['id'] ?>,'respondida')">Respondida</button><?php endif; ?>
                  <?php if ($c['estado'] !== 'archivada'):  ?><button class="btn-accion btn-rechazado"  onclick="conAccion(<?= $c['id'] ?>,'archivada')">Archivar</button><?php endif; ?>
                  <?php if ($c['estado'] !== 'pendiente'):  ?><button class="btn-accion btn-pendiente"  onclick="conAccion(<?= $c['id'] ?>,'pendiente')">↩ Pendiente</button><?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /panel-consultas -->

  <!-- ══ TAB: NOTICIAS ══ -->
  <div class="tab-panel" id="panel-noticias">

    <div class="stats-grid" style="margin-top:2rem;">
      <div class="stat-card total">
        <div class="stat-num" id="not-stat-total"><?= $not_stats['total'] ?></div>
        <div class="stat-label">Total</div>
      </div>
      <div class="stat-card publicada">
        <div class="stat-num" id="not-stat-publicada"><?= $not_stats['publicada'] ?></div>
        <div class="stat-label">Publicadas</div>
      </div>
      <div class="stat-card borrador">
        <div class="stat-num" id="not-stat-borrador"><?= $not_stats['borrador'] ?></div>
        <div class="stat-label">Borradores</div>
      </div>
      <div class="stat-card archivada">
        <div class="stat-num" id="not-stat-archivada"><?= $not_stats['archivada'] ?></div>
        <div class="stat-label">Archivadas</div>
      </div>
    </div>

    <!-- Formulario nueva noticia -->
    <div class="card" style="margin-top:1.5rem;">
      <div class="card-header">
        <span class="icon">✏️</span>
        <h2>Nueva Noticia / Novedad</h2>
      </div>
      <div class="card-body">
        <div class="add-noticia-form">
          <div>
            <label style="font-size:.82rem;font-weight:700;display:block;margin-bottom:.3rem;">Título *</label>
            <input type="text" id="not-titulo" placeholder="ej: Inicio de inscripciones 2027" maxlength="200">
          </div>
          <div>
            <label style="font-size:.82rem;font-weight:700;display:block;margin-bottom:.3rem;">Categoría *</label>
            <select id="not-categoria">
              <option value="institucional">Institucional</option>
              <option value="academica">Académica</option>
              <option value="deportiva">Deportiva</option>
              <option value="cultural">Cultural</option>
              <option value="general" selected>General</option>
            </select>
          </div>
          <div class="span2">
            <label style="font-size:.82rem;font-weight:700;display:block;margin-bottom:.3rem;">Resumen (opcional)</label>
            <input type="text" id="not-resumen" placeholder="Breve descripción que aparecerá en listados" maxlength="400">
          </div>
          <div class="span2">
            <label style="font-size:.82rem;font-weight:700;display:block;margin-bottom:.3rem;">Contenido *</label>
            <textarea id="not-contenido" rows="4" placeholder="Escribí el cuerpo completo de la noticia..." maxlength="5000"></textarea>
          </div>
          <div>
            <label style="font-size:.82rem;font-weight:700;display:block;margin-bottom:.3rem;">URL de imagen (opcional)</label>
            <input type="url" id="not-imagen" placeholder="https://...">
          </div>
          <div>
            <label style="font-size:.82rem;font-weight:700;display:block;margin-bottom:.3rem;">Fecha de publicación (opcional)</label>
            <input type="date" id="not-fecha-pub">
          </div>
          <div style="display:flex;align-items:center;gap:.6rem;">
            <select id="not-estado" style="width:auto;padding:.6rem .8rem;border:2px solid #E5E7EB;border-radius:10px;font-size:.88rem;font-family:inherit;">
              <option value="borrador">Guardar como borrador</option>
              <option value="publicada">Publicar ahora</option>
            </select>
          </div>
          <div style="display:flex;align-items:flex-end;">
            <button class="btn-agregar" onclick="guardarNoticia()">+ Guardar noticia</button>
          </div>
          <p id="not-msg" class="span2" style="font-size:.84rem;font-weight:700;min-height:1.1rem;"></p>
        </div>
      </div>
    </div>

    <!-- Tabla de noticias -->
    <div class="card" style="margin-top:1.5rem;" id="not-tabla-card">
      <div class="card-header">
        <span class="icon">📰</span>
        <h2>Noticias y Novedades</h2>
      </div>
      <div class="card-body" id="not-card-body">
        <?php if (empty($noticias)): ?>
          <p class="empty-msg" id="not-empty">No hay noticias registradas aún.</p>
        <?php else: ?>
        <table id="tabla-noticias">
          <thead>
            <tr>
              <th>#</th>
              <th>Título</th>
              <th>Categoría</th>
              <th>Estado</th>
              <th>Fecha pub.</th>
              <th>Creada</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($noticias as $n):
              $rowCls = match($n['estado']) {
                'publicada' => 'row-admitido',
                'archivada' => 'row-rechazado',
                default     => ''
              };
              $catCls = 'cat-' . $n['categoria'];
              $catLabel = ['institucional'=>'Institucional','academica'=>'Académica','deportiva'=>'Deportiva','cultural'=>'Cultural','general'=>'General'][$n['categoria']] ?? ucfirst($n['categoria']);
            ?>
            <tr id="not-row-<?= $n['id'] ?>" class="<?= $rowCls ?>">
              <td><?= $n['id'] ?></td>
              <td>
                <span class="noticia-titulo"><?= htmlspecialchars($n['titulo']) ?></span>
                <?php if ($n['resumen']): ?>
                  <br><span class="noticia-resumen"><?= htmlspecialchars(mb_strimwidth($n['resumen'], 0, 80, '…')) ?></span>
                <?php endif; ?>
              </td>
              <td><span class="cat-tag <?= $catCls ?>"><?= $catLabel ?></span></td>
              <td id="not-estado-<?= $n['id'] ?>">
                <span class="estado-badge estado-<?= $n['estado'] ?>"><?= ucfirst($n['estado']) ?></span>
              </td>
              <td style="white-space:nowrap"><?= $n['fecha_pub_fmt'] ?: '—' ?></td>
              <td style="white-space:nowrap"><?= $n['fecha'] ?></td>
              <td>
                <div class="acciones" id="not-acc-<?= $n['id'] ?>">
                  <button class="btn-accion btn-contactado" onclick="abrirEditarNoticia(<?= $n['id'] ?>)">✏️ Editar</button>
                  <?php if ($n['estado'] !== 'publicada'): ?>
                    <button class="btn-accion btn-admitido" onclick="cambiarEstadoNoticia(<?= $n['id'] ?>, 'publicada')">Publicar</button>
                  <?php endif; ?>
                  <?php if ($n['estado'] === 'publicada'): ?>
                    <button class="btn-accion btn-pendiente" onclick="cambiarEstadoNoticia(<?= $n['id'] ?>, 'borrador')">Despublicar</button>
                  <?php endif; ?>
                  <?php if ($n['estado'] !== 'archivada'): ?>
                    <button class="btn-accion btn-rechazado" onclick="cambiarEstadoNoticia(<?= $n['id'] ?>, 'archivada')">Archivar</button>
                  <?php endif; ?>
                  <button class="btn-accion" style="background:#FEE2E2;color:#991B1B;" onclick="confirmarEliminarNoticia(<?= $n['id'] ?>, <?= json_encode($n['titulo']) ?>)">🗑️ Eliminar</button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /panel-noticias -->

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

<!-- Modal editar noticia -->
<div class="overlay" id="overlay-editar-noticia" onclick="cerrarModalNot(event)">
  <div class="modal-not-box">
    <h3>✏️ Editar Noticia</h3>
    <input type="hidden" id="edit-not-id">
    <div class="modal-not-grid">
      <div class="span2">
        <label>Título *</label>
        <input type="text" id="edit-not-titulo" maxlength="200">
      </div>
      <div>
        <label>Categoría *</label>
        <select id="edit-not-categoria">
          <option value="institucional">Institucional</option>
          <option value="academica">Académica</option>
          <option value="deportiva">Deportiva</option>
          <option value="cultural">Cultural</option>
          <option value="general">General</option>
        </select>
      </div>
      <div>
        <label>Estado *</label>
        <select id="edit-not-estado">
          <option value="borrador">Borrador</option>
          <option value="publicada">Publicada</option>
          <option value="archivada">Archivada</option>
        </select>
      </div>
      <div class="span2">
        <label>Resumen (opcional)</label>
        <input type="text" id="edit-not-resumen" maxlength="400">
      </div>
      <div class="span2">
        <label>Contenido *</label>
        <textarea id="edit-not-contenido" rows="5" maxlength="5000"></textarea>
      </div>
      <div>
        <label>URL de imagen (opcional)</label>
        <input type="url" id="edit-not-imagen">
      </div>
      <div>
        <label>Fecha de publicación (opcional)</label>
        <input type="date" id="edit-not-fecha-pub">
      </div>
    </div>
    <p class="modal-error" id="edit-not-error" style="margin-top:.8rem;"></p>
    <div class="modal-btns" style="margin-top:1.2rem;">
      <button class="btn-cancelar" onclick="document.getElementById('overlay-editar-noticia').classList.remove('open')">Cancelar</button>
      <button class="btn-confirmar" id="btn-guardar-edicion" onclick="guardarEdicionNoticia()">Guardar cambios</button>
    </div>
  </div>
</div>

<!-- Modal confirmar eliminación noticia -->
<div class="overlay" id="overlay-eliminar-noticia" onclick="cerrarModalDelNot(event)">
  <div class="modal-del-box">
    <h3>🗑️ Eliminar noticia</h3>
    <p id="del-not-texto">¿Estás seguro que querés eliminar esta noticia? Esta acción no se puede deshacer.</p>
    <input type="hidden" id="del-not-id">
    <div class="modal-btns" style="justify-content:center;">
      <button class="btn-cancelar" onclick="document.getElementById('overlay-eliminar-noticia').classList.remove('open')">Cancelar</button>
      <button class="btn-confirmar" id="btn-confirmar-eliminar" style="background:#DC2626;" onclick="ejecutarEliminarNoticia()">Sí, eliminar</button>
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
  setInterval(pollingOpiniones,  20000);
  setInterval(pollingPropuestas, 20000);
  setInterval(pollingConsultas,  20000);

  // ── Polling opiniones ────────────────────────────────────────────
  async function pollingOpiniones() {
    try {
      const res = await fetch('../opiniones/polling.php', { credentials:'same-origin' });
      if (!res.ok) return;
      const { stats, opiniones } = await res.json();

      // Actualizar stats del tab
      const grid = document.querySelector('#panel-opiniones .stats-grid');
      if (grid) {
        const nums = grid.querySelectorAll('.stat-num');
        const vals = [stats.total, stats.pendiente, stats.aprobado, stats.rechazado];
        nums.forEach((n, i) => { if (vals[i] !== undefined && n.textContent !== String(vals[i])) n.textContent = vals[i]; });
      }

      if (!opiniones || !opiniones.length) return;
      const tbody = document.querySelector('#tabla-opiniones tbody');
      if (!tbody) return;

      const mesesEs = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
      let nuevas = 0;
      opiniones.forEach(o => {
        if (document.getElementById(`op-row-${o.id}`)) return;
        const isOn    = o.estado === 'aprobado';
        const rowCls  = isOn ? 'row-admitido' : (o.estado === 'rechazado' ? 'row-rechazado' : '');
        const periodo = mesesEs[parseInt(o.mes)] + ' ' + o.anio;
        const txt     = o.texto.length > 120 ? esc(o.texto.substring(0,120)) + '…' : esc(o.texto);
        tbody.insertAdjacentHTML('afterbegin', `
          <tr id="op-row-${o.id}" class="${rowCls} fila-nueva" data-ts="${o.ts}">
            <td>${o.id}</td>
            <td><span class="nombre-opinion">${esc(o.nombre)}</span></td>
            <td><span class="texto-opinion">${txt}</span></td>
            <td><span class="mes-anio">${periodo}</span></td>
            <td style="white-space:nowrap">${o.fecha}</td>
            <td><div class="toggle-wrap">
              <label class="toggle-switch">
                <input type="checkbox" id="op-chk-${o.id}" ${isOn?'checked':''} onchange="toggleOpinion(${o.id}, this.checked)">
                <span class="toggle-track"></span>
              </label>
              <span class="toggle-lbl ${isOn?'on':'off'}" id="op-lbl-${o.id}">${isOn?'ON':'OFF'}</span>
            </div></td>
          </tr>`);
        nuevas++;
      });
      if (nuevas > 0) mostrarToast(nuevas === 1 ? 'Nueva opinión recibida' : `${nuevas} nuevas opiniones`);
    } catch { /* reintentar */ }
  }

  // ── Polling propuestas ────────────────────────────────────────────
  async function pollingPropuestas() {
    try {
      const res = await fetch('../propuestas/polling.php', { credentials:'same-origin' });
      if (!res.ok) return;
      const { postulaciones } = await res.json();
      if (!postulaciones || !postulaciones.length) return;

      const tabla = document.querySelector('#panel-propuestas .card:last-child table');
      if (!tabla) return;
      const tbody = tabla.querySelector('tbody');
      if (!tbody) return;

      let nuevas = 0;
      postulaciones.forEach(p => {
        if (document.getElementById(`post-row-${p.id}`)) return;
        const rowCls  = POST_ROW[p.estado]  || '';
        const badgeCls= POST_BADGE[p.estado]|| p.estado;
        const expTxt  = p.experiencia_descripcion.length > 100 ? esc(p.experiencia_descripcion.substring(0,100)) + '…' : esc(p.experiencia_descripcion);
        const todos   = ['revisado','seleccionado','rechazado','pendiente'];
        const btns    = todos.filter(e => e !== p.estado).map(e => {
          const cls = {revisado:'btn-contactado',seleccionado:'btn-admitido',rechazado:'btn-rechazado',pendiente:'btn-pendiente'}[e];
          return `<button class="btn-accion ${cls}" onclick="postAccion(${p.id},'${e}')">${POST_LABELS[e]}</button>`;
        }).join('');
        tbody.insertAdjacentHTML('afterbegin', `
          <tr id="post-row-${p.id}" class="${rowCls} fila-nueva">
            <td>${p.id}</td>
            <td style="white-space:nowrap"><strong>${esc(p.puesto_titulo)}</strong></td>
            <td>${esc(p.apellido)}, ${esc(p.nombre)}</td>
            <td>${esc(p.dni)}</td>
            <td>${esc(p.email)}</td>
            <td>${esc(p.telefono)}</td>
            <td style="text-align:center">${p.experiencia_anios} año${p.experiencia_anios != 1 ? 's' : ''}</td>
            <td><span class="exp-txt">${expTxt}</span></td>
            <td style="white-space:nowrap">${p.fecha}</td>
            <td id="post-estado-${p.id}"><span class="estado-badge estado-${badgeCls}">${POST_LABELS[p.estado]}</span></td>
            <td><div class="acciones" id="post-acc-${p.id}">${btns}</div></td>
          </tr>`);
        nuevas++;
      });
      if (nuevas > 0) mostrarToast(nuevas === 1 ? 'Nueva postulación de trabajo recibida' : `${nuevas} nuevas postulaciones`);
    } catch { /* reintentar */ }
  }

  // ── Puestos vacantes ──────────────────────────────────────────────
  async function togglePuesto(id, isOn) {
    const chk = document.getElementById(`puesto-chk-${id}`);
    chk.disabled = true;
    const body = new FormData();
    body.append('id', id);
    body.append('activo', isOn ? '1' : '0');
    try {
      const res  = await fetch('../propuestas/cambiar_estado_puesto.php', { method:'POST', body });
      const data = await res.json();
      if (data.success) {
        document.getElementById(`puesto-row-${id}`).className = isOn ? 'row-admitido' : '';
        const lbl = document.getElementById(`puesto-lbl-${id}`);
        lbl.textContent = isOn ? 'ON' : 'OFF';
        lbl.className   = `toggle-lbl ${isOn ? 'on' : 'off'}`;
      } else { chk.checked = !isOn; alert(data.message || 'Error.'); }
    } catch { chk.checked = !isOn; alert('Error de conexión.'); }
    finally  { chk.disabled = false; }
  }

  async function agregarPuesto() {
    const titulo = document.getElementById('new-titulo').value.trim();
    const desc   = document.getElementById('new-desc').value.trim();
    const tipo   = document.getElementById('new-tipo').value;
    const urg    = document.getElementById('new-urgente').checked ? '1' : '0';
    const msg    = document.getElementById('new-puesto-msg');
    msg.style.color = '#DC2626'; msg.textContent = '';

    if (titulo.length < 3)  { msg.textContent = 'Ingresá un título válido.';   return; }
    if (desc.length   < 10) { msg.textContent = 'Ingresá una descripción.';    return; }

    const body = new FormData();
    body.append('titulo', titulo); body.append('descripcion', desc);
    body.append('tipo', tipo);     body.append('urgente', urg);
    try {
      const res  = await fetch('../propuestas/agregar_puesto.php', { method:'POST', body });
      const data = await res.json();
      if (data.success) {
        msg.style.color = '#059669';
        msg.textContent = `✅ Puesto "${titulo}" agregado correctamente.`;
        document.getElementById('new-titulo').value = '';
        document.getElementById('new-desc').value   = '';
        document.getElementById('new-urgente').checked = false;
        // Insertar fila en tabla
        const tbody = document.querySelector('#tabla-puestos tbody');
        const tipoCls = tipo === 'Part-time' ? 'part' : (tipo === 'Medio turno' ? 'medio' : 'full');
        const urgTag  = urg === '1' ? '<span class="puesto-tag urgente">Urgente</span>' : '';
        tbody.insertAdjacentHTML('beforeend', `
          <tr id="puesto-row-${data.id}" class="row-admitido">
            <td>${data.id}</td>
            <td><strong>${esc(data.titulo)}</strong> ${urgTag}</td>
            <td><span class="exp-txt">${esc(data.descripcion)}</span></td>
            <td><span class="puesto-tag ${tipoCls}">${esc(data.tipo)}</span></td>
            <td>
              <div class="toggle-wrap">
                <label class="toggle-switch">
                  <input type="checkbox" id="puesto-chk-${data.id}" checked onchange="togglePuesto(${data.id}, this.checked)">
                  <span class="toggle-track"></span>
                </label>
                <span class="toggle-lbl on" id="puesto-lbl-${data.id}">ON</span>
              </div>
            </td>
          </tr>`);
      } else { msg.textContent = data.message || 'Error al guardar.'; }
    } catch { msg.textContent = 'Error de conexión.'; }
  }

  // ── Postulaciones ─────────────────────────────────────────────────
  const POST_LABELS = { pendiente:'Pendiente', revisado:'Revisado', seleccionado:'Seleccionado', rechazado:'Rechazado' };
  const POST_BADGE  = { pendiente:'pendiente', revisado:'contactado', seleccionado:'admitido', rechazado:'rechazado' };
  const POST_ROW    = { pendiente:'', revisado:'row-contactado', seleccionado:'row-admitido', rechazado:'row-rechazado' };

  async function postAccion(id, estado) {
    const btns = document.querySelectorAll(`#post-acc-${id} button`);
    btns.forEach(b => b.disabled = true);
    const body = new FormData();
    body.append('id', id); body.append('estado', estado);
    try {
      const res  = await fetch('../propuestas/cambiar_estado_postulacion.php', { method:'POST', body });
      const data = await res.json();
      if (data.success) {
        document.getElementById(`post-row-${id}`).className = POST_ROW[estado];
        document.getElementById(`post-estado-${id}`).innerHTML =
          `<span class="estado-badge estado-${POST_BADGE[estado]}">${POST_LABELS[estado]}</span>`;
        const todos = ['revisado','seleccionado','rechazado','pendiente'];
        document.getElementById(`post-acc-${id}`).innerHTML = todos
          .filter(e => e !== estado)
          .map(e => {
            const cls = { revisado:'btn-contactado', seleccionado:'btn-admitido', rechazado:'btn-rechazado', pendiente:'btn-pendiente' }[e];
            return `<button class="btn-accion ${cls}" onclick="postAccion(${id},'${e}')">${POST_LABELS[e]}</button>`;
          }).join('');
      } else { alert(data.message||'Error.'); btns.forEach(b=>b.disabled=false); }
    } catch { alert('Error de conexión.'); btns.forEach(b=>b.disabled=false); }
  }

  // ── Gestión de opiniones (toggle ON/OFF) ──────────────────────────
  async function toggleOpinion(id, isOn) {
    const estado = isOn ? 'aprobado' : 'pendiente';
    const chk    = document.getElementById(`op-chk-${id}`);
    chk.disabled = true;

    try {
      const body = new FormData();
      body.append('id', id);
      body.append('estado', estado);
      const res  = await fetch('../opiniones/cambiar_estado.php', { method: 'POST', body });
      const data = await res.json();

      if (data.success) {
        const row = document.getElementById(`op-row-${id}`);
        row.className = isOn ? 'row-admitido' : '';
        row.dataset.ts = row.dataset.ts; // mantener para sort

        const lbl = document.getElementById(`op-lbl-${id}`);
        lbl.textContent = isOn ? 'ON' : 'OFF';
        lbl.className   = `toggle-lbl ${isOn ? 'on' : 'off'}`;

        actualizarStatsOpiniones();
      } else {
        alert(data.message || 'Error al actualizar.');
        chk.checked = !isOn; // revertir visualmente
      }
    } catch {
      alert('No se pudo conectar con el servidor.');
      chk.checked = !isOn;
    } finally {
      chk.disabled = false;
    }
  }

  function sortOpiniones(dir) {
    const tbody = document.querySelector('#tabla-opiniones tbody');
    if (!tbody) return;
    const rows = [...tbody.querySelectorAll('tr')];
    rows.sort((a, b) => {
      const at = parseInt(a.dataset.ts || 0);
      const bt = parseInt(b.dataset.ts || 0);
      return dir === 'desc' ? bt - at : at - bt;
    });
    rows.forEach(r => tbody.appendChild(r));
    document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('activo'));
    document.getElementById(`sort-${dir}`).classList.add('activo');
  }

  function actualizarStatsOpiniones() {
    const conteos = { total: 0, pendiente: 0, aprobado: 0, rechazado: 0 };
    document.querySelectorAll('#tabla-opiniones tbody tr').forEach(row => {
      const cls = [...row.classList].find(c => c.startsWith('row-'))?.replace('row-', '');
      if (cls === 'admitido')  { conteos.aprobado++;  conteos.total++; }
      else if (cls === 'rechazado') { conteos.rechazado++; conteos.total++; }
      else                          { conteos.pendiente++; conteos.total++; }
    });
    const grid = document.querySelector('#panel-opiniones .stats-grid');
    if (!grid) return;
    const nums = grid.querySelectorAll('.stat-num');
    if (nums[0]) nums[0].textContent = conteos.total;
    if (nums[1]) nums[1].textContent = conteos.pendiente;
    if (nums[2]) nums[2].textContent = conteos.aprobado;
    if (nums[3]) nums[3].textContent = conteos.rechazado;
  }

  // ── Consultas ─────────────────────────────────────────────────────
  const CON_ROW   = { pendiente:'', leida:'row-contactado', respondida:'row-admitido', archivada:'row-rechazado' };
  const CON_BADGE = { pendiente:'pendiente', leida:'contactado', respondida:'admitido', archivada:'rechazado' };
  const CON_LABELS = { pendiente:'Pendiente', leida:'Leída', respondida:'Respondida', archivada:'Archivada' };

  function buildBotonesConsulta(id, estadoActual) {
    const map = {
      leida:      '<button class="btn-accion btn-contactado" onclick="conAccion(ID,\'leida\')">Leída</button>',
      respondida: '<button class="btn-accion btn-admitido"   onclick="conAccion(ID,\'respondida\')">Respondida</button>',
      archivada:  '<button class="btn-accion btn-rechazado"  onclick="conAccion(ID,\'archivada\')">Archivar</button>',
      pendiente:  '<button class="btn-accion btn-pendiente"  onclick="conAccion(ID,\'pendiente\')">↩ Pendiente</button>',
    };
    return Object.entries(map)
      .filter(([e]) => e !== estadoActual)
      .map(([, btn]) => btn.replaceAll('ID', id))
      .join('');
  }

  async function conAccion(id, estado) {
    const btns = document.querySelectorAll(`#con-acc-${id} button`);
    btns.forEach(b => b.disabled = true);
    const body = new FormData();
    body.append('id', id); body.append('estado', estado);
    try {
      const res  = await fetch('../consultas/cambiar_estado.php', { method:'POST', body });
      const data = await res.json();
      if (data.success) {
        document.getElementById(`con-row-${id}`).className = CON_ROW[estado];
        document.getElementById(`con-estado-${id}`).innerHTML =
          `<span class="estado-badge estado-${CON_BADGE[estado]}">${CON_LABELS[estado]}</span>`;
        document.getElementById(`con-acc-${id}`).innerHTML = buildBotonesConsulta(id, estado);
        actualizarStatsConsultas();
      } else { alert(data.message || 'Error.'); btns.forEach(b => b.disabled = false); }
    } catch { alert('Error de conexión.'); btns.forEach(b => b.disabled = false); }
  }

  function actualizarStatsConsultas() {
    const c = { total:0, pendiente:0, leida:0, respondida:0, archivada:0 };
    document.querySelectorAll('#tabla-consultas tbody tr').forEach(row => {
      const cls = [...row.classList].find(k => k.startsWith('row-'))?.replace('row-','');
      if (cls === 'contactado')  { c.leida++;      c.total++; }
      else if (cls === 'admitido')  { c.respondida++; c.total++; }
      else if (cls === 'rechazado') { c.archivada++;  c.total++; }
      else                          { c.pendiente++;  c.total++; }
    });
    const upd = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    upd('con-stat-total', c.total); upd('con-stat-pendiente', c.pendiente);
    upd('con-stat-leida', c.leida); upd('con-stat-respondida', c.respondida);
    upd('con-stat-archivada', c.archivada);
  }

  async function pollingConsultas() {
    try {
      const res = await fetch('../consultas/polling.php', { credentials:'same-origin' });
      if (!res.ok) return;
      const { stats, consultas } = await res.json();

      const upd = (id, val) => { const el = document.getElementById(id); if (el && el.textContent !== String(val)) el.textContent = val; };
      upd('con-stat-total', stats.total); upd('con-stat-pendiente', stats.pendiente);
      upd('con-stat-leida', stats.leida); upd('con-stat-respondida', stats.respondida);
      upd('con-stat-archivada', stats.archivada);

      if (!consultas || !consultas.length) return;

      // Si todavía muestra el mensaje vacío, crear la tabla
      const cardBody = document.getElementById('consultas-card-body');
      if (!document.getElementById('tabla-consultas')) {
        const emptyEl = document.getElementById('consultas-empty');
        if (emptyEl) emptyEl.remove();
        cardBody.insertAdjacentHTML('beforeend', `
          <table id="tabla-consultas">
            <thead><tr>
              <th>#</th><th>Nombre</th><th>Email</th><th>Asunto</th>
              <th>Mensaje</th><th>Fecha</th><th>Estado</th><th>Acciones</th>
            </tr></thead>
            <tbody></tbody>
          </table>`);
      }

      const tbody = document.querySelector('#tabla-consultas tbody');
      if (!tbody) return;
      let nuevas = 0;
      consultas.forEach(c => {
        if (document.getElementById(`con-row-${c.id}`)) return;
        const asuntoTxt = c.asunto ? (c.asunto.length > 50 ? esc(c.asunto.substring(0,50))+'…' : esc(c.asunto)) : '—';
        const msgTxt    = c.mensaje.length > 80 ? esc(c.mensaje.substring(0,80))+'…' : esc(c.mensaje);
        tbody.insertAdjacentHTML('afterbegin', `
          <tr id="con-row-${c.id}" class="${CON_ROW[c.estado]} fila-nueva">
            <td>${c.id}</td>
            <td><strong>${esc(c.nombre)}</strong></td>
            <td>${esc(c.email)}</td>
            <td>${asuntoTxt}</td>
            <td><span class="comentario-txt">${msgTxt}</span></td>
            <td style="white-space:nowrap">${c.fecha}</td>
            <td id="con-estado-${c.id}"><span class="estado-badge estado-${CON_BADGE[c.estado]}">${CON_LABELS[c.estado]}</span></td>
            <td><div class="acciones" id="con-acc-${c.id}">${buildBotonesConsulta(c.id, c.estado)}</div></td>
          </tr>`);
        nuevas++;
      });
      if (nuevas > 0) mostrarToast(nuevas === 1 ? 'Nueva consulta de contacto recibida' : `${nuevas} nuevas consultas de contacto`);
    } catch { /* reintentar */ }
  }

  // ── Noticias ──────────────────────────────────────────────────────────

  const NOT_CAT_LABEL = {
    institucional: 'Institucional', academica: 'Académica',
    deportiva: 'Deportiva', cultural: 'Cultural', general: 'General'
  };

  function notBuildRow(n) {
    const rowCls  = n.estado === 'publicada' ? 'row-admitido' : (n.estado === 'archivada' ? 'row-rechazado' : '');
    const catLabel = NOT_CAT_LABEL[n.categoria] || n.categoria;
    const resumenHtml = n.resumen
      ? `<br><span class="noticia-resumen">${esc(n.resumen.length > 80 ? n.resumen.substring(0,80)+'…' : n.resumen)}</span>`
      : '';
    return `<tr id="not-row-${n.id}" class="${rowCls}">
      <td>${n.id}</td>
      <td><span class="noticia-titulo">${esc(n.titulo)}</span>${resumenHtml}</td>
      <td><span class="cat-tag cat-${n.categoria}">${catLabel}</span></td>
      <td id="not-estado-${n.id}"><span class="estado-badge estado-${n.estado}">${n.estado.charAt(0).toUpperCase()+n.estado.slice(1)}</span></td>
      <td style="white-space:nowrap">${n.fecha_pub_fmt || '—'}</td>
      <td style="white-space:nowrap">${n.fecha}</td>
      <td><div class="acciones" id="not-acc-${n.id}">${notBuildBotones(n.id, n.estado)}</div></td>
    </tr>`;
  }

  function notBuildBotones(id, estado) {
    let btns = `<button class="btn-accion btn-contactado" onclick="abrirEditarNoticia(${id})">✏️ Editar</button>`;
    if (estado !== 'publicada') btns += `<button class="btn-accion btn-admitido" onclick="cambiarEstadoNoticia(${id},'publicada')">Publicar</button>`;
    if (estado === 'publicada') btns += `<button class="btn-accion btn-pendiente" onclick="cambiarEstadoNoticia(${id},'borrador')">Despublicar</button>`;
    if (estado !== 'archivada') btns += `<button class="btn-accion btn-rechazado" onclick="cambiarEstadoNoticia(${id},'archivada')">Archivar</button>`;
    btns += `<button class="btn-accion" style="background:#FEE2E2;color:#991B1B;" onclick="confirmarEliminarNoticia(${id}, ${JSON.stringify(_notData[id]?.titulo || '')})">🗑️ Eliminar</button>`;
    return btns;
  }

  // Cache local de datos de noticias para el modal de edición
  const _notData = {};
  <?php foreach ($noticias as $n): ?>
  _notData[<?= $n['id'] ?>] = {
    titulo:     <?= json_encode($n['titulo']) ?>,
    resumen:    <?= json_encode($n['resumen']) ?>,
    contenido:  <?= json_encode($n['contenido']) ?>,
    categoria:  <?= json_encode($n['categoria']) ?>,
    imagen_url: <?= json_encode($n['imagen_url']) ?>,
    estado:     <?= json_encode($n['estado']) ?>,
    fecha_pub:  <?= json_encode($n['fecha_pub_raw'] ?? '') ?>
  };
  <?php endforeach; ?>

  async function guardarNoticia() {
    const titulo    = document.getElementById('not-titulo').value.trim();
    const resumen   = document.getElementById('not-resumen').value.trim();
    const contenido = document.getElementById('not-contenido').value.trim();
    const categoria = document.getElementById('not-categoria').value;
    const imagen    = document.getElementById('not-imagen').value.trim();
    const estado    = document.getElementById('not-estado').value;
    const fechaPub  = document.getElementById('not-fecha-pub').value;
    const msg       = document.getElementById('not-msg');

    msg.style.color = '#DC2626'; msg.textContent = '';

    if (titulo.length < 3)   { msg.textContent = 'El título debe tener al menos 3 caracteres.'; return; }
    if (contenido.length < 10) { msg.textContent = 'El contenido debe tener al menos 10 caracteres.'; return; }

    const body = new FormData();
    body.append('titulo', titulo);
    body.append('resumen', resumen);
    body.append('contenido', contenido);
    body.append('categoria', categoria);
    body.append('imagen_url', imagen);
    body.append('estado', estado);
    body.append('fecha_pub', fechaPub);

    try {
      const res  = await fetch('../noticias/guardar.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        msg.style.color = '#059669';
        msg.textContent = `✅ Noticia "${titulo}" guardada correctamente.`;
        document.getElementById('not-titulo').value    = '';
        document.getElementById('not-resumen').value   = '';
        document.getElementById('not-contenido').value = '';
        document.getElementById('not-imagen').value    = '';
        document.getElementById('not-fecha-pub').value = '';
        document.getElementById('not-estado').value    = 'borrador';

        const n = data.noticia;
        _notData[n.id] = {
          titulo: n.titulo, resumen: n.resumen, contenido: n.contenido,
          categoria: n.categoria, imagen_url: n.imagen_url,
          estado: n.estado, fecha_pub: n.fecha_pub || ''
        };

        asegurarTablaNoticia();
        const tbody = document.querySelector('#tabla-noticias tbody');
        const tpl = document.createElement('template');
        tpl.innerHTML = notBuildRow(n);
        tbody.insertBefore(tpl.content.firstChild, tbody.firstChild);
        document.querySelector('#tabla-noticias tbody tr').classList.add('fila-nueva');
        actualizarStatsNoticias();
      } else {
        msg.textContent = data.message || 'Error al guardar.';
      }
    } catch { msg.textContent = 'Error de conexión.'; }
  }

  function asegurarTablaNoticia() {
    if (!document.getElementById('tabla-noticias')) {
      const emptyEl = document.getElementById('not-empty');
      if (emptyEl) emptyEl.remove();
      document.getElementById('not-card-body').insertAdjacentHTML('beforeend', `
        <table id="tabla-noticias">
          <thead><tr>
            <th>#</th><th>Título</th><th>Categoría</th><th>Estado</th>
            <th>Fecha pub.</th><th>Creada</th><th>Acciones</th>
          </tr></thead>
          <tbody></tbody>
        </table>`);
    }
  }

  function abrirEditarNoticia(id) {
    const d = _notData[id];
    if (!d) return;
    document.getElementById('edit-not-id').value       = id;
    document.getElementById('edit-not-titulo').value   = d.titulo;
    document.getElementById('edit-not-resumen').value  = d.resumen;
    document.getElementById('edit-not-contenido').value = d.contenido;
    document.getElementById('edit-not-categoria').value = d.categoria;
    document.getElementById('edit-not-estado').value   = d.estado;
    document.getElementById('edit-not-imagen').value   = d.imagen_url;
    document.getElementById('edit-not-fecha-pub').value = d.fecha_pub || '';
    document.getElementById('edit-not-error').textContent = '';
    document.getElementById('btn-guardar-edicion').disabled = false;
    document.getElementById('btn-guardar-edicion').textContent = 'Guardar cambios';
    document.getElementById('overlay-editar-noticia').classList.add('open');
  }

  function cerrarModalNot(e) {
    if (e.target === document.getElementById('overlay-editar-noticia'))
      document.getElementById('overlay-editar-noticia').classList.remove('open');
  }

  async function guardarEdicionNoticia() {
    const id        = document.getElementById('edit-not-id').value;
    const titulo    = document.getElementById('edit-not-titulo').value.trim();
    const resumen   = document.getElementById('edit-not-resumen').value.trim();
    const contenido = document.getElementById('edit-not-contenido').value.trim();
    const categoria = document.getElementById('edit-not-categoria').value;
    const estado    = document.getElementById('edit-not-estado').value;
    const imagen    = document.getElementById('edit-not-imagen').value.trim();
    const fechaPub  = document.getElementById('edit-not-fecha-pub').value;
    const errorEl   = document.getElementById('edit-not-error');
    const btn       = document.getElementById('btn-guardar-edicion');

    errorEl.textContent = '';
    if (titulo.length < 3)    { errorEl.textContent = 'El título debe tener al menos 3 caracteres.'; return; }
    if (contenido.length < 10) { errorEl.textContent = 'El contenido debe tener al menos 10 caracteres.'; return; }

    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const body = new FormData();
    body.append('id', id); body.append('titulo', titulo);
    body.append('resumen', resumen); body.append('contenido', contenido);
    body.append('categoria', categoria); body.append('imagen_url', imagen);
    body.append('estado', estado); body.append('fecha_pub', fechaPub);

    try {
      const res  = await fetch('../noticias/actualizar.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        document.getElementById('overlay-editar-noticia').classList.remove('open');

        _notData[id] = { titulo, resumen, contenido, categoria, imagen_url: imagen, estado, fecha_pub: fechaPub };

        const row = document.getElementById(`not-row-${id}`);
        if (row) {
          row.className = estado === 'publicada' ? 'row-admitido' : (estado === 'archivada' ? 'row-rechazado' : '');
          const catLabel = NOT_CAT_LABEL[categoria] || categoria;
          const resumenHtml = resumen
            ? `<br><span class="noticia-resumen">${esc(resumen.length > 80 ? resumen.substring(0,80)+'…' : resumen)}</span>`
            : '';
          row.cells[1].innerHTML = `<span class="noticia-titulo">${esc(titulo)}</span>${resumenHtml}`;
          row.cells[2].innerHTML = `<span class="cat-tag cat-${categoria}">${catLabel}</span>`;
          document.getElementById(`not-estado-${id}`).innerHTML =
            `<span class="estado-badge estado-${estado}">${estado.charAt(0).toUpperCase()+estado.slice(1)}</span>`;
          document.getElementById(`not-acc-${id}`).innerHTML = notBuildBotones(id, estado);
        }
        actualizarStatsNoticias();
      } else {
        errorEl.textContent = data.message || 'Error al actualizar.';
        btn.disabled = false;
        btn.textContent = 'Guardar cambios';
      }
    } catch {
      errorEl.textContent = 'Error de conexión.';
      btn.disabled = false;
      btn.textContent = 'Guardar cambios';
    }
  }

  async function cambiarEstadoNoticia(id, estado) {
    const btns = document.querySelectorAll(`#not-acc-${id} button`);
    btns.forEach(b => b.disabled = true);

    const body = new FormData();
    body.append('id', id);
    body.append('titulo',    _notData[id]?.titulo    || '');
    body.append('resumen',   _notData[id]?.resumen   || '');
    body.append('contenido', _notData[id]?.contenido || '');
    body.append('categoria', _notData[id]?.categoria || 'general');
    body.append('imagen_url',_notData[id]?.imagen_url|| '');
    body.append('estado', estado);
    body.append('fecha_pub', _notData[id]?.fecha_pub || '');

    try {
      const res  = await fetch('../noticias/actualizar.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        if (_notData[id]) _notData[id].estado = estado;
        const row = document.getElementById(`not-row-${id}`);
        if (row) {
          row.className = estado === 'publicada' ? 'row-admitido' : (estado === 'archivada' ? 'row-rechazado' : '');
          document.getElementById(`not-estado-${id}`).innerHTML =
            `<span class="estado-badge estado-${estado}">${estado.charAt(0).toUpperCase()+estado.slice(1)}</span>`;
          document.getElementById(`not-acc-${id}`).innerHTML = notBuildBotones(id, estado);
        }
        actualizarStatsNoticias();
      } else {
        alert(data.message || 'Error al cambiar el estado.');
        btns.forEach(b => b.disabled = false);
      }
    } catch {
      alert('Error de conexión.');
      btns.forEach(b => b.disabled = false);
    }
  }

  function confirmarEliminarNoticia(id, titulo) {
    document.getElementById('del-not-id').value = id;
    document.getElementById('del-not-texto').textContent =
      `¿Estás seguro que querés eliminar "${titulo}"? Esta acción no se puede deshacer.`;
    document.getElementById('btn-confirmar-eliminar').disabled = false;
    document.getElementById('btn-confirmar-eliminar').textContent = 'Sí, eliminar';
    document.getElementById('overlay-eliminar-noticia').classList.add('open');
  }

  function cerrarModalDelNot(e) {
    if (e.target === document.getElementById('overlay-eliminar-noticia'))
      document.getElementById('overlay-eliminar-noticia').classList.remove('open');
  }

  async function ejecutarEliminarNoticia() {
    const id  = document.getElementById('del-not-id').value;
    const btn = document.getElementById('btn-confirmar-eliminar');
    btn.disabled = true;
    btn.textContent = 'Eliminando...';

    const body = new FormData();
    body.append('id', id);

    try {
      const res  = await fetch('../noticias/eliminar.php', { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        document.getElementById('overlay-eliminar-noticia').classList.remove('open');
        const row = document.getElementById(`not-row-${id}`);
        if (row) row.remove();
        delete _notData[id];
        actualizarStatsNoticias();

        const tbody = document.querySelector('#tabla-noticias tbody');
        if (tbody && tbody.rows.length === 0) {
          document.getElementById('tabla-noticias')?.remove();
          document.getElementById('not-card-body').insertAdjacentHTML('beforeend',
            '<p class="empty-msg" id="not-empty">No hay noticias registradas aún.</p>');
        }
      } else {
        alert(data.message || 'Error al eliminar.');
        btn.disabled = false;
        btn.textContent = 'Sí, eliminar';
      }
    } catch {
      alert('Error de conexión.');
      btn.disabled = false;
      btn.textContent = 'Sí, eliminar';
    }
  }

  function actualizarStatsNoticias() {
    const c = { total: 0, publicada: 0, borrador: 0, archivada: 0 };
    document.querySelectorAll('#tabla-noticias tbody tr').forEach(row => {
      const cls = [...row.classList].find(k => k.startsWith('row-'))?.replace('row-', '');
      if (cls === 'admitido')       { c.publicada++;  c.total++; }
      else if (cls === 'rechazado') { c.archivada++;  c.total++; }
      else                          { c.borrador++;   c.total++; }
    });
    const upd = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    upd('not-stat-total', c.total);
    upd('not-stat-publicada', c.publicada);
    upd('not-stat-borrador', c.borrador);
    upd('not-stat-archivada', c.archivada);
  }
</script>

</body>
</html>
