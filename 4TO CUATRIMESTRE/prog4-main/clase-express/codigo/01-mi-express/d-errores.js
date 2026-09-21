// ============================================================
//  1.D  Propagacion de errores: por que existe el middleware de 4 args
// ============================================================
'use strict';

// En la cadena hay DOS caminos: el normal y el de error.
//  - next()      -> siguiente middleware NORMAL (3 argumentos)
//  - next(err)   -> saltea todos los normales y va al primero de ERROR
//                   (4 argumentos: err, req, res, next)
//
// Es el equivalente estructural del try/catch: una via alternativa que
// salta por encima del flujo principal hasta encontrar quien la atienda.

function ejecutar(capas, req, res) {
  function despachar(i, err) {
    if (i >= capas.length) {
      if (err) { res.statusCode = 500; return res.end('error no atendido'); }
      res.statusCode = 404; return res.end('no encontrado');
    }
    const mw = capas[i];
    const esDeError = mw.length === 4;              // <-- se decide por ARIDAD

    const siguiente = (e) => despachar(i + 1, e ?? err);

    if (err && !esDeError) return siguiente(err);   // hay error: saltear normales
    if (!err && esDeError) return siguiente();      // no hay error: saltear los de error

    try {
      if (esDeError) mw(err, req, res, siguiente);
      else mw(req, res, siguiente);
    } catch (e) {
      siguiente(e);                                 // captura SINCRONICA
    }
  }
  despachar(0, null);
}

const res = { statusCode: 0, end(c) { console.log('  respuesta', this.statusCode, c); } };

console.log('--- camino feliz');
ejecutar([
  (req, res, next) => { console.log('  normal 1'); next(); },
  (req, res) => { res.statusCode = 200; res.end('ok'); },
  (err, req, res, next) => { console.log('  NO se ejecuta'); },
], {}, { ...res });

console.log('\n--- error lanzado de forma sincronica');
ejecutar([
  (req, res, next) => { throw new Error('se rompio'); },
  (req, res, next) => { console.log('  NO se ejecuta'); next(); },
  (err, req, res, next) => { console.log('  manejador de error:', err.message);
                             res.statusCode = 500; res.end('atendido'); },
], {}, { ...res });

console.log('\n--- error pasado a next(err)');
ejecutar([
  (req, res, next) => next(new Error('validacion fallida')),
  (err, req, res, next) => { res.statusCode = 400; res.end(err.message); },
], {}, { ...res });

console.log(`
--- Y ahora EL problema, el que rompe servidores en produccion:`);

// El try/catch de arriba solo atrapa errores SINCRONICOS. Si el
// middleware es async, la excepcion se convierte en una promesa
// rechazada, y la promesa no la esta esperando nadie.
ejecutar([
  async (req, res, next) => { throw new Error('fallo asincronico'); },
  (err, req, res, next) => { console.log('  NUNCA llega aca'); res.end('x'); },
], {}, { ...res });

process.on('unhandledRejection', (e) => {
  console.log(`
  Se disparo 'unhandledRejection':`, e.message, `

  La respuesta nunca se envio: el cliente queda esperando hasta que se
  corte por timeout. Y en Node moderno, un rechazo no atendido TERMINA
  EL PROCESO por defecto. Un solo endpoint mal escrito voltea el
  servidor entero.

  Los dos arreglos:
   1. envolver cada handler asincronico:
        const asincronico = (fn) => (req,res,next) =>
          Promise.resolve(fn(req,res,next)).catch(next);
   2. usar Express 5, que hace exactamente eso por vos.
      (en Express 4 hay que envolver a mano, SIEMPRE)
`);
});

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Implementa `asincronico` y arregla el ultimo caso.
//  2. Por que la deteccion es por ARIDAD (mw.length === 4) y no por un
//     flag? Que pasa si escribis (err, req, res) con tres parametros?
//     Y si usas parametros por defecto o rest args?
//  3. Un middleware de error que llama a next(err) que hace? Para que
//     serviria encadenar dos manejadores de error?
// ------------------------------------------------------------
