// ============================================================
//  2.D  `this` se decide en la llamada, no en la definicion
// ============================================================
//  En Java, `this` dentro de un metodo es siempre el objeto.
//  En JavaScript, `this` es el objeto que esta a la izquierda del punto
//  EN EL MOMENTO DE LLAMAR. Si no hay punto, no hay objeto.
'use strict';

class Alerta {
  constructor(nivel) { this.nivel = nivel; }
  disparar() {
    return `alerta ${this.nivel}`;
  }
}

const a = new Alerta('critico');
console.log('1) llamada normal:', a.disparar());

console.log('\n2) el metodo se pasa como valor y pierde el receptor');
const suelto = a.disparar;
try { suelto(); } catch (e) { console.log('   ', e.constructor.name + ':', e.message); }
// En modo estricto `this` es undefined -> TypeError.
// En modo no estricto `this` seria globalThis y devolveria 'alerta undefined',
// que es peor: falla en silencio.

console.log('\n3) el caso real: pasarlo a un callback');
// setTimeout(a.disparar, 0)  <-- mismo bug
// [1,2].forEach(a.disparar)  <-- mismo bug
const arr = ['x'];
try { arr.forEach(a.disparar); } catch (e) { console.log('    forEach ->', e.constructor.name); }

console.log('\n4) las tres soluciones');
console.log('   bind:   ', a.disparar.bind(a)());
console.log('   flecha: ', (() => a.disparar())());
class AlertaFlecha {
  nivel = 'critico';
  disparar = () => `alerta ${this.nivel}`;   // campo: captura this en la construccion
}
const b = new AlertaFlecha();
const sueltoOk = b.disparar;
console.log('   campo flecha:', sueltoOk());

console.log('\n5) call y apply: la misma funcion, otro receptor');
console.log(a.disparar.call({ nivel: 'inventado' }));
// Y por eso existe el patron defensivo de la libreria estandar:
const hasOwn = Object.prototype.hasOwnProperty;
console.log('   hasOwn prestado:', hasOwn.call({ x: 1 }, 'x'));
// (hoy se escribe Object.hasOwn(obj,'x'), pero vas a ver la version con .call
//  en todo el codigo de npm, y ahora sabes por que)

console.log('\n6) las arrow NO tienen this propio');
class Reloj {
  constructor() { this.tics = 0; }
  arrancarMal() { setTimeout(function () { this.tics++; }, 0); }   // this = Timeout
  arrancarBien() { setTimeout(() => { this.tics++; }, 0); }        // this = el Reloj
}
const r = new Reloj();
r.arrancarBien();
setTimeout(() => console.log('   tics =', r.tics), 10);

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Escribi tu propio `bind` en tres lineas usando una arrow y rest args.
//  2. Que devuelve `this` en el tope de un archivo .js de Node (CommonJS)?
//     Y en un .mjs? Imprimilo en los dos. La respuesta te sorprende.
// ------------------------------------------------------------
