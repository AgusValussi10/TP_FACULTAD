// ============================================================
//  3.C  Donde SI se paga caro: el objeto que cambia de forma
// ============================================================
//  El bloque anterior mide lecturas. Este mide algo mas comun en un
//  servidor: construir objetos. Y aca la diferencia es de otro orden.

const N = 1_000_000;

function medir(etiqueta, fn) {
  for (let i = 0; i < 50_000; i++) fn(i);             // calentamiento
  const t0 = process.hrtime.bigint();
  let ultimo;
  for (let i = 0; i < N; i++) ultimo = fn(i);
  const ms = Number(process.hrtime.bigint() - t0) / 1e6;
  console.log(`${etiqueta.padEnd(38)} ${ms.toFixed(1).padStart(7)} ms`);
  return ms;
}

// A) Forma unica, declarada de una vez.
class Lectura {
  constructor(id, valor) {
    this.id = id;
    this.valor = valor;
    this.error = null;        // <- se inicializa aunque no se use
  }
}
const conClase = (i) => new Lectura(i, i * 2);

// B) Misma informacion, pero la forma se completa segun el caso.
const condicional = (i) => {
  const o = { id: i, valor: i * 2 };
  if (i % 2 === 0) o.error = null;         // dos formas distintas
  return o;
};

// C) El anti-patron: propiedades agregadas en cualquier orden.
const desordenado = (i) => {
  const o = {};
  if (i % 3 === 0) { o.valor = i * 2; o.id = i; }
  else if (i % 3 === 1) { o.id = i; o.valor = i * 2; }
  else { o.error = null; o.id = i; o.valor = i * 2; }
  return o;
};

// D) delete: degrada el objeto a modo diccionario.
const conDelete = (i) => {
  const o = { id: i, valor: i * 2, temporal: true };
  delete o.temporal;
  return o;
};

console.log(`${N.toLocaleString('es-AR')} objetos creados de cuatro maneras\n`);
const a = medir('A) clase, forma unica', conClase);
const b = medir('B) propiedad condicional (2 formas)', condicional);
const c = medir('C) orden variable (3 formas)', desordenado);
const d = medir('D) con delete', conDelete);

console.log('');
for (const [n, v] of [['B', b], ['C', c], ['D', d]]) {
  console.log(`${n} / A : ${(v / a).toFixed(2)}x`);
}

console.log(`
Regla que se llevan:
  no es "usar clases es mas rapido". Es que la clase te OBLIGA a fijar la
  forma en un solo lugar. El mismo efecto se consigue con un objeto literal
  completo. Lo que se paga es la variabilidad, no la sintaxis.
`);

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Arregla la version B para que tenga una sola forma sin perder
//     informacion. Cuanto recuperas?
//  2. Reemplaza `delete o.temporal` por `o.temporal = undefined` y medi.
//  3. Consigna de honestidad: para que N el ahorro total supera 1 ms?
//     Escribi el numero. Es el argumento que vas a usar la proxima vez
//     que alguien quiera "optimizar" sin medir.
// ------------------------------------------------------------
