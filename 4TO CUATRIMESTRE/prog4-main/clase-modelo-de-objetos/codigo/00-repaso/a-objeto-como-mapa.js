// ============================================================
//  0.A  Un objeto no es una instancia de una clase.
//       Es un mapa de claves de texto a valores, con un enlace
//       a otro objeto.
// ============================================================

// En Java o C# esto no se puede escribir: no hay clase que lo respalde.
const sensor = {
  id: 'S-01',
  temperatura: 21.5,
  ubicacion: 'deposito-norte',
};

console.log('--- 1. Las propiedades se agregan y se sacan en tiempo de ejecucion');
sensor.firmware = '2.1.0';        // no estaba declarada en ningun lado
delete sensor.ubicacion;          // y esta deja de existir
console.log(sensor);

console.log('\n--- 2. Las claves son texto (o simbolos). Siempre.');
const raro = {};
raro[1] = 'uno';
raro[true] = 'verdadero';
console.log(Object.keys(raro));     // ['1', 'true']  <- se convirtieron a texto
console.log(raro['1'] === raro[1]); // true

console.log('\n--- 3. El orden de las claves NO es el de insercion, del todo');
const orden = {};
orden.b = 1;
orden[2] = 1;
orden.a = 1;
orden[1] = 1;
console.log(Object.keys(orden));  // enteros primero en orden numerico, despues el resto
// Regla del spec (OrdinaryOwnPropertyKeys): claves que son indices enteros,
// en orden numerico ascendente; despues las demas cadenas en orden de insercion.

console.log('\n--- 4. Acceso a una propiedad que no existe: undefined, no error');
console.log(sensor.presion);      // undefined
// console.log(sensor.presion.valor); // ESTO si explota: TypeError


