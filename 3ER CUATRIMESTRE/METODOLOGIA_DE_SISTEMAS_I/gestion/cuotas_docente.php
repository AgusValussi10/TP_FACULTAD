<?php

// RFG19 — El docente visualiza el estado de las cuotas de los alumnos de sus cursos.
// Solo lectura y sin importes: registrar pagos es tarea administrativa (RN14).
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'docente') {
    header('Location: /');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Docente');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Estado de cuotas – Educar para Transformar</title>
  <link rel="icon" href="../assets/logo.avif">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Merriweather:wght@700&display=swap" rel="stylesheet">
  <style>
    :root { --naranja: #F97316; --naranja-bg: #FFF7ED; --azul: #374151; --gris-texto: #111827; --borde: #E5E7EB; --sombra: 0 4px 18px rgba(249,115,22,.13); }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Nunito', sans-serif; color: var(--gris-texto); background: #F9FAFB; }
    header { background: #fff; padding: .75rem 1.5rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 12px rgba(249,115,22,.12); }
    .logo-area { display: flex; align-items: center; gap: .7rem; text-decoration: none; color: inherit; }
    .logo-circle { height: 48px; border-radius: 6px; }
    .logo-text strong { display: block; font-size: .95rem; font-weight: 900; }
    .btn-logout { background: #C2410C; color: #fff; border-radius: 20px; padding: .4rem 1rem; font-weight: 900; font-size: .85rem; text-decoration: none; }
    .welcome { background: linear-gradient(135deg, #374151 0%, #1F2937 45%, #111827 100%); color: #fff; padding: 2.2rem 1.5rem; text-align: center; }
    .welcome h1 { font-family: 'Merriweather', serif; font-size: clamp(1.4rem, 4vw, 2rem); margin-bottom: .4rem; }
    .container { max-width: 1060px; margin: 2rem auto; padding: 0 1.5rem 3rem; }
    .volver { display: inline-block; margin-bottom: 1rem; color: var(--azul); font-weight: 700; font-size: .85rem; text-decoration: none; }
    .card { background: #fff; border-radius: 16px; box-shadow: var(--sombra); }
    .card-header { padding: 1.1rem 1.5rem; display: flex; align-items: center; gap: .7rem; border-bottom: 2px solid var(--borde); }
    .card-header h2 { font-size: 1rem; font-weight: 800; color: var(--azul); }
    .card-body { padding: 1.2rem 1.5rem; }
    .nota { font-size: .82rem; color: #6B7280; margin-bottom: 1rem; }
    table { width: 100%; border-collapse: collapse; font-size: .86rem; }
    th { background: var(--naranja-bg); color: var(--naranja); font-weight: 800; padding: .5rem .6rem; text-align: left; font-size: .78rem; text-transform: uppercase; }
    td { padding: .55rem .6rem; border-bottom: 1px solid #F3F4F6; vertical-align: top; }
    .badge { display: inline-block; border-radius: 12px; padding: .12rem .5rem; font-size: .74rem; font-weight: 800; margin: .1rem .1rem 0 0; }
    .impaga { background: #fee2e2; color: #991B1B; }
    .proxima_a_vencer { background: #fef3c7; color: #92400E; }
    .pendiente { background: #E5E7EB; color: #374151; }
    .pagada { background: #d1fae5; color: #065F46; }
    .empty-msg { color: #9CA3AF; text-align: center; padding: 1.2rem 0; }
  </style>
</head>
<body>
<header>
  <a href="../" class="logo-area">
    <img src="../assets/logo.avif" alt="Educar para Transformar" class="logo-circle">
    <div class="logo-text"><strong>Educar para Transformar</strong></div>
  </a>
  <a href="../auth/logout.php" class="btn-logout">Cerrar sesión</a>
</header>

<div class="welcome">
  <h1>Estado de cuotas</h1>
  <p><?= $nombre ?> · Alumnos de tus cursos</p>
</div>

<div class="container">
  <a href="../portals/portal_docente.php" class="volver">&larr; Volver al portal</a>
  <div class="card">
    <div class="card-header"><span>💳</span><h2>Cuotas del año <span id="anio"></span></h2></div>
    <div class="card-body">
      <p class="nota">Solo lectura y sin importes. Los pagos se registran en Administración.</p>
      <div id="lista"><p class="empty-msg">Cargando…</p></div>
    </div>
  </div>
</div>

<script>
  const esc = s => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };

  (async () => {
    const lista = document.getElementById('lista');
    try {
      const data = await (await fetch('cuotas_estado.php')).json();
      if (!data.success) { lista.innerHTML = `<p class="empty-msg">${esc(data.message)}</p>`; return; }
      document.getElementById('anio').textContent = data.anio;
      if (!data.alumnos.length) { lista.innerHTML = '<p class="empty-msg">No tenés alumnos a cargo.</p>'; return; }
      lista.innerHTML = '<table><thead><tr><th>Alumno</th><th>Curso</th><th>Situación</th><th>Cuotas</th></tr></thead><tbody>'
        + data.alumnos.map(a => {
          const situacion = a.impagas ? ['impaga', `${a.impagas} impaga${a.impagas > 1 ? 's' : ''}`]
            : a.proximas_a_vencer ? ['proxima_a_vencer', 'Próxima a vencer'] : ['pagada', 'Al día'];
          const cuotas = a.cuotas.map(c => `<span class="badge ${c.estado_visual}" title="${esc(c.estado_texto)}">${esc(c.periodo)}</span>`).join('');
          return `<tr><td>${esc(a.alumno)}</td><td>${esc(a.curso)}</td>
            <td><span class="badge ${situacion[0]}">${situacion[1]}</span></td>
            <td>${cuotas || '<small>Sin cuotas generadas</small>'}</td></tr>`;
        }).join('') + '</tbody></table>';
    } catch {
      lista.innerHTML = '<p class="empty-msg">Error al cargar las cuotas.</p>';
    }
  })();
</script>
</body>
</html>
