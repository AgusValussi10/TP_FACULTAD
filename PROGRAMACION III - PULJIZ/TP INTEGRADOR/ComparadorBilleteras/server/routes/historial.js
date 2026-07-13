const router = require('express').Router();
const pool = require('../db');
const authMiddleware = require('../middleware/auth');

// Todas las rutas requieren token de usuario logueado
router.use(authMiddleware);

// Endpoint que devuelve el historial de consultas del usuario, paginado real (LIMIT/OFFSET en la DB)
router.get('/', async (req, res) => {
  // Normaliza page/limit para evitar valores inválidos o negativos, limit tope 100
  const page = Math.max(parseInt(req.query.page) || 1, 1);
  const limit = Math.min(Math.max(parseInt(req.query.limit) || 10, 1), 100);
  const offset = (page - 1) * limit;
  try {
    // Total de registros del usuario, usado para calcular totalPages
    const [[{ total }]] = await pool.query(
      'SELECT COUNT(*) AS total FROM historial_consultas WHERE usuario_id = ?',
      [req.user.id]
    );

    // Trae solo la página pedida, con el nombre de la billetera ganadora de cada consulta
    const [rows] = await pool.query(`
      SELECT h.id, h.monto, h.moneda_destino, h.mejor_tasa, h.total_ars,
             h.consultado_en, b.nombre AS mejor_billetera, b.color_hex
      FROM historial_consultas h
      LEFT JOIN billeteras b ON b.id = h.mejor_billetera_id
      WHERE h.usuario_id = ?
      ORDER BY h.consultado_en DESC
      LIMIT ? OFFSET ?
    `, [req.user.id, limit, offset]);

    res.json({ resultados: rows, page, limit, total, totalPages: Math.ceil(total / limit) || 1 });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al obtener historial' });
  }
});

// Endpoint que guarda una consulta nueva en el historial del usuario autenticado
router.post('/', async (req, res) => {
  if (!req.body) return res.status(400).json({ error: 'Body requerido' });
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
