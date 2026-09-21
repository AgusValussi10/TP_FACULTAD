// ============================================================
//  2.B  Cuatro bugs de orden, y por que son el MISMO bug
//  Ejecutar:  node 02-express/b-orden.js
// ============================================================
//  Todo lo que sigue se explica con una sola frase, la del bloque 1:
//  el router es una LISTA que se recorre en orden y se detiene en la
//  primera capa que responde.
'use strict';
const express = require('express');
const app = express();

// --- BUG 1: la ruta con parametro tapa a la ruta fija
app.get('/lecturas/:id', (req, res) => res.json({ camino: ':id', id: req.params.id }));
app.get('/lecturas/promedio', (req, res) => res.json({ camino: 'promedio' }));
//        ^ nunca se alcanza: '/lecturas/promedio' ya matcheo con :id

// --- BUG 2: el 404 puesto antes de las rutas que faltan
app.get('/temprano', (req, res) => res.json({ ok: true }));
app.use('/tarde', (req, res) => res.status(404).json({ error: 'no encontrado' }));
app.get('/tarde/existe', (req, res) => res.json({ nunca: true }));
//        ^ el use() de arriba ya respondio

// --- BUG 3: el middleware de auth registrado despues de la ruta
app.get('/privado', (req, res) => res.json({ secreto: true }));
app.use((req, res, next) => { req.autenticado = false; next(); });
//        ^ /privado ya respondio: la auth no corre nunca

// --- BUG 4: el manejador de errores que no tiene cuatro parametros
app.get('/roto', () => { throw new Error('boom'); });
app.use((err, req, res) => {                       // <-- solo TRES parametros
  res.status(500).json({ error: 'atendido por mi' });
});
// Express lo cuenta como middleware NORMAL, no de error. Nunca lo llama.
// La deteccion es por aridad, exactamente como en 01-mi-express/d-errores.js

const servidor = app.listen(3000, async () => {
  const g = async (u) => {
    const r = await fetch('http://localhost:3000' + u);
    console.log(u.padEnd(22), r.status, (await r.text()).slice(0, 60));
  };
  await g('/lecturas/promedio');   // esperado 'promedio', sale ':id'
  await g('/tarde/existe');        // esperado 200, sale 404
  await g('/roto');                // esperado el JSON propio, sale el HTML de Express
  console.log(`
Los cuatro son el mismo error: registrar en un orden y esperar otro.
La regla operativa, en el orden en que va el archivo:

  1. middlewares globales (log, cuerpo, seguridad)
  2. rutas, de la mas especifica a la mas general
  3. el 404
  4. el manejador de errores, SIEMPRE ultimo y SIEMPRE con 4 parametros
`);
  servidor.close();
});

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Arregla los cuatro bugs sin borrar ninguna ruta.
//  2. Escribi un test con node:test que falle con el archivo actual y
//     pase con el arreglado. Cual de los cuatro es mas dificil de
//     testear y por que?
// ------------------------------------------------------------
