// Ingreso (RFGm01): solo familias (padre) y alumnos.
import { api } from '../api.js';
import { html, mensaje } from '../util.js';

export function render(main, ctx) {
  main.innerHTML = html`
    <section class="login" aria-labelledby="titulo-login">
      <div class="login-cabecera">
        <img src="/app/icons/icon-192.png" alt="" width="88" height="88">
        <h1 id="titulo-login">Educar para Transformar</h1>
        <p class="sub">Cuotas, pagos e inscripciones para familias y alumnos.</p>
      </div>
      <div id="login-msg"></div>
      <form id="form-login" class="tarjeta" novalidate>
        <div class="campo">
          <label for="usuario">Usuario</label>
          <input id="usuario" name="usuario" autocomplete="username" autocapitalize="none" spellcheck="false" required>
        </div>
        <div class="campo">
          <label for="password">Contraseña</label>
          <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn" type="submit" id="btn-ingresar">Ingresar</button>
      </form>
      <p class="sub">¿Sos docente o trabajás en la escuela?<br>Ingresá desde la <a href="/">web</a>.</p>
    </section>`;

  const msg = document.getElementById('login-msg');
  if (ctx.estado.avisoLogin) mensaje(msg, ctx.estado.avisoLogin, 'info');

  document.getElementById('form-login').addEventListener('submit', async (e) => {
    e.preventDefault();
    const usuario = document.getElementById('usuario').value.trim();
    const password = document.getElementById('password').value;
    if (!usuario || !password) {
      mensaje(msg, 'Completá el usuario y la contraseña.');
      return;
    }
    const btn = document.getElementById('btn-ingresar');
    btn.disabled = true;
    btn.textContent = 'Ingresando…';
    const r = await api('login.php', { usuario, password });
    btn.disabled = false;
    btn.textContent = 'Ingresar';
    if (!r.success) {
      mensaje(msg, r.message);
      document.getElementById('password').value = '';
      return;
    }
    ctx.alIniciarSesion(r.data);
  });
}
