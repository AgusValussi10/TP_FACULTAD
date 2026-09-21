require('dotenv').config();
const express = require('express');
const cors = require('cors');
const path = require('path');

const app = express();

app.use(cors()); //permite que la app pueda pegarle a esta API sin que el navegador/cliente la bloquee por política de CORS
app.use(express.json()); //parsea automáticamente el body de las requests que vienen en JSON

// Panel admin (HTML estático)
app.use('/admin', express.static(path.join(__dirname, 'admin')));

// Rutas API — cada módulo maneja su propio prefijo /api/<recurso>
app.use('/api/auth',        require('./routes/auth'));
app.use('/api/cotizaciones',require('./routes/cotizaciones'));
app.use('/api/billeteras',  require('./routes/billeteras'));
app.use('/api/alertas',     require('./routes/alertas'));
app.use('/api/historial',   require('./routes/historial'));
app.use('/api/favoritos',   require('./routes/favoritos'));
app.use('/api/resenas',     require('./routes/resenas'));
app.use('/api/admin',       require('./routes/admin'));

// Endpoint que confirma que la API está levantada
app.get('/', (req, res) => {
  res.json({ status: 'ok', app: 'BrasilPagos API', version: '1.0.0' });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`BrasilPagos API corriendo en http://localhost:${PORT}`);
  console.log(`Panel admin en http://localhost:${PORT}/admin`);
});
