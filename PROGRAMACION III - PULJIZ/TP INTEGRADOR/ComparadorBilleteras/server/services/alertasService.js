// Capa de negocio: validación y reglas sobre alertas.

const repo = require('../repositories/alertasRepository');

const CONDICIONES_VALIDAS = ['supera', 'baja_de'];

function validarAlerta({ billetera_id, condicion, valor_objetivo }) {
  if (!billetera_id || !condicion || !valor_objetivo) {
    return 'billetera_id, condicion y valor_objetivo son requeridos';
  }
  if (!CONDICIONES_VALIDAS.includes(condicion)) {
    return 'condicion debe ser "supera" o "baja_de"';
  }
  return null;
}

function listar(usuario_id) {
  return repo.findByUsuario(usuario_id);
}

async function crear({ usuario_id, billetera_id, condicion, valor_objetivo, moneda_destino }) {
  const id = await repo.insertAlerta({ usuario_id, billetera_id, condicion, valor_objetivo, moneda_destino });
  return { id };
}

async function actualizarActiva({ id, usuario_id, activa }) {
  const affectedRows = await repo.updateActiva({ id, usuario_id, activa });
  return affectedRows > 0;
}

async function eliminar({ id, usuario_id }) {
  const affectedRows = await repo.remove({ id, usuario_id });
  return affectedRows > 0;
}

module.exports = { validarAlerta, listar, crear, actualizarActiva, eliminar };
