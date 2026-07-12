const router = require('express').Router();
const pool = require('../db');
const authMiddleware = require('../middleware/auth');

// Todas las rutas requieren token
router.use(authMiddleware);

// GET /api/alertas
router.get('/', async (req, res) => {
  try {
    const [rows] = await pool.query(`
      SELECT a.id, a.billetera_id, b.nombre AS billetera_nombre, b.color_hex,
             a.moneda_origen, a.moneda_destino, a.condicion, a.valor_objetivo,
             a.activa, a.disparada_en, a.creado_en
      FROM alertas a
      JOIN billeteras b ON b.id = a.billetera_id
      WHERE a.usuario_id = ?
      ORDER BY a.creado_en DESC
    `, [req.user.id]);
    res.json(rows);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al obtener alertas' });
  }
});

// POST /api/alertas
router.post('/', async (req, res) => {
  const { billetera_id, condicion, valor_objetivo, moneda_destino } = req.body;
  if (!billetera_id || !condicion || !valor_objetivo) {
    return res.status(400).json({ error: 'billetera_id, condicion y valor_objetivo son requeridos' });
  }
  if (!['supera', 'baja_de'].includes(condicion)) {
    return res.status(400).json({ error: 'condicion debe ser "supera" o "baja_de"' });
  }
  try {
    const [result] = await pool.query(
      'INSERT INTO alertas (usuario_id, billetera_id, condicion, valor_objetivo, moneda_destino) VALUES (?, ?, ?, ?, ?)',
      [req.user.id, billetera_id, condicion, valor_objetivo, moneda_destino || 'BRL']
    );
    res.status(201).json({ id: result.insertId, message: 'Alerta creada' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al crear alerta' });
  }
});

// PATCH /api/alertas/:id  — activar/desactivar
router.patch('/:id', async (req, res) => {
  const { activa } = req.body;
  if (activa === undefined) return res.status(400).json({ error: 'activa es requerido' });
  try {
    const [result] = await pool.query(
      'UPDATE alertas SET activa = ? WHERE id = ? AND usuario_id = ?',
      [activa ? 1 : 0, req.params.id, req.user.id]
    );
    if (result.affectedRows === 0) return res.status(404).json({ error: 'Alerta no encontrada' });
    res.json({ message: 'Alerta actualizada' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al actualizar alerta' });
  }
});

// DELETE /api/alertas/:id
router.delete('/:id', async (req, res) => {
  try {
    const [result] = await pool.query(
      'DELETE FROM alertas WHERE id = ? AND usuario_id = ?',
      [req.params.id, req.user.id]
    );
    if (result.affectedRows === 0) return res.status(404).json({ error: 'Alerta no encontrada' });
    res.json({ message: 'Alerta eliminada' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al eliminar alerta' });
  }
});

module.exports = router;
