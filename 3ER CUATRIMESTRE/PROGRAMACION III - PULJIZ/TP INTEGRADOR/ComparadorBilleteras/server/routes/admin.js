const router        = require('express').Router();
const pool          = require('../db');
const bcrypt        = require('bcryptjs');
const jwt           = require('jsonwebtoken');
const firebaseAdmin = require('../firebaseAdmin');
const checkAdminAuth = require('../middleware/adminAuth');

// Endpoint que loguea a un administrador contra la tabla admin_usuarios y devuelve un JWT con rol admin
router.post('/login', async (req, res) => {
  const { usuario, password } = req.body;
  if (!usuario || !password) {
    return res.status(400).json({ error: 'usuario y password son requeridos' });
  }
  try {
    const [rows] = await pool.query('SELECT * FROM admin_usuarios WHERE usuario = ?', [usuario]);
    if (rows.length === 0) {
      return res.status(401).json({ error: 'Usuario o contraseña incorrectos' });
    }
    const admin = rows[0];
    const ok = await bcrypt.compare(password, admin.password_hash);
    if (!ok) {
      return res.status(401).json({ error: 'Usuario o contraseña incorrectos' });
    }
    // JWT de admin, expira en 8h, incluye el rol para que adminAuth.js lo valide
    const token = jwt.sign(
      { adminUsuario: admin.usuario, rol: 'admin' },
      process.env.JWT_SECRET,
      { expiresIn: '8h' }
    );
    res.json({ token, adminUsuario: admin.usuario, nombreVisible: admin.nombre_visible });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error interno del servidor' });
  }
});

// Endpoint que devuelve los contadores del dashboard admin (usuarios, billeteras, consultas, alertas, hoy)
router.get('/stats', checkAdminAuth, async (req, res) => {
  try {
    const [[{ usuarios }]]  = await pool.query('SELECT COUNT(*) as usuarios FROM usuarios');
    const [[{ billeteras }]]= await pool.query('SELECT COUNT(*) as billeteras FROM billeteras');
    const [[{ consultas }]] = await pool.query('SELECT COUNT(*) as consultas FROM historial_consultas');
    const [[{ alertas }]]   = await pool.query('SELECT COUNT(*) as alertas FROM alertas WHERE activa = 1');
    const [[{ hoy }]]       = await pool.query(
      'SELECT COUNT(*) as hoy FROM historial_consultas WHERE DATE(consultado_en) = CURDATE()'
    );
    res.json({ usuarios, billeteras, consultas, alertas, hoy });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error interno' });
  }
});

// Endpoint que lista todos los usuarios registrados en MySQL
router.get('/usuarios', checkAdminAuth, async (req, res) => {
  try {
    const [rows] = await pool.query(
      'SELECT id, nombre, email, pais_residencia, moneda_base, creado_en FROM usuarios ORDER BY creado_en DESC'
    );
    res.json(rows);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error interno del servidor' });
  }
});

