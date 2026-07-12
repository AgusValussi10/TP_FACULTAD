const router = require('express').Router();
const pool = require('../db');
const authMiddleware = require('../middleware/auth');

// GET /api/cotizaciones?monto=500
// Devuelve el ranking de billeteras ordenado por mejor cotización
router.get('/', async (req, res) => {
  const monto = parseFloat(req.query.monto) || 1;
  try {
    const [rows] = await pool.query(`
      SELECT
        ca.billetera_id,
        ca.nombre,
        ca.iniciales,
        ca.color_hex,
        ca.comision_pct,
        ca.limite_diario_brl,
        ca.tiempo_estimado,
        ca.tasa,
        ca.registrado_en,
        ROUND(ca.tasa * ? * (1 + ca.comision_pct / 100), 2) AS total_ars
      FROM cotizaciones_actuales ca
      ORDER BY total_ars ASC
    `, [monto]);

    if (rows.length === 0) {
      return res.json({ monto, moneda: 'BRL', resultados: [] });
    }

    const peorTotal = rows[rows.length - 1].total_ars;
    const resultados = rows.map((r, i) => ({
      ...r,
      es_mejor: i === 0,
      ahorro_ars: i < rows.length - 1 ? Math.round(peorTotal - r.total_ars) : null,
    }));

    res.json({ monto, moneda: 'BRL', resultados });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al obtener cotizaciones' });
  }
});

// GET /api/cotizaciones/historial?billetera_id=1
// Historial de tasas de una billetera (últimos 30 registros)
router.get('/historial', async (req, res) => {
  const { billetera_id } = req.query;
  if (!billetera_id) return res.status(400).json({ error: 'billetera_id es requerido' });
  try {
    const [rows] = await pool.query(`
      SELECT tasa, registrado_en
      FROM cotizaciones
      WHERE billetera_id = ? AND moneda_origen = 'ARS' AND moneda_destino = 'BRL'
      ORDER BY registrado_en DESC
      LIMIT 30
    `, [billetera_id]);
    res.json(rows);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al obtener historial' });
  }
});

// POST /api/cotizaciones  (solo admin — usa header X-Admin-Key)
// Body: { tasas: [ { billetera_id, tasa }, ... ] }
router.post('/', async (req, res) => {
  if (req.headers['x-admin-key'] !== process.env.JWT_SECRET) {
    return res.status(403).json({ error: 'No autorizado' });
  }
  const { tasas } = req.body;
  if (!Array.isArray(tasas) || tasas.length === 0) {
    return res.status(400).json({ error: 'tasas debe ser un array no vacío' });
  }
  try {
    const values = tasas.map(t => [t.billetera_id, 'ARS', 'BRL', t.tasa]);
    await pool.query(
      'INSERT INTO cotizaciones (billetera_id, moneda_origen, moneda_destino, tasa) VALUES ?',
      [values]
    );
    res.status(201).json({ message: `${tasas.length} cotizaciones registradas` });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al guardar cotizaciones' });
  }
});

module.exports = router;
