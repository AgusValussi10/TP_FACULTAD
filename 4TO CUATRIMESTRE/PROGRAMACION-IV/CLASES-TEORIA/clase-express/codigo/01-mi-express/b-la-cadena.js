// ============================================================
//  1.B  Aplanar la cadena: el despachador
// ============================================================
//  El anidamiento del archivo anterior se aplana con cinco lineas.
//  Estas cinco lineas son el corazon de Express, de Koa, de Fastify y
//  de casi cualquier framework HTTP de los ultimos quince años.
'use strict';

function ejecutarCadena(middlewares, req, res) {
  function despachar(i) {
    if (i >= middlewares.length) return;          // se acabo la cadena
    const mw = middlewares[i];
    mw(req, res, () => despachar(i + 1));         // `next` = "segui en i+1"
  }
  despachar(0);
}

// La idea completa: `next` no hace nada magico. Es una funcion que,
// cuando se la llama, ejecuta el middleware SIGUIENTE de la lista.
// El orden de registro es el orden de ejecucion, y no hay ninguna otra
// regla escondida.

const cadena = [
  (req, res, next) => { console.log('1 entra'); next(); console.log('1 sale'); },
  (req, res, next) => { console.log('2 entra'); next(); console.log('2 sale'); },
  (req, res, next) => { console.log('3 termina, no llama a next'); },
  (req, res, next) => { console.log('4 NUNCA se ejecuta'); },
];

console.log('--- ejecucion');
ejecutarCadena(cadena, {}, {});

console.log(`
Se imprime 1,2,3 y despues "2 sale","1 sale".
Es una PILA, no una fila: cada middleware ENVUELVE a los siguientes.
Por eso un middleware puede medir el tiempo total, atrapar errores de
los que vienen despues, o tocar la respuesta ya generada.
`);

console.log('--- el bug numero uno de Express: llamar a next() dos veces');
const doble = [
  (req, res, next) => { next(); next(); },              // <-- el error
  (req, res, next) => { console.log('  soy el handler y respondo'); res.end(); },
];
const resFalso = {
  terminado: false,
  end() {
    if (this.terminado) console.log('  !! ERR_HTTP_HEADERS_SENT');
    this.terminado = true;
  },
};
ejecutarCadena(doble, {}, resFalso);

console.log(`
El handler corrio dos veces y la segunda intento responder sobre una
respuesta ya cerrada. En un servidor real eso es exactamente
"Cannot set headers after they are sent to the client": el error mas
reportado de Express, y siempre significa lo mismo.

Arreglo: un indice compartido que solo puede avanzar.
`);

function ejecutarSeguro(middlewares, req, res) {
  let ultimo = -1;
  function despachar(i) {
    if (i <= ultimo) throw new Error('next() llamado dos veces en el middleware ' + i);
    ultimo = i;
    if (i >= middlewares.length) return;
    middlewares[i](req, res, () => despachar(i + 1));
  }
  despachar(0);
}
try { ejecutarSeguro(doble, {}, { end() {} }); }
catch (e) { console.log('  detectado:', e.message); }

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Agrega a `ejecutarCadena` la medicion del tiempo total, sin tocar
//     ningun middleware.
//  2. Que pasa si un middleware hace `await` antes de llamar a next()?
//     Se sigue imprimiendo "1 sale" al final? Probalo y explica por que.
// ------------------------------------------------------------
