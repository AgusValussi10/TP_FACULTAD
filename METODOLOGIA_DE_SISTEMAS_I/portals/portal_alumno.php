<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'alumno') {
    header('Location: /');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Alumno');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Alumno – Educar para Transformar</title>
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

    /* HEADER */
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

    /* WELCOME BANNER */
    .welcome {
      background: linear-gradient(135deg, #374151 0%, #1F2937 45%, #111827 100%);
      color: var(--blanco); padding: 2.5rem 1.5rem;
      text-align: center;
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

    /* GRID DE CARDS */
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

    /* Tabla */
    table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    th { background: var(--verde-bg); color: var(--verde); font-weight: 800;
         padding: .5rem .7rem; text-align: left; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
    td { padding: .55rem .7rem; border-bottom: 1px solid #F3F4F6; }
    tr:last-child td { border-bottom: none; }
    .badge {
      display: inline-block; border-radius: 12px;
      padding: .15rem .55rem; font-size: .78rem; font-weight: 800;
    }
    .badge-verde  { background: #d4edda; color: #1a7c34; }
    .badge-amarillo { background: #fff3cd; color: #856404; }
    .badge-rojo   { background: #f8d7da; color: #842029; }

    /* Lista de eventos */
    .evento-item {
      display: flex; align-items: flex-start; gap: .8rem;
      padding: .7rem 0; border-bottom: 1px solid #F3F4F6;
    }
    .evento-item:last-child { border-bottom: none; }
    .evento-fecha {
      background: var(--verde-bg); color: var(--verde);
      border-radius: 10px; padding: .35rem .6rem;
      font-size: .78rem; font-weight: 800; text-align: center;
      min-width: 48px; flex-shrink: 0;
    }
    .evento-desc { font-size: .88rem; line-height: 1.5; }
    .evento-desc strong { display: block; font-weight: 800; color: var(--gris-texto); }
    .evento-desc span   { color: #6b7280; font-size: .82rem; }

    /* Comunicados */
    .comunicado {
      padding: .8rem; border-left: 4px solid var(--verde-claro);
      background: var(--verde-bg); border-radius: 0 10px 10px 0;
      margin-bottom: .75rem; font-size: .88rem; line-height: 1.5;
    }
    .comunicado:last-child { margin-bottom: 0; }
    .comunicado strong { display: block; font-weight: 800; margin-bottom: .2rem; color: var(--azul); }

    /* Horario */
    .hora-badge {
      background: var(--azul); color: var(--blanco);
      border-radius: 8px; padding: .2rem .5rem;
      font-size: .78rem; font-weight: 700;
    }

    @media (max-width: 600px) { .grid { grid-template-columns: 1fr; } }
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
    <span class="user-badge">🎓 Alumno</span>
    <a href="../auth/logout.php" class="btn-logout">Cerrar sesión</a>
  </div>
</header>

<div class="welcome">
  <h1>¡Hola, <?= $nombre ?>!</h1>
  <p>Bienvenido/a a tu portal de alumno.</p>
  <span class="rol-badge">Portal Alumno</span>
</div>

<div class="container">
  <div class="grid">

    <!-- HORARIO -->
    <div class="card">
      <div class="card-header"><span class="icon">🗓️</span><h2>Mi Horario – Semana Actual</h2></div>
      <div class="card-body">
        <table>
          <thead><tr><th>Día</th><th>Materia</th><th>Hora</th></tr></thead>
          <tbody>
            <tr><td>Lunes</td><td>Matemática</td><td><span class="hora-badge">08:00</span></td></tr>
            <tr><td>Lunes</td><td>Lengua y Literatura</td><td><span class="hora-badge">09:30</span></td></tr>
            <tr><td>Martes</td><td>Inglés</td><td><span class="hora-badge">08:00</span></td></tr>
            <tr><td>Martes</td><td>Ciencias Naturales</td><td><span class="hora-badge">10:00</span></td></tr>
            <tr><td>Miércoles</td><td>Historia</td><td><span class="hora-badge">08:00</span></td></tr>
            <tr><td>Jueves</td><td>Educación Física</td><td><span class="hora-badge">09:00</span></td></tr>
            <tr><td>Viernes</td><td>Tecnología</td><td><span class="hora-badge">08:00</span></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CALIFICACIONES -->
    <div class="card">
      <div class="card-header"><span class="icon">📊</span><h2>Mis Calificaciones</h2></div>
      <div class="card-body">
        <table>
          <thead><tr><th>Materia</th><th>Nota</th><th>Estado</th></tr></thead>
          <tbody>
            <tr><td>Matemática</td><td><strong>8</strong></td><td><span class="badge badge-verde">Aprobado</span></td></tr>
            <tr><td>Lengua</td><td><strong>9</strong></td><td><span class="badge badge-verde">Aprobado</span></td></tr>
            <tr><td>Inglés</td><td><strong>10</strong></td><td><span class="badge badge-verde">Aprobado</span></td></tr>
            <tr><td>Historia</td><td><strong>7</strong></td><td><span class="badge badge-verde">Aprobado</span></td></tr>
            <tr><td>Cs. Naturales</td><td><strong>6</strong></td><td><span class="badge badge-amarillo">Regular</span></td></tr>
            <tr><td>Tecnología</td><td><strong>9</strong></td><td><span class="badge badge-verde">Aprobado</span></td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PRÓXIMOS EVENTOS -->
    <div class="card">
      <div class="card-header"><span class="icon">📅</span><h2>Próximos Eventos</h2></div>
      <div class="card-body">
        <div class="evento-item">
          <div class="evento-fecha">12<br>MAY</div>
          <div class="evento-desc"><strong>Examen de Matemática</strong><span>Unidad 3 – Funciones cuadráticas · Aula 5</span></div>
        </div>
        <div class="evento-item">
          <div class="evento-fecha">15<br>MAY</div>
          <div class="evento-desc"><strong>Entrega TP de Historia</strong><span>Revolución Industrial · Envío por plataforma</span></div>
        </div>
        <div class="evento-item">
          <div class="evento-fecha">20<br>MAY</div>
          <div class="evento-desc"><strong>Acto Escolar</strong><span>Día de la Escarapela · Patio central · 10:00 hs</span></div>
        </div>
        <div class="evento-item">
          <div class="evento-fecha">28<br>MAY</div>
          <div class="evento-desc"><strong>Evaluación de Inglés</strong><span>Reading &amp; Writing · Nivel B1</span></div>
        </div>
      </div>
    </div>

    <!-- COMUNICADOS -->
    <div class="card">
      <div class="card-header"><span class="icon">📢</span><h2>Comunicados</h2></div>
      <div class="card-body">
        <div class="comunicado">
          <strong>Cambio de horario – Educación Física</strong>
          A partir del 13/05, la clase pasa al turno tarde (14:30 hs) por refacción del gimnasio.
        </div>
        <div class="comunicado">
          <strong>Recordatorio: uniforme obligatorio</strong>
          Se recuerda que el uso del uniforme es obligatorio todos los días. Ante dudas, consultar preceptoría.
        </div>
        <div class="comunicado">
          <strong>Biblioteca – nuevos libros disponibles</strong>
          Se incorporaron 40 nuevos títulos de literatura argentina y latinoamericana. ¡Pasá a conocerlos!
        </div>
      </div>
    </div>

  </div>
</div>

</body>
</html>
