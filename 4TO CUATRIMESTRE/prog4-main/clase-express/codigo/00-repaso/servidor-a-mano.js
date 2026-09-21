// ============================================================
//  0. El punto de partida: el servidor de la clase 1, crecido
// ============================================================
//  Ejecutar:  node 00-repaso/servidor-a-mano.js
//
//  No es un mal servidor. Anda. El problema es todo lo que ya
//  aparece repetido, y lo que va a aparecer cuando crezca.
'use strict';
const http = require('node:http');
const crypto = require('node:crypto');

const lecturas = [];

function leerCuerpo(req) {
  return new Promise((resolver, rechazar) => {
    let datos = '';
    req.on('data', (c) => { datos += c; });
    req.on('end', () => {
      try { resolver(datos ? JSON.parse(datos) : {}); }
      catch { rechazar(new Error('JSON invalido')); }
    });
  });
}

const servidor = http.createServer(async (req, res) => {
  const inicio = process.hrtime.bigint();
  const id = crypto.randomUUID();
  const url = new URL(req.url, 'http://localhost');

  // (1) log de entrada — repetido conceptualmente en cada request
  console.log(`--> ${id.slice(0, 8)} ${req.method} ${url.pathname}`);

  try {
    if (req.method === 'GET' && url.pathname === '/salud') {
      res.setHeader('content-type', 'application/json');
      res.end(JSON.stringify({ ok: true }));
    }

    else if (req.method === 'GET' && url.pathname === '/lecturas') {
      res.setHeader('content-type', 'application/json');
      res.end(JSON.stringify(lecturas));
    }

    else if (req.method === 'POST' && url.pathname === '/lecturas') {
      // (2) autenticacion — hay que acordarse de ponerla en CADA ruta
      if (req.headers.authorization !== 'Bearer secreto') {
        res.statusCode = 401;
        res.setHeader('content-type', 'application/json');
        res.end(JSON.stringify({ error: 'no autenticado' }));
      } else {
        const cuerpo = await leerCuerpo(req);           // (3) parseo, otra vez
        if (typeof cuerpo.valor !== 'number') {         // (4) validacion a mano
          res.statusCode = 400;
          res.setHeader('content-type', 'application/json');
          res.end(JSON.stringify({ error: 'valor debe ser numero' }));
        } else {
          lecturas.push({ ...cuerpo, id: crypto.randomUUID() });
          res.statusCode = 201;
          res.setHeader('content-type', 'application/json');
          res.end(JSON.stringify(lecturas.at(-1)));
        }
      }
    }

    // (5) ruta con parametro: hay que partir la URL a mano
    else if (req.method === 'GET' && /^\/lecturas\/[^/]+$/.test(url.pathname)) {
      const idBuscado = url.pathname.split('/')[2];
      const encontrada = lecturas.find((l) => l.id === idBuscado);
      res.setHeader('content-type', 'application/json');
      if (!encontrada) { res.statusCode = 404; res.end(JSON.stringify({ error: 'no existe' })); }
      else res.end(JSON.stringify(encontrada));
    }

    else {
      res.statusCode = 404;
      res.setHeader('content-type', 'application/json');
      res.end(JSON.stringify({ error: 'no encontrado' }));
    }
  } catch (e) {
    // (6) un unico try/catch gigante, y ojo: solo atrapa lo que se hizo await
    res.statusCode = 500;
    res.end(JSON.stringify({ error: 'error interno' }));
  }

  // (7) log de salida
  const ms = Number(process.hrtime.bigint() - inicio) / 1e6;
  console.log(`<-- ${id.slice(0, 8)} ${res.statusCode} ${ms.toFixed(1)}ms`);
});

servidor.listen(3001, () => console.log('escuchando en http://localhost:3001'));

