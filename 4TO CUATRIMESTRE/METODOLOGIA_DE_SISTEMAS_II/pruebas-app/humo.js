// Prueba de humo de la PWA con Playwright + Edge del sistema (no forma parte del repo).
// Uso: node humo.js http://127.0.0.1:8096
const { chromium } = require('playwright');
const path = require('path');

const BASE = process.argv[2] || 'http://127.0.0.1:8096';
const SHOTS = path.join(__dirname, 'shots');
const resultados = [];

async function caso(nombre, fn, browser) {
  const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true, locale: 'es-AR' });
  const page = await ctx.newPage();
  page.setDefaultTimeout(20000); // php -S en Windows atiende de a un pedido
  const errores = [];
  page.on('pageerror', (e) => errores.push('pageerror: ' + e.message));
  // Los 401/403 de la API son esperables (así se detecta la falta de sesión): el navegador los anota como "Failed to load resource".
  page.on('console', (m) => { if (m.type() === 'error' && !m.text().startsWith('Failed to load resource')) errores.push('console: ' + m.text()); });
  try {
    await fn(page);
    if (errores.length) throw new Error(errores.join(' | '));
    resultados.push(['OK', nombre]);
  } catch (e) {
    resultados.push(['FALLA', nombre, e.message.split('\n')[0]]);
    await page.screenshot({ path: path.join(SHOTS, 'FALLA-' + nombre.replace(/\W+/g, '_') + '.png'), fullPage: true }).catch(() => {});
  }
  await ctx.close();
}

async function ingresar(page, usuario, password) {
  await page.goto(BASE + '/app/');
  await page.fill('#usuario', usuario);
  await page.fill('#password', password);
  await page.click('button:has-text("Ingresar")');
}

(async () => {
  const browser = await chromium.launch({ channel: 'msedge', headless: true });

  await caso('docente rechazado', async (page) => {
    await ingresar(page, 'maria.rodriguez', 'docente123');
    // El subtítulo del login ya dice "familias y alumnos": se busca el mensaje de rechazo, dentro de la alerta.
    await page.locator('.alerta-error', { hasText: 'ingresan desde la web' }).waitFor();
    await page.screenshot({ path: path.join(SHOTS, '01-docente-rechazado.png') });
  }, browser);

  await caso('primera visita: lo tipeado no se borra al instalarse el SW', async (page) => {
    await page.goto(BASE + '/app/');
    await page.fill('#usuario', 'laura.martinez');
    await page.waitForTimeout(4000); // margen para que el SW se instale y tome el control
    const valor = await page.inputValue('#usuario');
    if (valor !== 'laura.martinez') throw new Error('El campo se vació: "' + valor + '"');
    const sw = await page.evaluate(() => navigator.serviceWorker.getRegistration('/app/').then((r) => !!r));
    if (!sw) throw new Error('El service worker no se registró');
  }, browser);

  await caso('padre con dos hijos: inicio, selector, cuenta y salir', async (page) => {
    await ingresar(page, 'laura.martinez', 'padre123');
    await page.getByText('Saldo a pagar').waitFor();
    await page.getByText('Natación').first().waitFor();
    if (!(await page.isVisible('#selector-hijo'))) throw new Error('No se ve el selector de hijo');
    await page.screenshot({ path: path.join(SHOTS, '02-inicio-padre.png'), fullPage: true });
    const opciones = await page.$$eval('#sel-alumno option', (o) => o.map((x) => x.value));
    if (opciones.length !== 2) throw new Error('Esperaba 2 hijos, hay ' + opciones.length);
    await page.selectOption('#sel-alumno', opciones[1]);
    await page.getByText('Fútbol').first().waitFor();
    await page.screenshot({ path: path.join(SHOTS, '03-inicio-otro-hijo.png'), fullPage: true });
    await page.click('nav a:has-text("Cuenta")');
    await page.getByText('Cambiar contraseña').waitFor();
    await page.screenshot({ path: path.join(SHOTS, '04-cuenta.png'), fullPage: true });
    await page.fill('#actual', 'padre123');
    await page.fill('#nueva', 'corta');
    await page.fill('#confirmacion', 'corta');
    await page.click('button:has-text("Guardar contraseña")');
    await page.getByText('al menos 8').waitFor();
    await page.click('button:has-text("Cerrar sesión")');
    await page.waitForSelector('#usuario');
  }, browser);

  await caso('alumno sin selector', async (page) => {
    await ingresar(page, 'ana.garcia', 'alumno123');
    await page.getByText('Saldo a pagar').waitFor();
    if (await page.isVisible('#selector-hijo')) throw new Error('El alumno no debería ver el selector');
    await page.screenshot({ path: path.join(SHOTS, '05-inicio-alumno.png'), fullPage: true });
  }, browser);

  await caso('sesión vencida vuelve al login con aviso', async (page) => {
    await ingresar(page, 'ana.garcia', 'alumno123');
    await page.getByText('Saldo a pagar').waitFor();
    await page.context().clearCookies();
    await page.click('nav a:has-text("Cuenta")');
    await page.click('nav a:has-text("Inicio")');
    await page.getByText('Tu sesión venció').waitFor();
  }, browser);

  await browser.close();
  for (const r of resultados) console.log(r.join(' · '));
  process.exit(resultados.some((r) => r[0] !== 'OK') ? 1 : 0);
})();
