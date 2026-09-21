// ============================================================
//  10. UNA COSA QUE NO TE VOY A EXPLICAR HOY
// ============================================================
//
//  Ejecutalo:  node d-misterio.js
//
//  Este servidor tiene dos rutas. Las dos devuelven un número.
//  Una tarda casi nada. La otra hace una cuenta larga.
//
//  Hacé el experimento en este orden EXACTO:
//
//    1. Levantá el servidor.
//    2. Abrí http://localhost:3000/rapido varias veces.
//       Responde al instante.
//    3. Abrí http://localhost:3000/lento en UNA pestaña.
//    4. MIENTRAS esa pestaña está cargando, abrí OTRA pestaña
//       en http://localhost:3000/rapido
//
//    ¿Qué pasó?
// ============================================================

const http = require('node:http');

// Este número está calibrado para que la cuenta tarde unos 5
// segundos. Si en tu máquina tarda mucho más o mucho menos,
// ajustalo: es solo la cantidad de vueltas del bucle.
const VUELTAS = 2_500_000_000;

const trabajoLargo = () => {
  let acumulado = 0;
  for (let i = 0; i < VUELTAS; i++) {
    acumulado += Math.sqrt(i);
  }
  return Math.round(acumulado);
};

const servidor = http.createServer((pedido, respuesta) => {
  respuesta.writeHead(200, { 'content-type': 'application/json' });

  if (pedido.url === '/lento') {
    console.log('  → empieza el trabajo largo');
    const resultado = trabajoLargo();
    console.log('  → termina el trabajo largo');
    return respuesta.end(JSON.stringify({ ruta: 'lento', resultado }));
  }

  respuesta.end(JSON.stringify({ ruta: 'rapido', hora: Date.now() }));
});

servidor.listen(3000, () => {
  console.log('Servidor en http://localhost:3000');
  console.log('Rutas: /rapido  y  /lento\n');
});

// ============================================================
//  LO QUE ACABÁS DE VER
// ============================================================
//
//  La ruta rápida dejó de responder mientras la lenta trabajaba.
//
//  No es que se puso más lenta. Es que NO RESPONDIÓ hasta que la
//  otra terminó.
//
//  Si hiciste una API en otro lenguaje, esto no te pasaba: cada
//  pedido tenía su propio hilo y los demás seguían atendiéndose.
//
//  Acá no. Un pedido lento frenó el servidor entero.
//
//  Las preguntas para la clase que viene:
//
//    · ¿Por qué pasa esto?
//    · ¿Por qué alguien diseñaría un servidor así?
//    · Si es tan malo, ¿por qué Node se usa en todos lados?
//    · ¿Cómo se arregla?
//
//  Traé una hipótesis escrita. No importa si es incorrecta.
//  Vamos a discutirlas antes de que yo explique nada.
// ============================================================
