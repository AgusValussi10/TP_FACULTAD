<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'enfermeria') {
    header('Location: /');
    exit;
}
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Enfermería');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal Enfermería – Educar para Transformar</title>
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
    .logo-circle { height: 48px; width: auto; border-radius: 6px; object-fit: contain; }
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
    <span class="user-badge">⚕️ Enfermería</span>
    <a href="../auth/logout.php" class="btn-logout">Cerrar sesión</a>
  </div>
</header>

<div class="welcome">
  <h1>Bienvenido/a, <?= $nombre ?>!</h1>
  <p>Este es tu espacio de gestión de enfermería.</p>
  <span class="rol-badge">Portal Enfermería</span>
</div>

<div class="container">
  <div class="grid">

    <!-- ACCIONES RÁPIDAS -->
    <div class="card">
      <div class="card-header"><span class="icon">⚡</span><h2>Accesos Rápidos</h2></div>
      <div class="card-body">
        <a href="../gestion/enfermeria.php" class="accion-btn">🩺 Registrar atención</a>
      </div>
    </div>

  </div>
</div>

</body>
</html>
