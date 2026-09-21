// Capa de negocio: validación + orquestación transaccional de reseñas.

const pool = require('../db');
const { insertResena, recalcularRating } = require('../repositories/resenasRepository');

// Función para validar que la calificación esté en el rango 1-5 y los campos requeridos estén presentes
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

// Función para crear una reseña y recalcular el rating de la billetera en una misma transacción
async function crearResena({ billetera_id, usuario_id, autor_nombre, calificacion, comentario }) {
  const cal = parseInt(calificacion, 10);
  const conn = await pool.getConnection();
  try {
    // Arranca la transacción: si falla el recálculo del rating, no debe quedar la reseña insertada sola
    await conn.beginTransaction();
    const id = await insertResena(conn, { billetera_id, usuario_id, autor_nombre, calificacion: cal, comentario });
    await recalcularRating(conn, billetera_id);
    await conn.commit();
    return { id, autor_nombre, calificacion: cal, comentario: comentario ?? null };
  } catch (err) {
    // Ante cualquier error en el insert o el recálculo, se revierte todo
    await conn.rollback();
    throw err;
  } finally {
    conn.release();
  }
}

module.exports = { validarResena, crearResena };
