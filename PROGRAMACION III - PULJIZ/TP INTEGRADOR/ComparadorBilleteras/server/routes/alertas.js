const router = require('express').Router();
const authMiddleware = require('../middleware/auth');
const service = require('../services/alertasService');

// Todas las rutas requieren token de usuario logueado
router.use(authMiddleware);

// Endpoint que lista las alertas del usuario autenticado
router.get('/', async (req, res) => {
  try {
    const alertas = await service.listar(req.user.id);
    res.json(alertas);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al obtener alertas' });
  }
});

// Endpoint que crea una alerta nueva para el usuario autenticado
router.post('/', async (req, res) => {
  const { billetera_id, condicion, valor_objetivo, moneda_destino } = req.body;

  // Validación de campos y condición delegada al service
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

// Endpoint que activa o pausa una alerta existente del usuario autenticado
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

// Endpoint que elimina una alerta del usuario autenticado
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
