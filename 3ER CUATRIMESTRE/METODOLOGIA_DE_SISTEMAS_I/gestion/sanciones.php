<?php

// RFG14 (RN13) — Registro y consulta de sanciones disciplinarias (admin y docente).
require_once '../auth/session.php';
$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['admin', 'docente'], true)) {
    header('Location: /');
    exit;
}
$es_admin = $rol === 'admin';
$nombre   = htmlspecialchars($_SESSION['nombre'] ?? '');

require_once 'helpers.php';
require_once '../database/db_config.php';
// Alumnos que se pueden sancionar: el docente, solo los de los cursos donde dicta.
$docente_id = $es_admin ? 0 : (int) $_SESSION['usuario_id'];
$stmt = $conn->prepare(
    "SELECT DISTINCT u.id, u.nombre, c.nombre AS curso
     FROM usuarios u
     LEFT JOIN alumno_curso ac ON ac.alumno_id = u.id
     LEFT JOIN cursos c ON c.id = ac.curso_id
     WHERE u.rol = 'alumno' AND u.activo = 1
       AND (? = 0 OR EXISTS (SELECT 1 FROM materias m WHERE m.curso_id = c.id AND m.docente_id = ?))
     ORDER BY u.nombre"
);
$stmt->bind_param('ii', $docente_id, $docente_id);
$stmt->execute();
$alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$hoy    = date('Y-m-d');
$minimo = date('Y-m-d', strtotime('-' . SANCION_DIAS_ATRAS_MAX . ' days'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sanciones – Educar para Transformar</title>
  <link rel="icon" href="../assets/logo.avif">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Merriweather:wght@700&display=swap" rel="stylesheet">
  <style>
    :root { --naranja: #F97316; --naranja-bg: #FFF7ED; --azul: #374151; --gris-texto: #111827; --borde: #E5E7EB; --sombra: 0 4px 18px rgba(249,115,22,.13); }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Nunito', sans-serif; color: var(--gris-texto); background: #F9FAFB; }
    header { background: #fff; padding: .75rem 1.5rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 12px rgba(249,115,22,.12); position: sticky; top: 0; z-index: 100; }
    .logo-area { display: flex; align-items: center; gap: .7rem; text-decoration: none; color: inherit; }
    .logo-circle { height: 48px; border-radius: 6px; }
    .logo-text strong { display: block; font-size: .95rem; font-weight: 900; }
    .logo-text span { font-size: .7rem; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; }
    .btn-logout { background: #C2410C; color: #fff; border-radius: 20px; padding: .4rem 1rem; font-weight: 900; font-size: .85rem; text-decoration: none; }
    .welcome { background: linear-gradient(135deg, #374151 0%, #1F2937 45%, #111827 100%); color: #fff; padding: 2.2rem 1.5rem; text-align: center; }
    .welcome h1 { font-family: 'Merriweather', serif; font-size: clamp(1.4rem, 4vw, 2rem); margin-bottom: .4rem; }
    .container { max-width: 1060px; margin: 2rem auto; padding: 0 1.5rem 3rem; }
    .volver { display: inline-block; margin-bottom: 1rem; color: var(--azul); font-weight: 700; font-size: .85rem; text-decoration: none; }
    .card { background: #fff; border-radius: 16px; box-shadow: var(--sombra); margin-bottom: 1.5rem; }
    .card-header { padding: 1.1rem 1.5rem; display: flex; align-items: center; gap: .7rem; border-bottom: 2px solid var(--borde); }
    .card-header h2 { font-size: 1rem; font-weight: 800; color: var(--azul); }
    .card-body { padding: 1.2rem 1.5rem; }
    .form-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; margin-bottom: 1rem; }
    .form-row label { display: flex; flex-direction: column; gap: .3rem; font-size: .82rem; font-weight: 800; color: var(--azul); }
    input, select, textarea { padding: .5rem .65rem; border: 2px solid var(--borde); border-radius: 8px; font-family: inherit; font-size: .9rem; }
    textarea { width: 100%; min-height: 80px; resize: vertical; }
    .btn { background: var(--naranja); color: #fff; border: none; border-radius: 10px; padding: .55rem 1.2rem; font-weight: 800; cursor: pointer; font-family: inherit; }
    .msg { margin: .6rem 0; font-weight: 700; font-size: .88rem; min-height: 1.2em; }
    table { width: 100%; border-collapse: collapse; font-size: .86rem; }
    th { background: var(--naranja-bg); color: var(--naranja); font-weight: 800; padding: .5rem .6rem; text-align: left; font-size: .78rem; text-transform: uppercase; }
    td { padding: .55rem .6rem; border-bottom: 1px solid #F3F4F6; vertical-align: top; }
    .badge { display: inline-block; border-radius: 12px; padding: .12rem .5rem; font-size: .74rem; font-weight: 800; margin: .1rem 0; }
    .b-apercibimiento { background: #fef3c7; color: #92400E; }
    .b-suspension { background: #fee2e2; color: #991B1B; }
    .b-otra { background: #E5E7EB; color: #374151; }
    .b-contacto { background: #DC2626; color: #fff; }
    .empty-msg { color: #9CA3AF; text-align: center; padding: 1.2rem 0; }
  </style>
</head>
<body>
<header>
  <a href="../" class="logo-area">
    <img src="../assets/logo.avif" alt="Educar para Transformar" class="logo-circle">
    <div class="logo-text"><strong>Educar para Transformar</strong><span>Centro Educativo – Resistencia, Chaco</span></div>
  </a>
  <a href="../auth/logout.php" class="btn-logout">Cerrar sesión</a>
</header>

<div class="welcome">
  <h1>Sanciones disciplinarias</h1>
  <p><?= $nombre ?> · <?= $es_admin ? 'Administración' : 'Docente' ?></p>
</div>

<div class="container">
  <a href="../portals/portal_<?= $es_admin ? 'admin' : 'docente' ?>.php" class="volver">&larr; Volver al portal</a>

  <div class="card">
    <div class="card-header"><span>📝</span><h2>Registrar sanción</h2></div>
    <div class="card-body">
      <?php if (empty($alumnos)) : ?>
        <p class="empty-msg">No tenés alumnos a cargo.</p>
      <?php else : ?>
      <div class="form-row">
        <label for="f-alumno">Alumno
          <input type="text" id="f-alumno" list="dl-alumnos" placeholder="Escribí nombre o curso…" autocomplete="off" style="min-width:280px;">
        </label>
        <datalist id="dl-alumnos">
          <?php foreach ($alumnos as $a) : ?>
          <option value="<?= htmlspecialchars($a['nombre'] . ($a['curso'] ? ' — ' . $a['curso'] : '')) ?>">
          <?php endforeach; ?>
        </datalist>
        <label for="f-tipo">Tipo
          <select id="f-tipo">
            <?php foreach (TIPOS_SANCION as $valor => $texto) : ?>
            <option value="<?= $valor ?>"><?= $texto ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label for="f-dias" id="lbl-dias" style="display:none;">Días de suspensión
          <input type="number" id="f-dias" min="1" max="<?= SUSPENSION_DIAS_MAX ?>" style="width:110px;">
        </label>
        <label for="f-fecha">Fecha
          <input type="date" id="f-fecha" value="<?= $hoy ?>" min="<?= $minimo ?>" max="<?= $hoy ?>">
        </label>
      </div>
      <label for="f-desc" style="font-size:.82rem;font-weight:800;color:var(--azul);">Descripción del hecho (10 a 1000 caracteres)</label>
      <textarea id="f-desc" maxlength="1000"></textarea>
      <div class="msg" id="msg-form"></div>
      <button type="button" class="btn" id="btn-guardar">Registrar y notificar</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span>⚠️</span><h2>Sanciones registradas<?= $es_admin ? '' : ' (alumnos de tus cursos)' ?></h2></div>
    <div class="card-body">
      <div class="form-row">
        <label for="fl-curso">Curso <select id="fl-curso"><option value="0">Todos</option></select></label>
        <label for="fl-tipo">Tipo
          <select id="fl-tipo"><option value="">Todos</option>
            <?php foreach (TIPOS_SANCION as $valor => $texto) : ?>
            <option value="<?= $valor ?>"><?= $texto ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label for="fl-desde">Desde <input type="date" id="fl-desde"></label>
        <label for="fl-hasta">Hasta <input type="date" id="fl-hasta"></label>
      </div>
      <div id="lista"><p class="empty-msg">Cargando…</p></div>
    </div>
  </div>
</div>

<script>
  const ALUMNOS = <?= json_encode(array_map(fn($a) => [
      'id' => (int) $a['id'], 'texto' => $a['nombre'] . ($a['curso'] ? ' — ' . $a['curso'] : ''),
  ], $alumnos)) ?>;
  const $ = id => document.getElementById(id);
  const esc = s => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };
  const msg = (el, texto, ok) => { $(el).textContent = texto; $(el).style.color = ok ? '#059669' : '#DC2626'; };
  let cursosCargados = false;

  if ($('f-tipo')) {
    $('f-tipo').addEventListener('change', () => { $('lbl-dias').style.display = $('f-tipo').value === 'suspension' ? '' : 'none'; });
    $('btn-guardar').addEventListener('click', guardar);
  }

  async function guardar() {
    const alumno = ALUMNOS.find(a => a.texto === $('f-alumno').value.trim());
    if (!alumno) { msg('msg-form', 'Elegí un alumno de la lista.'); return; }
    const body = new FormData();
    body.append('alumno_id', alumno.id);
    body.append('tipo', $('f-tipo').value);
    body.append('dias_suspension', $('f-tipo').value === 'suspension' ? $('f-dias').value : '');
    body.append('fecha', $('f-fecha').value);
    body.append('descripcion', $('f-desc').value);
    $('btn-guardar').disabled = true;
    try {
      const data = await (await fetch('sanciones_guardar.php', { method: 'POST', body })).json();
      msg('msg-form', (data.success ? '✅ ' : '') + data.message, data.success && !data.sin_contacto);
      if (data.success) { $('f-desc').value = ''; $('f-alumno').value = ''; cargar(); }
    } catch { msg('msg-form', 'Error de conexión.'); }
    $('btn-guardar').disabled = false;
  }

  async function cargar() {
    const p = new URLSearchParams({ curso_id: $('fl-curso').value, tipo: $('fl-tipo').value, desde: $('fl-desde').value, hasta: $('fl-hasta').value });
    try {
      const data = await (await fetch('sanciones_listar.php?' + p)).json();
      if (!data.success) { $('lista').innerHTML = `<p class="empty-msg">${esc(data.message)}</p>`; return; }
      if (!cursosCargados) {
        $('fl-curso').innerHTML += data.cursos.map(c => `<option value="${c.id}">${esc(c.nombre)}</option>`).join('');
        cursosCargados = true;
      }
      $('lista').innerHTML = data.sanciones.length ? tabla(data.sanciones) : '<p class="empty-msg">No hay sanciones con esos filtros.</p>';
    } catch { $('lista').innerHTML = '<p class="empty-msg">Error al cargar las sanciones.</p>'; }
  }

  function tabla(filas) {
    return `<table><thead><tr><th>Fecha</th><th>Alumno</th><th>Tipo</th><th>Descripción</th><th>Registró</th></tr></thead><tbody>`
      + filas.map(s => `<tr>
          <td>${s.fecha_ar}</td>
          <td>${esc(s.alumno)}<br><small>${esc(s.curso || 'Sin curso')}</small></td>
          <td><span class="badge b-${s.tipo}">${esc(s.tipo_texto)}${s.dias_suspension ? ` · ${s.dias_suspension} d` : ''}</span>
            ${s.sin_contacto ? '<br><span class="badge b-contacto" title="El alumno no tiene padre/tutor vinculado">Avisar a la familia</span>' : ''}</td>
          <td>${esc(s.descripcion)}</td>
          <td>${esc(s.registrado_por)}</td>
        </tr>`).join('') + '</tbody></table>';
  }

  ['fl-curso', 'fl-tipo', 'fl-desde', 'fl-hasta'].forEach(id => $(id).addEventListener('change', cargar));
  cargar();
</script>
</body>
</html>
