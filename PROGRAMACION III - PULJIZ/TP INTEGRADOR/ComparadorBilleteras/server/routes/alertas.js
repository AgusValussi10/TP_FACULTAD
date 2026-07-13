const router = require('express').Router();
const authMiddleware = require('../middleware/auth');
const service = require('../services/alertasService');

// Todas las rutas requieren token
router.use(authMiddleware);

// GET /api/alertas
router.get('/', async (req, res) => {
  try {
    const alertas = await service.listar(req.user.id);
    res.json(alertas);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al obtener alertas' });
  }
});

// POST /api/alertas
router.post('/', async (req, res) => {
  const { billetera_id, condicion, valor_objetivo, moneda_destino } = req.body;

  const error = service.validarAlerta({ billetera_id, condicion, valor_objetivo });
  if (error) return res.status(400).json({ error });

  try {
    const { id } = await service.crear({ usuario_id: req.user.id, billetera_id, condicion, valor_objetivo, moneda_destino });
    res.status(201).json({ id, message: 'Alerta creada' });
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
    const ok = await service.actualizarActiva({ id: req.params.id, usuario_id: req.user.id, activa });
    if (!ok) return res.status(404).json({ error: 'Alerta no encontrada' });
    res.json({ message: 'Alerta actualizada' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al actualizar alerta' });
  }
});

// DELETE /api/alertas/:id
router.delete('/:id', async (req, res) => {
  try {
    const ok = await service.eliminar({ id: req.params.id, usuario_id: req.user.id });
    if (!ok) return res.status(404).json({ error: 'Alerta no encontrada' });
    res.json({ message: 'Alerta eliminada' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al eliminar alerta' });
  }
});

module.exports = router;
