'use strict';
function crearNotificadorConsola(registrador = console) {
  return {
    async alertar({ sensorId, valor, umbral }) {
      registrador.warn(`ALERTA ${sensorId}: ${valor.toFixed(1)}C supera ${umbral}C`);
    },
  };
}
module.exports = { crearNotificadorConsola };
