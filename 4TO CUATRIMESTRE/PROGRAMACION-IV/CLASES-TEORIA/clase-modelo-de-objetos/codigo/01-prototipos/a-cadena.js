// para leer Object.x
//   1. Se busca en el objeto mismo. tiene Object una propiedad propia llamada 'x'?
//   2. Si no existe, se sigue el enlace: [[Prototype]] y repetir.
//   3. Y así sucesivamente, hasta llegar a null. vamos a devolver undefined

const sensorBase = {
  tipo: 'generico',
  describir() {
    return `sensor ${this.id} (${this.tipo})`;
  },
};

// [[Prototype]] -> Object || Null

// Object.create(X) crea un objeto vacio cuyo [[Prototype]] es X.
const s1 = Object.create(sensorBase);
s1.id = 'S-01';

console.log(s1.describir());          // 'sensor S-01 (generico)'
console.log(Object.keys(s1));  
console.log(s1.device)  

console.log('\n--- El algoritmo, paso a paso, para s1.describir');
// 1. Tiene s1 una propiedad PROPIA llamada 'describir'?  -> No.
// 2. Entonces se sigue el enlace: s1.[[Prototype]] es sensorBase.
// 3. Tiene sensorBase una propiedad propia 'describir'?  -> Si. Se devuelve.
// 4. Si no la hubiera tenido, se seguia a Object.prototype.
// 5. Y de ahi a null: se devuelve undefined. NO hay error.
console.log(Object.hasOwn(s1, 'describir'));                 // false
console.log(Object.getPrototypeOf(s1) === sensorBase);       // true
console.log(Object.getPrototypeOf(sensorBase) === Object.prototype); // true
console.log(Object.getPrototypeOf(Object.prototype));        // null  <- fin de la cadena

console.log('\n--- `this` no es el objeto donde se encontro el metodo');
// describir vive en sensorBase, pero `this` es s1: el objeto por el que
// se hizo la llamada. Esto se llama "receptor" (receiver).
const s2 = Object.create(sensorBase);
s2.id = 'S-02';
s2.tipo = 'temperatura';   // propia: tapa a la del prototipo
console.log(s2.describir());  // 'sensor S-02 (temperatura)'
console.log(s1.describir());  // sigue diciendo 'generico'

console.log('\n--- La cadena es viva: si cambia el prototipo, cambian los hijos');
sensorBase.unidad = 'C';
console.log(s1.unidad, s2.unidad);   // 'C' 'C'  <- ya existian antes del cambio

console.log('\n--- in vs hasOwn: la diferencia mas importante del bloque');
console.log('unidad' in s1);              // true  (mira toda la cadena)
console.log(Object.hasOwn(s1, 'unidad')); // false (solo propiedades propias)

