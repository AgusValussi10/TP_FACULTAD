<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Administrador');

require_once '../database/db_config.php';
$alumnos = [];
$res = $conn->query("SELECT u.id, u.nombre, c.nombre AS curso FROM usuarios u LEFT JOIN alumno_curso ac ON ac.alumno_id = u.id LEFT JOIN cursos c ON c.id = ac.curso_id WHERE u.rol = 'alumno' AND u.activo = 1 ORDER BY u.nombre");
if ($res) while ($r = $res->fetch_assoc()) $alumnos[] = $r;
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Legajo de Alumno – Educar para Transformar</title>
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
    .card-body { padding: 1.2rem 1.5rem; }
    .form-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; margin-bottom: 1rem; }
    .form-row label { display: flex; flex-direction: column; gap: .3rem; font-size: .82rem; font-weight: 800; color: var(--azul); }
    .form-row select { padding: .55rem .7rem; border: 2px solid var(--borde); border-radius: 8px; font-family: inherit; font-size: .9rem; min-width: 260px; }
    .btn {
      background: var(--naranja); color: var(--blanco); border: none; border-radius: 10px;
      padding: .55rem 1.2rem; font-weight: 800; font-size: .88rem; cursor: pointer;
      font-family: inherit; transition: opacity .2s;
    }
    .btn:hover { opacity: .88; }
    table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    th { background: var(--naranja-bg); color: var(--naranja); font-weight: 800;
         padding: .5rem .7rem; text-align: left; font-size: .8rem; text-transform: uppercase; }
    td { padding: .55rem .7rem; border-bottom: 1px solid #F3F4F6; }
    tr:last-child td { border-bottom: none; }
    .badge {
      display: inline-block; border-radius: 12px;
      padding: .15rem .55rem; font-size: .78rem; font-weight: 800;
    }
    .badge-verde  { background: #d1fae5; color: #065F46; }
    .badge-rojo   { background: #fee2e2; color: #991B1B; }
    .badge-amarillo { background: #fef3c7; color: #92400E; }
    .badge-gris   { background: #E5E7EB; color: #6B7280; }
    .seccion-titulo { font-size: .9rem; font-weight: 800; color: var(--azul); margin: 1rem 0 .4rem; border-bottom: 2px solid var(--borde); padding-bottom: .4rem; }
    .empty-msg { color: #9CA3AF; font-size: .85rem; text-align: center; padding: .8rem 0; }
    .alumno-header {
      display: flex; align-items: center; gap: 1rem;
      background: var(--naranja-bg); border-radius: 12px; padding: 1rem; margin-bottom: 1rem;
    }
    .alumno-avatar {
      width: 52px; height: 52px; border-radius: 50%;
      background: var(--azul); color: var(--blanco);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem; font-weight: 900; flex-shrink: 0;
    }
    .alumno-datos strong { display: block; font-weight: 800; font-size: 1rem; }
    .alumno-datos span   { font-size: .82rem; color: #5d6d7e; }
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
  <h1>Legajo de Alumno</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge">Panel Administración</span>
</div>

<div class="container">
  <a href="../portals/portal_admin.php" class="volver">&larr; Volver al panel</a>

  <div class="card">
    <div class="card-header"><span class="icon">📂</span><h2>Seleccionar Alumno</h2></div>
    <div class="card-body">
      <div class="form-row">
        <label>Alumno
          <select id="sel-alumno">
            <option value="">Elegí un alumno…</option>
            <?php foreach ($alumnos as $a): ?>
            <option value="<?= $a['id'] ?>">
              <?= htmlspecialchars($a['nombre']) ?>
              <?= $a['curso'] ? ' — ' . htmlspecialchars($a['curso']) : '' ?>
            </option>
            <?php endforeach; ?>
          </select>
        </label>
        <button type="button" class="btn" id="btn-ver">Ver legajo</button>
      </div>
    </div>
  </div>

  <div id="legajo-contenido" style="display:none;"></div>
</div>

<script>
  const selAlumno = document.getElementById('sel-alumno');
  const btnVer    = document.getElementById('btn-ver');
  const contenido = document.getElementById('legajo-contenido');

  btnVer.addEventListener('click', async () => {
    const alumno_id = selAlumno.value;
    if (!alumno_id) return;
    contenido.innerHTML = '<div class="card"><div class="card-body"><p class="empty-msg">Cargando legajo…</p></div></div>';
    contenido.style.display = '';

    try {
      const res  = await fetch(`legajo_listar.php?alumno_id=${encodeURIComponent(alumno_id)}`);
      const data = await res.json();
      if (!data.success) {
        contenido.innerHTML = `<div class="card"><div class="card-body"><p class="empty-msg">${esc(data.message)}</p></div></div>`;
        return;
      }

      const d = data;
      const iniciales = iniciales2(d.alumno.nombre);
      const cursoStr  = d.curso ? `${d.curso.curso} · Nivel ${d.curso.nivel_educativo}` : 'Sin curso asignado';
      const estadoAlumno = d.alumno.activo ? '<span class="badge badge-verde">Activo</span>' : '<span class="badge badge-rojo">Suspendido</span>';

      const condicion = d.curso ? d.curso.condicion : 'regular';
      const badgeCondicion = condicion === 'pendiente_regularizacion'
        ? '<span class="badge badge-rojo">Pendiente de regularización</span>'
        : '<span class="badge badge-verde">Regular</span>';
      const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
      const cuotasHtml = (!d.cuotas_pendientes || !d.cuotas_pendientes.length)
        ? '<p class="empty-msg">Sin cuotas pendientes.</p>'
        : `<table><thead><tr><th>Concepto</th><th>Período</th><th>Importe</th><th>Vencimiento</th><th>Días de atraso</th></tr></thead><tbody>` +
          d.cuotas_pendientes.map(c => `
            <tr>
              <td>${c.concepto === 'matricula' ? 'Matrícula' : 'Cuota'}</td>
              <td>${MESES[c.mes - 1]} ${c.anio}</td>
              <td>$${Number(c.importe).toFixed(2)}</td>
              <td>${c.fecha_vencimiento}</td>
              <td>${c.dias_atraso > 0 ? `<span class="badge badge-rojo">${c.dias_atraso} días</span>` : '—'}</td>
            </tr>
          `).join('') + '</tbody></table>';

      let html = `
        <div class="card">
          <div class="card-header"><span class="icon">👤</span><h2>Datos Personales</h2></div>
          <div class="card-body">
            <div class="alumno-header">
              <div class="alumno-avatar">${iniciales}</div>
              <div class="alumno-datos">
                <strong>${esc(d.alumno.nombre)}</strong>
                <span>Usuario: ${esc(d.alumno.usuario)} · Alta: ${d.alumno.alta} · ${cursoStr}</span>
              </div>
              <div style="margin-left:auto;">${estadoAlumno}</div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><span class="icon">💳</span><h2>Situación Económica</h2></div>
          <div class="card-body">
            <div style="margin-bottom:1rem;">${badgeCondicion}</div>
            ${cuotasHtml}
          </div>
        </div>

        <div class="card">
          <div class="card-header"><span class="icon">📊</span><h2>Calificaciones</h2></div>
          <div class="card-body" style="overflow-x:auto;">
            ${tablaSimple(d.calificaciones, ['Materia','Evaluación','Nota','Fecha'], ['materia','evaluacion','nota','fecha'])}
          </div>
        </div>

        <div class="card">
          <div class="card-header"><span class="icon">📋</span><h2>Asistencia por Materia</h2></div>
          <div class="card-body" style="overflow-x:auto;">
            ${tablaSimple(d.asistencia, ['Materia','Total registros','Faltas'], ['materia','total','faltas'])}
          </div>
        </div>

        <div class="card">
          <div class="card-header"><span class="icon">🔄</span><h2>Recuperatorios</h2></div>
          <div class="card-body" style="overflow-x:auto;">
            ${tablaSimple(d.recuperatorios, ['Materia','Período','Fecha','Turno','Estado'], ['materia','periodo','fecha','turno','estado'])}
          </div>
        </div>

        <div class="card">
          <div class="card-header"><span class="icon">⚠️</span><h2>Sanciones</h2></div>
          <div class="card-body" style="overflow-x:auto;">
            ${tablaSimple(d.sanciones, ['Tipo','Descripción','Fecha','Registrado por'], ['tipo','descripcion','fecha','registrado_por'])}
          </div>
        </div>

        <div class="card">
          <div class="card-header"><span class="icon">🏥</span><h2>Atenciones de Enfermería</h2></div>
          <div class="card-body" style="overflow-x:auto;">
            ${tablaSimple(d.enfermeria, ['Fecha','Motivo','Hora','Observaciones','Atendido por'], ['fecha','motivo','hora','observaciones','atendido_por'])}
          </div>
        </div>

        <div class="card">
          <div class="card-header"><span class="icon">🍽️</span><h2>Servicios Reservados</h2></div>
          <div class="card-body" style="overflow-x:auto;">
            ${tablaSimple(d.servicios, ['Servicio','Mes','Año','Estado'], ['servicio','mes','anio','estado'])}
          </div>
        </div>
      `;
      contenido.innerHTML = html;
    } catch {
      contenido.innerHTML = '<div class="card"><div class="card-body"><p class="empty-msg">Error al cargar el legajo.</p></div></div>';
    }
  });

  function tablaSimple(filas, cabeceras, campos) {
    if (!filas || !filas.length) return '<p class="empty-msg">Sin registros.</p>';
    let h = '<table><thead><tr>' + cabeceras.map(c => `<th>${c}</th>`).join('') + '</tr></thead><tbody>';
    filas.forEach(f => {
      h += '<tr>' + campos.map(c => `<td>${esc(f[c] ?? '—')}</td>`).join('') + '</tr>';
    });
    return h + '</tbody></table>';
  }

  function esc(str) {
    if (str === null || str === undefined) return '—';
    const d = document.createElement('div'); d.textContent = String(str); return d.innerHTML;
  }

  function iniciales2(nombre) {
    const p = nombre.trim().split(/\s+/);
    return ((p[0]?.[0] ?? '') + (p[p.length-1]?.[0] ?? '')).toUpperCase();
  }
</script>

</body>
</html>
