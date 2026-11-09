// Paso 3 (RFGm03/RFGm04): cuotas, facturas con detalle y PDF, deuda por concepto.
const { BASE, correr, captura, ingresar } = require('./comun');

async function entrarComoLaura(page) {
  await ingresar(page, 'laura.martinez', 'padre123');
  await page.getByText('Saldo a pagar').waitFor();
}

correr([
  ['padre: cuotas con vencidas en rojo, por vencer en amarillo y pagadas con recibo', async (page) => {
    await entrarComoLaura(page);
    await page.click('nav a:has-text("Cuotas")');
    await page.getByRole('heading', { name: 'Cuotas' }).waitFor();
    if (!(await page.locator('.cuota-impaga .badge-rojo').count())) throw new Error('No hay cuota vencida en rojo');
    if (!(await page.locator('.cuota-proxima_a_vencer .badge-amarillo').count())) throw new Error('No hay cuota por vencer en amarillo');
    await captura(page, '06-cuotas-pendientes');
    await page.click('summary:has-text("Pagadas")');
    const recibo = page.locator('a:has-text("Recibo")').first();
    await recibo.waitFor();
    const href = await recibo.getAttribute('href');
    const pdf = await page.request.get(BASE + href);
    if (pdf.headers()['content-type'] !== 'application/pdf') throw new Error('El recibo no es un PDF');
    await captura(page, '07-cuotas-pagadas');
  }],

  ['padre: facturas con detalle, pagos y PDF', async (page) => {
    await entrarComoLaura(page);
    await page.click('nav a:has-text("Facturas")');
    await page.locator('.factura').first().waitFor();
    if (!(await page.locator('.factura .badge-rojo:has-text("Vencida")').count())) throw new Error('No se marca la factura vencida');
    await captura(page, '08-facturas');
    await page.locator('.factura summary').first().click();
    await page.getByRole('heading', { name: 'Pagos' }).first().waitFor();
    await captura(page, '09-factura-detalle');
    const href = await page.locator('a:has-text("Descargar PDF")').first().getAttribute('href');
    const pdf = await page.request.get(BASE + href);
    if (pdf.headers()['content-type'] !== 'application/pdf') throw new Error('La factura no es un PDF');
  }],

  ['padre: deuda por concepto y filtro inválido', async (page) => {
    await entrarComoLaura(page);
    await page.click('nav a:has-text("Facturas")');
    await page.locator('.factura').first().waitFor();
    await page.click('[role="tab"]:has-text("Deuda por concepto")');
    await page.getByRole('heading', { name: 'Deporte · Natación' }).waitFor();
    await page.locator('summary:has-text("Por mes")').first().click();
    await captura(page, '10-deuda-por-concepto');
    await page.fill('#desde', '2026-12');
    await page.fill('#hasta', '2026-01');
    await page.click('button:has-text("Ver")');
    await page.getByText('no puede ser posterior').waitFor();
  }],

  ['alumno: cuotas sin botón de recibo', async (page) => {
    await ingresar(page, 'ana.garcia', 'alumno123');
    await page.getByText('Saldo a pagar').waitFor();
    await page.click('nav a:has-text("Cuotas")');
    await page.click('summary:has-text("Pagadas")');
    await page.waitForTimeout(500);
    if (await page.locator('a:has-text("Recibo")').count()) throw new Error('El alumno no debería ver el recibo');
  }],
]).then((codigo) => process.exit(codigo));
