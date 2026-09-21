// Capa de datos: única responsable de tocar `resenas` y el rating agregado en `billeteras`.
// Recibe una conexión (no el pool) para poder participar en la transacción del service.

// Función para insertar una reseña nueva y devolver su id generado (participa de la transacción del service)
async function insertResena(conn, { billetera_id, usuario_id, autor_nombre, calificacion, comentario }) {
  const [result] = await conn.query(
    `INSERT INTO resenas (billetera_id, usuario_id, autor_nombre, calificacion, comentario, fecha_resena)
     VALUES (?, ?, ?, ?, ?, CURDATE())`,
    [billetera_id, usuario_id, autor_nombre, calificacion, comentario ?? null]
  );
  return result.insertId;
}

// Función para recalcular el rating promedio y la cantidad de reseñas de una billetera
async function recalcularRating(conn, billetera_id) {
  await conn.query(
    `UPDATE billeteras
     SET rating_promedio = (SELECT ROUND(AVG(calificacion), 1) FROM resenas WHERE billetera_id = ?),
         cantidad_resenas = (SELECT COUNT(*) FROM resenas WHERE billetera_id = ?)
     WHERE id = ?`,
    [billetera_id, billetera_id, billetera_id]
  );
}

module.exports = { insertResena, recalcularRating };
