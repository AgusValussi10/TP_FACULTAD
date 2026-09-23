// ============================================================
//  1.E  Mi-Express: 60 lineas, y ya no necesitas el framework
//       para entender el framework.
//  Ejecutar:  node 01-mi-express/e-mi-express.js
// ============================================================
'use strict';
const http = require('node:http');

function crearApp() {
  const capas = [];

  function compilar(patron) {
    const nombres = [];
    const fuente = patron.replace(/\/:([^/]+)/g, (_, n) => { nombres.push(n); return '/([^/]+)'; });
    return { regex: new RegExp('^' + fuente + '$'), nombres };
  }

  function agregar(metodo, patron, mws) {
    const { regex, nombres } = compilar(patron);
    for (const mw of mws) capas.push({ metodo, regex, nombres, mw });
  }

  const app = {
    use(mw) { capas.push({ metodo: null, regex: null, nombres: [], mw }); return app; },
    get(p, ...m) { agregar('GET', p, m); return app; },
    post(p, ...m) { agregar('POST', p, m); return app; },

    manejar(req, res) {
      const ruta = req.url.split('?')[0];
      let ultimo = -1;

      function despachar(i, err) {
        if (i <= ultimo) throw new Error('next() llamado dos veces');
        ultimo = i;

        if (i >= capas.length) {                        // nadie respondio
          res.statusCode = err ? 500 : 404;
          return res.json({ error: err ? 'error interno' : 'no encontrado' });
        }
        const capa = capas[i];
        const esDeError = capa.mw.length === 4;
        const siguiente = (e) => despachar(i + 1, e ?? err);

        if (err && !esDeError) return siguiente(err);
        if (!err && esDeError) return siguiente();
        if (capa.metodo && capa.metodo !== req.method) return siguiente();
        if (capa.regex && !capa.regex.test(ruta)) return siguiente();
        if (capa.regex) {
          const m = capa.regex.exec(ruta);
          req.params = Object.fromEntries(capa.nombres.map((n, k) => [n, m[k + 1]]));
        }

        // clave: se envuelve SIEMPRE en una promesa. Asi un handler
        // asincronico que lanza termina en el manejador de errores.
        try {
          const r = esDeError ? capa.mw(err, req, res, siguiente)
                              : capa.mw(req, res, siguiente);
          if (r && typeof r.then === 'function') r.catch(siguiente);
        } catch (e) { siguiente(e); }
      }
      despachar(0, null);
    },

    escuchar(puerto, cb) {
      return http.createServer((req, res) => {
        // lo unico que "agrega" el framework a res: helpers
        res.json = (obj) => {
          res.setHeader('content-type', 'application/json');
          res.end(JSON.stringify(obj));
        };
        res.estado = (c) => { res.statusCode = c; return res; };
        app.manejar(req, res);
      }).listen(puerto, cb);
    },
  };
  return app;
}

// ------------------------------------------------------------
//  Y ahora el servidor del bloque 0, escrito con esto:
// ------------------------------------------------------------
const app = crearApp();
const lecturas = [];

app.use((req, res, next) => {                     // log
  const t0 = process.hrtime.bigint();
  res.on('finish', () => {
    const ms = Number(process.hrtime.bigint() - t0) / 1e6;
    console.log(`${req.method} ${req.url} -> ${res.statusCode} ${ms.toFixed(1)}ms`);
  });
  next();
});

app.use(async (req, res, next) => {               // parseo de cuerpo
  if (req.method === 'GET') return next();
  let datos = '';
  for await (const trozo of req) datos += trozo;
  try { req.body = datos ? JSON.parse(datos) : {}; }
  catch { return next(Object.assign(new Error('JSON invalido'), { estado: 400 })); }
  next();
});

const autenticar = (req, res, next) => {          // middleware puntual
  if (req.headers.authorization !== 'Bearer secreto') {
    return next(Object.assign(new Error('no autenticado'), { estado: 401 }));
  }
  req.usuario = { nombre: 'ana' };
  next();
};

app.get('/salud', (req, res) => res.json({ ok: true }));
app.get('/lecturas', (req, res) => res.json(lecturas));
app.get('/lecturas/:id', (req, res, next) => {
  const l = lecturas.find((x) => x.id === req.params.id);
  if (!l) return next(Object.assign(new Error('no existe'), { estado: 404 }));
  res.json(l);
});
app.post('/lecturas', autenticar, async (req, res) => {
  if (typeof req.body.valor !== 'number') {
    throw Object.assign(new Error('valor debe ser numero'), { estado: 400 });
  }
  const l = { id: String(lecturas.length + 1), valor: req.body.valor };
  lecturas.push(l);
  res.estado(201).json(l);
});
app.get('/explota', async () => { throw new Error('fallo asincronico'); });

app.use((err, req, res, next) => {                // manejador de errores, al final
  const estado = err.estado ?? 500;
  if (estado >= 500) console.error('ERROR', err.message);
  res.estado(estado).json({ error: estado >= 500 ? 'error interno' : err.message });
});

if (require.main === module) {
  app.escuchar(3000, () => console.log('mi-express escuchando en http://localhost:3000\n'));
}
module.exports = { crearApp };

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Mové el manejador de errores al PRINCIPIO del archivo. Que pasa?
//     Por que tiene que ir ultimo?
//  2. Agrega app.use('/api', otroRouter): montar un router en un prefijo.
//  3. Compara este archivo con el de 00-repaso. Que se gano? Que se
//     perdio en claridad sobre lo que realmente pasa?
// ------------------------------------------------------------
