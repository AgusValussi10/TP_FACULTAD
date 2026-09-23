// Ejecutar:  node 04-configuracion/demo.js
'use strict';
const { cargarConfig, esquema } = require('./config');

console.log('--- 1. Entorno incompleto (lo que pasa en el primer deploy)');
const r = esquema.safeParse({ PORT: 'tres mil' });
for (const i of r.error.issues) console.log(`   ${i.path.join('.') || '(raiz)'}: ${i.message}`);

console.log('\n--- 2. Entorno valido');
const config = cargarConfig({
  NODE_ENV: 'production',
  PORT: '8080',
  UMBRAL_ALERTA: '30.5',
  DATABASE_URL: 'postgres://usuario:clave@db:5432/telemetria',
  API_TOKEN: 'a'.repeat(32),
});
console.log('  ', { ...config, apiToken: '***', urlBaseDatos: '***' });

console.log('\n--- 3. La config es inmutable');
try { config.puerto = 9999; }
catch (e) { console.log('   ', e.constructor.name + ':', e.message); }

console.log(`
Cuatro reglas que se llevan:

 1. UN SOLO archivo lee process.env. Si aparece un process.env.X en
    medio de un handler, la configuracion dejo de ser rastreable.
 2. La validacion es al ARRANQUE, no en el primer uso. Un servidor que
    arranca y explota a las tres horas porque faltaba una variable es
    peor que uno que no arranca.
 3. Se coacciona el tipo en el borde: process.env solo tiene cadenas.
    PORT es '3000', no 3000. Nunca compares  process.env.PORT === 3000.
 4. Nada de secretos en el repositorio, ni de archivos config.produccion.js.
    Eso es el factor III y es el que mas se viola.

Y el que mas duele en clase: si config.js hace process.exit(1), no se
puede testear. Por eso cargarConfig recibe el entorno como PARAMETRO y
el process.exit vive afuera del camino de test.
`);
