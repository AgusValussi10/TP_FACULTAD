// ============================================================
//  2.C  Encapsulamiento real: #campos privados
// ============================================================
'use strict';

// --- Nivel 0: la convencion del guion bajo. No encapsula nada.
class ConGuion {
  constructor() { this._token = 'secreto'; }
}
console.log('--- convencion _:', JSON.stringify(new ConGuion()));  // el secreto viaja

// --- Nivel 1: closure. Encapsula de verdad, pero cuesta memoria.
function crearContador() {
  let n = 0;                            // no hay forma de leerlo desde afuera
  return { incrementar: () => ++n, valor: () => n };
}
const c = crearContador();
c.incrementar();
console.log('--- closure:', c.valor(), Object.keys(c));   // el estado no aparece
// Costo: cada objeto lleva sus propias funciones. Mil objetos, mil closures.

// --- Nivel 2: WeakMap. Era la tecnica profesional antes de 2022.
const privado = new WeakMap();
class ConWeakMap {
  constructor(token) { privado.set(this, { token }); }
  enmascarado() { return privado.get(this).token.slice(0, 2) + '***'; }
}
console.log('--- weakmap:', new ConWeakMap('abcdef').enmascarado());

// --- Nivel 3: campos privados del lenguaje. Lo que se usa hoy.
class Umbral {
  #maximo;                       // declaracion obligatoria
  #historial = [];
  static #creados = 0;           // tambien hay estaticos privados

  constructor(maximo) {
    this.#maximo = maximo;
    Umbral.#creados++;
  }
  registrar(valor) {
    this.#historial.push(valor);
    return valor > this.#maximo;
  }
  #promedio() {                  // metodos privados tambien
    return this.#historial.reduce((a, b) => a + b, 0) / this.#historial.length;
  }
  get resumen() { return { promedio: this.#promedio(), n: this.#historial.length }; }
  static get creados() { return Umbral.#creados; }
}

const u = new Umbral(30);
u.registrar(28); u.registrar(35);
console.log('\n--- privados del lenguaje');
console.log('resumen:', u.resumen, '| creados:', Umbral.creados);
console.log('Object.keys:', Object.keys(u));                       // []  vacio
console.log('JSON:', JSON.stringify(u));                           // {}  no filtra
console.log('getOwnPropertyNames:', Object.getOwnPropertyNames(u)); // []  ni por reflexion

// El acceso desde afuera no es undefined: es error de SINTAXIS.
// Descomentar la linea siguiente impide que el archivo compile:
// console.log(u.#maximo);

// Y esto permite el truco de deteccion por marca:
class Umbral2 {
  #marca;
  static es(obj) {
    try { obj.#marca; return true; } catch { return false; }
  }
}
console.log('deteccion por marca:', Umbral2.es(new Umbral2()), Umbral2.es({}));

console.log('\n--- Lo importante: #privado NO es _privado en otra escala');
// #campos son privados a la CLASE, no a la instancia: un metodo puede leer
// los campos privados de OTRA instancia de la misma clase.
class Medicion {
  #v;
  constructor(v) { this.#v = v; }
  mayorQue(otra) { return this.#v > otra.#v; }   // legal
}
console.log(new Medicion(5).mayorQue(new Medicion(3)));

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Compara los tres niveles en memoria: 100_000 objetos con closure
//     contra 100_000 con #campos. Usa process.memoryUsage().heapUsed.
//  2. Por que WeakMap y no Map en el nivel 2? Que pasaria con la memoria
//     si fuera un Map comun?
// ------------------------------------------------------------
