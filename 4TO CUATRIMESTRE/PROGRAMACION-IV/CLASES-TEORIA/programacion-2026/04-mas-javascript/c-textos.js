// ============================================================
//  13. TEXTOS Y NÚMEROS: el kit de supervivencia
// ============================================================
//
//  Ejecutalo:  node c-textos.js
//
//  No hay teoría acá. Es la lista de métodos que vas a usar todos
//  los días. Conviene tenerla a mano y volver cuando haga falta.
// ============================================================

const texto = '  Programación IV - Comisión 1  ';

console.log('--- limpiar y medir ---');
console.log(`[${texto.trim()}]`); // saca espacios de las puntas
console.log('largo:', texto.trim().length);

console.log('\n--- cambiar mayúsculas ---');
console.log('mayúsculas:', texto.trim().toUpperCase());
console.log('minúsculas:', texto.trim().toLowerCase());

console.log('\n--- buscar ---');
console.log('¿incluye "Comisión"?', texto.includes('Comisión'));
console.log('¿empieza con espacio?', texto.startsWith(' '));
console.log('¿termina en "1"?', texto.trim().endsWith('1'));
console.log('posición de "IV":', texto.indexOf('IV')); // -1 si no está

console.log('\n--- cortar y partir ---');
console.log('primeros 15:', texto.trim().slice(0, 15));
console.log('últimos 3:  ', texto.trim().slice(-3));
console.log('partido por " - ":', texto.trim().split(' - '));

console.log('\n--- reemplazar ---');
console.log(texto.trim().replace('IV', '4'));
console.log('todas las "o":', 'formato'.replaceAll('o', '0'));

console.log('\n--- juntar ---');
const partes = ['alumnos', 'aprobados', '2026'];
console.log(partes.join('/'));

// ------------------------------------------------------------
//  NÚMEROS
// ------------------------------------------------------------

console.log('\n--- convertir texto a número ---');
console.log("Number('42')     →", Number('42'));
console.log("Number('42.5')   →", Number('42.5'));
console.log("Number('hola')   →", Number('hola')); // NaN
console.log("Number('')       →", Number('')); // 0  ← ojo con esto
console.log("parseInt('42px') →", parseInt('42px')); // 42

// Cómo verificar que la conversión salió bien:
const convertir = (texto) => {
  const numero = Number(texto);
  if (texto.trim() === '' || Number.isNaN(numero)) {
    return null;
  }
  return numero;
};

console.log('\n--- conversión segura ---');
for (const t of ['42', 'hola', '', '  ', '3.14']) {
  console.log(`[${t}] →`, convertir(t));
}

console.log('\n--- redondeos ---');
console.log('Math.round(3.6) →', Math.round(3.6));
console.log('Math.floor(3.9) →', Math.floor(3.9)); // hacia abajo
console.log('Math.ceil(3.1)  →', Math.ceil(3.1)); // hacia arriba
console.log('Math.trunc(3.9) →', Math.trunc(3.9)); // corta la parte decimal
console.log('(3.14159).toFixed(2) →', (3.14159).toFixed(2)); // ← devuelve TEXTO

console.log('\n--- máximos, mínimos, aleatorios ---');
const numeros = [4, 8, 15, 16, 23, 42];
console.log('máximo:', Math.max(...numeros)); // el ... expande el array
console.log('mínimo:', Math.min(...numeros));
console.log('aleatorio entre 0 y 1:', Math.random().toFixed(3));

const aleatorioEntre = (min, max) =>
  Math.floor(Math.random() * (max - min + 1)) + min;
console.log('aleatorio entre 1 y 6:', aleatorioEntre(1, 6));

// ------------------------------------------------------------
//  FECHAS (lo mínimo)
// ------------------------------------------------------------

const ahora = new Date();

console.log('\n--- fechas ---');
console.log('objeto Date:', ahora.toString().slice(0, 24));
console.log('formato ISO:', ahora.toISOString()); // ← el que se usa en APIs
console.log('milisegundos:', Date.now());
console.log('año:', ahora.getFullYear());

// En una API, las fechas se mandan SIEMPRE en formato ISO.
// Es texto, no ambiguo, y ordenable alfabéticamente.

// ============================================================
//  PARA PROBAR VOS
// ============================================================
//
//  1. Escribí `normalizarNombre(texto)` que saque espacios de las
//     puntas y ponga la primera letra en mayúscula y el resto en
//     minúscula. '  ANA gómez ' → 'Ana gómez'
//
//  2. Escribí `esEmailValido(texto)` sin usar expresiones
//     regulares: que tenga una sola arroba, algo antes, algo
//     después, y un punto después de la arroba.
//
//  3. Escribí `formatearPesos(numero)` que devuelva '$ 15.000,00'
//     (buscá el método toLocaleString y probá con 'es-AR').
//
//  4. Dado '/alumnos/12345/notas', partilo y quedate con el
//     número, convertido a number.
// ============================================================
