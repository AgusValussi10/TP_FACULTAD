'use strict';
// Traduce errores del DOMINIO a codigos HTTP. El dominio no sabe que
// existe el 400; esta tabla es la unica que lo sabe.
const MAPA = {
  LECTURA_INVALIDA: 400,
  CONVERSION_INVALIDA: 422,
  NO_ENCONTRADO: 404,
};

function crearManejadorDeErrores({ registrador = console } = {}) {
  return function manejadorDeErrores(err, req, res, next) {
    const estado = MAPA[err.codigo] ?? 500;
    if (estado >= 500) registrador.error({ idPedido: req.idPedido, msg: err.message, stack: err.stack });

    // Formato Problem Details (RFC 9457), el mismo que se usa en la
    // clase de HTTP. No se inventa un formato de error por proyecto.
    res.status(estado).type('application/problem+json').json({
      type: 'about:blank',
      title: estado >= 500 ? 'Error interno' : err.message,
      status: estado,
      instance: req.originalUrl,
      idPedido: req.idPedido,
    });
  };
}
module.exports = { crearManejadorDeErrores };
