// ============================================================
//  5.B  Cinco defensas, de la peor a la mejor
// ============================================================
'use strict';

const PAYLOADS = [
  '{"__proto__": {"esAdmin": true}}',
  '{"constructor": {"prototype": {"esAdmin": true}}}',
  '{"a": {"__proto__": {"esAdmin": true}}}',      // anidado
];

function probar(nombre, fusionar) {
  const resultados = PAYLOADS.map((p) => {
    try { fusionar({}, JSON.parse(p)); } catch { /* rechazar tambien vale */ }
    const contaminado = ({}).esAdmin === true;
    delete Object.prototype.esAdmin;
    return contaminado ? 'CAE' : 'ok ';
  });
  console.log(`${nombre.padEnd(34)} ${resultados.join('  ')}`);
}

console.log('defensa'.padEnd(34), '__proto__  ctor  anidado');
console.log('-'.repeat(60));

// --- 0. Vulnerable (referencia)
const v0 = (d, o) => {
  for (const k in o) {
    if (o[k] && typeof o[k] === 'object') { d[k] ??= {}; v0(d[k], o[k]); }
    else d[k] = o[k];
  }
  return d;
};
probar('0) sin defensa', v0);

// --- 1. Lista negra de claves. Frecuente y frágil.
const PROHIBIDAS = new Set(['__proto__', 'constructor', 'prototype']);
const v1 = (d, o) => {
  for (const k in o) {
    if (PROHIBIDAS.has(k)) continue;
    if (o[k] && typeof o[k] === 'object') { d[k] ??= {}; v1(d[k], o[k]); }
    else d[k] = o[k];
  }
  return d;
};
probar('1) lista negra de claves', v1);
// Funciona, pero depende de acordarse de TODAS las claves peligrosas, en
// TODAS las funciones que fusionen algo. Una lista negra siempre es una
// apuesta a que no falta nada.

// --- 2. Solo propiedades propias + Object.keys
const v2 = (d, o) => {
  for (const k of Object.keys(o)) {
    if (k === '__proto__') continue;
    if (o[k] && typeof o[k] === 'object') { d[k] ??= {}; v2(d[k], o[k]); }
    else d[k] = o[k];
  }
  return d;
};
probar('2) Object.keys (+ __proto__)', v2);

// --- 3. Escribir con defineProperty: nunca dispara setters ni herencia
const v3 = (d, o) => {
  for (const k of Object.keys(o)) {
    if (k === '__proto__') continue;
    const val = o[k];
    if (val && typeof val === 'object') {
      const sub = Object.hasOwn(d, k) && typeof d[k] === 'object' ? d[k] : {};
      Object.defineProperty(d, k, { value: v3(sub, val), writable: true, enumerable: true, configurable: true });
    } else {
      Object.defineProperty(d, k, { value: val, writable: true, enumerable: true, configurable: true });
    }
  }
  return d;
};
probar('3) defineProperty', v3);

// --- 4. Sacar el prototipo del problema: objetos sin cadena
//     Object.create(null) crea un objeto SIN [[Prototype]]. No hay
//     Object.prototype al que llegar, asi que no hay nada que contaminar.
const v4 = (d, o) => {
  for (const k of Object.keys(o)) {
    const val = o[k];
    if (val && typeof val === 'object') {
      const sub = Object.hasOwn(d, k) && typeof d[k] === 'object' && d[k] !== null
        ? d[k] : Object.create(null);
      d[k] = v4(sub, val);
    } else d[k] = val;
  }
  return d;
};
probar('4) destino sin prototipo', (d, o) => v4(Object.create(null), o));

console.log(`
La que se lleva el premio no es una funcion: es la 5.

  5) VALIDAR EL ESQUEMA EN EL BORDE.
     El servidor no fusiona lo que llega. Lo valida contra un esquema que
     declara que campos existen y de que tipo son, y CONSTRUYE un objeto
     nuevo solo con esos campos. Todo lo demas se descarta.
     Ninguna clave inesperada llega nunca al codigo de negocio.
`);

// --- 5. Validacion por lista blanca, sin dependencias
function validar(esquema, entrada) {
  const salida = Object.create(null);
  for (const [campo, tipo] of Object.entries(esquema)) {
    const v = entrada?.[campo];
    if (v === undefined) continue;
    if (typeof v !== tipo) throw new TypeError(`${campo} debe ser ${tipo}`);
    salida[campo] = v;
  }
  return salida;
}
const limpio = validar({ max: 'number', min: 'number' },
                       JSON.parse('{"max": 40, "__proto__": {"esAdmin": true}}'));
console.log('5) validado:', JSON.stringify(limpio), '| contaminado?', ({}).esAdmin === true);

console.log(`
Y dos medidas de proceso, que no reemplazan a lo anterior:

  - node --disable-proto=throw app.js
      hace que tocar el getter/setter de __proto__ lance TypeError.
      Probalo:  node --disable-proto=throw 05-seguridad/a-contaminacion.js
  - Object.freeze(Object.prototype) al arranque.
      Barato y efectivo, pero rompe librerias que parchean prototipos.
      Se prueba en staging antes, no se activa a ciegas.
`);

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. La defensa 4 protege Object.prototype, pero deja pasar la clave
//     '__proto__' como dato normal. Es un problema? En que caso si?
//  2. Agrega al banco de pruebas un payload con  {"toString": "x"}.
//     Cuales defensas lo dejan pasar? Que rompe?
//  3. Escribi la version de `validar` que acepte campos anidados.
// ------------------------------------------------------------
