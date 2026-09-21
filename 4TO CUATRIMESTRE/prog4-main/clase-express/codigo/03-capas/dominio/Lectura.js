// ============================================================
//  DOMINIO — no conoce Express, ni HTTP, ni la base de datos.
//  Si mañana el sistema se opera por MQTT o por linea de comandos,
//  este archivo no se toca.
// ============================================================
'use strict';

class ErrorDeDominio extends Error {
  constructor(mensaje, codigo) {
    super(mensaje);
    this.name = 'ErrorDeDominio';
    this.codigo = codigo;         // codigo del DOMINIO, no un status HTTP
  }
}

class Lectura {
  #sensorId; #valor; #unidad; #tomadaEn;

  constructor({ sensorId, valor, unidad, tomadaEn }) {
    if (!sensorId) throw new ErrorDeDominio('sensorId requerido', 'LECTURA_INVALIDA');
    if (!Number.isFinite(valor)) throw new ErrorDeDominio('valor debe ser finito', 'LECTURA_INVALIDA');
    if (!['C', 'F', '%'].includes(unidad)) throw new ErrorDeDominio('unidad no soportada', 'LECTURA_INVALIDA');
    this.#sensorId = sensorId;
    this.#valor = valor;
    this.#unidad = unidad;
    this.#tomadaEn = tomadaEn ?? new Date();
  }

  get sensorId() { return this.#sensorId; }
  get valor() { return this.#valor; }
  get unidad() { return this.#unidad; }
  get tomadaEn() { return this.#tomadaEn; }

  enCelsius() {
    if (this.#unidad === 'C') return this.#valor;
    if (this.#unidad === 'F') return (this.#valor - 32) * 5 / 9;
    throw new ErrorDeDominio('no es una temperatura', 'CONVERSION_INVALIDA');
  }

  superaUmbral(umbral) { return this.enCelsius() > umbral; }

  aObjeto() {
    return { sensorId: this.#sensorId, valor: this.#valor,
             unidad: this.#unidad, tomadaEn: this.#tomadaEn.toISOString() };
  }
}

module.exports = { Lectura, ErrorDeDominio };
