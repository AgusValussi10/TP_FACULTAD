// ============================================================
//  16. RECIBIR DATOS: el método POST
// ============================================================
//
//  Ejecutalo:  node e-api-post.js
//
//  Hasta ahora la API solo devolvía datos. Ahora también los
//  recibe.
//
//  Para probarlo necesitás una herramienta que mande POST. El
//  navegador solo hace GET desde la barra de direcciones.
//  Desde la terminal:
//
//      curl -X POST http://localhost:3000/alumnos \
//           -H "content-type: application/json" \
//           -d '{"legajo":99999,"nombre":"Nuevo","nota":7}'
//
//  (Hay un archivo pruebas.txt al lado con todos los comandos
//   listos para copiar y pegar.)
// ============================================================

const http = require('node:http');

const alumnos = [
  { legajo: 12345, nombre: 'Ana Gómez', nota: 8 },
  { legajo: 12346, nombre: 'Beto Ruiz', nota: 4 },
];

const responderJSON = (res, estado, datos) => {
  res.writeHead(estado, { 'content-type': 'application/json' });
  res.end(JSON.stringify(datos, null, 2));
};

// ------------------------------------------------------------
//  LEER EL CUERPO DEL req
// ------------------------------------------------------------
//
//  Acá hay algo nuevo e importante.
//
//  El cuerpo de un req NO llega entero de una vez. Llega por
//  PEDAZOS, a medida que la red los va trayendo. Node te avisa de
//  cada pedazo, y después te avisa que terminó.
//
//  Así que hay que juntar los pedazos y esperar el aviso de fin.

const leerCuerpo = (req, cuandoTermine) => {
  let acumulado = '';

  // "cada vez que llegue un pedazo, hacé esto"
  req.on('data', (pedazo) => {
    acumulado += pedazo;
  });

  // "cuando no venga nada más, hacé esto"
  req.on('end', () => {
    cuandoTermine(acumulado);
  });
};

// Otra vez el mismo patrón: le entregás funciones a Node para que
// las llame cuando corresponda. Esta es la tercera vez que
// aparece hoy (createServer, readFile, y ahora esto). No es
// casualidad: así funciona todo Node.

const servidor = http.createServer((req, res) => {
  const { url, method } = req;
  console.log(`${method} ${url}`);

  // --- GET: listar ---
  if (method === 'GET' && url === '/alumnos') {
    return responderJSON(res, 200, alumnos);
  }

  // --- POST: crear ---
  if (method === 'POST' && url === '/alumnos') {
    return leerCuerpo(req, (cuerpoTexto) => {
      // 1. ¿Es JSON válido?
      let datos;
      try {
        datos = JSON.parse(cuerpoTexto);
      } catch (error) {
        return responderJSON(res, 400, {
          error: 'El cuerpo no es JSON válido',
          detalle: error.message,
        });
      }

      // 2. ¿Están todos los campos?
      const faltantes = ['legajo', 'nombre', 'nota'].filter(
        (campo) => datos[campo] === undefined,
      );
      if (faltantes.length > 0) {
        return responderJSON(res, 400, {
          error: 'Faltan campos obligatorios',
          faltantes,
        });
      }

      // 3. ¿Los tipos son correctos?
      if (typeof datos.legajo !== 'number' || typeof datos.nota !== 'number') {
        return responderJSON(res, 400, {
          error: 'legajo y nota tienen que ser números',
        });
      }

      // 4. ¿La nota está en rango?
      if (datos.nota < 1 || datos.nota > 10) {
        return responderJSON(res, 400, {
          error: 'La nota tiene que estar entre 1 y 10',
          recibido: datos.nota,
        });
      }

      // 5. ¿Ya existe?
      if (alumnos.some((a) => a.legajo === datos.legajo)) {
        return responderJSON(res, 409, {
          error: 'Ya existe un alumno con ese legajo',
          legajo: datos.legajo,
        });
      }

      // 6. Recién ahora se guarda.
      //    Fijate que NO guardo `datos` tal cual: armo un objeto
      //    nuevo solo con los campos que me interesan. Si el
      //    cliente mandó campos de más, se descartan.
      const nuevo = {
        legajo: datos.legajo,
        nombre: datos.nombre,
        nota: datos.nota,
      };
      alumnos.push(nuevo);

      // 201 = creado. Y por convención se devuelve lo creado.
      return responderJSON(res, 201, nuevo);
    });
  }

  // --- DELETE ---
  if (method === 'DELETE' && url.startsWith('/alumnos/')) {
    const legajo = Number(url.split('/')[2]);
    const posicion = alumnos.findIndex((a) => a.legajo === legajo);

    if (posicion === -1) {
      return responderJSON(res, 404, { error: 'No encontrado', legajo });
    }

    const borrado = alumnos.splice(posicion, 1)[0];
    return responderJSON(res, 200, { borrado });
  }

  responderJSON(res, 404, { error: 'Ruta no encontrada' });
});

servidor.listen(3000, () => {
  console.log('API escuchando en http://localhost:3000');
  console.log('Mirá pruebas.txt para los comandos de prueba\n');
});

// ============================================================
//  LO IMPORTANTE DE ESTE ARCHIVO
// ============================================================
//
//  Contá las líneas de validación contra las líneas que guardan
//  el dato. Son seis validaciones y una línea de `push`.
//
//  Eso NO es exagerado. Es lo normal.
//
//      Todo lo que viene del cliente es sospechoso hasta que lo
//      validaste. Siempre. Sin excepciones.
//
//  El cliente puede ser un navegador, otro servidor, o alguien
//  probando qué pasa si te manda basura a propósito. Tu API no
//  tiene forma de distinguirlos.
//
//  Esta idea vuelve, mucho más a fondo, en la clase de seguridad.

// ============================================================
//  PARA PROBAR VOS
// ============================================================
//
//  1. Agregá PUT /alumnos/:legajo para modificar la nota.
//
//  2. Hacé que POST rechace nombres de menos de 3 caracteres.
//
//  3. Probá mandar un legajo como texto: {"legajo":"12345",...}
//     ¿Qué responde? ¿Está bien esa res?
//
//  4. Probá mandar un campo de más:
//     {"legajo":1,"nombre":"X","nota":5,"admin":true}
//     Fijate que NO se guarda. Buscá en el código por qué.
//     Ese detalle tiene nombre propio y es una vulnerabilidad
//     conocida cuando no se hace.
//
//  5. Difícil: hacé que los datos se guarden en un archivo JSON
//     con lo que aprendiste en 05-archivos, así no se pierden al
//     reiniciar.
// ============================================================
