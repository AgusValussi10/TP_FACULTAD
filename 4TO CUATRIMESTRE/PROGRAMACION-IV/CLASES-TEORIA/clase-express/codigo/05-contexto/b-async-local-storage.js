// ============================================================
//  5.B  AsyncLocalStorage: contexto que sobrevive al await
// ============================================================
'use strict';
const { AsyncLocalStorage } = require('node:async_hooks');

const almacen = new AsyncLocalStorage();

function registrar(mensaje) {
  const ctx = almacen.getStore();                  // no recibe nada por parametro
  console.log(`[${ctx?.idPedido ?? 'sin-contexto'}] ${mensaje}`);
}

async function consultarBaseDeDatos(ms) {
  await new Promise((r) => setTimeout(r, ms));
  registrar('  (desde 3 niveles abajo, sin haber pasado nada)');
  return 'datos';
}

async function manejarPedido(id, msDemora) {
  // run() ejecuta la funcion CON ese contexto asociado. Todo lo que
  // se dispare adentro —promesas, timers, callbacks, streams— lo hereda.
  await almacen.run({ idPedido: id, inicio: Date.now() }, async () => {
    registrar('empieza');
    await consultarBaseDeDatos(msDemora);
    registrar('termina');
  });
}

console.log('--- los mismos dos pedidos concurrentes');
manejarPedido('A', 30);
manejarPedido('B', 5);

setTimeout(() => {
  console.log(`
Ahora A termina siendo A. El contexto viaja con la cadena de
continuaciones, no con la variable.

Como funciona, en una frase: Node mantiene un "recurso asincronico" por
cada operacion pendiente (un timer, un socket, una promesa). Cuando se
crea uno nuevo, hereda el contexto del que lo creo. AsyncLocalStorage se
apoya en ese arbol.

Lo que NO hay que hacer con esto:
 - meter ahi el usuario y usarlo como si fuera un parametro implicito de
   la logica de negocio. El caso de uso tiene que recibir sus datos como
   parametros: si no, volves a tener una variable global, solo que mas
   dificil de rastrear.
 - usarlo para pasar datos entre pedidos. No es un cache.

Para lo que SI sirve, y es muchisimo:
 - id de correlacion en TODOS los logs, sin tocar ninguna firma;
 - trazas distribuidas (OpenTelemetry lo usa internamente);
 - la tenencia (tenant) en sistemas multiempresa;
 - medir cuanto tardo el pedido desde cualquier punto del codigo.

El costo: hay un overhead real, historicamente entre 3% y 10% segun la
version de Node y el uso. Es de las pocas cosas de esta materia donde la
respuesta correcta es "medilo en tu carga antes de decidir".
`);
}, 60);
