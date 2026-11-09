// Instalación de la app.
// - Android / Chrome / Edge: el navegador dispara 'beforeinstallprompt'; lo guardamos
//   y mostramos nuestro botón "Instalar app".
// - iOS (Safari): no hay evento ni botón programable; mostramos los pasos
//   "Compartir → Agregar a inicio".

let eventoInstalacion = null;
const oyentes = new Set();

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  eventoInstalacion = e;
  oyentes.forEach((fn) => fn());
});
window.addEventListener('appinstalled', () => {
  eventoInstalacion = null;
  oyentes.forEach((fn) => fn());
});

export function instalada() {
  return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

export function esIOS() {
  const ua = window.navigator.userAgent;
  // iPadOS se presenta como Mac, pero con pantalla táctil.
  return /iphone|ipad|ipod/i.test(ua) || (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1);
}

/** 'instalada' | 'disponible' (hay botón) | 'ios' (instrucciones) | 'no_disponible'. */
export function estadoInstalacion() {
  if (instalada()) return 'instalada';
  if (eventoInstalacion) return 'disponible';
  if (esIOS()) return 'ios';
  return 'no_disponible';
}

export async function instalar() {
  if (!eventoInstalacion) return false;
  eventoInstalacion.prompt();
  const { outcome } = await eventoInstalacion.userChoice;
  eventoInstalacion = null;
  oyentes.forEach((fn) => fn());
  return outcome === 'accepted';
}

/** Llama a fn cuando cambia la disponibilidad (devuelve la función para desuscribirse). */
export function alCambiarInstalacion(fn) {
  oyentes.add(fn);
  return () => oyentes.delete(fn);
}
