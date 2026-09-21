// ============================================================
//  ADAPTADOR DE ENTRADA — la unica capa que sabe que existe HTTP.
//  Su trabajo: traducir HTTP -> caso de uso -> HTTP. Nada mas.
// ============================================================
'use strict';
const express = require('express');
const { z } = require('zod');

const esquemaLectura = z.object({
  sensorId: z.string().min(1).max(64),
  valor: z.number().finite(),
  unidad: z.enum(['C', 'F', '%']),
}).strict();                      // .strict() rechaza claves de mas: lista blanca

function crearRutasLecturas({ registrarLectura, repositorio }) {
  const router = express.Router();

  router.post('/', async (req, res) => {
    const parseo = esquemaLectura.safeParse(req.body);
    if (!parseo.success) {
      return res.status(400).json({
        tipo: 'about:blank', titulo: 'Datos invalidos', estado: 400,
        errores: parseo.error.issues.map((i) => ({ campo: i.path.join('.'), detalle: i.message })),
      });
    }
    const lectura = await registrarLectura(parseo.data);   // <- el caso de uso
    res.status(201).json(lectura.aObjeto());
  });

  router.get('/:sensorId', async (req, res) => {
    res.json(await repositorio.listarPorSensor(req.params.sensorId));
  });

  return router;
}

module.exports = { crearRutasLecturas };
