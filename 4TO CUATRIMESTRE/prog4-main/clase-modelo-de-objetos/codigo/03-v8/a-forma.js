// ============================================================
//  3.A  La forma del objeto: clases ocultas (hidden classes)
// ============================================================
//  Ejecutar con:  node --allow-natives-syntax 03-v8/a-forma.js
//
//  Si un objeto es un mapa de texto a valores, cada acceso deberia
//  costar una busqueda de hash. En la practica cuesta casi lo mismo
//  que un acceso a campo en Java. Por que?
//
//  Porque V8 no guarda las claves en cada objeto. Guarda un puntero a
//  una estructura compartida —el "mapa" o clase oculta— que dice
//  "esta forma tiene x en la ranura 0 e y en la ranura 1". El objeto
//  guarda solo los valores, en un arreglo.

function hacer() { const o = {}; o.x = 1; o.y = 2; return o; }

const a = hacer();
const b = hacer();
console.log('mismos pasos, misma forma:      ', %HaveSameMap(a, b));   // true

const c = {}; c.y = 2; c.x = 1;             // mismas claves, ORDEN distinto
console.log('orden distinto, misma forma?:   ', %HaveSameMap(a, c));   // false

const d = hacer(); d.z = 3;                 // una propiedad de mas
console.log('con propiedad extra:            ', %HaveSameMap(a, d));   // false

const e = hacer(); delete e.x;              // delete
console.log('despues de delete:              ', %HaveSameMap(a, e));   // false

const f = { x: 1, y: 2 };                   // literal completo
console.log('literal vs construido a pedazos:', %HaveSameMap(a, f));   // false

// Cada asignacion de una propiedad NUEVA hace una TRANSICION a otra clase
// oculta. V8 arma un arbol de transiciones: {} -> {x} -> {x,y}.
// Dos objetos comparten forma solo si recorrieron el mismo camino.
//
// Consecuencia de diseño, y es la unica regla que hay que recordar:
//
//    INICIALIZA TODAS LAS PROPIEDADES EN EL CONSTRUCTOR,
//    SIEMPRE EN EL MISMO ORDEN, Y NO USES delete.
//
// Poner `this.error = null` en el constructor no es prolijidad: es lo que
// mantiene una sola forma para todas las instancias.

console.log('\n--- Por eso las clases son buenas para el motor');
class Lectura {
  constructor(id, valor) { this.id = id; this.valor = valor; this.error = null; }
}
console.log('dos instancias de clase:', %HaveSameMap(new Lectura(1,2), new Lectura(3,4)));

console.log('\n--- Y el modo diccionario');
// Si un objeto recibe demasiadas propiedades dinamicas, o se le borra una,
// V8 lo degrada a un hash real ("modo diccionario"): pierde las ranuras.
const dic = {};
for (let i = 0; i < 100; i++) dic['k' + i] = i;
console.log('objeto con 100 claves dinamicas -> modo diccionario?',
            !%HasFastProperties(dic));

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Construi dos objetos con las mismas cinco propiedades, uno en el
//     constructor y otro asignando de a una despues. Comparar mapas.
//  2. Que pasa si en vez de `delete o.x` haces `o.x = undefined`?
//     Sigue teniendo la misma forma? Cual de las dos preferis y por que?
// ------------------------------------------------------------
