'use strict';

const log = (req, res, next) => {
  console.log('  [log] entra', req.method, req.url);
  next();                                    // cede
  console.log('  [log] sale ', res.statusCode);   // y vuelve
};

const autenticar = (req, res, next) => {
  if (req.headers.authorization !== 'Bearer secreto') {
    res.statusCode = 401;
    return res.end('no autenticado');         // TERMINA: no llama a next
  }
  req.usuario = { nombre: 'ana' };            // enriquece req para los que siguen
  next();
};

const manejador = (req, res) => {
  res.statusCode = 200;
  res.end(`hola ${req.usuario.nombre}`);
};

// --- Ejecutados a mano, sin ningun framework, para ver la forma
function simular(headers) {
  const req = { method: 'GET', url: '/', headers };
  const res = { statusCode: 0, end(cuerpo) { console.log('  [res]', this.statusCode, cuerpo); } };
  log(req, res, () => autenticar(req, res, () => manejador(req, res)));
}

console.log('--- con credencial');
simular({ authorization: 'Bearer secreto' });

console.log('\n--- sin credencial');
simular({});

console.log(`
Mira el anidamiento de la linea que simula:
    log(req, res, () => autenticar(req, res, () => manejador(req, res)))
Eso es exactamente el callback hell de la clase de asincronia, con otro
disfraz. Con tres middlewares se lee; con quince es ilegible.

El trabajo del framework es UNO SOLO: aplanar ese anidamiento.
`);

