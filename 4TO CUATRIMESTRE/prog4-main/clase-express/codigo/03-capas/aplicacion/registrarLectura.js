// ============================================================
//  APLICACION — el caso de uso. Orquesta, no decide reglas.
//  Recibe sus dependencias como PARAMETROS (composicion, no import).
// ============================================================
'use strict';
const { Lectura } = require('../dominio/Lectura');

function crearRegistrarLectura({ repositorio, notificador, umbral, reloj = () => new Date() }) {
  return async function registrarLectura(datos) {
    const lectura = new Lectura({ ...datos, tomadaEn: reloj() });   // valida el dominio
    await repositorio.guardar(lectura);

    if (lectura.superaUmbral(umbral)) {
      await notificador.alertar({
        sensorId: lectura.sensorId,
        valor: lectura.enCelsius(),
        umbral,
      });
    }
    return lectura;
  };
}

module.exports = { crearRegistrarLectura };
