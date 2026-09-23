// ============================================================
//  2.A  `class` es azucar sintactico... con dientes
// ============================================================
'use strict';

class Sensor {
  constructor(id, tipo) {
    this.id = id;
    this.tipo = tipo;
  }
  describir() {
    return `sensor ${this.id} (${this.tipo})`;
  }
  static desdeJSON(texto) {
    const { id, tipo } = JSON.parse(texto);
    return new Sensor(id, tipo);
  }
}

// La version equivalente con funcion constructora:
function SensorViejo(id, tipo) {
  this.id = id;
  this.tipo = tipo;
}
SensorViejo.prototype.describir = function () {
  return `sensor ${this.id} (${this.tipo})`;
};
SensorViejo.desdeJSON = function (texto) { /* ... */ };

console.log('--- Los metodos viven en el prototipo, no en la instancia');
const s = new Sensor('S-01', 'temperatura');
console.log(Object.keys(s));                            // ['id','tipo'] y nada mas
console.log(Object.hasOwn(s, 'describir'));             // false
console.log(Object.hasOwn(Sensor.prototype, 'describir')); // true
console.log(typeof Sensor);                             // 'function'

console.log('\n--- Diferencias reales entre class y funcion constructora');

// 1. Una clase no se puede llamar sin new.
try { Sensor('S-02', 'x'); } catch (e) { console.log('1)', e.constructor.name + ':', e.message); }
console.log('   la funcion vieja si:', new SensorViejo('S-02','x').id, '(y sin new corrompe el objeto global)');

// 2. El cuerpo de una clase es siempre modo estricto.

// 3. Las clases NO se hoistean como las funciones: estan en TDZ.
try { new Tarde(); } catch (e) { console.log('3)', e.constructor.name + ':', e.message); }
class Tarde {}

// 4. Los metodos no son enumerables: no aparecen en for...in
const encontrados = [];
for (const k in s) encontrados.push(k);
console.log('4) for...in sobre la instancia:', encontrados);

console.log('\n--- Todas las instancias comparten el mismo objeto de metodos');
const a = new Sensor('A', 'x');
const b = new Sensor('B', 'y');
console.log(a.describir === b.describir);                        // true
console.log(Object.getPrototypeOf(a) === Object.getPrototypeOf(b)); // true
// Esto importa para memoria: mil instancias -> UNA copia de cada metodo.

console.log('\n--- Campos de instancia: la excepcion');
class ConCampo {
  metodo() {}                      // va al prototipo
  flecha = () => {};               // va a CADA INSTANCIA
}
const c1 = new ConCampo(), c2 = new ConCampo();
console.log('metodo compartido:', c1.metodo === c2.metodo);   // true
console.log('flecha compartida:', c1.flecha === c2.flecha);   // false  <- una por instancia

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Mil instancias con un metodo normal contra mil con una arrow como
//     campo: cual ocupa mas? Medilo con process.memoryUsage().heapUsed.
//  2. Por que entonces se usan tanto los campos flecha en React?
//     (pista: el bloque d-this.js)
// ------------------------------------------------------------
