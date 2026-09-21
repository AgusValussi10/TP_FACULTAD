// ============================================================
//  4.C  Mixins: composicion cuando ya tenes clases
// ============================================================
'use strict';
// Un mixin es un conjunto de metodos que se inyecta en un prototipo.
// Hay dos formas. La segunda es la que conviene.

// --- Forma 1: copiar metodos al prototipo (Object.assign)
const Registrable = {
  registrar(msg) { return `[${this.constructor.name}] ${msg}`; },
};
class Basica {}
Object.assign(Basica.prototype, Registrable);
console.log(new Basica().registrar('hola'));
// Problema: los metodos quedan COPIADOS. No hay rastro de que vinieron de
// un mixin, no se puede llamar a `super`, y si dos mixins traen el mismo
// nombre gana el ultimo, en silencio.

// --- Forma 2: mixin como funcion que devuelve una subclase
//     (patron "subclass factory", el que se usa en librerias serias)
const ConReintento = (Base) => class ConReintento extends Base {
  async enviar(datos, intentos = 3) {
    for (let i = 1; i <= intentos; i++) {
      try { return await super.enviar(datos); }        // <- super FUNCIONA
      catch (e) {
        if (i === intentos) throw e;
        await new Promise(r => setTimeout(r, 2 ** i));
      }
    }
  }
};

const ConMetricas = (Base) => class ConMetricas extends Base {
  async enviar(datos) {
    const t0 = Date.now();
    try { return await super.enviar(datos); }
    finally { this.ultimaDuracion = Date.now() - t0; }
  }
};

class TransporteHTTP {
  #fallas;
  constructor(fallas = 0) { this.#fallas = fallas; }
  async enviar(datos) {
    if (this.#fallas-- > 0) throw new Error('red caida');
    return `enviado ${JSON.stringify(datos)}`;
  }
}

// La composicion se lee de adentro hacia afuera y queda en la CADENA,
// no copiada: cada mixin es un eslabon real de prototipos.
class TransporteRobusto extends ConMetricas(ConReintento(TransporteHTTP)) {}

(async () => {
  const t = new TransporteRobusto(2);          // las dos primeras van a fallar
  console.log(await t.enviar({ t: 21.5 }), '| ms:', t.ultimaDuracion);

  console.log('\n--- la cadena que quedo armada');
  let p = Object.getPrototypeOf(new TransporteRobusto(0)), nombres = [];
  while (p && p !== Object.prototype) { nombres.push(p.constructor.name); p = Object.getPrototypeOf(p); }
  console.log(nombres.join(' -> '));
  // (los mixins llevan nombre porque se escribieron como expresiones de clase
  //  con nombre; si no, la cadena aparece con eslabones anonimos y el stack
  //  trace de produccion se vuelve ilegible)

  console.log(`
Cuando usar cada cosa:
  - composicion por parametros (4.B): cuando podes elegir el diseño.
  - mixins de subclase (4.C): cuando ya hay una jerarquia y tenes que
    agregar una capacidad transversal (log, metricas, reintento, cache)
    sin duplicarla en cada rama.
  - Object.assign sobre el prototipo: casi nunca. Solo para objetos
    sueltos, sin super y sin conflictos de nombres.
`);
})();

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Invertí el orden: ConReintento(ConMetricas(TransporteHTTP)).
//     Que mide ahora `ultimaDuracion`? Cual de los dos ordenes queres?
//  2. Escribi un mixin ConCache que no reenvie si los datos son iguales
//     a los del ultimo envio.
// ------------------------------------------------------------
