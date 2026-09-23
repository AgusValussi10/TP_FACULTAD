// ============================================================
//  COMPOSITION ROOT — el unico archivo donde se decide QUE
//  implementacion concreta usa cada puerto.
//  Es el reemplazo del contenedor de inyeccion de dependencias.
// ============================================================
'use strict';
const express = require('express');
const { crearRegistrarLectura } = require('./aplicacion/registrarLectura');
const { crearRepositorioEnMemoria } = require('./adaptadores/persistencia/repositorioEnMemoria');
const { crearNotificadorConsola } = require('./adaptadores/persistencia/notificadorConsola');
const { crearRutasLecturas } = require('./adaptadores/http/rutasLecturas');
const { crearManejadorDeErrores } = require('./adaptadores/http/manejadorDeErrores');

function crearApp(config) {
  // 1. adaptadores de salida
  const repositorio = crearRepositorioEnMemoria();
  const notificador = crearNotificadorConsola();

  // 2. casos de uso, armados con esos adaptadores
  const registrarLectura = crearRegistrarLectura({
    repositorio, notificador, umbral: config.umbralAlerta,
  });

  // 3. adaptador de entrada
  const app = express();
  app.use(express.json({ limit: '100kb' }));
  app.get('/salud', (req, res) => res.json({ ok: true, version: config.version }));
  app.use('/lecturas', crearRutasLecturas({ registrarLectura, repositorio }));
  app.use((req, res) => res.status(404).json({ title: 'No encontrado', status: 404 }));
  app.use(crearManejadorDeErrores());
  return app;
}

module.exports = { crearApp };
