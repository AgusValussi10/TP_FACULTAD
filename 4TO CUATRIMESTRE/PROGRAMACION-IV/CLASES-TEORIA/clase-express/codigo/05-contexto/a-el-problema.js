// ============================================================
//  5.A  El problema: no hay hilo donde colgar el contexto
// ============================================================
//  Ejecutar:  node 05-contexto/a-el-problema.js
//
//  En el lenguaje del que venis, esto se resuelve con un
//  ThreadLocal: cada pedido tiene su hilo, y el contexto vive ahi.
//  En Node hay UN SOLO hilo para todos los pedidos concurrentes.
//  Un ThreadLocal seria una variable global.
'use strict';

// --- Intento 1: variable global. Lo que todos escriben primero.
let idPedidoActual = null;

function registrar(mensaje) {
  console.log(`[${idPedidoActual}] ${mensaje}`);
}

async function consultarBaseDeDatos(ms) {
  await new Promise((r) => setTimeout(r, ms));
  return 'datos';
}

async function manejarPedido(id, msDemora) {
  idPedidoActual = id;                       // "guardo el contexto"
  registrar('empieza');
  await consultarBaseDeDatos(msDemora);      // <-- acá cede el control
  registrar('termina');                      // y acá el contexto ya no es mio
}

console.log('--- dos pedidos concurrentes, como en un servidor real');
manejarPedido('A', 30);
manejarPedido('B', 5);

setTimeout(() => {
  console.log(`
Mira los ids: el pedido A termina diciendo que es B.
La variable global la piso el segundo pedido mientras el primero
esperaba a la base de datos.

Esto NO es un bug de concurrencia clasico: no hay dos hilos, no hay
condicion de carrera de memoria. Es la consecuencia directa del event
loop de la clase 3: entre el await y su continuacion, otro pedido
corrio en el mismo hilo.

Intento 2: pasar el contexto como parametro a todas las funciones.
   registrar(ctx, mensaje)
   consultarBaseDeDatos(ctx, ms)
   validar(ctx, datos)
Funciona, es explicito y es lo que muchos equipos eligen. El costo es
que TODA funcion que quiera loggear necesita el parametro, incluidas
las que estan diez niveles abajo y no tienen nada que ver con HTTP.
Se contamina toda la firma del sistema para transportar un id.

La tercera opcion es AsyncLocalStorage, y esta en el archivo de al lado.
`);
}, 60);
