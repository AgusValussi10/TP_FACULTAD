// Capa de negocio: validación + orquestación transaccional de reseñas.

const pool = require('../db');
const { insertResena, recalcularRating } = require('../repositories/resenasRepository');

function validarResena({ billetera_id, calificacion }) {
  if (!billetera_id || !calificacion) {
    return 'billetera_id y calificacion son requeridos';
  }
  const cal = parseInt(calificacion, 10);
  if (Number.isNaN(cal) || cal < 1 || cal > 5) {
    return 'calificacion debe ser entre 1 y 5';
  }
  return null;
}

async function crearResena({ billetera_id, usuario_id, autor_nombre, calificacion, comentario }) {
  const cal = parseInt(calificacion, 10);
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();
    const id = await insertResena(conn, { billetera_id, usuario_id, autor_nombre, calificacion: cal, comentario });
    await recalcularRating(conn, billetera_id);
    await conn.commit();
    return { id, autor_nombre, calificacion: cal, comentario: comentario ?? null };
  } catch (err) {
    await conn.rollback();
    throw err;
  } finally {
    conn.release();
  }
}

module.exports = { validarResena, crearResena };
