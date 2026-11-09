<?php

/**
 * Service worker de la app (se sirve como JS desde /app/, así su alcance es /app/).
 *
 * Estrategia:
 * - Precachea solo el "shell" (HTML, CSS, JS, íconos, manifest, pantalla offline).
 * - /app/api/ va SIEMPRE por red y nunca se cachea: tiene datos personales.
 * - Sin conexión, una navegación muestra el shell cacheado (que avisa que no hay red)
 *   o, si no está, offline.html.
 *
 * La versión de la caché es un hash del contenido del shell: cambia sola en cada
 * deploy que toque esos archivos, el navegador detecta un sw.php distinto y
 * actualiza (sin acordarse de subir un número a mano).
 */

const SHELL = [
    'index.html', 'offline.html', 'manifest.webmanifest', 'css/app.css',
    'js/main.js', 'js/api.js', 'js/util.js', 'js/instalar.js',
    'js/vistas/login.js', 'js/vistas/inicio.js', 'js/vistas/cuenta.js',
    'icons/icon-192.png', 'icons/icon-512.png', 'icons/maskable-192.png', 'icons/maskable-512.png',
    'icons/apple-touch-icon.png',
];

// sha256 solo para versionar la caché (no protege nada); SonarCloud rechaza sha1.
$hash = hash_init('sha256');
foreach (SHELL as $archivo) {
    $ruta = __DIR__ . '/' . $archivo;
    hash_update($hash, $archivo . (is_file($ruta) ? (string) file_get_contents($ruta) : ''));
}
$version = substr(hash_final($hash), 0, 12);
$urls = array_merge(['/app/'], array_map(fn ($a) => '/app/' . $a, SHELL));

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache');
?>
// Generado por app/sw.php — versión del shell: <?= $version ?>

const CACHE = 'educar-shell-<?= $version ?>';
const SHELL = <?= json_encode($urls, JSON_UNESCAPED_SLASHES) ?>;

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((claves) => Promise.all(claves.filter((k) => k.startsWith('educar-shell-') && k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);
  // API, otros orígenes y métodos que no son GET: directo a la red, sin cachear nada.
  if (req.method !== 'GET' || url.origin !== self.location.origin || url.pathname.startsWith('/app/api/')) {
    return;
  }
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() => caches.match('/app/').then((r) => r || caches.match('/app/offline.html')))
    );
    return;
  }
  if (url.pathname.startsWith('/app/')) {
    event.respondWith(caches.match(req).then((r) => r || fetch(req)));
  }
});
