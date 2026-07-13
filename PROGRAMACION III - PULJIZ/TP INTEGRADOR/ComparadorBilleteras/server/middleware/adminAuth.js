const jwt = require('jsonwebtoken');

// Middleware que valida el JWT de administrador (rol 'admin') y cuelga req.adminUsuario
module.exports = function adminAuth(req, res, next) {
  const header = req.headers['authorization'];
  if (!header || !header.startsWith('Bearer ')) {
    return res.status(401).json({ error: 'Token de administrador requerido' });
  }
  const token = header.slice(7);
  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    // Solo deja pasar tokens emitidos con rol admin (POST /api/admin/login)
    if (decoded.rol !== 'admin') {
      return res.status(403).json({ error: 'Acceso denegado' });
    }
    // Usuario admin logueado, usado luego para auditoría (modificado_por)
    req.adminUsuario = decoded.adminUsuario;
    next();
  } catch {
    return res.status(401).json({ error: 'Token inválido o expirado' });
  }
};
