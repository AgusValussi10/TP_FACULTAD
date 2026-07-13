// Capa de negocio: validación y reglas sobre alertas.

const repo = require('../repositories/alertasRepository');

const CONDICIONES_VALIDAS = ['supera', 'baja_de'];

// Función para validar los campos requeridos y la condición de una alerta antes de crearla
function validarAlerta({ billetera_id, condicion, valor_objetivo }) {
  if (!billetera_id || !condicion || !valor_objetivo) {
    return 'billetera_id, condicion y valor_objetivo son requeridos';
  }
  if (!CONDICIONES_VALIDAS.includes(condicion)) {
    return 'condicion debe ser "supera" o "baja_de"';
  }
  return null;
}

// Función para listar las alertas de un usuario
function listar(usuario_id) {
  return repo.findByUsuario(usuario_id);
}

// Función para crear una alerta nueva y devolver su id
async function crear({ usuario_id, billetera_id, condicion, valor_objetivo, moneda_destino }) {
  const id = await repo.insertAlerta({ usuario_id, billetera_id, condicion, valor_objetivo, moneda_destino });
  return { id };
}

// Función para activar o pausar una alerta existente
async function actualizarActiva({ id, usuario_id, activa }) {
  const affectedRows = await repo.updateActiva({ id, usuario_id, activa });
  return affectedRows > 0;
}

// Función para eliminar una alerta existente
async function eliminar({ id, usuario_id }) {
  const affectedRows = await repo.remove({ id, usuario_id });
  return affectedRows > 0;
}

module.exports = { validarAlerta, listar, crear, actualizarActiva, eliminar };
