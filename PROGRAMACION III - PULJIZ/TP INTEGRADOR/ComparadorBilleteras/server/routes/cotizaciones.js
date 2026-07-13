const router = require('express').Router();
const pool = require('../db');
const authMiddleware = require('../middleware/auth');
const checkAdminAuth = require('../middleware/adminAuth');

// Endpoint que devuelve el ranking de billeteras ordenado por mejor cotización para el monto dado
router.get('/', async (req, res) => {
  const monto = parseFloat(req.query.monto) || 1;
  try {
    // Usa la VIEW cotizaciones_actuales (última tasa por billetera) y calcula el total en ARS con comisión incluida
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

    // Marca la mejor opción y calcula el ahorro de cada billetera contra la peor cotización
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

// Endpoint que devuelve el historial de tasas de una billetera (últimos 30 registros, sin paginar a propósito)
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

// Endpoint admin que carga nuevas tasas en lote — Body: { tasas: [ { billetera_id, tasa }, ... ] }
router.post('/', checkAdminAuth, async (req, res) => {
  const { tasas } = req.body;
  if (!Array.isArray(tasas) || tasas.length === 0) {
    return res.status(400).json({ error: 'tasas debe ser un array no vacío' });
  }
  try {
    // Arma un INSERT múltiple, dejando registrado qué admin cargó cada tasa (auditoría modificado_por)
    const values = tasas.map(t => [t.billetera_id, 'ARS', 'BRL', t.tasa, req.adminUsuario]);
    await pool.query(
      'INSERT INTO cotizaciones (billetera_id, moneda_origen, moneda_destino, tasa, modificado_por) VALUES ?',
      [values]
    );
    res.status(201).json({ message: `${tasas.length} cotizaciones registradas` });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al guardar cotizaciones' });
  }
});

module.exports = router;
