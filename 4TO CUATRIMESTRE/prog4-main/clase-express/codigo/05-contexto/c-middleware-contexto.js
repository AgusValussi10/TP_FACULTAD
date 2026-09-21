// ============================================================
//  5.C  El middleware de contexto, como se usa de verdad
//  Ejecutar:  node 05-contexto/c-middleware-contexto.js
// ============================================================
'use strict';
const express = require('express');
const crypto = require('node:crypto');
const { AsyncLocalStorage } = require('node:async_hooks');

const almacen = new AsyncLocalStorage();

// --- El middleware: una sola vez, al principio de todo.
const contextoPorPedido = (req, res, next) => {
  const idPedido = req.get('x-request-id') ?? crypto.randomUUID();
  res.setHeader('x-request-id', idPedido);        // se devuelve al cliente
  almacen.run({ idPedido, inicio: process.hrtime.bigint() }, next);
};

// --- El registrador: no recibe el id por parametro nunca.
const log = {
  info(msg, extra = {}) {
    const ctx = almacen.getStore();
    console.log(JSON.stringify({ nivel: 'info', idPedido: ctx?.idPedido, msg, ...extra }));
  },
  error(msg, extra = {}) {
    const ctx = almacen.getStore();
    console.error(JSON.stringify({ nivel: 'error', idPedido: ctx?.idPedido, msg, ...extra }));
  },
};

// --- Logica de dominio, tres niveles abajo, sin saber nada de HTTP.
async function calcularPromedio(sensorId) {
  log.info('consultando repositorio', { sensorId });   // <- el id aparece solo
  await new Promise((r) => setTimeout(r, 10));
  return 21.5;
}

const app = express();
app.use(contextoPorPedido);
app.use((req, res, next) => {
  log.info('pedido entra', { metodo: req.method, ruta: req.originalUrl });
  res.on('finish', () => {
    const ctx = almacen.getStore();
    const ms = Number(process.hrtime.bigint() - ctx.inicio) / 1e6;
    log.info('pedido sale', { estado: res.statusCode, ms: +ms.toFixed(1) });
  });
  next();
});

app.get('/promedio/:sensorId', async (req, res) => {
  res.json({ promedio: await calcularPromedio(req.params.sensorId) });
});
app.get('/explota', async () => { throw new Error('fallo'); });
app.use((err, req, res, next) => {
  log.error('error no atendido', { msg: err.message });   // correlacionado
  res.status(500).json({ error: 'error interno' });
});

const servidor = app.listen(3000, async () => {
  await Promise.all([
    fetch('http://localhost:3000/promedio/S-01'),
    fetch('http://localhost:3000/promedio/S-02'),
    fetch('http://localhost:3000/explota'),
  ]);
  console.log(`
Mira los idPedido: las lineas de los tres pedidos estan intercaladas
—porque el servidor es concurrente— pero cada linea sabe a cual
pertenece. En produccion eso es la diferencia entre poder investigar un
error y no poder.

Y notar que 'consultando repositorio' salio correlacionado sin que
calcularPromedio reciba ningun parametro de contexto.
`);
  servidor.close();
});
