// ============================================================
//  1.B  La lectura sube por la cadena. La escritura NO.
// ============================================================
//  Este es el error conceptual mas caro del modelo de objetos.

const base = { contador: 0, etiquetas: [] };

const a = Object.create(base);
const b = Object.create(base);

console.log('--- Asignacion a una propiedad heredada');
a.contador = a.contador + 1;   // LEE de base (0), ESCRIBE en a (1)
console.log('a.contador =', a.contador);           // 1
console.log('b.contador =', b.contador);           // 0  <- no se toco
console.log('base.contador =', base.contador);     // 0
console.log('propia en a?', Object.hasOwn(a, 'contador'));  // true, se creo recien

console.log('\n--- Mutacion de un objeto heredado: acá SI se comparte');
a.etiquetas.push('critico');   // no asigna: MUTA el array que vive en base
console.log('b.etiquetas =', b.etiquetas);         // ['critico']  <- efecto cruzado
console.log('propia en a?', Object.hasOwn(a, 'etiquetas'));  // false

// La regla: `obj.x = v` casi siempre crea una propiedad PROPIA en obj.
// `obj.x.metodo()` no asigna nada: opera sobre el objeto que encontro la
// busqueda, este donde este.
//
// Esto explica por que el estado compartido en el prototipo es un bug
// clasico: los arrays y objetos declarados "en la clase" que se comparten
// entre todas las instancias.

console.log('\n--- El caso donde la escritura NI SIQUIERA crea la propiedad');
const soloLectura = {};
Object.defineProperty(soloLectura, 'version', {
  value: '1.0',
  writable: false,
  enumerable: true,
});
const hijo = Object.create(soloLectura);
hijo.version = '2.0';               // en modo no estricto: falla en silencio
console.log('hijo.version =', hijo.version);   // '1.0'

// En un modulo ESM o con 'use strict' al tope del archivo, la misma linea
// lanza TypeError. Es la misma operacion con dos comportamientos: por eso
// conviene escribir siempre en modo estricto.

console.log('\n--- Setters: la escritura tampoco es una escritura');
const conSetter = {
  set temperatura(v) {
    if (typeof v !== 'number') throw new TypeError('temperatura debe ser numero');
    this._t = v;
  },
  get temperatura() { return this._t; },
};
const medidor = Object.create(conSetter);
medidor.temperatura = 21.5;   // ejecuta el setter heredado, con this = medidor
console.log(medidor.temperatura, Object.hasOwn(medidor, '_t'));  // 21.5 true

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Escribi el algoritmo de [[Set]] en tus palabras, en tres lineas.
//     Pista: antes de escribir, el motor busca en la cadena si hay un
//     setter o una propiedad no escribible.
//  2. Corregi `base` para que cada objeto tenga su propio array de
//     etiquetas sin usar clases.
// ------------------------------------------------------------
