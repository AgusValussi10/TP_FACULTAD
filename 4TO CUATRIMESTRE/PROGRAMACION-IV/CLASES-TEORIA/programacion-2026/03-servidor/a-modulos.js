// ============================================================
//  7. USAR CÓDIGO QUE NO ESCRIBISTE VOS
// ============================================================
//
//  Ejecutalo:  node a-modulos.js
//
//  Node trae funcionalidad ya hecha, organizada en módulos.
//  Para usar un módulo hay que pedirlo con `require`.
// ============================================================

const os = require('node:os');
const http = require('node:http')
const path = require('node:path');

// El prefijo `node:` deja claro que es un módulo del núcleo de
// Node y no una biblioteca que instalaste. Es la forma moderna
// y la vamos a usar siempre.

console.log('Sistema operativo:', os.platform());
console.log('Núcleos del procesador:', os.cpus().length);
console.log('Memoria total (GB):', (os.totalmem() / 1024 ** 3).toFixed(1));

console.log('\nCarpeta de este archivo:', __dirname);
console.log('Ruta completa del archivo:', __filename);
console.log('Solo el nombre:', path.basename(__filename));

// ------------------------------------------------------------
//  QUÉ ES `require`, POR AHORA
// ------------------------------------------------------------
//
//  `require('node:os')` significa:
//     "traeme el módulo os y guardámelo en esta variable"
//
//  Lo que devuelve es un objeto común, con funciones adentro.
//  Por eso `os.platform()` se escribe igual que cualquier
//  llamada a un método de un objeto.

console.log('\n¿qué tipo es `os`?', typeof os);

// Cómo funciona `require` por dentro, cómo resuelve las rutas,
// qué pasa si dos archivos se piden mutuamente, y en qué se
// diferencia de la sintaxis `import`: todo eso es tema de la
// clase de módulos. Por hoy alcanza con saber pedirlos.

// ------------------------------------------------------------
//  MÓDULOS DEL NÚCLEO QUE VAS A USAR EN LA MATERIA
// ------------------------------------------------------------
//
//    node:http    servidores y clientes HTTP     ← hoy
//    node:fs      archivos
//    node:path    manejo de rutas de archivos
//    node:os      información del sistema
//    node:crypto  hash, cifrado, números aleatorios
//    node:url     parseo de URLs
//
//  No hay que instalar ninguno. Vienen con Node.

// ------------------------------------------------------------
//  TU PROPIO MÓDULO
// ------------------------------------------------------------
//
//  Cualquier archivo tuyo también es un módulo. Al lado de este
//  hay un archivo `saludos.js`. Miralo y volvé.

const saludos = require('./saludos.js');

console.log('\n' + saludos.formal('Ana'));
console.log(saludos.informal('Ana'));

// La regla mínima: lo que ponés en `module.exports` es lo que
// el otro archivo recibe. Lo demás queda privado.

// ============================================================
//  PARA PROBAR VOS
// ============================================================
//
//  1. Agregá una función `gritado` en saludos.js que devuelva
//     el saludo en mayúsculas (buscá el método .toUpperCase()).
//     Exportala y usala desde acá.
//
//  2. Usá `node:crypto` para imprimir un identificador aleatorio.
//     La función se llama randomUUID().
//
//  3. Averiguá qué devuelve `os.uptime()` y mostralo en horas.
// ============================================================


const funcionalidadX = (variable) => {
  return `Buen dia, ${variable}`;
}

// module.exports = {
//   funcionalidadX,
// };

export {funcionalidadX}