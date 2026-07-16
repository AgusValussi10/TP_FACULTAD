// Capa de datos: únicas funciones que tocan `alertas` vía SQL.

const pool = require('../db');

// Función para traer todas las alertas de un usuario, con datos de la billetera asociada
async function findByUsuario(usuario_id) {
  const [rows] = await pool.query(
    `SELECT a.id, a.billetera_id, b.nombre AS billetera_nombre, b.color_hex,
      a.moneda_origen, a.moneda_destino, a.condicion, a.valor_objetivo,
      a.activa, a.disparada_en, a.creado_en
      FROM alertas a
      JOIN billeteras b ON b.id = a.billetera_id
      WHERE a.usuario_id = ?
      ORDER BY a.creado_en DESC`,
    [usuario_id]
  );
  return rows;
}

// Función para insertar una alerta nueva y devolver su id generado
async function insertAlerta({ usuario_id, billetera_id, condicion, valor_objetivo, moneda_destino }) {
  const [result] = await pool.query(
    'INSERT INTO alertas (usuario_id, billetera_id, condicion, valor_objetivo, moneda_destino) VALUES (?, ?, ?, ?, ?)',
    [usuario_id, billetera_id, condicion, valor_objetivo, moneda_destino || 'BRL']
  );
  return result.insertId;
}

// Función para activar/pausar una alerta, verificando que sea del usuario dueño
async function updateActiva({ id, usuario_id, activa }) {
  const [result] = await pool.query(
    'UPDATE alertas SET activa = ? WHERE id = ? AND usuario_id = ?',
    [activa ? 1 : 0, id, usuario_id]
  );
  return result.affectedRows;
}

// Función para eliminar una alerta, verificando que sea del usuario dueño
async function remove({ id, usuario_id }) {
  const [result] = await pool.query(
    'DELETE FROM alertas WHERE id = ? AND usuario_id = ?',
    [id, usuario_id]
  );
  return result.affectedRows;
}

module.exports = { findByUsuario, insertAlerta, updateActiva, remove };
