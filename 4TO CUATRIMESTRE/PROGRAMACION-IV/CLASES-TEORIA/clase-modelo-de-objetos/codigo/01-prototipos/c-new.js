// ============================================================
//  1.C  Que hace exactamente `new`
// ============================================================
//  Antes de las clases ES6, la herencia se armaba con funciones
//  constructoras. Entender esto no es historia: es lo que la
//  palabra `class` hace por debajo.


function Sensor(id, tipo) {
  this.id = id;
  this.tipo = tipo;
}

// `prototype` es una propiedad de la FUNCION. No es el prototipo de la
// funcion: es el objeto que se le va a asignar como [[Prototype]] a las
// instancias que la funcion cree con `new`. El nombre es desafortunado.
Sensor.prototype.describir = function () {
  return `sensor ${this.id} (${this.tipo})`;
};

const s = new Sensor('S-01', 'temperatura');
console.log(s.describir());
console.log(Object.getPrototypeOf(s) === Sensor.prototype);  // true
console.log(s.constructor === Sensor);                       // true (heredado)

console.log('\n--- `new` implementado a mano, sin usar new');
function nuevo(Constructor, ...args) {
  console.log(args[0])
  // 1. Crear un objeto vacio cuyo [[Prototype]] sea Constructor.prototype
  const obj = Object.create(Constructor.prototype);
  // 2. Llamar al constructor con `this` apuntando a ese objeto
  const resultado = Constructor.apply(obj, args);
  // 3. Si el constructor devolvio un OBJETO, gana ese. Si no, gana obj.
  return (typeof resultado === 'object' && resultado !== null) ? resultado : obj;
}

const s2 = nuevo(Sensor, 'S-02', 'humedad');
console.log(s2.describir());
console.log(s2 instanceof Sensor);   // true: es la misma construccion

console.log('\n--- instanceof no compara clases: recorre la cadena');
// x instanceof C  es, en esencia:
//   recorrer la cadena de prototipos de x buscando C.prototype
function esInstancia(obj, Constructor) {
  let proto = Object.getPrototypeOf(obj);
  while (proto !== null) {
    if (proto === Constructor.prototype) return true;
    proto = Object.getPrototypeOf(proto);
  }
  return false;
}
console.log(esInstancia(s2, Sensor), esInstancia(s2, Object), esInstancia(s2, Array));

console.log('\n--- Y por eso instanceof miente cuando hay dos realms');
// En Node: un array creado dentro de otro contexto (vm, worker, o un
// paquete que trae su propia copia) NO es instancia de TU Array.
const vm = require('node:vm');
const arrayDeOtroContexto = vm.runInNewContext('[1,2,3]');
console.log('Array.isArray:', Array.isArray(arrayDeOtroContexto));           // true
console.log('instanceof Array:', arrayDeOtroContexto instanceof Array);      // false
// Regla profesional: para chequeos de tipo usar Array.isArray, no instanceof.

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Que pasa si llamas a Sensor('S-03','x') SIN new, en modo no estricto?
//     Y en modo estricto? Proba las dos.
//  2. Reescribi `nuevo` para que tire TypeError si Constructor es una
//     arrow function. Pista: las arrow no tienen .prototype.
// ------------------------------------------------------------
