// Arranque de la app: sesión, micro-router por hash, selector de hijo y service worker.
import { api } from './api.js';
import { html, mensaje } from './util.js';
import * as login from './vistas/login.js';
import * as inicio from './vistas/inicio.js';
import * as cuenta from './vistas/cuenta.js';

// Rutas que necesitan sesión. La de login se muestra sola cuando no hay sesión.
const RUTAS = { inicio, cuenta };
const RUTA_INICIAL = 'inicio';

const main = document.getElementById('contenido');
const estado = { usuario: null, alumnos: [], alumnoId: null, avisoLogin: '' };

const ctx = {
  estado,
  navegar,
  alIniciarSesion,
  cerrarSesion,
  /** Alumno elegido (el propio, si es alumno). */
  alumno: () => estado.alumnos.find((a) => a.id === estado.alumnoId) || null,
};

// ── Sesión ───────────────────────────────────────────────────────────────

function leerPreferencia() {
  try {
    return Number(localStorage.getItem('alumnoId')) || null;
  } catch (e) {
    return null;
  }
}

function guardarPreferencia(id) {
  try {
    localStorage.setItem('alumnoId', String(id));
  } catch (e) {
    // Sin almacenamiento (modo privado): se elige el primero cada vez.
  }
}

function aplicarSesion(datos) {
  estado.usuario = datos.usuario;
  estado.alumnos = datos.alumnos || [];
  const preferido = leerPreferencia();
  estado.alumnoId = estado.alumnos.some((a) => a.id === preferido) ? preferido : (estado.alumnos[0]?.id ?? null);
}

function alIniciarSesion(datos) {
  aplicarSesion(datos);
  estado.avisoLogin = '';
  navegar(RUTA_INICIAL);
  refrescar();
}

async function cerrarSesion() {
  await api('logout.php', {});
  estado.usuario = null;
  estado.alumnos = [];
  navegar('login');
  refrescar();
}

window.addEventListener('sesion-vencida', () => {
  if (!estado.usuario) return;
  estado.usuario = null;
  estado.avisoLogin = 'Tu sesión venció por inactividad. Volvé a ingresar.';
  navegar('login');
  refrescar();
});

// ── Router ───────────────────────────────────────────────────────────────

function navegar(ruta) {
  const destino = '#/' + ruta;
  if (location.hash !== destino) {
    history.replaceState(null, '', destino);
  }
}

function rutaActual() {
  return location.hash.replace(/^#\/?/, '').split('?')[0] || RUTA_INICIAL;
}

function pintarMarco() {
  const conSesion = !!estado.usuario;
  document.getElementById('barra').hidden = !conSesion;
  document.getElementById('nav').hidden = !conSesion;
  const selector = document.getElementById('selector-hijo');
  selector.hidden = !conSesion || estado.usuario.rol !== 'padre' || estado.alumnos.length < 2;
  if (!conSesion) return;

  const rol = estado.usuario.rol === 'padre' ? 'Familia' : 'Alumno/a';
  document.getElementById('barra-usuario').textContent = `${estado.usuario.nombre} · ${rol}`;
  if (!selector.hidden) {
    const sel = document.getElementById('sel-alumno');
    sel.innerHTML = estado.alumnos.map((a) => html`<option value="${a.id}">${a.nombre}${a.curso ? ' · ' + a.curso : ''}</option>`).join('');
    sel.value = String(estado.alumnoId);
  }
  const ruta = rutaActual();
  document.querySelectorAll('#nav a').forEach((a) => {
    if (a.dataset.ruta === ruta) a.setAttribute('aria-current', 'page');
    else a.removeAttribute('aria-current');
  });
}

/** Pinta la vista sin dejar promesas sin manejar: si una vista falla, se avisa en pantalla. */
function refrescar() {
  mostrar().catch((e) => {
    main.innerHTML = '';
    mensaje(main, 'Ocurrió un error al mostrar la pantalla. Probá de nuevo.');
    console.error(e);
  });
}

/**
 * Cada vista se dibuja en un contenedor NUEVO. Si mientras una vista espera a la API
 * se pasa a otra pantalla (por ejemplo, la sesión vence y aparece el login), la vista
 * vieja termina escribiendo en un contenedor que ya no está en la página y no pisa nada.
 */
function contenedorNuevo() {
  const cont = document.createElement('div');
  main.replaceChildren(cont);
  return cont;
}

async function mostrar() {
  pintarMarco();
  if (!estado.usuario) {
    if (rutaActual() !== 'login') navegar('login');
    login.render(contenedorNuevo(), ctx);
    return;
  }
  let ruta = rutaActual();
  if (!RUTAS[ruta]) {
    ruta = RUTA_INICIAL;
    navegar(ruta);
    pintarMarco();
  }
  const cont = contenedorNuevo();
  cont.innerHTML = '<p class="cargando">Cargando…</p>';
  await RUTAS[ruta].render(cont, ctx);
  main.focus({ preventScroll: true });
}

document.getElementById('sel-alumno').addEventListener('change', (e) => {
  estado.alumnoId = Number(e.target.value);
  guardarPreferencia(estado.alumnoId);
  refrescar();
});
window.addEventListener('hashchange', refrescar);

// ── Red y service worker ─────────────────────────────────────────────────

function avisoRed() {
  document.getElementById('aviso-red').hidden = navigator.onLine;
}
window.addEventListener('online', avisoRed);
window.addEventListener('offline', avisoRed);

function registrarServiceWorker() {
  // Requiere HTTPS (o localhost): por HTTP en la red local no se registra.
  if (!('serviceWorker' in navigator) || !window.isSecureContext) return;
  // Cuando un deploy trae un shell nuevo, el SW nuevo toma el control: recargar una vez.
  // Solo si ya había uno: en la PRIMERA visita el SW también toma el control
  // (clients.claim) y recargar ahí borraba lo que el usuario estaba escribiendo.
  const habiaControlador = !!navigator.serviceWorker.controller;
  navigator.serviceWorker.register('/app/sw.php', { scope: '/app/' }).catch(() => {});
  let recargado = false;
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    if (!habiaControlador || recargado) return;
    recargado = true;
    location.reload();
  });
}

async function arrancar() {
  registrarServiceWorker();
  avisoRed();
  const r = await api('sesion.php');
  if (r.success) {
    aplicarSesion(r.data);
  } else if (r.offline) {
    main.innerHTML = '';
    mensaje(main, r.message);
    main.insertAdjacentHTML('beforeend', '<button class="btn" type="button" id="reintentar">Reintentar</button>');
    document.getElementById('reintentar').addEventListener('click', () => location.reload());
    return;
  }
  refrescar();
}

arrancar().catch(() => mensaje(main, 'No se pudo iniciar la app. Recargá la página.'));
