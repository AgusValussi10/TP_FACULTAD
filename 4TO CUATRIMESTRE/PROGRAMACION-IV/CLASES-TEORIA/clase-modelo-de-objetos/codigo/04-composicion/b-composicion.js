// ============================================================
//  4.B  La misma solucion, por composicion
// ============================================================
'use strict';
// La idea: el objeto no ES sus capacidades, las TIENE. Y las recibe.

// --- Capacidades: objetos chicos, sin herencia, testeables solos.
const porRed = (url) => ({
  async enviar(datos) { return `POST ${url} ${JSON.stringify(datos)}`; },
});

const porDisco = (ruta) => ({
  async enviar(datos) { return `escribo en ${ruta}: ${JSON.stringify(datos)}`; },
});

const conBateria = (nivelInicial) => {
  let nivel = nivelInicial;                    // estado privado por closure
  return {
    consumir(p) { nivel = Math.max(0, nivel - p); },
    get bateria() { return nivel; },
  };
};

// --- El dispositivo se arma pidiendo lo que necesita.
function crearSensor({ id, leer, transporte, energia }) {
  return {
    id,
    async medirYEnviar() {
      const datos = leer();
      energia?.consumir(1);
      return transporte ? await transporte.enviar(datos) : 'sin transporte';
    },
    get bateria() { return energia?.bateria; },
  };
}

// OJO con el atajo que uno escribe primero:
//     ...(energia ?? {})
// El spread COPIA VALORES: evalua el getter una sola vez, en el momento de
// armar el objeto, y deja el 100 congelado. Es un bug silencioso y clasico.
// Copiar propiedades no es lo mismo que delegar.

const leerTemp = () => ({ t: 21.5 });

const conectado = crearSensor({ id: 'S-01', leer: leerTemp, transporte: porRed('http://a') });
const offline   = crearSensor({ id: 'S-02', leer: leerTemp, transporte: porDisco('/sd/log') });
const aBateria  = crearSensor({ id: 'S-03', leer: leerTemp, transporte: porRed('http://a'),
                                energia: conBateria(100) });

(async () => {
  console.log(await conectado.medirYEnviar());
  console.log(await offline.medirYEnviar());
  console.log(await aBateria.medirYEnviar(), '| bateria:', aBateria.bateria);

  console.log(`
Que se gano:
  - agregar una capacidad no toca ninguna clase existente;
  - n capacidades independientes -> n objetos, no 2^n clases;
  - cada capacidad se prueba sola, sin construir la jerarquia;
  - el reemplazo por una falsa en los tests es trivial: es un parametro.

Que se perdio:
  - instanceof ya no sirve para clasificar. Hay que preguntar por
    capacidad: typeof obj.enviar === 'function'  o una marca explicita.
  - la estructura del sistema ya no se lee en un diagrama de clases;
    esta en el lugar donde se arman los objetos (el "composition root").
`);

  // La version con clases NO desaparece: la clase sirve para el dato
  // con forma fija (una Lectura, un Umbral). La composicion sirve para
  // el comportamiento. Esa es la division util.
})();

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Agrega la capacidad "cifrado" sin tocar ninguna funcion existente.
//  2. Escribi un test de `porDisco` sin crear ningun sensor.
//  3. Contra la version con herencia: cuantos archivos tocarias en cada
//     una para agregar "reintento con espera exponencial al enviar"?
// ------------------------------------------------------------
