// ============================================================
//  2.B  extends, super y la cadena doble
// ============================================================
'use strict';

class Dispositivo {
  constructor(id) {
    this.id = id;
    this.creadoEn = Date.now();
  }
  describir() { return `dispositivo ${this.id}`; }
  static familia() { return 'generico'; }
}

class Sensor extends Dispositivo {
  constructor(id, unidad) {
    super(id);              // OBLIGATORIO antes de tocar `this`
    this.unidad = unidad;
  }
  describir() {
    return `${super.describir()} midiendo en ${this.unidad}`;
  }
}

const s = new Sensor('S-01', 'C');
console.log(s.describir());

console.log('\n--- extends enlaza DOS cadenas, no una');
console.log(Object.getPrototypeOf(Sensor.prototype) === Dispositivo.prototype); // instancias
console.log(Object.getPrototypeOf(Sensor) === Dispositivo);                     // estaticos
console.log(Sensor.familia());   // 'generico' <- los estaticos se heredan de verdad

console.log('\n--- Sin super(), `this` no existe todavia');
class Rota extends Dispositivo {
  constructor(id) {
    try { this.id = id; } catch (e) { console.log(e.constructor.name + ':', e.message); }
    super(id);
  }
}
new Rota('X');
// En una subclase, el objeto lo crea la clase BASE. `this` esta en una
// especie de zona muerta hasta que super() retorna. Esto no es un capricho:
// permite que la base decida que objeto se construye (ver Symbol.species).

console.log('\n--- super no es "la clase padre": es "el prototipo de donde estoy"');
// super.describir() NO significa Dispositivo.prototype.describir.
// Significa: buscar 'describir' en el [[Prototype]] del objeto donde se
// definio el metodo, y llamarlo con el `this` actual.
const suelto = { describir() { return 'suelto: ' + super.toString(); } };
Object.setPrototypeOf(suelto, { toString() { return 'proto casero'; } });
console.log(suelto.describir());

console.log('\n--- El problema que viene: la jerarquia crece hacia el costado');
class SensorTemperatura extends Sensor {}
class SensorTemperaturaConAlarma extends SensorTemperatura {}
class SensorTemperaturaConAlarmaYBateria extends SensorTemperaturaConAlarma {}
const largo = new SensorTemperaturaConAlarmaYBateria('S-99', 'C');
let n = 0, p = largo;
while ((p = Object.getPrototypeOf(p))) n++;
console.log('saltos hasta null para resolver un metodo del tope:', n);
// Cada nivel es un salto mas en la resolucion, y sobre todo: es una decision
// de diseño que ya no se puede deshacer. Volvemos a esto en el bloque 4.

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Agregale a Sensor un metodo estatico `familia` que devuelva 'sensor'
//     y llamalo desde SensorTemperatura. De donde sale?
//  2. Que devuelve `new.target` dentro del constructor de Dispositivo
//     cuando construis un Sensor? Imprimilo con new.target.name.
//     Para que serviria eso en una clase abstracta?
// ------------------------------------------------------------
