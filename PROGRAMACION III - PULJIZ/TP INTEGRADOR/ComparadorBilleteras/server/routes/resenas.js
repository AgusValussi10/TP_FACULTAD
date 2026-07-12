const router = require('express').Router();
const pool = require('../db');
const auth = require('../middleware/auth');

// POST /api/resenas
router.post('/', auth, async (req, res) => {
  const { billetera_id, calificacion, comentario } = req.body;
  if (!billetera_id || !calificacion) {
    return res.status(400).json({ error: 'billetera_id y calificacion son requeridos' });
  }
  const cal = parseInt(calificacion, 10);
  if (cal < 1 || cal > 5) {
    return res.status(400).json({ error: 'calificacion debe ser entre 1 y 5' });
  }
  try {
    const [result] = await pool.query(
      `INSERT INTO resenas (billetera_id, usuario_id, autor_nombre, calificacion, comentario, fecha_resena)
       VALUES (?, ?, ?, ?, ?, CURDATE())`,
      [billetera_id, req.user.id, req.user.nombre, cal, comentario ?? null]
    );
    await pool.query(
      `UPDATE billeteras
       SET rating_promedio = (SELECT ROUND(AVG(calificacion), 1) FROM resenas WHERE billetera_id = ?),
           cantidad_resenas = (SELECT COUNT(*) FROM resenas WHERE billetera_id = ?)
       WHERE id = ?`,
      [billetera_id, billetera_id, billetera_id]
    );
    res.status(201).json({
      id: result.insertId,
      autor_nombre: req.user.nombre,
      calificacion: cal,
      comentario: comentario ?? null,
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al guardar la reseña' });
  }
});

module.exports = router;
