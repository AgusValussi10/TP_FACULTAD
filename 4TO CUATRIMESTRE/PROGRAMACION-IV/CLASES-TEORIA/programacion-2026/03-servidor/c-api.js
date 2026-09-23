// ============================================================
//  9. UNA API DE VERDAD (chiquita)
// ============================================================
//
//  Ejecutalo:  node c-api.js
//
//  Acá se junta TODO lo de la clase:
//    · objetos y arrays        (los datos)
//    · filter y map            (las consultas)
//    · funciones como valores  (el manejador de reqs)
//    · desestructuración       (leer el req)
//    · JSON                    (la res)
//    · módulos                 (require)
//
//  Probá estas direcciones en el navegador:
//    http://localhost:3000/alumnos
//    http://localhost:3000/alumnos/aprobados
//    http://localhost:3000/alumnos/12345
//    http://localhost:3000/estadisticas
// ============================================================

const { errorMonitor } = require('node:events');
const http = require('node:http');

// ------------------------------------------------------------
//  LOS DATOS
// ------------------------------------------------------------
//
//  En una API real esto vendría de una base de datos. Por ahora
//  es un array en memoria: se pierde al reiniciar el proceso.

const alumnos = [
  { legajo: 12345, nombre: 'Ana Gómez', nota: 8 },
  { legajo: 12346, nombre: 'Beto Ruiz', nota: 4 },
  { legajo: 12347, nombre: 'Carla Díaz', nota: 9 },
  { legajo: 12348, nombre: 'Diego Paz', nota: 2 },
  { legajo: 12349, nombre: 'Elena Sosa', nota: 7 },
];

// ------------------------------------------------------------
//  UNA AYUDA PARA NO REPETIR CÓDIGO
// ------------------------------------------------------------
//
//  Fijate que recibe `res` como parámetro. Es una función
//  común y corriente: no tiene nada especial por trabajar con
//  HTTP.

const responderJSON = (res, estado, datos) => {
  res.writeHead(estado, { 'content-type': 'application/json' });
  res.end(JSON.stringify(datos, null, 2));
};

class ErrorDeValidacion extends Error {
  constructor(campo, mensaje) {
    super(mensaje);
    this.campo = campo;
  }
}

// ------------------------------------------------------------
//  EL SERVIDOR
// ------------------------------------------------------------

const servidor = http.createServer((req, res) => {
  // Desestructuración: sacamos url y method del objeto req.
  // NGINX (servidor Web)
  try {
  const { url, method, headers } = req;

  console.log(`${method} ${url} ${headers}`);

  // Solo aceptamos GET en esta versión.
  if (method !== 'GET') {
    return responderJSON(res, 405, {
      error: 'Método no permitido',
      permitidos: ['GET'],
    });
  }

  // --- Ruta: lista completa ---
  if (url === '/alumnos') {
    return responderJSON(res, 200, alumnos);
  }

  // --- Ruta: solo los aprobados ---
  if (url === '/alumnos/aprobados') {
    const aprobados = alumnos.filter((a) => a.nota >= 6);
    return responderJSON(res, 200, aprobados);
  }

  // --- Ruta: estadísticas ---
  if (url === '/estadisticas') {
    const suma = alumnos.reduce((total, a) => total + a.nota, 0);
    return responderJSON(res, 200, {
      total: alumnos.length,
      aprobados: alumnos.filter((a) => a.nota >= 6).length,
      desaprobados: alumnos.filter((a) => a.nota < 6).length,
      promedio: Number((suma / alumnos.length).toFixed(2)),
      notaMaxima: Math.max(...alumnos.map((a) => a.nota)),
      notaMinima: Math.min(...alumnos.map((a) => a.nota)),
    });
  }

  // --- Ruta: un alumno por legajo ---
  //
  //  La url llega como texto: '/alumnos/12345'
  //  Hay que partirla y quedarse con la última parte.
  if (url.startsWith('/alumnos/')) {
    const partes = url.split('/'); // ['', 'alumnos', '12345']
    const legajo = Number(partes[2]); // texto → número

    // Si no es un número válido:
    if (Number.isNaN(legajo)) {
      //throw 'ocurrio un error';
      //throw { codigo: 400, mensaje: 'El legajo debe ser un número', recibido: partes[2] };
      throw new ErrorDeValidacion('legajo', 'El legajo debe ser un número');
      return responderJSON(res, 400, {
        error: 'El legajo debe ser un número',
        recibido: partes[2],
      });
    }

    const encontrado = alumnos.find((alumno) => alumno.legajo === legajo);

    // `find` devuelve undefined si no encuentra nada.
    if (!encontrado) {
      return responderJSON(res, 404, {
        error: 'Alumno no encontrado',
        legajo,
      });
    }

    return responderJSON(res, 200, encontrado);
    
  }

  // --- Nada coincidió ---
  throw new Error('Ruta no encontrada');
} catch (error) {
  if (error instanceof ErrorDeValidacion) {
    return responderJSON(res, 400, {
      error: 'Error de validación',
      campo: error.campo,
      mensaje: error.message,
    });
  }
  return responderJSON(res, 500, {
    error: 'Error interno del servidor',
    mensaje: error.message,
    err: error,
  });
}

});

servidor.listen(3000, () => {
  console.log('API escuchando en http://localhost:3000\n');
});

// ============================================================
//  MIRÁ LO QUE ACABÁS DE ESCRIBIR
// ============================================================
//
//  Esto es una API HTTP funcionando: rutas, códigos de estado,
//  ress JSON, manejo de errores.
//
//  Sin frameworks. Sin instalar nada. Sin archivos de
//  configuración. Con lo que trae Node y lo que aprendiste hoy.
//
//  En la clase de Express vamos a reemplazar esa cadena de `if`
//  por un enrutador de verdad. Pero conviene haber escrito los
//  `if` primero: cuando veas lo que hace Express, vas a saber
//  exactamente qué problema te está resolviendo.
// ============================================================

// ============================================================
//  PARA PROBAR VOS
// ============================================================
//
//  1. Agregá una ruta /alumnos/desaprobados.
//
//  2. Agregá una ruta /salud que devuelva { estado: 'ok' } y la
//     hora actual.
//
//  3. La ruta /alumnos/aprobados funciona por casualidad de orden.
//     Movela DEBAJO del bloque de /alumnos/:legajo y probá otra
//     vez. ¿Qué pasa? ¿Por qué? Este bug tiene nombre y lo vas a
//     volver a encontrar en Express.
//
//  4. Difícil: hacé que /alumnos acepte un filtro por la dirección,
//     así:  /alumnos?minima=6
//     (pista: mirá el módulo node:url, o partí el texto por '?')
// ============================================================