// Endpoint que crea un usuario nuevo tanto en MySQL como en Firebase (si está configurado)
router.post('/usuarios', checkAdminAuth, async (req, res) => {
  const { nombre, email, password, pais_residencia } = req.body;
  if (!nombre || !email || !password) {
    return res.status(400).json({ error: 'nombre, email y password son requeridos' });
  }
  if (nombre.trim().length < 2) {
    return res.status(400).json({ error: 'El nombre debe tener al menos 2 caracteres' });
  }
  if (password.length < 6) {
    return res.status(400).json({ error: 'La contraseña debe tener al menos 6 caracteres' });
  }
  try {
    const [existing] = await pool.query('SELECT id FROM usuarios WHERE email = ?', [email]);
    if (existing.length > 0) {
      return res.status(409).json({ error: 'Ya existe una cuenta con ese email' });
    }

    // Crear en Firebase si está disponible (sin bloquear la creación en MySQL si falla)
    let firebaseCreado = false;
    if (firebaseAdmin) {
      try {
        await firebaseAdmin.auth.createUser({
          email,
          password,
          displayName: nombre,
          emailVerified: true,
        });
        firebaseCreado = true;
      } catch (fbErr) {
        if (fbErr.code === 'auth/email-already-exists') {
          return res.status(409).json({ error: 'Ya existe una cuenta Firebase con ese email' });
        }
        console.error('[firebase] Error al crear usuario:', fbErr.message);
      }
    }

    // Crear en MySQL
    const hash = await bcrypt.hash(password, 10);
    const [result] = await pool.query(
      'INSERT INTO usuarios (nombre, email, password_hash, pais_residencia) VALUES (?, ?, ?, ?)',
      [nombre, email, hash, pais_residencia || 'AR']
    );

    res.status(201).json({
      message: 'Usuario creado' + (firebaseCreado ? ' en MySQL y Firebase' : ' solo en MySQL'),
      id: result.insertId,
      firebase: firebaseCreado,
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error interno del servidor' });
  }
});

// Endpoint que edita nombre/email/país de un usuario en MySQL y sincroniza el nombre en Firebase
router.put('/usuarios/:id', checkAdminAuth, async (req, res) => {
  const { nombre, email, pais_residencia } = req.body;
  const { id } = req.params;
  if (!nombre || !email) {
    return res.status(400).json({ error: 'nombre y email son requeridos' });
  }
  try {
    // Evita que el nuevo email choque con el de otro usuario
    const [conflict] = await pool.query(
      'SELECT id FROM usuarios WHERE email = ? AND id != ?',
      [email, id]
    );
    if (conflict.length > 0) {
      return res.status(409).json({ error: 'Ese email ya está en uso por otro usuario' });
    }
    const [result] = await pool.query(
      'UPDATE usuarios SET nombre = ?, email = ?, pais_residencia = ? WHERE id = ?',
      [nombre, email, pais_residencia || 'AR', id]
    );
    if (result.affectedRows === 0) {
      return res.status(404).json({ error: 'Usuario no encontrado' });
    }

    // Sincronizar displayName en Firebase
    if (firebaseAdmin) {
      try {
        const userRecord = await firebaseAdmin.auth.getUserByEmail(email);
        await firebaseAdmin.auth.updateUser(userRecord.uid, { displayName: nombre });
      } catch (fbErr) {
        console.warn('[firebase] No se pudo actualizar displayName:', fbErr.message);
      }
    }

    res.json({ message: 'Usuario actualizado' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error interno del servidor' });
  }
});

// Endpoint que elimina un usuario de MySQL y, si existe, también de Firebase
router.delete('/usuarios/:id', checkAdminAuth, async (req, res) => {
  try {
    const [[usuario]] = await pool.query('SELECT email FROM usuarios WHERE id = ?', [req.params.id]);
    if (!usuario) {
      return res.status(404).json({ error: 'Usuario no encontrado' });
    }

    // Si no está en Firebase (auth/user-not-found) no se considera error, solo se borra de MySQL
    let firebaseEliminado = false;
    if (firebaseAdmin) {
      try {
        const userRecord = await firebaseAdmin.auth.getUserByEmail(usuario.email);
        await firebaseAdmin.auth.deleteUser(userRecord.uid);
        firebaseEliminado = true;
      } catch (fbErr) {
        if (fbErr.code !== 'auth/user-not-found') {
          console.warn('[firebase] No se pudo eliminar usuario:', fbErr.message);
        }
      }
    }

    await pool.query('DELETE FROM usuarios WHERE id = ?', [req.params.id]);
    res.json({
      message: 'Usuario eliminado' + (firebaseEliminado ? ' de MySQL y Firebase' : ' solo de MySQL'),
      firebase: firebaseEliminado,
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error interno del servidor' });
  }
});

// Endpoint que lista todas las billeteras sin filtrar por activa (para el panel admin)
router.get('/billeteras', checkAdminAuth, async (req, res) => {
  try {
    const [rows] = await pool.query(`
      SELECT b.id, b.nombre, b.iniciales, b.color_hex, b.activa,
             b.rating_promedio, b.cantidad_resenas,
             b.actualizado_en, b.modificado_por,
             bc.comision_pct
      FROM billeteras b
      LEFT JOIN billetera_condiciones bc ON bc.billetera_id = b.id
      ORDER BY b.id ASC
    `);
    res.json(rows);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al obtener billeteras' });
  }
});

// Endpoint que actualiza manualmente el rating y cantidad de reseñas de una billetera (deja auditoría modificado_por)
router.put('/billeteras/:id/rating', checkAdminAuth, async (req, res) => {
  const { rating_promedio, cantidad_resenas } = req.body;
  if (rating_promedio == null) return res.status(400).json({ error: 'rating_promedio es requerido' });
  const rating = parseFloat(rating_promedio);
  if (isNaN(rating) || rating < 0 || rating > 5) {
    return res.status(400).json({ error: 'El rating debe ser un número entre 0 y 5' });
  }
  try {
    // Registra qué admin hizo el cambio (modificado_por) además del nuevo rating
    const [result] = await pool.query(
      'UPDATE billeteras SET rating_promedio = ?, cantidad_resenas = ?, modificado_por = ? WHERE id = ?',
      [rating.toFixed(1), cantidad_resenas || 0, req.adminUsuario, req.params.id]
    );
    if (result.affectedRows === 0) return res.status(404).json({ error: 'Billetera no encontrada' });
    res.json({ message: 'Rating actualizado' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error interno' });
  }
});

// Endpoint que muestra/oculta una billetera en la app (toggle del campo activa, deja auditoría modificado_por)
router.patch('/billeteras/:id/toggle', checkAdminAuth, async (req, res) => {
  try {
    const [[b]] = await pool.query('SELECT activa FROM billeteras WHERE id = ?', [req.params.id]);
    if (!b) return res.status(404).json({ error: 'Billetera no encontrada' });
    const nuevo = b.activa ? 0 : 1;
    await pool.query(
      'UPDATE billeteras SET activa = ?, modificado_por = ? WHERE id = ?',
      [nuevo, req.adminUsuario, req.params.id]
    );
    res.json({ activa: nuevo });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al actualizar billetera' });
  }
});

// Endpoint que devuelve todos los usuarios de Firebase Auth + marca cuáles tienen cuenta MySQL
router.get('/firebase-usuarios', checkAdminAuth, async (req, res) => {
  if (!firebaseAdmin) {
    return res.status(503).json({ error: 'Firebase Admin no configurado (falta serviceAccountKey.json)' });
  }
  try {
    // Traer todos los usuarios de Firebase (paginado de a 1000)
    const firebaseUsers = [];
    let pageToken;
    do {
      const result = await firebaseAdmin.auth.listUsers(1000, pageToken);
      result.users.forEach(u => firebaseUsers.push({
        uid:           u.uid,
        email:         u.email || null,
        nombre:        u.displayName || null,
        emailVerified: u.emailVerified,
        proveedor:     u.providerData.map(p => p.providerId).join(', '),
        creado_en:     u.metadata.creationTime,
      }));
      pageToken = result.pageToken;
    } while (pageToken);

    // Cruzar con MySQL por email
    const [mysqlRows] = await pool.query(
      'SELECT id, nombre, email, creado_en FROM usuarios'
    );
    const mysqlByEmail = new Map(mysqlRows.map(r => [r.email.toLowerCase(), r]));
    const firebaseEmails = new Set(firebaseUsers.map(u => (u.email || '').toLowerCase()));

    // Firebase users con su mysql_id si existe
    const merged = firebaseUsers.map(u => ({
      ...u,
      mysql_id: u.email ? (mysqlByEmail.get(u.email.toLowerCase())?.id ?? null) : null,
    }));

    // Agregar usuarios MySQL que NO tienen cuenta Firebase
    mysqlRows.forEach(r => {
      if (!firebaseEmails.has(r.email.toLowerCase())) {
        merged.push({
          uid:           null,
          email:         r.email,
          nombre:        r.nombre,
          emailVerified: false,
          proveedor:     'solo MySQL',
          creado_en:     r.creado_en,
          mysql_id:      r.id,
          mysqlOnly:     true,
        });
      }
    });

    // Ordenar: primero sin Firebase (MySQL-only), luego sin MySQL, luego el resto
    merged.sort((a, b) => {
      if (a.mysqlOnly && !b.mysqlOnly) return -1;
      if (!a.mysqlOnly && b.mysqlOnly) return 1;
      if (a.mysql_id === null && b.mysql_id !== null) return -1;
      if (a.mysql_id !== null && b.mysql_id === null) return 1;
      return new Date(b.creado_en) - new Date(a.creado_en);
    });

    res.json(merged);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al obtener usuarios de Firebase' });
  }
});

// Endpoint que crea el registro MySQL para un usuario que solo existe en Firebase
router.post('/sincronizar-usuario', checkAdminAuth, async (req, res) => {
  const { email, nombre } = req.body;
  if (!email) return res.status(400).json({ error: 'email es requerido' });
  try {
    const [existing] = await pool.query('SELECT id FROM usuarios WHERE email = ?', [email]);
    if (existing.length > 0) {
      return res.status(409).json({ error: 'El usuario ya existe en MySQL' });
    }
    const placeholderHash = await bcrypt.hash(Math.random().toString(36), 10);
    const [result] = await pool.query(
      'INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)',
      [nombre || email.split('@')[0], email, placeholderHash]
    );
    res.json({ message: 'Usuario sincronizado', id: result.insertId });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error al sincronizar usuario' });
  }
});

module.exports = router;
