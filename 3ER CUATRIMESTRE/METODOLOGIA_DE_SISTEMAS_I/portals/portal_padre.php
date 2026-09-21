<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'padre') {
    header('Location: /');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Padre/Tutor');

require_once '../database/db_config.php';
require_once '../gestion/helpers.php';

$padre_id = (int) $_SESSION['usuario_id'];

// Hijos vinculados, con curso, nivel y condición de regularización (RFG09/RFG10/RFG13).
$hijos = [];
$stmt = $conn->prepare(
    "SELECT u.id, u.nombre, c.nombre AS curso_nombre, c.nivel_educativo, ac.condicion
     FROM padre_alumno pa
     JOIN usuarios u ON u.id = pa.alumno_id
     LEFT JOIN alumno_curso ac ON ac.alumno_id = u.id
     LEFT JOIN cursos c ON c.id = ac.curso_id
     WHERE pa.padre_id = ?
     ORDER BY u.nombre"
);
$stmt->bind_param('i', $padre_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $hijos[] = $row;
}
$stmt->close();

$hijos_pendientes_regularizacion = array_filter($hijos, fn($h) => ($h['condicion'] ?? 'regular') === 'pendiente_regularizacion');

// Últimas notificaciones (RFG10), más recientes primero.
$notificaciones = [];
$stmt = $conn->prepare(
    "SELECT tipo, mensaje, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') AS fecha
     FROM notificaciones
     WHERE padre_id = ?
     ORDER BY created_at DESC
     LIMIT 10"
);
$stmt->bind_param('i', $padre_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $notificaciones[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Familias – Educar para Transformar</title>
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

    /* Hijo info */
    .hijo-card {
      display: flex; align-items: center; gap: 1rem;
      background: var(--verde-bg); border-radius: 12px; padding: 1rem;
      margin-bottom: 1rem;
    }
    .hijo-avatar {
      width: 52px; height: 52px; border-radius: 50%;
      background: var(--azul); color: var(--blanco);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem; font-weight: 900; flex-shrink: 0;
    }
    .hijo-datos strong { display: block; font-weight: 800; font-size: 1rem; }
    .hijo-datos span   { font-size: .82rem; color: #5d6d7e; }

    /* Notas */
    table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    th { background: var(--verde-bg); color: var(--verde); font-weight: 800;
         padding: .5rem .7rem; text-align: left; font-size: .8rem; text-transform: uppercase; }
    td { padding: .55rem .7rem; border-bottom: 1px solid #F3F4F6; }
    tr:last-child td { border-bottom: none; }
    .badge {
      display: inline-block; border-radius: 12px;
      padding: .15rem .55rem; font-size: .78rem; font-weight: 800;
    }
    .badge-verde    { background: #d4edda; color: #1a7c34; }
    .badge-amarillo { background: #fff3cd; color: #856404; }
    .badge-rojo     { background: #f8d7da; color: #842029; }

    /* Asistencia */
    .asistencia-bar-wrap { margin-bottom: 1rem; }
    .asistencia-label {
      display: flex; justify-content: space-between;
      font-size: .85rem; font-weight: 700; margin-bottom: .35rem;
    }
    .bar-bg { background: #e2edf8; border-radius: 20px; height: 14px; overflow: hidden; }
    .bar-fill { height: 100%; border-radius: 20px; background: var(--verde); }
    .bar-fill.warning { background: var(--naranja); }
    .asistencia-detalle {
      display: flex; gap: 1.5rem; margin-top: 1rem;
      font-size: .85rem;
    }
    .det-item strong { display: block; font-size: 1.3rem; font-weight: 900; color: var(--azul); }
    .det-item span   { color: #6b7280; }

    /* Comunicados */
    .comunicado {
      padding: .8rem; border-left: 4px solid var(--verde-claro);
      background: var(--verde-bg); border-radius: 0 10px 10px 0;
      margin-bottom: .75rem; font-size: .88rem; line-height: 1.5;
    }
    .comunicado:last-child { margin-bottom: 0; }
    .comunicado strong { display: block; font-weight: 800; margin-bottom: .2rem; color: var(--azul); }

    /* Reuniones */
    .reunion-item {
      display: flex; align-items: flex-start; gap: .8rem;
      padding: .7rem 0; border-bottom: 1px solid #F3F4F6;
    }
    .reunion-item:last-child { border-bottom: none; }
    .reunion-fecha {
      background: var(--azul); color: var(--blanco);
      border-radius: 10px; padding: .35rem .6rem;
      font-size: .78rem; font-weight: 700; text-align: center;
      min-width: 48px; flex-shrink: 0;
    }
    .reunion-desc strong { display: block; font-weight: 800; font-size: .9rem; }
    .reunion-desc span   { color: #6b7280; font-size: .82rem; }

    /* Accesos rápidos */
    .accion-btn {
      display: block; width: 100%;
      background: var(--verde-bg); color: var(--azul);
      border: 2px solid var(--borde); border-radius: 10px;
      padding: .85rem 1rem; margin-bottom: .6rem;
      font-size: .9rem; font-weight: 800; cursor: pointer;
      text-align: left; font-family: inherit;
      transition: background .2s, border-color .2s;
      text-decoration: none;
    }
    .accion-btn:hover { background: var(--verde); color: var(--blanco); border-color: var(--verde); }
    .accion-btn:last-child { margin-bottom: 0; }

    /* Notificaciones */
    .notificacion { border-left-color: var(--azul); }
    .empty-msg { color: #9CA3AF; font-size: .9rem; text-align: center; padding: 1.2rem 0; }
    .boletin-prom-anual { margin-top: .6rem; padding: .4rem .8rem; border-radius: 10px; font-size: .88rem; font-weight: 800; }
    .boletin-aprobada   { background: #d1fae5; color: #065F46; }
    .boletin-reprobada  { background: #fee2e2; color: #991B1B; }
    .aviso-mora {
      max-width: 1100px; margin: 1.2rem auto 0; padding: .9rem 1.2rem;
      background: #FEE2E2; color: #991B1B; border-radius: 12px;
      font-weight: 700; font-size: .88rem;
    }

    @media (max-width: 600px) { .grid { grid-template-columns: 1fr; } }
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
  <h1>Bienvenido/a, <?= $nombre ?>!</h1>
  <p>Seguí el progreso escolar de tu hijo/a desde acá.</p>
  <span class="rol-badge">Portal Familias</span>
</div>

<?php if (!empty($hijos_pendientes_regularizacion)): ?>
<div class="aviso-mora">
  ⚠️ Hay cuotas atrasadas hace más de <?= DIAS_LIMITE_REGULARIZACION ?> días para
  <?= htmlspecialchars(implode(', ', array_column($hijos_pendientes_regularizacion, 'nombre'))) ?>.
  Situación: pendiente de regularización.
</div>
<?php endif; ?>

<div class="container">
  <div class="grid">

    <!-- DATOS DEL HIJO -->
    <div class="card" style="grid-column: 1 / -1;">
      <div class="card-header"><span class="icon">👦</span><h2>Información del Alumno<?= count($hijos) > 1 ? 'es' : '' ?></h2></div>
      <div class="card-body">
        <?php if (empty($hijos)): ?>
          <p class="empty-msg">No hay alumnos vinculados a tu cuenta.</p>
        <?php else: ?>
          <?php foreach ($hijos as $hijo):
            $partes = preg_split('/\s+/', trim($hijo['nombre']));
            $iniciales = strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[count($partes) - 1] ?? '', 0, 1));
          ?>
          <div class="hijo-card">
            <div class="hijo-avatar"><?= htmlspecialchars($iniciales) ?></div>
            <div class="hijo-datos">
              <strong><?= htmlspecialchars($hijo['nombre']) ?></strong>
              <span>
                <?= $hijo['curso_nombre'] ? htmlspecialchars($hijo['curso_nombre']) : 'Sin curso asignado' ?>
                <?= $hijo['nivel_educativo'] ? ' · Nivel ' . htmlspecialchars($hijo['nivel_educativo']) : '' ?>
              </span>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- SERVICIOS -->
    <div class="card">
      <div class="card-header"><span class="icon">🍽️</span><h2>Servicios</h2></div>
      <div class="card-body">
        <a href="../gestion/servicios.php" class="accion-btn">🍽️ Reservar comedor / transporte</a>
      </div>
    </div>

    <!-- DEPORTES -->
    <div class="card">
      <div class="card-header"><span class="icon">🏅</span><h2>Deportes</h2></div>
      <div class="card-body">
        <a href="../gestion/deportes.php" class="accion-btn">🏅 Inscribirse a instalaciones deportivas</a>
      </div>
    </div>

    <!-- NOTIFICACIONES -->
    <div class="card">
      <div class="card-header"><span class="icon">🔔</span><h2>Notificaciones</h2></div>
      <div class="card-body">
        <?php if (empty($notificaciones)): ?>
          <p class="empty-msg">Sin notificaciones por el momento.</p>
        <?php else: ?>
          <?php foreach ($notificaciones as $n): ?>
            <div class="comunicado notificacion">
              <strong><?= htmlspecialchars(ucfirst($n['tipo'])) ?> · <?= $n['fecha'] ?></strong>
              <?= htmlspecialchars($n['mensaje']) ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- BOLETÍN DE CALIFICACIONES (RFG07) -->
    <div class="card" style="grid-column: 1 / -1;">
      <div class="card-header"><span class="icon">📄</span><h2>Boletín de Calificaciones</h2></div>
      <div class="card-body">
        <?php if (count($hijos) > 1): ?>
        <div style="margin-bottom:1rem;">
          <label style="font-size:.82rem;font-weight:800;color:var(--azul);">Alumno
            <select id="sel-hijo" style="margin-left:.5rem;padding:.4rem .6rem;border:2px solid var(--borde);border-radius:8px;font-family:inherit;">
              <?php foreach ($hijos as $h): ?>
              <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
        <?php endif; ?>
        <div id="boletin-padre-body"><p class="empty-msg">Cargando boletín…</p></div>
      </div>
    </div>

    <!-- ASISTENCIA -->
    <div class="card">
      <div class="card-header"><span class="icon">✅</span><h2>Asistencia – Mayo 2026</h2></div>
      <div class="card-body">
        <div class="asistencia-bar-wrap">
          <div class="asistencia-label"><span>Asistencia general</span><span style="color:#1a7c34">92%</span></div>
          <div class="bar-bg"><div class="bar-fill" style="width:92%"></div></div>
        </div>
        <div class="asistencia-bar-wrap">
          <div class="asistencia-label"><span>Educación Física</span><span style="color:#856404">78%</span></div>
          <div class="bar-bg"><div class="bar-fill warning" style="width:78%"></div></div>
        </div>
        <div class="asistencia-detalle">
          <div class="det-item"><strong>18</strong><span>Días asistidos</span></div>
          <div class="det-item"><strong>2</strong><span>Ausencias justif.</span></div>
          <div class="det-item"><strong>0</strong><span>Ausencias s/justif.</span></div>
        </div>
      </div>
    </div>

    <!-- COMUNICADOS -->
    <div class="card">
      <div class="card-header"><span class="icon">📢</span><h2>Comunicados de la Institución</h2></div>
      <div class="card-body">
        <div class="comunicado">
          <strong>Reunión de padres – 22/05 a las 18:30 hs</strong>
          Se convoca a los padres de 3° año a una reunión informativa sobre el sistema de evaluación. Presencia obligatoria.
        </div>
        <div class="comunicado">
          <strong>Acto escolar – 20/05</strong>
          Se solicita la presencia de los alumnos a las 9:30 hs para el Día de la Escarapela. Asistencia con uniforme completo.
        </div>
        <div class="comunicado">
          <strong>Pago de cuota – vencimiento 10/05</strong>
          Recordamos que el vencimiento de la cuota mensual es el día 10 de cada mes. Consultas en Secretaría.
        </div>
      </div>
    </div>

    <!-- PRÓXIMAS REUNIONES -->
    <div class="card">
      <div class="card-header"><span class="icon">🤝</span><h2>Próximas Reuniones</h2></div>
      <div class="card-body">
        <div class="reunion-item">
          <div class="reunion-fecha">22<br>MAY</div>
          <div class="reunion-desc"><strong>Reunión de padres – 3° Año</strong><span>18:30 hs · Aula Magna · Obligatoria</span></div>
        </div>
        <div class="reunion-item">
          <div class="reunion-fecha">10<br>JUN</div>
          <div class="reunion-desc"><strong>Entrevista con el tutor</strong><span>Turno a confirmar · Solicitar por secretaría</span></div>
        </div>
        <div class="reunion-item">
          <div class="reunion-fecha">25<br>JUN</div>
          <div class="reunion-desc"><strong>Cierre de trimestre</strong><span>Entrega de boletines · 17:00 hs</span></div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  <?php if (!empty($hijos)): ?>
  const hijosIds = <?= json_encode(array_column($hijos, 'id')) ?>;
  const boletinBody = document.getElementById('boletin-padre-body');
  const selHijo = document.getElementById('sel-hijo');

  async function cargarBoletin(alumnoId) {
    boletinBody.innerHTML = '<p class="empty-msg">Cargando…</p>';
    try {
      const res  = await fetch(`../gestion/boletin_listar.php?alumno_id=${encodeURIComponent(alumnoId)}`);
      const data = await res.json();
      if (!data.success || !data.materias.length) {
        boletinBody.innerHTML = '<p class="empty-msg">Todavía no hay calificaciones suficientes para generar el boletín.</p>';
        return;
      }
      let html = '<div style="overflow-x:auto;">';
      data.materias.forEach(mat => {
        html += `<h3 style="font-size:.9rem;font-weight:800;color:var(--azul);margin:.8rem 0 .3rem;">${esc(mat.materia)}</h3>`;
        html += '<table><thead><tr><th>Evaluación</th><th>Nota</th><th>Fecha</th><th>Período</th></tr></thead><tbody>';
        let hayFilas = false;
        mat.periodos.forEach(p => {
          p.calificaciones.forEach(c => {
            hayFilas = true;
            html += `<tr><td>${esc(c.evaluacion)}</td><td><strong>${c.nota}</strong></td><td>${c.fecha}</td><td>${esc(p.periodo)}</td></tr>`;
          });
          if (p.calificaciones.length > 0) {
            html += `<tr style="background:#f9fafb;"><td colspan="3" style="text-align:right;font-size:.8rem;color:#6b7280;">Promedio ${esc(p.periodo)}</td><td><strong>${p.promedio}</strong></td></tr>`;
          }
        });
        if (!hayFilas) html += '<tr><td colspan="4" class="empty-msg" style="padding:.5rem 0;">Sin evaluaciones cargadas.</td></tr>';
        html += '</tbody></table>';
        if (mat.promedio_anual !== null) {
          const cls = mat.aprobada ? 'boletin-aprobada' : 'boletin-reprobada';
          html += `<div class="boletin-prom-anual ${cls}">Promedio anual: ${mat.promedio_anual} — ${mat.aprobada ? 'Aprobada ✅' : 'Reprobada ❌'}</div>`;
        }
      });
      html += '</div>';
      boletinBody.innerHTML = html;
    } catch {
      boletinBody.innerHTML = '<p class="empty-msg">Error al cargar el boletín.</p>';
    }
  }

  function esc(str) { const d = document.createElement('div'); d.textContent = str; return d.innerHTML; }

  cargarBoletin(hijosIds[0]);
  if (selHijo) selHijo.addEventListener('change', () => cargarBoletin(selHijo.value));
  <?php endif; ?>
</script>
</body>
</html>
