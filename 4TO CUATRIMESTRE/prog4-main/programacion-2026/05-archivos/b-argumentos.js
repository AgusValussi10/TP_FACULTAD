// ============================================================
//  15. PROGRAMAS QUE RECIBEN DATOS DESDE AFUERA
// ============================================================
//
//  Ejecutalo así, probando distintas cosas:
//
//      node b-argumentos.js
//      node b-argumentos.js Ana 25
//      node b-argumentos.js --nombre=Ana --edad=25
//      PUERTO=4000 node b-argumentos.js
// ============================================================

// ------------------------------------------------------------
//  15.1 · Argumentos de la línea de comandos
// ------------------------------------------------------------

console.log('--- process.argv completo ---');
console.log(process.argv);

// Siempre trae al menos dos elementos:
//   [0] la ruta del ejecutable de node
//   [1] la ruta de tu archivo
//   [2] en adelante: lo que escribiste vos

const argumentos = process.argv.slice(2);
console.log('\n--- solo tus argumentos ---');
console.log(argumentos);

if (argumentos.length === 0) {
  console.log('(no pasaste ninguno; probá: node b-argumentos.js Ana 25)');
} else {
  const [nombre, edad] = argumentos;
  console.log(`nombre: ${nombre}, edad: ${edad}`);
  console.log('ojo: la edad llegó como', typeof edad);
}

// ------------------------------------------------------------
//  15.2 · Argumentos con nombre
// ------------------------------------------------------------
//
//  Los posicionales se vuelven confusos rápido. Con nombre es
//  más claro:  --puerto=3000 --modo=produccion

const parsearBanderas = (lista) => {
  const resultado = {};
  for (const item of lista) {
    if (item.startsWith('--')) {
      const [clave, valor] = item.slice(2).split('=');
      resultado[clave] = valor ?? true; // sin = significa true
    }
  }
  return resultado;
};

console.log('\n--- banderas con nombre ---');
console.log(parsearBanderas(argumentos));

// El operador ?? se lee "si es null o undefined, usá esto otro".
// Es distinto de || : el ?? NO se activa con 0 ni con ''.
console.log('\n--- ?? contra || ---');
console.log('0 ?? 99 →', 0 ?? 99); // 0    (0 no es null)
console.log('0 || 99 →', 0 || 99); // 99   (0 cuenta como falso)

// ------------------------------------------------------------
//  15.3 · Variables de entorno
// ------------------------------------------------------------
//
//  Es la forma estándar de configurar un programa sin tocar el
//  código: distinto puerto en tu máquina y en el servidor, sin
//  cambiar una línea.

const puerto = Number(process.env.PUERTO) || 3000;
const modo = process.env.NODE_ENV || 'desarrollo';

console.log('\n--- configuración ---');
console.log('puerto:', puerto);
console.log('modo:  ', modo);

console.log('\nProbá:  PUERTO=4000 node b-argumentos.js');
console.log('(en Windows PowerShell: $env:PUERTO=4000; node b-argumentos.js)');

// REGLA IMPORTANTE para el resto de la materia:
//
//   Nada de contraseñas, claves ni direcciones de base de datos
//   escritas en el código. Van en variables de entorno.
//
//   Un código con una contraseña adentro es un código que no se
//   puede publicar, no se puede compartir y no se puede rotar.

// ------------------------------------------------------------
//  15.4 · Terminar el proceso a propósito
// ------------------------------------------------------------
//
//  process.exit(0) termina bien. process.exit(1) termina con
//  error. Los scripts y las herramientas de integración continua
//  miran ese número para saber si algo falló.

if (argumentos.includes('--fallar')) {
  console.error('\nAlgo salió mal a propósito');
  process.exit(1);
}

console.log('\nTerminó bien');

// ============================================================
//  PARA PROBAR VOS
// ============================================================
//
//  1. Escribí una calculadora:
//        node calculadora.js 5 + 3
//     Que soporte + - * / y avise si la operación no existe.
//
//  2. Hacé que falle con process.exit(1) si le faltan argumentos,
//     imprimiendo cómo se usa.
//
//  3. Escribí un programa que reciba --archivo=algo.json, lo lea
//     y muestre cuántos elementos tiene. Que avise con claridad
//     si el archivo no existe.
// ============================================================
