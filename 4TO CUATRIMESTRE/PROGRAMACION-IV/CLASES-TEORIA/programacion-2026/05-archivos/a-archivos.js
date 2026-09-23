// ============================================================
//  14. ARCHIVOS: guardar cosas que sobrevivan al proceso
// ============================================================
//
//  Ejecutalo:  node a-archivos.js
//
//  Hasta ahora todo lo que guardábamos se perdía al terminar el
//  proceso. Acá aprendemos a persistir.
//
//  Y aparece algo importante: hay DOS versiones de cada función.
// ============================================================

const fs = require('node:fs');
const path = require('node:path');

// `path.join` arma rutas de forma correcta en cualquier sistema
// operativo. En Windows usa \ y en Linux /. No armes rutas
// pegando textos con + porque se rompe al cambiar de máquina.

const archivo = path.join(__dirname, 'datos.json');

// ------------------------------------------------------------
//  14.1 · Escribir
// ------------------------------------------------------------

const alumnos = [
  { legajo: 12345, nombre: 'Ana Gómez', nota: 8 },
  { legajo: 12346, nombre: 'Beto Ruiz', nota: 4 },
];

fs.writeFileSync(archivo, JSON.stringify(alumnos, null, 2), 'utf8');
console.log('Archivo escrito en:', archivo);

// Fijate que hay que convertir a texto con JSON.stringify.
// Un archivo guarda texto o bytes, no objetos de JavaScript.

// ------------------------------------------------------------
//  14.2 · Leer
// ------------------------------------------------------------

const contenido = fs.readFileSync(archivo, 'utf8');
console.log('\n--- contenido crudo (es texto) ---');
console.log(contenido);
console.log('tipo:', typeof contenido);

const recuperados = JSON.parse(contenido);
console.log('\n--- después de JSON.parse ---');
console.log('tipo:', typeof recuperados);
console.log('primer nombre:', recuperados[0].nombre);

// ------------------------------------------------------------
//  14.3 · Otras operaciones útiles
// ------------------------------------------------------------

console.log('\n--- operaciones ---');
console.log('¿existe el archivo?', fs.existsSync(archivo));

fs.appendFileSync(
  path.join(__dirname, 'registro.log'),
  `${new Date().toISOString()} - se ejecutó el ejemplo\n`,
  'utf8',
);
console.log('línea agregada a registro.log');

const info = fs.statSync(archivo);
console.log('tamaño en bytes:', info.size);

// ------------------------------------------------------------
//  14.4 · Leer un archivo que no existe
// ------------------------------------------------------------
//
//  Lanza un error. Hay que atraparlo.

const leerSeguro = (ruta) => {
  try {
    return JSON.parse(fs.readFileSync(ruta, 'utf8'));
  } catch (error) {
    if (error.code === 'ENOENT') {
      console.log(`(el archivo ${path.basename(ruta)} no existe, devuelvo vacío)`);
      return [];
    }
    throw error; // otro error: que lo maneje quien sepa
  }
};

console.log('\n--- lectura segura ---');
console.log(leerSeguro(archivo).length, 'registros');
console.log(leerSeguro(path.join(__dirname, 'no-existe.json')).length, 'registros');

// El `error.code === 'ENOENT'` es la forma estándar de preguntar
// "¿el problema fue que no existe?". Node usa los mismos códigos
// de error que el sistema operativo.

// ============================================================
//
//  14.5 · ALGO RARO: hay DOS versiones de cada función
//
// ============================================================
//
//  Fijate que todas las funciones que usamos terminan en `Sync`:
//
//      readFileSync    writeFileSync    existsSync
//
//  Existen también sin ese sufijo. Y funcionan MUY distinto.

console.log('\n--- las dos versiones ---');

// VERSIÓN 1: la que veníamos usando
const datos1 = fs.readFileSync(archivo, 'utf8');
console.log('1. leí el archivo y ya tengo el contenido acá');

// VERSIÓN 2: sin el Sync
fs.readFile(archivo, 'utf8', (error, datos2) => {
  console.log('3. ¡recién ahora tengo el contenido!');
});

console.log('2. esta línea se ejecutó ANTES de tener el contenido');

// ------------------------------------------------------------
//  MIRÁ EL ORDEN DE LOS NÚMEROS EN LA SALIDA
// ------------------------------------------------------------
//
//  Salen 1, 2, 3. No 1, 2, 3 en el orden en que están escritos:
//  el "3" está escrito ANTES que el "2" en el archivo.
//
//  Qué pasó:
//
//    · `readFileSync` FRENA el programa hasta que el archivo esté
//      leído. Cuando la línea termina, ya tenés el contenido.
//
//    · `readFile` (sin Sync) NO frena nada. Le entregás una
//      función y el programa SIGUE. Cuando el archivo esté listo,
//      Node llama a esa función.
//
//  ¿Te suena? Es exactamente lo mismo que hace `createServer`:
//  vos escribís una función y otro la llama cuando corresponda.
//
//  Y ahora la pregunta que cierra la clase:
//
//      ¿Por qué existirían dos versiones?
//      ¿Cuál conviene usar en un servidor, y por qué?
//
//  Esa pregunta se responde la clase que viene. Y está conectada
//  con el misterio del servidor que se bloquea.

// ============================================================
//  PARA PROBAR VOS
// ============================================================
//
//  1. Escribí `guardar(alumnos)` y `cargar()` que persistan el
//     array en un archivo JSON. Que `cargar()` devuelva un array
//     vacío si el archivo no existe.
//
//  2. Escribí una función `registrar(mensaje)` que agregue una
//     línea a un archivo de registro con la fecha ISO adelante.
//
//  3. Movés el archivo `datos.json` a otro lado y ejecutás de
//     nuevo. ¿Qué pasa? ¿Y si le ponés contenido inválido?
//
//  4. Ejecutá el archivo tres veces y mirá registro.log.
//     ¿Se sobrescribe o se acumula? ¿Por qué?
// ============================================================
