const router = require('express').Router();
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const pool = require('../db');

// POST /api/auth/register
router.post('/register', async (req, res) => {
  const { nombre, email, password } = req.body;
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
    const hash = await bcrypt.hash(password, 10);
    const [result] = await pool.query(
      'INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)',
      [nombre, email, hash]
    );
    res.status(201).json({ message: 'Usuario creado', id: result.insertId });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error interno del servidor' });
  }
});

// POST /api/auth/login
router.post('/login', async (req, res) => {
  const { email, password } = req.body;
  if (!email || !password) {
    return res.status(400).json({ error: 'email y password son requeridos' });
  }
  try {
    const [rows] = await pool.query('SELECT * FROM usuarios WHERE email = ?', [email]);
    if (rows.length === 0) {
      return res.status(401).json({ error: 'Email o contraseña incorrectos' });
    }
    const usuario = rows[0];
    const ok = await bcrypt.compare(password, usuario.password_hash);
    if (!ok) {
      return res.status(401).json({ error: 'Email o contraseña incorrectos' });
    }
    const token = jwt.sign(
      { id: usuario.id, email: usuario.email, nombre: usuario.nombre },
      process.env.JWT_SECRET,
      { expiresIn: '7d' }
    );
    res.json({
      token,
      usuario: { id: usuario.id, nombre: usuario.nombre, email: usuario.email },
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error interno del servidor' });
  }
});

// POST /api/auth/firebase-login  (intercambia Firebase ID token por JWT propio)
router.post('/firebase-login', async (req, res) => {
  const { idToken } = req.body;
  if (!idToken) return res.status(400).json({ error: 'idToken requerido' });

  let email, nombre;

  // Intentar con Firebase Admin SDK si está disponible
  const firebaseAdmin = require('../firebaseAdmin');
  if (firebaseAdmin) {
    try {
      const decoded = await firebaseAdmin.auth.verifyIdToken(idToken);
      email = decoded.email;
      nombre = decoded.name || email.split('@')[0];
    } catch (err) {
      console.error('[firebase-login] Admin SDK falló:', err.message);
      return res.status(401).json({ error: 'Token de Firebase inválido' });
    }
  } else {
    // Fallback: verificar via API pública de Google (no requiere serviceAccountKey)
    try {
      const r = await fetch(`https://oauth2.googleapis.com/tokeninfo?id_token=${idToken}`);
      const info = await r.json();
      if (!r.ok || !info.email) {
        return res.status(401).json({ error: 'Token de Firebase inválido' });
      }
      if (info.email_verified !== 'true') {
        return res.status(401).json({ error: 'Email no verificado' });
      }
      email = info.email;
      nombre = info.name || email.split('@')[0];
    } catch (err) {
      console.error('[firebase-login] tokeninfo falló:', err.message);
      return res.status(503).json({ error: 'No se pudo verificar el token' });
    }
  }

  try {
    let [rows] = await pool.query('SELECT * FROM usuarios WHERE email = ?', [email]);
    let usuario;
    if (rows.length === 0) {
      const [result] = await pool.query(
        'INSERT INTO usuarios (nombre, email, password_hash, pais_residencia) VALUES (?, ?, ?, ?)',
        [nombre, email, '', 'Argentina']
      );
      usuario = { id: result.insertId, nombre, email };
      console.log('[firebase-login] usuario creado en MySQL:', email);
    } else {
      usuario = rows[0];
    }

    const token = jwt.sign(
      { id: usuario.id, email: usuario.email, nombre: usuario.nombre },
      process.env.JWT_SECRET,
      { expiresIn: '7d' }
    );
    res.json({ token, usuario: { id: usuario.id, nombre: usuario.nombre, email: usuario.email } });
  } catch (err) {
    console.error('[firebase-login] DB error:', err.message);
    res.status(500).json({ error: 'Error interno' });
  }
});

// GET /api/auth/me  (requiere token)
const authMiddleware = require('../middleware/auth');
router.get('/me', authMiddleware, async (req, res) => {
  try {
    const [rows] = await pool.query(
      'SELECT id, nombre, email, pais_residencia, moneda_base, creado_en FROM usuarios WHERE id = ?',
      [req.user.id]
    );
    if (rows.length === 0) return res.status(404).json({ error: 'Usuario no encontrado' });
    res.json(rows[0]);
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Error interno del servidor' });
  }
});

module.exports = router;
