// ============================================================
//  3.B  Caches en linea (inline caches): el costo de la forma
// ============================================================
//  Ejecutar con:  node 03-v8/b-cache-en-linea.js
//
//  Cada lugar del codigo donde se lee una propiedad ("sitio de acceso")
//  guarda un cache: "la ultima vez que pase por aca, el objeto tenia la
//  forma M y `valor` estaba en la ranura 3". Si el siguiente objeto tiene
//  la misma forma, el acceso es un desplazamiento fijo en memoria: cuesta
//  lo mismo que un campo en Java.
//
//   - 1 forma vista   -> MONOMORFICO  : salto directo
//   - 2 a 4 formas    -> POLIMORFICO  : compara contra una lista corta
//   - 5 o mas         -> MEGAMORFICO  : el cache se abandona y se cae a
//                                       una tabla hash global
//
//  Los tres casos de abajo ejecutan EL MISMO codigo, sobre objetos del
//  MISMO tamaño, leyendo la MISMA propiedad. Lo unico que cambia es en
//  que ranura quedo `valor` en cada objeto.

const N = 3_000_000;

function leer(o) { return o.valor; }        // <- unico sitio de acceso

const CLAVES = ['a','b','c','d','e','f','g','h','i','j','k','l'];

// Fabrica objetos con las mismas 13 propiedades, pero con `valor`
// insertada en la posicion `pos`: misma cantidad de datos, otra forma.
function fabrica(pos) {
  return () => {
    const o = {};
    for (let i = 0; i < CLAVES.length; i++) {
      if (i === pos) o.valor = 1;
      o[CLAVES[i]] = i;
    }
    return o;
  };
}

function armar(cantidadDeFormas) {
  const fabricas = [];
  for (let i = 0; i < cantidadDeFormas; i++) fabricas.push(fabrica(i));
  const datos = new Array(N);
  for (let i = 0; i < N; i++) datos[i] = fabricas[i % cantidadDeFormas]();
  return datos;
}

function medir(etiqueta, datos) {
  let s = 0;
  for (let i = 0; i < 100_000; i++) s += leer(datos[i]);   // calentamiento
  const t0 = process.hrtime.bigint();
  s = 0;
  for (let i = 0; i < N; i++) s += leer(datos[i]);
  const ms = Number(process.hrtime.bigint() - t0) / 1e6;
  console.log(`${etiqueta.padEnd(26)} ${ms.toFixed(1).padStart(7)} ms`);
  return ms;
}

console.log(`${N.toLocaleString('es-AR')} lecturas de la misma propiedad, mismo sitio de acceso\n`);
const mono = medir('1 forma   (monomorfico)',  armar(1));
const poli = medir('4 formas  (polimorfico)',  armar(4));
const mega = medir('12 formas (megamorfico)',  armar(12));

console.log(`\npolimorfico / monomorfico : ${(poli / mono).toFixed(2)}x`);
console.log(`megamorfico / monomorfico : ${(mega / mono).toFixed(2)}x`);

console.log(`
Lo que hay que leer de estos numeros:
  - hasta 4 formas el costo extra es chico y a veces ni se mide;
  - el escalon esta al pasar a megamorfico, y es del orden de 2x;
  - el codigo fuente es identico en los tres casos. La diferencia la
    puso la forma de los datos, no el algoritmo.
`);

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Corre el archivo tres veces. Cuanto varia? Es honesto reportar
//     una sola corrida?
//  2. Cambia 12 por 5, y despues por 4. Donde esta exactamente el escalon?
//  3. Antes de optimizar nada en un servidor real: cuantos accesos a
//     propiedad tiene que haber para que 2x en esta operacion se note
//     al lado de una consulta a base de datos de 5 ms?
// ------------------------------------------------------------
