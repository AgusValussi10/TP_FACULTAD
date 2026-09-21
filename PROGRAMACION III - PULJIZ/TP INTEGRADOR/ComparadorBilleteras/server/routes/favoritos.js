const router = require('express').Router();
const pool = require('../db');
const authMiddleware = require('../middleware/auth');

// Todas las rutas requieren token de usuario logueado
router.use(authMiddleware);

// Endpoint que lista las billeteras favoritas (activas) del usuario autenticado
router.get('/', async (req, res) => {
  try {
    const [rows] = await pool.query(`
      SELECT f.id, f.billetera_id, b.nombre, b.iniciales, b.color_hex, f.creado_en
      FROM favoritos f
      JOIN billeteras b ON b.id = f.billetera_id
      WHERE f.usuario_id = ? AND b.activa = 1
      ORDER BY f.creado_en DESC
    `, [req.user.id]);
    res.json(rows);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al obtener favoritos' });
  }
});

// Endpoint que agrega una billetera a favoritos (INSERT IGNORE evita duplicados)
router.post('/', async (req, res) => {
  const { billetera_id } = req.body;
  if (!billetera_id) return res.status(400).json({ error: 'billetera_id es requerido' });
  try {
    const [result] = await pool.query(
      'INSERT IGNORE INTO favoritos (usuario_id, billetera_id) VALUES (?, ?)',
      [req.user.id, billetera_id]
    );
    res.status(201).json({ id: result.insertId, message: 'Favorito guardado' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al guardar favorito' });
  }
});

// Endpoint que quita una billetera de favoritos
router.delete('/:billetera_id', async (req, res) => {
  try {
    const [result] = await pool.query(
      'DELETE FROM favoritos WHERE usuario_id = ? AND billetera_id = ?',
      [req.user.id, req.params.billetera_id]
    );
    if (result.affectedRows === 0) return res.status(404).json({ error: 'Favorito no encontrado' });
    res.json({ message: 'Favorito eliminado' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al eliminar favorito' });
  }
});

module.exports = router;
