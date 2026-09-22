<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'docente') {
    header('Location: /');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Docente');

require_once '../database/db_config.php';

$docente_id = (int) $_SESSION['usuario_id'];

// Materias asignadas al docente (RFG: gestión de materias por admin).
$materias_docente = [];
$stmt = $conn->prepare(
    "SELECT m.id, m.nombre, c.nombre AS curso_nombre, COUNT(ac.alumno_id) AS alumnos
     FROM materias m
     JOIN cursos c ON c.id = m.curso_id
     LEFT JOIN alumno_curso ac ON ac.curso_id = c.id
     WHERE m.docente_id = ?
     GROUP BY m.id, m.nombre, c.nombre
     ORDER BY c.nombre, m.nombre"
);
$stmt->bind_param('i', $docente_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $materias_docente[] = $row;
}
$stmt->close();

// Últimas calificaciones cargadas por el docente, con materia y alumno.
$ultimas_calificaciones = [];
$stmt = $conn->prepare(
    "SELECT u.nombre AS alumno_nombre, m.nombre AS materia_nombre, c.evaluacion, c.nota,
            DATE_FORMAT(c.fecha_evaluacion,'%d/%m/%Y') AS fecha
     FROM calificaciones c
     JOIN materias m ON m.id = c.materia_id
     JOIN usuarios u ON u.id = c.alumno_id
     WHERE m.docente_id = ?
     ORDER BY c.fecha_evaluacion DESC, c.id DESC
     LIMIT 10"
);
$stmt->bind_param('i', $docente_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $ultimas_calificaciones[] = $row;
}
$stmt->close();

$solicitudes = [];
$pendientes  = 0;
$result = $conn->query(
    "SELECT id, nombre_alumno, apellido_alumno, nivel_educativo, nombre_tutor, email, telefono, estado, DATE_FORMAT(created_at,'%d/%m/%Y') AS fecha
     FROM solicitudes_inscripcion
     ORDER BY created_at DESC
     LIMIT 50"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
        if ($row['estado'] === 'pendiente') $pendientes++;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Docente – Educar para Transformar</title>
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
    .logo-circle {
      height: 48px; width: auto; border-radius: 6px; object-fit: contain;
    }
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

    .container { max-width: 1100px; margin: 2rem auto; padding: 0 1.5rem 3rem; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
    .card {
      background: var(--blanco); border-radius: 16px;
      box-shadow: var(--sombra); overflow: hidden;
    }
    .card-header {
      padding: 1.1rem 1.5rem; display: flex; align-items: center; gap: .7rem;
      border-bottom: 2px solid var(--borde);
    }
    .card-header .icon { font-size: 1.5rem; }
    .card-header h2 { font-size: 1rem; font-weight: 800; color: var(--azul); }
    .card-body { padding: 1.2rem 1.5rem; }

    table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    th { background: var(--verde-bg); color: var(--verde); font-weight: 800;
         padding: .5rem .7rem; text-align: left; font-size: .8rem; text-transform: uppercase; }
    td { padding: .55rem .7rem; border-bottom: 1px solid #F3F4F6; }
    tr:last-child td { border-bottom: none; }

    .materia-item {
      display: flex; align-items: center; justify-content: space-between;
      padding: .75rem 0; border-bottom: 1px solid #F3F4F6;
    }
    .materia-item:last-child { border-bottom: none; }
    .materia-info strong { display: block; font-weight: 800; font-size: .92rem; }
    .materia-info span   { font-size: .8rem; color: #6b7280; }
    .materia-count {
      background: var(--verde-bg); color: var(--verde);
      border-radius: 20px; padding: .25rem .75rem;
      font-size: .8rem; font-weight: 800; white-space: nowrap;
    }

    .agenda-item {
      display: flex; align-items: flex-start; gap: .8rem;
      padding: .7rem 0; border-bottom: 1px solid #F3F4F6;
    }
    .agenda-item:last-child { border-bottom: none; }
    .agenda-hora {
      background: var(--azul); color: var(--blanco);
      border-radius: 8px; padding: .3rem .6rem;
      font-size: .78rem; font-weight: 700; text-align: center;
      min-width: 52px; flex-shrink: 0;
    }
    .agenda-desc strong { display: block; font-weight: 800; font-size: .9rem; }
    .agenda-desc span   { color: #6b7280; font-size: .82rem; }

    .comunicado {
      padding: .8rem; border-left: 4px solid var(--azul);
      background: #F3F4F6; border-radius: 0 10px 10px 0;
      margin-bottom: .75rem; font-size: .88rem; line-height: 1.5;
    }
    .comunicado:last-child { margin-bottom: 0; }
    .comunicado strong { display: block; font-weight: 800; margin-bottom: .2rem; color: var(--azul); }

    .accion-btn {
      display: block; width: 100%;
      background: var(--verde-bg); color: var(--azul);
      border: 2px solid var(--borde); border-radius: 10px;
      padding: .85rem 1rem; margin-bottom: .6rem;
      font-size: .9rem; font-weight: 800; cursor: pointer;
      text-align: left; font-family: inherit;
      transition: background .2s, border-color .2s;
    }
    .accion-btn:hover { background: var(--verde); color: var(--blanco); border-color: var(--verde); }
    .accion-btn:last-child { margin-bottom: 0; }

    @media (max-width: 600px) { .grid { grid-template-columns: 1fr; } }

    .badge-count {
      display: inline-block; background: #DC2626; color: #fff;
      border-radius: 20px; padding: .15rem .6rem;
      font-size: .75rem; font-weight: 900; margin-left: .4rem;
    }
    .estado-badge {
      display: inline-block; border-radius: 20px;
      padding: .2rem .65rem; font-size: .75rem; font-weight: 800;
      text-transform: uppercase; letter-spacing: .05em;
    }
    .estado-pendiente  { background: #FEF3C7; color: #92400E; }
    .estado-contactado { background: #DBEAFE; color: #1E40AF; }
    .estado-admitido   { background: #D1FAE5; color: #065F46; }
    .estado-rechazado  { background: #FEE2E2; color: #991B1B; }
    .empty-msg { color: #9CA3AF; font-size: .9rem; text-align: center; padding: 1.5rem 0; }
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
  <h1>Bienvenido/a, <?= $nombre ?>!</h1>
  <p>Este es tu espacio de gestión docente.</p>
  <span class="rol-badge">Portal Docente</span>
</div>

<div class="container">
  <div class="grid">

    <!-- MIS MATERIAS -->
    <div class="card">
      <div class="card-header"><span class="icon">📖</span><h2>Mis Materias</h2></div>
      <div class="card-body">
        <?php if (empty($materias_docente)): ?>
          <p class="empty-msg">Todavía no tenés materias asignadas. Pedile al administrador que te asigne una desde "Asignar Materias".</p>
        <?php else: ?>
          <?php foreach ($materias_docente as $m): ?>
          <div class="materia-item">
            <div class="materia-info"><strong><?= htmlspecialchars($m['nombre']) ?></strong><span><?= htmlspecialchars($m['curso_nombre']) ?></span></div>
            <span class="materia-count"><?= (int) $m['alumnos'] ?> alumno<?= (int) $m['alumnos'] === 1 ? '' : 's' ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- AGENDA DEL DÍA -->
    <div class="card">
      <div class="card-header"><span class="icon">📅</span><h2>Agenda de Hoy</h2></div>
      <div class="card-body">
        <div class="agenda-item">
          <div class="agenda-hora">08:00</div>
          <div class="agenda-desc"><strong>Matemática – 3°A</strong><span>Aula 5 · Funciones cuadráticas</span></div>
        </div>
        <div class="agenda-item">
          <div class="agenda-hora">09:30</div>
          <div class="agenda-desc"><strong>Matemática – 3°B</strong><span>Aula 7 · Funciones cuadráticas</span></div>
        </div>
        <div class="agenda-item">
          <div class="agenda-hora">11:00</div>
          <div class="agenda-desc"><strong>Reunión pedagógica</strong><span>Sala de docentes · Planificación anual</span></div>
        </div>
        <div class="agenda-item">
          <div class="agenda-hora">14:00</div>
          <div class="agenda-desc"><strong>Álgebra – 2°A</strong><span>Aula 3 · Sistemas de ecuaciones</span></div>
        </div>
        <div class="agenda-item">
          <div class="agenda-hora">15:30</div>
          <div class="agenda-desc"><strong>Atención a padres</strong><span>Preceptoría · Turno libre</span></div>
        </div>
      </div>
    </div>

    <!-- CALIFICACIONES -->
    <div class="card">
      <div class="card-header"><span class="icon">📝</span><h2>Últimas Calificaciones</h2></div>
      <div class="card-body" style="overflow-x:auto;">
        <?php if (empty($ultimas_calificaciones)): ?>
          <p class="empty-msg">Todavía no cargaste calificaciones.</p>
        <?php else: ?>
        <table>
          <thead><tr><th>Alumno</th><th>Materia</th><th>Evaluación</th><th>Nota</th><th>Fecha</th></tr></thead>
          <tbody>
            <?php foreach ($ultimas_calificaciones as $cal): ?>
            <tr>
              <td><?= htmlspecialchars($cal['alumno_nombre']) ?></td>
              <td><?= htmlspecialchars($cal['materia_nombre']) ?></td>
              <td><?= htmlspecialchars($cal['evaluacion']) ?></td>
              <td><strong><?= (int) $cal['nota'] ?></strong></td>
              <td><?= $cal['fecha'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- ACCIONES RÁPIDAS -->
    <div class="card">
      <div class="card-header"><span class="icon">⚡</span><h2>Acciones Rápidas</h2></div>
      <div class="card-body">
        <a href="../gestion/asistencia.php" class="accion-btn" style="text-decoration:none;">📋 Cargar asistencia</a>
        <a href="../gestion/calificaciones.php" class="accion-btn" style="text-decoration:none;">✏️ Registrar calificaciones</a>
        <a href="../gestion/planificacion.php" class="accion-btn" style="text-decoration:none;">📁 Planificación anual</a>
        <a href="../gestion/recuperatorios.php" class="accion-btn" style="text-decoration:none;">🔄 Exámenes recuperatorios</a>
        <a href="../gestion/legajo.php" class="accion-btn" style="text-decoration:none;">📂 Ver legajo de alumno</a>
      </div>
    </div>

    <!-- SOLICITUDES DE INSCRIPCIÓN (ancho completo) -->
    <div class="card" style="grid-column: 1 / -1;">
      <div class="card-header">
        <span class="icon">📋</span>
        <h2>Solicitudes de Inscripción
          <?php if ($pendientes > 0): ?>
            <span class="badge-count"><?= $pendientes ?> pendiente<?= $pendientes > 1 ? 's' : '' ?></span>
          <?php endif; ?>
        </h2>
      </div>
      <div class="card-body" style="overflow-x:auto;">
        <?php if (empty($solicitudes)): ?>
          <p class="empty-msg">No hay solicitudes registradas aún.</p>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Alumno</th>
                <th>Nivel</th>
                <th>Tutor / Contacto</th>
                <th>Email</th>
                <th>Fecha</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($solicitudes as $s): ?>
              <tr>
                <td><?= $s['id'] ?></td>
                <td><strong><?= htmlspecialchars($s['apellido_alumno'] . ', ' . $s['nombre_alumno']) ?></strong></td>
                <td><?= htmlspecialchars($s['nivel_educativo']) ?></td>
                <td><?= htmlspecialchars($s['nombre_tutor']) ?><br><small><?= htmlspecialchars($s['telefono']) ?></small></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td><?= $s['fecha'] ?></td>
                <td><span class="estado-badge estado-<?= $s['estado'] ?>"><?= ucfirst($s['estado']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- COMUNICADOS DEL PERSONAL (ancho completo) -->
    <div class="card" style="grid-column: 1 / -1;">
      <div class="card-header"><span class="icon">📢</span><h2>Comunicados del Personal</h2></div>
      <div class="card-body">
        <div class="comunicado">
          <strong>Planificación anual – fecha límite 16/05</strong>
          Recordamos que la entrega de la planificación anual debe realizarse antes del viernes 16 de mayo a Coordinación Pedagógica.
        </div>
        <div class="comunicado">
          <strong>Reunión general de docentes – 21/05 a las 17:00 hs</strong>
          Se convoca a todo el personal al SUM para tratar temas de convivencia escolar y acuerdos de evaluación.
        </div>
        <div class="comunicado">
          <strong>Capacitación en TIC – inscripción abierta</strong>
          El Ministerio de Educación del Chaco ofrece capacitación gratuita en herramientas digitales. Inscripción hasta el 30/05.
        </div>
      </div>
    </div>

  </div>
</div>

</body>
</html>
