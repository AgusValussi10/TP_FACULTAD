<?php

/**
 * Encabezado y cierre comunes de las pantallas del admin de la Parte 3.
 * Uso: require este archivo, llamar a layout_admin_inicio('Título', 'Bajada') y,
 * al final del HTML, layout_admin_fin().
 * Si no hay sesión de admin, redirige a la landing (como el resto de las pantallas).
 */

require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/helpers.php';

if (($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /');
    exit;
}

function layout_admin_inicio(string $titulo, string $bajada): void
{
    $t = htmlspecialchars($titulo);
    $b = htmlspecialchars($bajada);
    $nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Administrador');
    echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>$t – Educar para Transformar</title>
  <link rel="icon" href="../assets/logo.avif">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Merriweather:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<header>
  <a href="../" class="logo-area">
    <img src="../assets/logo.avif" alt="Educar para Transformar" class="logo-circle">
    <div class="logo-text"><strong>Educar para Transformar</strong><span>Centro Educativo – Resistencia, Chaco</span></div>
  </a>
  <div class="user-info">
    <span class="user-badge">🔑 $nombre</span>
    <a href="../auth/logout.php" class="btn-logout">Cerrar sesión</a>
  </div>
</header>
<div class="welcome"><h1>$t</h1><p>$b</p></div>
<div class="container">
  <a href="../portals/portal_admin.php" class="volver">← Volver al portal</a>
HTML;
}

function layout_admin_fin(): void
{
    echo "\n</div>\n</body>\n</html>\n";
}
