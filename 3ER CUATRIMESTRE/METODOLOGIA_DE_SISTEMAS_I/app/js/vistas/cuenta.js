// Cuenta: instalar la app, cambiar la contraseña (RFGm02) y cerrar sesión (RFGm01).
import { api } from '../api.js';
import { html, crudo, mensaje } from '../util.js';
import { estadoInstalacion, instalar, alCambiarInstalacion } from '../instalar.js';

let desuscribir = null;

function bloqueInstalar() {
  switch (estadoInstalacion()) {
    case 'instalada':
      return html`<p>✅ Ya estás usando la app instalada.</p>`;
    case 'disponible':
      return html`<p>Instalala para abrirla desde la pantalla de inicio, como cualquier app.</p>
        <button class="btn" type="button" id="btn-instalar">📲 Instalar app</button>`;
    case 'ios':
      return html`<p>En iPhone o iPad:</p>
        <ol class="pasos-ios">
          <li>Abrí esta página en <strong>Safari</strong>.</li>
          <li>Tocá <strong>Compartir</strong> (el cuadrado con la flecha hacia arriba).</li>
          <li>Elegí <strong>Agregar a inicio</strong> y confirmá con <strong>Agregar</strong>.</li>
        </ol>`;
    default:
      return html`<p class="sub">Para instalarla, abrí esta página en Chrome (Android) o Safari (iPhone) y buscá
        "Instalar app" o "Agregar a inicio" en el menú del navegador.</p>`;
  }
}

export function render(main, ctx) {
  const { usuario } = ctx.estado;
  main.innerHTML = html`
    <h1>Mi cuenta</h1>
    <p class="sub">${usuario.nombre} · ${usuario.rol === 'padre' ? 'Familia' : 'Alumno/a'}</p>

    <section class="tarjeta" aria-labelledby="t-inst">
      <h2 id="t-inst"><span aria-hidden="true">📱</span> Instalar la app</h2>
      <div id="bloque-instalar">${crudo(bloqueInstalar())}</div>
    </section>

    <section class="tarjeta" aria-labelledby="t-pass">
      <h2 id="t-pass"><span aria-hidden="true">🔒</span> Cambiar contraseña</h2>
      <div id="pass-msg"></div>
      <form id="form-pass" novalidate>
        <div class="campo">
          <label for="actual">Contraseña actual</label>
          <input id="actual" type="password" autocomplete="current-password" required>
        </div>
        <div class="campo">
          <label for="nueva">Contraseña nueva <span class="sub">(mínimo 8 caracteres)</span></label>
          <input id="nueva" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        </div>
        <div class="campo">
          <label for="confirmacion">Repetí la contraseña nueva</label>
          <input id="confirmacion" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        </div>
        <button class="btn" type="submit">Guardar contraseña</button>
      </form>
    </section>

    <button class="btn btn-sec" type="button" id="btn-salir">Cerrar sesión</button>`;

  const pintarInstalar = () => {
    const bloque = document.getElementById('bloque-instalar');
    if (!bloque) return;
    bloque.innerHTML = bloqueInstalar();
    document.getElementById('btn-instalar')?.addEventListener('click', () => instalar());
  };
  if (desuscribir) desuscribir();
  desuscribir = alCambiarInstalacion(pintarInstalar);
  pintarInstalar();

  const msg = document.getElementById('pass-msg');
  document.getElementById('form-pass').addEventListener('submit', async (e) => {
    e.preventDefault();
    const datos = {
      actual: document.getElementById('actual').value,
      nueva: document.getElementById('nueva').value,
      confirmacion: document.getElementById('confirmacion').value,
    };
    if (datos.nueva.length < 8) {
      mensaje(msg, 'La contraseña nueva tiene que tener al menos 8 caracteres.');
      return;
    }
    if (datos.nueva !== datos.confirmacion) {
      mensaje(msg, 'La confirmación no coincide con la contraseña nueva.');
      return;
    }
    const r = await api('password.php', datos);
    mensaje(msg, r.message, r.success ? 'ok' : 'error');
    if (r.success) e.target.reset();
  });

  document.getElementById('btn-salir').addEventListener('click', () => ctx.cerrarSesion());
}
