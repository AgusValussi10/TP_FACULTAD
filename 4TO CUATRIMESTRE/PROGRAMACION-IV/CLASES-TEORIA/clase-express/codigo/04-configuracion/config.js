// ============================================================
//  4. Configuracion segun los doce factores
// ============================================================
//  Factor III: la configuracion vive en el ENTORNO, no en el codigo.
//  Corolario que casi nadie aplica: si la configuracion esta mal, el
//  proceso NO ARRANCA. No arranca y falla en el primer pedido: no
//  arranca y punto.
'use strict';
const { z } = require('zod');

const esquema = z.object({
  NODE_ENV: z.enum(['development', 'test', 'production']).default('development'),
  PORT: z.coerce.number().int().min(1).max(65535).default(3000),
  UMBRAL_ALERTA: z.coerce.number().finite(),
  DATABASE_URL: z.string().url(),
  LOG_LEVEL: z.enum(['debug', 'info', 'warn', 'error']).default('info'),
  API_TOKEN: z.string().min(32, 'el token debe tener al menos 32 caracteres'),
});

function cargarConfig(entorno = process.env) {
  const r = esquema.safeParse(entorno);
  if (!r.success) {
    console.error('Configuracion invalida. El proceso no puede arrancar:\n');
    for (const i of r.error.issues) console.error(`  ${i.path.join('.')}: ${i.message}`);
    console.error('');
    process.exit(1);                       // fallar rapido y RUIDOSO
  }
  const c = r.data;
  return Object.freeze({                   // inmutable: nadie la cambia despues
    entorno: c.NODE_ENV,
    puerto: c.PORT,
    umbralAlerta: c.UMBRAL_ALERTA,
    urlBaseDatos: c.DATABASE_URL,
    nivelLog: c.LOG_LEVEL,
    apiToken: c.API_TOKEN,
    esProduccion: c.NODE_ENV === 'production',
  });
}

module.exports = { cargarConfig, esquema };
