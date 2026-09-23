// ============================================================
//  EL SERVIDOR QUE VAMOS A ENTENDER HOY
// ============================================================
//
//  No hay que instalar nada. No hay carpetas de configuración.
//  No hay clases, ni anotaciones, ni archivos XML.
//
//  Son quince líneas.
//
//  Ejecutalo:   node servidor-gancho.js
//  Después abrí:  http://localhost:3000
//
//  Al final de la clase vas a entender cada línea de este archivo,
//  y vas a haber escrito uno igual desde cero.
// ============================================================

const http = require('node:http');

const servidor = http.createServer((req, res) => {
  res.writeHead(200, { 'content-type': 'application/json' });
  res.end(
    JSON.stringify({
      mensaje: 'Hola desde Node',
      ruta: req.url,
      metodo: req.method,
    }),
  );
});

servidor.listen(3000, () => {
  console.log('Servidor escuchando en http://localhost:3000');
});
