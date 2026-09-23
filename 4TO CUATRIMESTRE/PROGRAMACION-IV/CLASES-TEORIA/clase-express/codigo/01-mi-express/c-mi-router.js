// ============================================================
//  1.C  El router: middlewares con condicion
// ============================================================
//  Un router no es una estructura nueva. Es la misma cadena, donde
//  cada eslabon tiene ademas un metodo y un patron de ruta.
'use strict';

function compilarRuta(patron) {
  // '/lecturas/:id' -> /^\/lecturas\/([^/]+)$/  + nombres ['id']
  const nombres = [];
  const fuente = patron
    .replace(/\/:([^/]+)/g, (_, n) => { nombres.push(n); return '/([^/]+)'; });
  return { regex: new RegExp('^' + fuente + '$'), nombres };
}

function crearRouter() {
  const capas = [];   // { metodo, regex, nombres, mw }

  function agregar(metodo, patron, ...mws) {
    const { regex, nombres } = compilarRuta(patron);
    for (const mw of mws) capas.push({ metodo, regex, nombres, mw });
  }

  const router = {
    use(mw) { capas.push({ metodo: null, regex: null, nombres: [], mw }); return router; },
    get: (p, ...m) => (agregar('GET', p, ...m), router),
    post: (p, ...m) => (agregar('POST', p, ...m), router),
    patch: (p, ...m) => (agregar('PATCH', p, ...m), router),
    delete: (p, ...m) => (agregar('DELETE', p, ...m), router),

    manejar(req, res, hecho) {
      const ruta = req.url.split('?')[0];
      function despachar(i) {
        if (i >= capas.length) return hecho();
        const capa = capas[i];
        const siguiente = () => despachar(i + 1);

        if (capa.metodo === null) return capa.mw(req, res, siguiente);   // use()
        if (capa.metodo !== req.method) return siguiente();

        const m = capa.regex.exec(ruta);
        if (!m) return siguiente();

        req.params = Object.fromEntries(capa.nombres.map((n, k) => [n, m[k + 1]]));
        capa.mw(req, res, siguiente);
      }
      despachar(0);
    },
  };
  return router;
}

// --- Demo sin red: se simula req y res
const router = crearRouter();
router.use((req, res, next) => { console.log('  use: pasa todo'); next(); });
router.get('/salud', (req, res) => console.log('  handler /salud'));
router.get('/lecturas/:id', (req, res) => console.log('  handler lectura', req.params));
router.post('/lecturas', (req, res) => console.log('  handler alta'));

for (const [metodo, url] of [['GET','/salud'], ['GET','/lecturas/S-01'], ['POST','/lecturas'], ['GET','/otra']]) {
  console.log(`\n${metodo} ${url}`);
  router.manejar({ method: metodo, url }, {}, () => console.log('  404 (nadie respondio)'));
}

console.log(`
Tres cosas que quedan claras al escribirlo:

 1. El router recorre las capas EN ORDEN y se detiene en la primera que
    responde. No hay tabla de hash de rutas ni busqueda optimizada: es
    una lista. Por eso el orden de registro importa tanto, y por eso
    poner la ruta '/:id' antes que '/salud' rompe /salud.

 2. req.params no existe: lo INVENTA el router, escribiendolo sobre el
    objeto req de node:http. Todo lo que Express "agrega" a req y res
    son propiedades que alguien asigno en algun middleware.

 3. El 404 no es una ruta: es lo que pasa cuando se termino la lista sin
    que nadie respondiera.
`);

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Registra '/lecturas/:id' ANTES de '/lecturas/promedio' y explica
//     por que /lecturas/promedio deja de funcionar.
//  2. Agrega soporte para montar un router adentro de otro (app.use('/api', r)).
//  3. Que pasa con una ruta que contiene un punto o un guion? Y con
//     '/archivos/*'? Que le falta a compilarRuta?
// ------------------------------------------------------------
