'use strict';
// Adaptador de salida. Implementa el "puerto" que el caso de uso espera:
// un objeto con guardar() y listarPorSensor(). El caso de uso no sabe si
// esto es memoria, Postgres o Firebase.
function crearRepositorioEnMemoria() {
  const filas = [];
  return {
    async guardar(lectura) { filas.push(lectura.aObjeto()); },
    async listarPorSensor(sensorId) { return filas.filter((f) => f.sensorId === sensorId); },
  };
}
module.exports = { crearRepositorioEnMemoria };
