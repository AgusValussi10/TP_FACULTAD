// Paso 2 (RFGm01/RFGm02): ingreso, selector de hijo, cuenta, sesión vencida.
const { BASE, correr, captura, ingresar } = require('./comun');

correr([
  ['docente rechazado', async (page) => {
    await ingresar(page, 'maria.rodriguez', 'docente123');
    // El subtítulo del login ya dice "familias y alumnos": se busca el mensaje de rechazo, dentro de la alerta.
    await page.locator('.alerta-error', { hasText: 'ingresan desde la web' }).waitFor();
    await captura(page, '01-docente-rechazado');
  }],

  ['primera visita: lo tipeado no se borra al instalarse el SW', async (page) => {
    await page.goto(BASE + '/app/');
    await page.fill('#usuario', 'laura.martinez');
    await page.waitForTimeout(4000); // margen para que el SW se instale y tome el control
    const valor = await page.inputValue('#usuario');
    if (valor !== 'laura.martinez') throw new Error('El campo se vació: "' + valor + '"');
    const sw = await page.evaluate(() => navigator.serviceWorker.getRegistration('/app/').then((r) => !!r));
    if (!sw) throw new Error('El service worker no se registró');
  }],

  ['padre con dos hijos: inicio, selector, cuenta y salir', async (page) => {
    await ingresar(page, 'laura.martinez', 'padre123');
    await page.getByText('Saldo a pagar').waitFor();
    await page.getByText('Natación').first().waitFor();
    if (!(await page.isVisible('#selector-hijo'))) throw new Error('No se ve el selector de hijo');
    await captura(page, '02-inicio-padre');
    const opciones = await page.$$eval('#sel-alumno option', (o) => o.map((x) => x.value));
    if (opciones.length !== 2) throw new Error('Esperaba 2 hijos, hay ' + opciones.length);
    await page.selectOption('#sel-alumno', opciones[1]);
    await page.getByText('Fútbol').first().waitFor();
    await captura(page, '03-inicio-otro-hijo');
    await page.click('nav a:has-text("Cuenta")');
    await page.getByText('Cambiar contraseña').waitFor();
    await captura(page, '04-cuenta');
    await page.fill('#actual', 'padre123');
    await page.fill('#nueva', 'corta');
    await page.fill('#confirmacion', 'corta');
    await page.click('button:has-text("Guardar contraseña")');
    await page.getByText('al menos 8').waitFor();
    await page.click('button:has-text("Cerrar sesión")');
    await page.waitForSelector('#usuario');
  }],

  ['alumno sin selector', async (page) => {
    await ingresar(page, 'ana.garcia', 'alumno123');
    await page.getByText('Saldo a pagar').waitFor();
    if (await page.isVisible('#selector-hijo')) throw new Error('El alumno no debería ver el selector');
    await captura(page, '05-inicio-alumno');
  }],

  ['sesión vencida vuelve al login con aviso', async (page) => {
    await ingresar(page, 'ana.garcia', 'alumno123');
    await page.getByText('Saldo a pagar').waitFor();
    await page.context().clearCookies();
    await page.click('nav a:has-text("Cuenta")');
    await page.click('nav a:has-text("Inicio")');
    await page.getByText('Tu sesión venció').waitFor();
  }],
]).then((codigo) => process.exit(codigo));
