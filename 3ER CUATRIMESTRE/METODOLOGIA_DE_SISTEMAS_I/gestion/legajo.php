<?php
require_once '../auth/session.php';
$rol_sesion = $_SESSION['rol'] ?? '';
if (!in_array($rol_sesion, ['admin', 'docente'], true)) {
    header('Location: /');
    exit;
}
$es_docente = $rol_sesion === 'docente';
$nombre = htmlspecialchars($_SESSION['nombre'] ?? ($es_docente ? 'Docente' : 'Administrador'));
$volver_url = $es_docente ? '../portals/portal_docente.php' : '../portals/portal_admin.php';

require_once '../gestion/helpers.php';
require_once '../database/db_config.php';
$alumnos = [];
if ($es_docente) {
    // Un docente solo puede buscar legajos de alumnos en cursos donde dicta alguna materia.
    $stmt = $conn->prepare(
        "SELECT DISTINCT u.id, u.nombre, c.nombre AS curso
         FROM usuarios u
         JOIN alumno_curso ac ON ac.alumno_id = u.id
         JOIN cursos c ON c.id = ac.curso_id
         JOIN materias m ON m.curso_id = c.id
         WHERE u.rol = 'alumno' AND u.activo = 1 AND m.docente_id = ?
         ORDER BY u.nombre"
    );
    $docente_id = (int) $_SESSION['usuario_id'];
    $stmt->bind_param('i', $docente_id);
    $stmt->execute();
    $alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $res = $conn->query("SELECT u.id, u.nombre, c.nombre AS curso FROM usuarios u LEFT JOIN alumno_curso ac ON ac.alumno_id = u.id LEFT JOIN cursos c ON c.id = ac.curso_id WHERE u.rol = 'alumno' AND u.activo = 1 ORDER BY u.nombre");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $alumnos[] = $r;
        }
    }
}
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
    .legajo-toolbar { display: flex; gap: .5rem; flex-wrap: wrap; margin-left: auto; }
    .btn-sec { background: var(--blanco); color: var(--azul); border: 2px solid var(--borde); border-radius: 10px; padding: .45rem 1rem; font-weight: 800; font-size: .82rem; cursor: pointer; font-family: inherit; text-decoration: none; }
    .btn-sec:hover { border-color: var(--naranja); color: var(--naranja); }
    .indicadores { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: .8rem; margin-bottom: 1.5rem; }
    .indicador { background: var(--blanco); border-radius: 14px; box-shadow: var(--sombra); padding: .9rem 1rem; border-top: 4px solid var(--borde); }
    .indicador b { display: block; font-size: 1.45rem; font-weight: 900; color: var(--azul); }
    .indicador span { font-size: .72rem; font-weight: 800; color: #6B7280; text-transform: uppercase; letter-spacing: .05em; }
    .indicador.ok { border-top-color: #10B981; } .indicador.mal { border-top-color: #EF4444; } .indicador.warn { border-top-color: #F59E0B; }
    .datos-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .9rem 1.5rem; }
    .dato span { display: block; font-size: .72rem; font-weight: 800; color: #6B7280; text-transform: uppercase; letter-spacing: .05em; }
    .dato strong { font-size: .92rem; font-weight: 700; }
    .aviso { border-radius: 10px; padding: .7rem 1rem; font-size: .86rem; margin-bottom: 1rem; }
    .aviso-salud { background: #FEF3C7; border-left: 4px solid #F59E0B; }
    .aviso-falta { background: #F3F4F6; border-left: 4px solid #9CA3AF; }
    .nota-baja { color: #DC2626; font-weight: 800; }
    .indice { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1.2rem; }
    .indice a { font-size: .78rem; font-weight: 800; color: var(--azul); background: var(--blanco); border: 1px solid var(--borde); border-radius: 20px; padding: .25rem .75rem; text-decoration: none; }
    .indice a:hover { border-color: var(--naranja); color: var(--naranja); }
    .solo-impresion { display: none; }
    @media print {
      header, .welcome, .volver, #card-selector, .legajo-toolbar, .indice { display: none !important; }
      body { background: #fff; }
      .container { max-width: none; margin: 0; padding: 0; }
      .card, .indicador { box-shadow: none; border: 1px solid #D1D5DB; break-inside: avoid; }
      .solo-impresion { display: block; margin-bottom: 1rem; font-size: .85rem; }
      .solo-impresion strong { font-family: 'Merriweather', serif; font-size: 1.1rem; }
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
    <span class="user-badge"><?= $es_docente ? '🎓 Docente' : '🔑 Admin' ?></span>
    <a href="../auth/logout.php" class="btn-logout">Cerrar sesión</a>
  </div>
</header>

<div class="welcome">
  <h1>Legajo de Alumno</h1>
  <p>Bienvenido/a, <?= $nombre ?></p>
  <span class="rol-badge"><?= $es_docente ? 'Portal Docente' : 'Panel Administración' ?></span>
</div>

<div class="container">
  <a href="<?= $volver_url ?>" class="volver">&larr; Volver al portal</a>

  <div class="card" id="card-selector">
    <div class="card-header"><span class="icon">📂</span><h2>Seleccionar Alumno</h2></div>
    <div class="card-body">
      <?php if (empty($alumnos)) : ?>
        <p class="empty-msg"><?= $es_docente ? 'Todavía no tenés alumnos a cargo (pedile al admin que te asigne una materia).' : 'No hay alumnos activos registrados.' ?></p>
      <?php else : ?>
      <div class="form-row">
        <label>Alumno
          <input type="text" id="sel-alumno-buscar" list="dl-alumnos" placeholder="Escribí nombre o curso…" autocomplete="off" style="min-width:280px;">
          <datalist id="dl-alumnos">
            <?php foreach ($alumnos as $a) : ?>
            <option value="<?= htmlspecialchars($a['nombre']) ?><?= $a['curso'] ? ' — ' . htmlspecialchars($a['curso']) : '' ?>">
            <?php endforeach; ?>
          </datalist>
          <input type="hidden" id="sel-alumno">
        </label>
        <button type="button" class="btn" id="btn-ver">Ver legajo</button>
      </div>
      <p class="empty-msg" id="alumno-no-encontrado" style="display:none;padding:0;text-align:left;">No se encontró ningún alumno con ese nombre. Elegí una opción de la lista o escribí el nombre completo.</p>
      <?php endif; ?>
    </div>
  </div>

  <div id="legajo-contenido" style="display:none;"></div>
</div>

<script>
  const contenido = document.getElementById('legajo-contenido');
  const ES_ADMIN  = <?= $es_docente ? 'false' : 'true' ?>;

  <?php if (!empty($alumnos)) : ?>
  const ALUMNOS = <?= json_encode(array_map(fn($a) => [
      'id'     => $a['id'],
      'nombre' => $a['nombre'],
      'texto'  => $a['nombre'] . ($a['curso'] ? ' — ' . $a['curso'] : ''),
  ], $alumnos)) ?>;

  const selAlumno     = document.getElementById('sel-alumno');
  const buscarAlumno  = document.getElementById('sel-alumno-buscar');
  const noEncontrado  = document.getElementById('alumno-no-encontrado');
  const btnVer        = document.getElementById('btn-ver');

  // Normaliza acentos/mayúsculas para que la búsqueda sea tolerante.
  function normalizar(s) {
    return (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
  }

  function resolverAlumno() {
    const texto = normalizar(buscarAlumno.value);
    if (!texto) { selAlumno.value = ''; noEncontrado.style.display = 'none'; return null; }

    let match = ALUMNOS.find(a => normalizar(a.texto) === texto || normalizar(a.nombre) === texto);
    if (!match) {
      const candidatos = ALUMNOS.filter(a => normalizar(a.nombre).includes(texto));
      if (candidatos.length === 1) match = candidatos[0];
    }
    selAlumno.value = match ? match.id : '';
    noEncontrado.style.display = match ? 'none' : '';
    return match;
  }
  buscarAlumno.addEventListener('input', resolverAlumno);
  buscarAlumno.addEventListener('keydown', e => { if (e.key === 'Enter') btnVer.click(); });

  btnVer.addEventListener('click', () => {
    resolverAlumno();
    if (!selAlumno.value) { noEncontrado.style.display = ''; return; }
    verLegajo(selAlumno.value);
  });

  // Abrir directo un legajo: legajo.php?alumno_id=N (por ejemplo, desde el ABM de alumnos).
  const desdeUrl = new URLSearchParams(location.search).get('alumno_id');
  if (desdeUrl) {
    const a = ALUMNOS.find(x => String(x.id) === desdeUrl);
    if (a) { buscarAlumno.value = a.texto; selAlumno.value = a.id; verLegajo(a.id); }
  }
  <?php endif; ?>

  async function verLegajo(alumno_id) {
    contenido.innerHTML = '<div class="card"><div class="card-body"><p class="empty-msg">Cargando legajo…</p></div></div>';
    contenido.style.display = '';
    try {
      const res  = await fetch(`legajo_listar.php?alumno_id=${encodeURIComponent(alumno_id)}`);
      const data = await res.json();
      if (!data.success) {
        contenido.innerHTML = `<div class="card"><div class="card-body"><p class="empty-msg">${esc(data.message)}</p></div></div>`;
        return;
      }
      contenido.innerHTML = renderLegajo(data);
      history.replaceState(null, '', `?alumno_id=${encodeURIComponent(alumno_id)}`);
    } catch {
      contenido.innerHTML = '<div class="card"><div class="card-body"><p class="empty-msg">Error al cargar el legajo.</p></div></div>';
    }
  }

  // ── Render ────────────────────────────────────────────────────
  function card(id, icon, titulo, cuerpo) {
    return `<div class="card" id="${id}"><div class="card-header"><span class="icon">${icon}</span><h2>${titulo}</h2></div>
            <div class="card-body" style="overflow-x:auto;">${cuerpo}</div></div>`;
  }
  function dato(label, valor) {
    return `<div class="dato"><span>${label}</span><strong>${valor === null || valor === undefined || valor === '' ? '—' : esc(valor)}</strong></div>`;
  }
  function indicador(valor, label, clase) {
    return `<div class="indicador ${clase || ''}"><b>${valor}</b><span>${label}</span></div>`;
  }
  const pesos = n => '$' + Number(n).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const MESES = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

  function renderLegajo(d) {
    const r = d.resumen;
    const cursoStr = d.curso ? `${d.curso.curso} · Nivel ${d.curso.nivel_educativo}` : 'Sin curso asignado';
    const badges = [
      d.alumno.activo ? '<span class="badge badge-verde">Activo</span>' : '<span class="badge badge-rojo">Suspendido</span>',
      d.curso && d.curso.condicion === 'pendiente_regularizacion' ? '<span class="badge badge-rojo">Pendiente de regularización</span>' : '',
    ].join('');

    let h = `
      <div class="solo-impresion"><strong>Educar para Transformar</strong> · Legajo del alumno · generado el ${esc(d.generado)}</div>
      <div class="card"><div class="card-body">
        <div class="alumno-header" style="margin-bottom:0;flex-wrap:wrap;">
          <div class="alumno-avatar">${iniciales2(d.alumno.nombre)}</div>
          <div class="alumno-datos">
            <strong>${esc(d.alumno.nombre)}</strong>
            <span>${esc(cursoStr)}${d.datos && d.datos.dni ? ' · DNI ' + esc(d.datos.dni) : ''}${d.datos && d.datos.edad !== undefined ? ' · ' + d.datos.edad + ' años' : ''}</span>
            <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.35rem;">${badges}</div>
          </div>
          <div class="legajo-toolbar">
            <button class="btn-sec" onclick="window.print()">🖨️ Imprimir / PDF</button>
            ${ES_ADMIN ? `<a class="btn-sec" href="alumnos.php?editar=${d.alumno.id}">✏️ Editar datos</a>` : ''}
          </div>
        </div>
      </div></div>

      <nav class="indice">
        <a href="#sec-datos">Datos personales</a><a href="#sec-familia">Familia</a><a href="#sec-salud">Salud</a>
        <a href="#sec-materias">Rendimiento</a><a href="#sec-notas">Calificaciones</a><a href="#sec-asistencia">Asistencia</a>
        <a href="#sec-recups">Recuperatorios</a><a href="#sec-sanciones">Sanciones</a><a href="#sec-actividades">Deportes y servicios</a>
        <a href="#sec-comunicacion">Comunicación</a>${Array.isArray(d.cuotas) ? '<a href="#sec-economica">Situación económica</a>' : ''}
      </nav>

      <div class="indicadores">
        ${indicador(r.promedio_general ?? '—', 'Promedio general', r.promedio_general === null ? '' : (r.promedio_general >= r.nota_aprobacion ? 'ok' : 'mal'))}
        ${indicador(r.asistencia_pct !== null ? r.asistencia_pct + '%' : '—', 'Asistencia', r.asistencia_pct === null ? '' : (r.asistencia_pct >= 85 ? 'ok' : (r.asistencia_pct >= 75 ? 'warn' : 'mal')))}
        ${indicador(`${r.materias_riesgo}/${r.materias_total}`, 'Materias en riesgo', r.materias_riesgo ? 'mal' : 'ok')}
        ${indicador(r.faltas_total, 'Faltas totales', '')}
        ${indicador(r.recups_pendientes, 'Recup. pendientes', r.recups_pendientes ? 'warn' : 'ok')}
        ${indicador(r.sanciones, 'Sanciones', r.sanciones ? 'warn' : 'ok')}
        ${Array.isArray(d.cuotas) ? indicador(Number(d.deuda_total) > 0 ? pesos(d.deuda_total) : 'Al día', 'Deuda', Number(d.deuda_total) > 0 ? 'mal' : 'ok') : ''}
      </div>`;

    // Datos personales
    const p = d.datos;
    let personales = '';
    if (!p) {
      personales += `<div class="aviso aviso-falta">Este alumno todavía no tiene cargados sus datos personales (DNI, nacimiento, domicilio, contactos).
        ${ES_ADMIN ? `<a href="alumnos.php?editar=${d.alumno.id}">Cargarlos desde Gestión de Alumnos</a>.` : 'Pedile a administración que los complete.'}</div>`;
    }
    personales += `<div class="datos-grid">
      ${dato('Nombre completo', d.alumno.nombre)}
      ${dato('DNI', p && p.dni)}
      ${dato('Fecha de nacimiento', p && p.fecha_nacimiento_ar ? `${p.fecha_nacimiento_ar} (${p.edad} años)` : null)}
      ${dato('Domicilio', p && p.domicilio)}
      ${dato('Teléfono', p && p.telefono)}
      ${dato('Email de contacto', p && p.email)}
      ${dato('Curso actual', d.curso ? d.curso.curso : 'Sin curso')}
      ${dato('Nivel', d.curso ? d.curso.nivel_educativo : null)}
      ${dato('Condición', d.curso ? (d.curso.condicion === 'pendiente_regularizacion' ? 'Pendiente de regularización' : 'Regular') : null)}
      ${dato('Usuario del sistema', d.alumno.usuario)}
      ${dato('Alta en el sistema', d.alumno.alta)}
      ${dato('Ingreso por inscripción', p && p.fecha_solicitud ? `Solicitud del ${p.fecha_solicitud} (${p.nivel_solicitado})` : 'Alta directa')}
    </div>`;
    h += card('sec-datos', '👤', 'Datos Personales', personales);

    // Familia
    let familia = `<div class="datos-grid" style="margin-bottom:1rem;">
      ${dato('Tutor declarado', p && p.tutor_nombre)}
      ${dato('Contacto de emergencia', p && p.contacto_emergencia)}
    </div>
    <div class="seccion-titulo">Padres / tutores con acceso al portal</div>
    ${tablaSimple(d.tutores.map(t => ({ nombre: t.nombre, usuario: t.usuario, estado: Number(t.activo) ? 'Activo' : 'Suspendido' })),
       ['Nombre', 'Usuario', 'Estado'], ['nombre', 'usuario', 'estado'])}`;
    h += card('sec-familia', '👨‍👩‍👧', 'Familia y Contactos', familia);

    // Salud
    let salud = `<div class="datos-grid" style="margin-bottom:1rem;">${dato('Obra social', p && p.obra_social)}</div>`;
    salud += p && p.observaciones_salud
      ? `<div class="aviso aviso-salud"><strong>⚕️ A tener en cuenta:</strong> ${esc(p.observaciones_salud)}</div>`
      : '<p class="empty-msg" style="text-align:left;">Sin observaciones de salud registradas.</p>';
    salud += `<div class="seccion-titulo">Atenciones de enfermería</div>
      ${tablaSimple(d.enfermeria, ['Fecha','Hora','Motivo','Observaciones','Atendido por'], ['fecha','hora','motivo','observaciones','atendido_por'])}`;
    h += card('sec-salud', '🏥', 'Salud', salud);

    // Rendimiento por materia
    h += card('sec-materias', '📈', 'Rendimiento por Materia', tablaSimple(d.materias.map(m => ({
        materia: m.materia, docente: m.docente || 'Sin docente',
        promedio: m.promedio === null ? '—' : { html: `<span class="${m.promedio < r.nota_aprobacion ? 'nota-baja' : ''}">${m.promedio}</span>` },
        evaluaciones: m.evaluaciones, faltas: m.faltas, recups: m.recups_pendientes || '—',
        situacion: { html: m.situacion === 'Aprobando' ? '<span class="badge badge-verde">Aprobando</span>'
                         : (m.situacion === 'En riesgo' ? '<span class="badge badge-rojo">En riesgo</span>' : '<span class="badge badge-gris">Sin notas</span>') },
      })), ['Materia','Docente','Promedio','Evaluaciones','Faltas','Recup. pend.','Situación'],
      ['materia','docente','promedio','evaluaciones','faltas','recups','situacion']));

    // Calificaciones
    h += card('sec-notas', '📊', 'Calificaciones', tablaSimple(d.calificaciones.map(c => ({
        materia: c.materia, trimestre: c.trimestre, evaluacion: c.evaluacion,
        nota: { html: `<span class="${c.nota < r.nota_aprobacion ? 'nota-baja' : ''}">${c.nota}</span>` }, fecha: c.fecha,
      })), ['Materia','Trimestre','Evaluación','Nota','Fecha'], ['materia','trimestre','evaluacion','nota','fecha']));

    // Asistencia
    h += card('sec-asistencia', '📋', `Asistencia por Materia <small style="font-weight:600;color:#6B7280;">(límite: ${r.limite_faltas} faltas)</small>`,
      tablaSimple(d.asistencia.map(a => ({
        materia: a.materia, presentes: a.presentes, tardes: a.tardes, faltas: a.faltas, total: a.total,
        pct: a.porcentaje !== null ? a.porcentaje + '%' : '—',
        semaforo: { html: `<span class="badge ${a.faltas >= r.limite_faltas ? 'badge-rojo' : (r.limite_faltas - a.faltas <= 2 ? 'badge-amarillo' : 'badge-verde')}">${esc(a.semaforo)}</span>` },
      })), ['Materia','Presentes','Tardes','Faltas','Clases','% asistencia','Estado'],
      ['materia','presentes','tardes','faltas','total','pct','semaforo']));

    // Recuperatorios
    h += card('sec-recups', '🔄', 'Recuperatorios', tablaSimple(d.recuperatorios.map(x => ({
        materia: x.materia, periodo: x.periodo, fecha: x.fecha || 'A confirmar',
        turno: x.turno ? (x.turno === 'mañana' ? 'Mañana' : 'Tarde') : '—', nota: x.nota ?? '—',
        estado: { html: x.estado === 'aprobado' ? '<span class="badge badge-verde">Aprobado</span>'
                    : (x.estado === 'desaprobado' ? '<span class="badge badge-rojo">Desaprobado</span>' : '<span class="badge badge-amarillo">Pendiente</span>') },
      })), ['Materia','Período','Fecha','Turno','Nota','Estado'], ['materia','periodo','fecha','turno','nota','estado']));

    // Sanciones
    const TIPOS = { apercibimiento: 'Apercibimiento', suspension: 'Suspensión', otra: 'Otra' };
    // Las anuladas siguen en el legajo (RN13), tachadas y con el motivo.
    h += card('sec-sanciones', '⚠️', 'Sanciones', tablaSimple(d.sanciones.map(s => {
      const tipo = esc(TIPOS[s.tipo] || s.tipo) + (s.dias_suspension ? ` (${s.dias_suspension} días)` : '');
      return Number(s.anulada) ? { ...s,
        tipo: { html: `<s>${tipo}</s> <span class="badge badge-gris">Anulada</span>` },
        descripcion: { html: `<s>${esc(s.descripcion)}</s><br><small>Motivo de la anulación: ${esc(s.motivo_anulacion)}</small>` },
      } : { ...s, tipo: { html: tipo } };
    }),
      ['Fecha','Tipo','Descripción','Registrado por'], ['fecha','tipo','descripcion','registrado_por']));

    // Deportes y servicios
    h += card('sec-actividades', '⚽', 'Deportes y Servicios',
      `<div class="seccion-titulo" style="margin-top:0;">Instalaciones deportivas</div>
       ${tablaSimple(d.deportes, ['Deporte','Horario','Inscripto desde'], ['deporte','horario','desde'])}
       <div class="seccion-titulo">Servicios reservados (comedor / transporte)</div>
       ${tablaSimple(d.servicios.map(s => ({ servicio: s.servicio === 'comedor' ? 'Comedor' : 'Transporte', periodo: `${MESES[s.mes]} ${s.anio}`, estado: s.estado })),
         ['Servicio','Mes','Estado'], ['servicio','periodo','estado'])}`);

    // Comunicación con la familia
    h += card('sec-comunicacion', '💬', 'Comunicación con la Familia',
      `<div class="seccion-titulo" style="margin-top:0;">Consultas sobre notas</div>
       ${tablaSimple(d.consultas.map(c => ({ fecha: c.fecha, materia: `${c.materia} · ${c.evaluacion} (${c.nota})`, padre: c.padre,
           mensaje: c.mensaje, respuesta: c.respuesta || (c.estado === 'pendiente' ? 'Sin responder' : '—') })),
         ['Fecha','Evaluación','Consultó','Consulta','Respuesta'], ['fecha','materia','padre','mensaje','respuesta'])}
       <div class="seccion-titulo">Últimas notificaciones enviadas</div>
       ${tablaSimple(d.notificaciones.map(n => ({ ...n, tipo: n.tipo.replace('_', ' ') })), ['Fecha','Tipo','Mensaje'], ['fecha','tipo','mensaje'])}`);

    h += seccionEconomica(d);
    return h;
  }

  // RFG12/RFG16: situación económica (el backend solo la envía al admin).
  function seccionEconomica(d) {
    if (!Array.isArray(d.cuotas)) return '';
    const filas = d.cuotas.map(c => ({
      concepto: c.concepto === 'matricula' ? 'Matrícula' : 'Cuota',
      periodo: `${String(c.mes).padStart(2,'0')}/${c.anio}`,
      importe: pesos(c.importe),
      recargo: Number(c.recargo) > 0 ? pesos(c.recargo) : '—',
      vencimiento: c.vencimiento,
      estado: { html: c.estado === 'pagada'
        ? `<span class="badge badge-verde">Pagada ${esc(c.fecha_pago || '')}</span>${c.metodo_pago ? `<br><small>${esc(c.metodo_pago)}</small>` : ''}`
        : (c.vencida ? '<span class="badge badge-rojo">Vencida</span>' : '<span class="badge badge-amarillo">Pendiente</span>') },
    }));
    const deuda = Number(d.deuda_total) > 0
      ? `<p style="margin-bottom:.7rem;font-weight:800;color:#991B1B;">Deuda pendiente: ${pesos(d.deuda_total)}</p>`
      : '<p style="margin-bottom:.7rem;font-weight:800;color:#065F46;">Sin deuda pendiente.</p>';
    return card('sec-economica', '💳', 'Situación Económica',
      deuda + tablaSimple(filas, ['Concepto','Período','Importe','Recargo','Vencimiento','Estado'], ['concepto','periodo','importe','recargo','vencimiento','estado']));
  }

  // Celdas: texto plano (se escapa) o { html } para badges ya armados.
  function tablaSimple(filas, cabeceras, campos) {
    if (!filas || !filas.length) return '<p class="empty-msg">Sin registros.</p>';
    let h = '<table><thead><tr>' + cabeceras.map(c => `<th>${c}</th>`).join('') + '</tr></thead><tbody>';
    filas.forEach(f => {
      h += '<tr>' + campos.map(c => {
        const v = f[c];
        return `<td>${v && typeof v === 'object' && 'html' in v ? v.html : esc(v ?? '—')}</td>`;
      }).join('') + '</tr>';
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
