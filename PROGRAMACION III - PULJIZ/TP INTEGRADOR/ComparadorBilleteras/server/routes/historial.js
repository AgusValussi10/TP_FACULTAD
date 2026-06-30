const router = require('express').Router();
const pool = require('../db');
const authMiddleware = require('../middleware/auth');

router.use(authMiddleware);

// GET /api/historial
router.get('/', async (req, res) => {
  try {
    const [rows] = await pool.query(`
      SELECT h.id, h.monto, h.moneda_destino, h.mejor_tasa, h.total_ars,
             h.consultado_en, b.nombre AS mejor_billetera, b.color_hex
      FROM historial_consultas h
      LEFT JOIN billeteras b ON b.id = h.mejor_billetera_id
      WHERE h.usuario_id = ?
      ORDER BY h.consultado_en DESC
      LIMIT 50
    `, [req.user.id]);
    res.json(rows);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al obtener historial' });
  }
});

// POST /api/historial
router.post('/', async (req, res) => {
  const { monto, moneda_destino, mejor_billetera_id, mejor_tasa, total_ars } = req.body;
  if (!monto) return res.status(400).json({ error: 'monto es requerido' });
  try {
    const [result] = await pool.query(
      'INSERT INTO historial_consultas (usuario_id, monto, moneda_destino, mejor_billetera_id, mejor_tasa, total_ars) VALUES (?, ?, ?, ?, ?, ?)',
      [req.user.id, monto, moneda_destino || 'BRL', mejor_billetera_id || null, mejor_tasa || null, total_ars || null]
    );
    res.status(201).json({ id: result.insertId });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al guardar historial' });
  }
});

module.exports = router;
