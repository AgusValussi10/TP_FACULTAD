// ============================================================
//  El pago de la arquitectura: estos tests no levantan un servidor,
//  no abren un puerto, no usan supertest y corren en milisegundos.
//  Ejecutar:  node --test 03-capas/pruebas.test.js
// ============================================================
'use strict';
const test = require('node:test');
const assert = require('node:assert');
const { Lectura, ErrorDeDominio } = require('./dominio/Lectura');
const { crearRegistrarLectura } = require('./aplicacion/registrarLectura');

test('el dominio rechaza una unidad no soportada', () => {
  assert.throws(() => new Lectura({ sensorId: 'S', valor: 1, unidad: 'K' }), ErrorDeDominio);
});

test('convierte Fahrenheit a Celsius', () => {
  const l = new Lectura({ sensorId: 'S', valor: 212, unidad: 'F' });
  assert.strictEqual(l.enCelsius(), 100);
});

test('el caso de uso alerta cuando se supera el umbral', async () => {
  const guardadas = [];
  const alertas = [];
  const registrar = crearRegistrarLectura({
    repositorio: { async guardar(l) { guardadas.push(l); } },   // doble de prueba
    notificador: { async alertar(a) { alertas.push(a); } },     // otro doble
    umbral: 30,
    reloj: () => new Date('2026-01-01T00:00:00Z'),              // tiempo fijo
  });

  await registrar({ sensorId: 'S-01', valor: 21, unidad: 'C' });
  assert.strictEqual(alertas.length, 0);

  await registrar({ sensorId: 'S-01', valor: 35, unidad: 'C' });
  assert.strictEqual(alertas.length, 1);
  assert.strictEqual(alertas[0].sensorId, 'S-01');
  assert.strictEqual(guardadas.length, 2);
});

test('el caso de uso no alerta si la lectura es invalida', async () => {
  const registrar = crearRegistrarLectura({
    repositorio: { async guardar() { assert.fail('no deberia guardar'); } },
    notificador: { async alertar() { assert.fail('no deberia alertar'); } },
    umbral: 30,
  });
  await assert.rejects(() => registrar({ sensorId: '', valor: 1, unidad: 'C' }), ErrorDeDominio);
});

// ------------------------------------------------------------
//  Comparar con lo que costaria testear esto si la logica estuviera
//  adentro del handler de Express: habria que levantar el servidor,
//  armar un request, y no habria forma de fijar el reloj.
// ------------------------------------------------------------
