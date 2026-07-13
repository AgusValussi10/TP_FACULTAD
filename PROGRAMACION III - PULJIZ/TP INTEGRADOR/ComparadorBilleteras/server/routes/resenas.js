const router = require('express').Router();
const auth = require('../middleware/auth');
const { validarResena, crearResena } = require('../services/resenasService');
  
// Endpoint que crea una reseña nueva y recalcula el rating de la billetera (transacción en el service)
router.post('/', auth, async (req, res) => {
  const { billetera_id, calificacion, comentario } = req.body;

  // Validación de calificación (1-5) y campos requeridos delegada al service
  const error = validarResena({ billetera_id, calificacion });
  if (error) return res.status(400).json({ error });

  try {
    const resena = await crearResena({
      billetera_id,
      usuario_id: req.user.id,
      autor_nombre: req.user.nombre,
      calificacion,
      comentario,
    });
    res.status(201).json(resena);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al guardar la reseña' });
  }
});

module.exports = router;
