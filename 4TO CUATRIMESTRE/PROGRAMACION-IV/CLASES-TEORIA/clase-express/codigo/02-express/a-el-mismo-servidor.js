// ============================================================
//  2.A  El mismo servidor, ahora con Express
//  Requiere:  npm install express
//  Ejecutar:  node 02-express/a-el-mismo-servidor.js
// ============================================================
'use strict';
const express = require('express');

const app = express();
const lecturas = [];

// express.json() es el middleware de parseo que escribimos a mano en 1.E.
app.use(express.json({ limit: '100kb' }));

app.use((req, res, next) => {
  const t0 = process.hrtime.bigint();
  res.on('finish', () => {
    const ms = Number(process.hrtime.bigint() - t0) / 1e6;
    console.log(`${req.method} ${req.originalUrl} -> ${res.statusCode} ${ms.toFixed(1)}ms`);
  });
  next();
});

const autenticar = (req, res, next) => {
  if (req.get('authorization') !== 'Bearer secreto') {
    return next(Object.assign(new Error('no autenticado'), { estado: 401 }));
  }
  req.usuario = { nombre: 'ana' };
  next();
};

app.get('/salud', (req, res) => res.json({ ok: true }));
app.get('/lecturas', (req, res) => res.json(lecturas));

app.post('/lecturas', autenticar, async (req, res) => {
  if (typeof req.body?.valor !== 'number') {
    throw Object.assign(new Error('valor debe ser numero'), { estado: 400 });
  }
  const l = { id: String(lecturas.length + 1), valor: req.body.valor };
  lecturas.push(l);
  res.status(201).json(l);
});

// Express 5: un handler async que lanza VA al manejador de errores solo.
// Express 4: esto colgaba el request. Es la diferencia mas importante
// entre las dos versiones y el motivo por el que existia express-async-errors.
app.get('/explota', async () => { throw new Error('fallo asincronico'); });

app.use((req, res) => res.status(404).json({ error: 'no encontrado' }));

app.use((err, req, res, next) => {
  const estado = err.estado ?? 500;
  if (estado >= 500) console.error('ERROR', err.stack);
  res.status(estado).json({ error: estado >= 500 ? 'error interno' : err.message });
});

if (require.main === module) app.listen(3000, () => console.log('express en :3000\n'));
module.exports = app;

// ------------------------------------------------------------
//  COMPARAR con 01-mi-express/e-mi-express.js.
//  Casi todo lo que hace Express aca ya lo escribimos nosotros.
//  Lo que Express agrega de verdad y no escribimos:
//   - negociacion de contenido, ETag, rangos, archivos estaticos;
//   - un enrutador con soporte de montaje, parametros opcionales,
//     expresiones regulares y router.param();
//   - quince años de casos borde de HTTP que no queres reescribir.
//
//  Y lo que NO agrega, y hay que poner uno:
//   - validacion de entrada;
//   - una forma normalizada de error;
//   - estructura del proyecto.  <- el resto de la clase
// ------------------------------------------------------------
