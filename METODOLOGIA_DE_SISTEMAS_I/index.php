<?php
require_once 'auth/session.php';

$sesion_activa = !empty($_SESSION['usuario_id']) && !empty($_SESSION['rol']);

if ($sesion_activa) {
    $nombre     = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario');
    $rol        = $_SESSION['rol'];
    $portal_url = 'portals/portal_' . $rol . '.php';

    $roles_nombres = [
        'alumno'  => 'Alumno/a',
        'padre'   => 'Padre / Tutor',
        'docente' => 'Docente',
        'admin'   => 'Administrador',
    ];
    $rol_nombre = $roles_nombres[$rol] ?? ucfirst($rol);

    $banner = '
<div id="sesion-activa-banner" style="
  position:fixed;top:0;left:0;right:0;z-index:9999;
  background:linear-gradient(90deg,#1F2937 0%,#374151 100%);
  color:#fff;
  display:flex;align-items:center;justify-content:center;
  gap:.9rem;padding:.65rem 1.5rem;
  font-family:\'Nunito\',sans-serif;font-size:.88rem;font-weight:600;
  box-shadow:0 3px 14px rgba(0,0,0,.4);
  flex-wrap:wrap;text-align:center;
">
  <span>🔒 Estás en una sesión activa como <strong>' . $nombre . '</strong> &mdash; ' . $rol_nombre . '. Cerrá sesión para poder navegar el inicio.</span>
  <div style="display:flex;gap:.5rem;flex-shrink:0;">
    <a href="' . $portal_url . '" style="
      background:#F97316;color:#fff !important;border-radius:20px;
      padding:.32rem 1rem;text-decoration:none;font-weight:800;font-size:.82rem;
      white-space:nowrap;display:inline-block;
    ">Ir a mi portal</a>
    <a href="auth/logout.php" style="
      background:transparent;color:#fff !important;border-radius:20px;
      padding:.32rem .9rem;text-decoration:none;font-weight:700;font-size:.82rem;
      border:1.5px solid rgba(255,255,255,.55);white-space:nowrap;display:inline-block;
    ">Cerrar sesión</a>
  </div>
</div>
<script>
  (function(){
    var b=document.getElementById("sesion-activa-banner");
    if(!b) return;
    var h=b.offsetHeight;
    document.body.style.paddingTop=h+"px";
    var hdr=document.querySelector("header");
    if(hdr) hdr.style.top=h+"px";
  })();
</script>
';

    ob_start();
    include 'index.html';
    $html = ob_get_clean();
    echo preg_replace('/<body([^>]*)>/i', '<body$1>' . $banner, $html);
} else {
    include 'index.html';
}
