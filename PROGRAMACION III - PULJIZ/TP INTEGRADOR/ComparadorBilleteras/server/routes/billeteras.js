const router = require('express').Router();
const pool = require('../db');

// Endpoint que lista las billeteras activas, con filtro opcional por nombre y país resuelto en SQL
router.get('/', async (req, res) => {
  const { nombre, pais } = req.query;
  try {
    // Arma el WHERE dinámico: siempre solo activas, suma condiciones según los filtros recibidos
    const condiciones = ['b.activa = 1'];
    const params = [];
    let joinPaises = '';

    if (nombre) {
      // Filtro por nombre parcial (case-insensitive por collation de MySQL)
      condiciones.push('b.nombre LIKE ?');
      params.push(`%${nombre}%`);
    }
    if (pais) {
      // Filtro por país: requiere JOIN contra billetera_paises (relación 1:N)
      joinPaises = 'JOIN billetera_paises bp ON bp.billetera_id = b.id';
      condiciones.push('bp.codigo_pais = ?');
      params.push(pais.toUpperCase());
    }

    // DISTINCT evita filas repetidas cuando el JOIN por país matchea más de un país por billetera
    const [billeteras] = await pool.query(`
      SELECT DISTINCT b.id, b.nombre, b.iniciales, b.color_hex, b.descripcion,
             b.url_oficial, b.rating_promedio, b.cantidad_resenas,
             bc.comision_pct, bc.limite_diario_brl, bc.limite_mensual_brl, bc.tiempo_estimado
      FROM billeteras b
      JOIN billetera_condiciones bc ON bc.billetera_id = b.id
      ${joinPaises}
      WHERE ${condiciones.join(' AND ')}
      ORDER BY b.rating_promedio DESC
    `, params);
    res.json(billeteras);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al obtener billeteras' });
  }
});

// Endpoint que devuelve el detalle completo de una billetera (condiciones, países, monedas, pros/contras, reseñas)
router.get('/:id', async (req, res) => {
  const { id } = req.params;
  try {
    const [[billetera]] = await pool.query(`
      SELECT b.id, b.nombre, b.iniciales, b.color_hex, b.descripcion,
             b.url_oficial, b.rating_promedio, b.cantidad_resenas,
             bc.comision_pct, bc.limite_diario_brl, bc.limite_mensual_brl,
             bc.tiempo_estimado, bc.detalle_comision
      FROM billeteras b
      JOIN billetera_condiciones bc ON bc.billetera_id = b.id
      WHERE b.id = ? AND b.activa = 1
    `, [id]);

    if (!billetera) return res.status(404).json({ error: 'Billetera no encontrada' });

    // Trae las tablas relacionadas 1:N por separado para armar la respuesta anidada
    const [paises] = await pool.query(
      'SELECT codigo_pais, metodo_pago FROM billetera_paises WHERE billetera_id = ?', [id]
    );
    const [monedas] = await pool.query(
      'SELECT moneda FROM billetera_monedas WHERE billetera_id = ?', [id]
    );
    const [pros] = await pool.query(
      'SELECT descripcion FROM billetera_pros_contras WHERE billetera_id = ? AND tipo = "pro" ORDER BY orden', [id]
    );
    const [contras] = await pool.query(
      'SELECT descripcion FROM billetera_pros_contras WHERE billetera_id = ? AND tipo = "contra" ORDER BY orden', [id]
    );
    const [requisitos] = await pool.query(
      'SELECT descripcion FROM billetera_requisitos WHERE billetera_id = ? ORDER BY orden', [id]
    );
    const [resenas] = await pool.query(
      'SELECT autor_nombre, calificacion, comentario, fecha_resena FROM resenas WHERE billetera_id = ? ORDER BY fecha_resena DESC LIMIT 5', [id]
    );

    res.json({
      ...billetera,
      paises,
      monedas: monedas.map(m => m.moneda),
      pros: pros.map(p => p.descripcion),
      contras: contras.map(c => c.descripcion),
      requisitos: requisitos.map(r => r.descripcion),
      resenas,
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al obtener billetera' });
  }
});

module.exports = router;
