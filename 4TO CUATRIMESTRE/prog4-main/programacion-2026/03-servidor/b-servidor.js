// ============================================================
//  8. EL SERVIDOR, LÍNEA POR LÍNEA
// ============================================================
//
//  Ejecutalo:  node b-servidor.js
//  Después abrí en el navegador:  http://localhost:3000
//  Para frenarlo: Ctrl+C en la terminal
//
//  Este es el mismo archivo del principio de la clase, ahora
//  con todo explicado.
// ============================================================

// ------------------------------------------------------------
//  LÍNEA 1: pedir el módulo http
// ------------------------------------------------------------
//
//  Trae la funcionalidad de servidor y cliente HTTP.
//  No hay que instalar nada: viene con Node.
const saludar = require('./a-modulos.js');
import saludar from './a-modulos.js';
const http = require('node:http');

// ------------------------------------------------------------
//  LÍNEA 2: crear el servidor
// ------------------------------------------------------------
//
//  `createServer` recibe UNA FUNCIÓN como parámetro.
//
//  Esa función es la que vos escribís pero NO llamás. Node la va
//  a llamar por vos, una vez por cada pedido que llegue.
//
//  Acordate del archivo de funciones: una función es un valor y
//  se puede pasar como parámetro. Acá está la utilidad.

const servidor = http.createServer((pedido, respuesta) => {
  // ┌──────────────────────────────────────────────────────┐
  // │  TODO ESTO CORRE UNA VEZ POR CADA PEDIDO QUE LLEGA   │
  // └──────────────────────────────────────────────────────┘

  // `pedido` tiene lo que mandó el cliente:
  console.log(`Llegó un pedido: ${pedido.method} ${pedido.url}`);

  // `respuesta` es lo que vas a devolver.

  // 1. El código de estado y las cabeceras.
  respuesta.writeHead(200, { 'content-type': 'application/json' });

  // 2. El cuerpo, y el cierre de la respuesta.
  //    `end` manda el contenido y cierra. Sin esto, el cliente
  //    se queda esperando para siempre.
  respuesta.end(
    JSON.stringify({
      mensaje: 'Hola desde Node',
      ruta: pedido.url,
      metodo: pedido.method,
      hora: new Date().toISOString(),
    }),
  );
});

// ------------------------------------------------------------
//  LÍNEA 3: ponerlo a escuchar
// ------------------------------------------------------------
//
//  Hasta acá el servidor existe pero no atiende a nadie.
//  `listen` lo pone a escuchar en un puerto.
//
//  El segundo parámetro es OTRA función: se ejecuta una sola vez,
//  cuando el servidor terminó de arrancar.

servidor.listen(3000, () => {
  console.log('Servidor escuchando en http://localhost:3000');
  console.log('Probá:');
  console.log('   http://localhost:3000/');
  console.log('   http://localhost:3000/alumnos');
  console.log('   http://localhost:3000/lo-que-sea');
  console.log('\nCtrl+C para frenarlo.\n');
});

// ============================================================
//  UNA COSA RARA: EL PROGRAMA NO TERMINA
// ============================================================
//
//  Todos los archivos anteriores llegaban a la última línea y
//  el proceso terminaba.
//
//  Este no. Node llega hasta acá, no hay más líneas... y el
//  proceso sigue vivo, esperando pedidos.
//
//  ¿Por qué?
//
//  Porque hay algo pendiente: un servidor escuchando. Node no
//  termina mientras haya trabajo pendiente registrado.
//
//  Quién lleva la cuenta de ese trabajo pendiente, y cómo decide
//  qué ejecutar y cuándo, es exactamente el tema de la clase que
//  viene.
//
//  Por ahora quedate con la observación: el proceso no termina, y
//  eso es raro.
// ============================================================

// ============================================================
//  PARA PROBAR VOS
// ============================================================
//
//  1. Cambiá el mensaje y recargá el navegador.
//     ¿Cambió? ¿Por qué no? (pista: hay que reiniciar el proceso)
//
//  2. Cambiá el código 200 por 404 y mirá la pestaña Red de las
//     herramientas de desarrollo del navegador (F12).
//
//  3. Sacá la línea del `writeHead` y mirá qué manda Node por
//     defecto en la cabecera content-type.
//
//  4. Abrí DOS pestañas del navegador y mirá la terminal.
//     ¿Cuántas veces se imprimió "Llegó un pedido"?
//     ¿Aparece algún pedido que vos no hiciste? (buscá /favicon.ico)
// ============================================================
