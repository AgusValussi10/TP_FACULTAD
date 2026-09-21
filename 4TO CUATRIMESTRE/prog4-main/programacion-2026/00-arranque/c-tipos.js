// ============================================================
//  3. TIPOS: los hay, pero no se declaran
// ============================================================
//
//  Ejecutalo:  node c-tipos.js
//
//  JavaScript SÍ tiene tipos. Lo que no tiene es declaración de
//  tipos: el tipo lo lleva el VALOR, no la variable.
// ============================================================

const texto = 'hola'; // string
const numero = 42; // number
const decimal = 3.14; // number  ← ¡también!
const verdadero = true; // boolean
const nada = null; // object
let sinAsignar; // undefined
const lista = [1, 2, 3]; // object (array)
const objeto = { a: 1 }; // object

undefined
null


console.log(typeof texto);
console.log(typeof numero);
console.log(typeof decimal);
console.log(typeof verdadero);
console.log(typeof nada);
console.log(typeof sinAsignar);
console.log(typeof lista);
console.log(typeof objeto);
console.log(typeof null); // "object"  ← ¡sí, es raro!

// ------------------------------------------------------------
//  PARÁ: typeof null dice "object". ¿Cómo?
// ------------------------------------------------------------
//
//  Es un error del lenguaje, y tiene 30 años.
//
//  En la primera implementación de JavaScript, en 1995, los valores
//  se guardaban con una etiqueta de tipo en los primeros bits. La
//  etiqueta de los objetos era 000, y `null` se representaba con
//  todos los bits en cero. Así que `typeof null` leía 000 y
//  respondía "object".
//
//  Se intentó arreglar y se rechazó: hay demasiado código en el
//  mundo que depende de ese comportamiento.
//
//  Lo traemos ahora por dos razones. Una práctica: si querés
//  saber si algo es null, comparalo con null, no uses typeof.
//  Y otra de fondo, que vale para toda la materia:
//
//      Las herramientas que usamos tienen historia, y esa historia
//      deja cicatrices. Entender por qué algo es raro es más útil
//      que memorizar que es raro.

console.log('\n¿nada es null?', nada === null); // así se pregunta

// ------------------------------------------------------------
//  DIFERENCIA 1: hay UN solo tipo numérico
// ------------------------------------------------------------
//
//  No hay int, long, float, double, decimal. Hay `number`.
//  42 y 3.14 son el mismo tipo.

console.log('\n42 y 3.14 son el mismo tipo:', typeof 42 === typeof 3.14);
console.log('10 / 3 =', 10 / 3); // no trunca: no hay división entera
console.log('7 / 2 =', 7 / 2);

// Si venís de un lenguaje con int, ojo con esto:
// `10 / 3` no da 3. Da 3.333...
// Para truncar: Math.trunc(10 / 3) o Math.floor(10 / 3)
console.log('Math.trunc(10 / 3) =', Math.trunc(10 / 3));

// ------------------------------------------------------------
//  DIFERENCIA 2: la variable puede cambiar de tipo
// ------------------------------------------------------------

let cosa = 'ahora soy texto';
console.log('\n' + typeof cosa);
cosa = 99;
console.log(typeof cosa);
cosa = true;
console.log(typeof cosa);

// Esto es legal. No es un error. Es tipado dinámico.
//
// ¿Es una buena idea hacerlo? No. Que se pueda no significa que
// convenga. Pero tenés que saber que es posible, porque vas a
// leer código ajeno que lo hace.

// ------------------------------------------------------------
//  DIFERENCIA 3: null y undefined son distintos
// ------------------------------------------------------------
//
//  En muchos lenguajes hay un solo "vacío". En JavaScript hay dos.

let noAsignada; // nunca le dimos valor
const vaciaAProposito = null; // le dimos el valor "nada"

console.log('\nnoAsignada:      ', noAsignada);
console.log('vaciaAProposito: ', vaciaAProposito);

// La diferencia en una frase:
//
//   undefined = "nadie puso nada acá"       (lo pone el lenguaje)
//   null      = "acá va nada, a propósito"  (lo ponés vos)
//
// Ejemplo real: si pedís una propiedad que no existe, da undefined.

const alumno = { nombre: 'Ana' };
console.log('alumno.legajo:', alumno.legajo); // undefined, no error

// Fijate que NO explotó. En otros lenguajes esto sería un error de
// compilación. Acá devuelve undefined y el programa sigue.
// Esa diferencia va a generar bugs. Tenelo presente.

// ------------------------------------------------------------
//  TEXTO: comillas y plantillas
// ------------------------------------------------------------

const materia = 'Programación IV';
const claseNumero = 1;

// Concatenación clásica (funciona, pero se vuelve ilegible):
console.log('\n' + 'Clase ' + claseNumero + ' de ' + materia);

// Plantilla con acento invertido (backtick). Esto se usa siempre:
console.log(`Clase ${claseNumero} de ${materia}`);

// Adentro de ${...} va cualquier expresión:
console.log(`El año que viene es la clase ${claseNumero + 1}`);

// Y sirve para varias líneas sin caracteres raros:
console.log(`
  Materia: ${materia}
  Clase:   ${claseNumero}
`);

