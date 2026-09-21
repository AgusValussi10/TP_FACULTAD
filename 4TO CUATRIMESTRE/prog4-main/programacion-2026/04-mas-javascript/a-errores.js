// ============================================================
//  11. ERRORES: cuando algo sale mal
// ============================================================
//
//  Ejecutalo:  node a-errores.js
//
//  Esto ya lo sabés de tu otro lenguaje. Cambia la sintaxis y
//  cambian dos cosas de fondo. Vamos derecho a las diferencias.
// ============================================================

// ------------------------------------------------------------
//  11.1 · Lo que es igual
// ------------------------------------------------------------

try {
  const numero = Number('no soy un número');
  if (Number.isNaN(numero)) {
    throw new Error('No se pudo convertir a número');
  }
  console.log('nunca llego acá');
} catch (error) {
  console.log('Atrapé el error:', error.message);
} finally {
  console.log('El finally corre siempre, haya error o no');
}

// ------------------------------------------------------------
//  11.2 · DIFERENCIA 1: se puede lanzar cualquier cosa
// ------------------------------------------------------------
//
//  En muchos lenguajes solo se puede lanzar algo que herede de
//  una clase de excepción. Acá se puede lanzar CUALQUIER valor.

try {
  throw 'un texto pelado';
} catch (error) {
  console.log('\nlancé un texto:', error);
  console.log('¿tiene .message?', error.message); // undefined
}

try {
  throw { codigo: 42 };
} catch (error) {
  console.log('lancé un objeto:', error.codigo);
}

// Se puede, pero NO SE HACE.
//
// Si lanzás algo que no es un Error, perdés el mensaje, perdés el
// nombre y perdés la traza de la pila (dónde ocurrió). Quien
// atrape tu error no va a poder hacer nada con él.
//
//     REGLA: siempre `throw new Error(...)`.

// ------------------------------------------------------------
//  11.3 · Qué trae un Error
// ------------------------------------------------------------

try {
  throw new Error('algo falló feo');
} catch (error) {
  console.log('\n--- anatomía de un Error ---');
  console.log('name:   ', error.name);
  console.log('message:', error.message);
  console.log('stack:  (las primeras dos líneas)');
  console.log(error.stack.split('\n').slice(0, 2).join('\n'));
}

// El `stack` dice EN QUÉ LÍNEA de qué archivo se originó el error
// y qué funciones estaban en curso. Es lo primero que vas a mirar
// cuando algo se rompa.

// ------------------------------------------------------------
//  11.4 · Errores propios
// ------------------------------------------------------------
//
//  Se puede crear un tipo de error propio para poder distinguirlo
//  después. Esto lo vas a usar en el proyecto del cuatrimestre.

class ErrorDeValidacion extends Error {
  constructor(campo, mensaje) {
    super(mensaje); // llama al constructor de Error
    this.name = 'ErrorDeValidacion';
    this.campo = campo;
  }
}

const validarNota = (nota) => {
  if (typeof nota !== 'number') {
    throw new ErrorDeValidacion('nota', 'La nota tiene que ser un número');
  }
  if (nota < 1 || nota > 10) {
    throw new ErrorDeValidacion('nota', 'La nota tiene que estar entre 1 y 10');
  }
  return nota;
};

console.log('\n--- validaciones ---');

const probar = (valor) => {
  try {
    validarNota(valor);
    console.log(`${JSON.stringify(valor)} → válida`);
  } catch (error) {
    if (error instanceof ErrorDeValidacion) {
      console.log(`${JSON.stringify(valor)} → inválida (${error.campo}): ${error.message}`);
    } else {
      throw error; // no es mío, que lo atrape otro
    }
  }
};

probar(8);
probar(15);
probar('ocho');

// Fijate el `throw error` del else: si el error NO es de los que
// yo sé manejar, lo vuelvo a lanzar. Atrapar errores que no sabés
// manejar es peor que no atraparlos: los escondés.

// ------------------------------------------------------------
//  11.5 · DIFERENCIA 2: el error que no atrapa nadie
// ------------------------------------------------------------
//
//  Si un error sube hasta arriba sin que nadie lo atrape, el
//  proceso TERMINA. Se muere el servidor entero, no solo el
//  pedido que falló.
//
//  Probá esto en un archivo aparte:
//
//      console.log('antes');
//      throw new Error('sin atrapar');
//      console.log('después');   ← nunca se ejecuta
//
//  Esto es importante para lo que viene: en una API, un error no
//  atrapado en el manejo de UN pedido puede tirar abajo el
//  servidor para TODOS.

// ------------------------------------------------------------
//  11.6 · El caso más común: JSON mal formado
// ------------------------------------------------------------
//
//  Cuando alguien le manda basura a tu API, `JSON.parse` lanza.
//  Si no lo atrapás, se cae el servidor.

const parsearSeguro = (texto) => {
  try {
    return { ok: true, datos: JSON.parse(texto) };
  } catch (error) {
    return { ok: false, error: error.message };
  }
};

console.log('\n--- parseo seguro ---');
console.log(parsearSeguro('{"nombre":"Ana"}'));
console.log(parsearSeguro('esto no es json'));

// Devolver un objeto que dice si salió bien, en vez de lanzar, es
// un patrón que vas a usar mucho. No siempre conviene, pero
// cuando el error es esperable (entrada del usuario), sí.

// ============================================================
//  PARA PROBAR VOS
// ============================================================
//
//  1. Escribí `dividir(a, b)` que lance un Error si b es 0.
//     Usala adentro de un try/catch.
//
//  2. Creá `ErrorNoEncontrado` que extienda Error y tenga una
//     propiedad `recurso`. Usalo en una función `buscarAlumno`.
//
//  3. Escribí una función `intentar(fn)` que reciba una función,
//     la ejecute, y devuelva { ok: true, valor } o
//     { ok: false, error }. Probala con una que funcione y una
//     que lance.
// ============================================================
