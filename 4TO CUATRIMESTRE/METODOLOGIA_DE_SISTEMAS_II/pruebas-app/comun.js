// Utilidades comunes de las pruebas en navegador de la app (Playwright + Edge del sistema).
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = process.argv[2] || process.env.APP_URL || 'http://127.0.0.1:8096';
const CAPTURAS = path.join(__dirname, 'capturas');
fs.mkdirSync(CAPTURAS, { recursive: true });

/** Corre una lista de casos [nombre, async (page) => {}] en tamaño celular y devuelve el código de salida. */
async function correr(casos) {
  const browser = await chromium.launch({ channel: 'msedge', headless: true });
  const resultados = [];
  for (const [nombre, fn] of casos) {
    const ctx = await browser.newContext({
      viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true, locale: 'es-AR',
    });
    const page = await ctx.newPage();
    page.setDefaultTimeout(20000); // php -S en Windows atiende de a un pedido: puede tardar
    const errores = [];
    page.on('pageerror', (e) => errores.push('pageerror: ' + e.message));
    // Los 401/403 de la API son esperables (así se detecta la falta de sesión): el navegador
    // los anota como "Failed to load resource". Cualquier otro error de consola cuenta.
    page.on('console', (m) => {
      if (m.type() === 'error' && !m.text().startsWith('Failed to load resource')) errores.push('console: ' + m.text());
    });
    try {
      await fn(page);
      if (errores.length) throw new Error(errores.join(' | '));
      resultados.push(['OK', nombre]);
    } catch (e) {
      resultados.push(['FALLA', nombre, e.message.split('\n')[0]]);
      await page.screenshot({ path: path.join(CAPTURAS, 'FALLA-' + nombre.replace(/\W+/g, '_') + '.png'), fullPage: true }).catch(() => {});
    }
    await ctx.close();
  }
  await browser.close();
  resultados.forEach((r) => console.log(r.join(' · ')));
  return resultados.some((r) => r[0] !== 'OK') ? 1 : 0;
}

async function captura(page, nombre) {
  await page.screenshot({ path: path.join(CAPTURAS, nombre + '.png'), fullPage: true });
}

async function ingresar(page, usuario, password) {
  await page.goto(BASE + '/app/');
  await page.fill('#usuario', usuario);
  await page.fill('#password', password);
  await page.click('button:has-text("Ingresar")');
}

module.exports = { BASE, correr, captura, ingresar };
