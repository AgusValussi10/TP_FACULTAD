// ============================================================
//  12. CLASES: sí, existen
// ============================================================
//
//  Ejecutalo:  node b-clases.js
//
//  Venís de un lenguaje orientado a objetos y hace rato te estás
//  preguntando dónde están las clases. Acá están.
// ============================================================

class Alumno {
  constructor(legajo, nombre) {
    this.legajo = legajo;
    this.nombre = nombre;
    this.notas = [];
  }

  agregarNota(nota) {
    this.notas.push(nota);
    return this; // devolver `this` permite encadenar
  }

  promedio() {
    if (this.notas.length === 0) return 0;
    const suma = this.notas.reduce((total, n) => total + n, 0);
    return Number((suma / this.notas.length).toFixed(2));
  }

  aprobado() {
    return this.promedio() >= 6;
  }
}

const ana = new Alumno(12345, 'Ana Gómez');
ana.agregarNota(8).agregarNota(7).agregarNota(9);

console.log(ana.nombre, '| promedio:', ana.promedio(), '| aprobó:', ana.aprobado());
console.log(ana);

// Fijate lo que NO hay:
//   - no se declaran los tipos de las propiedades
//   - no hay `public` ni `private` en cada método
//   - las propiedades se crean solas al asignarlas en el constructor

// ------------------------------------------------------------
//  12.1 · Herencia
// ------------------------------------------------------------

class AlumnoLibre extends Alumno {
  constructor(legajo, nombre) {
    super(legajo, nombre); // obligatorio antes de usar `this`
    this.condicion = 'libre';
  }

  aprobado() {
    // Los libres necesitan más
    return this.promedio() >= 7;
  }
}

const beto = new AlumnoLibre(12346, 'Beto Ruiz');
beto.agregarNota(6).agregarNota(7);

console.log('\n' + beto.nombre, '| promedio:', beto.promedio(), '| aprobó:', beto.aprobado());
console.log('¿es un Alumno?', beto instanceof Alumno);

// ------------------------------------------------------------
//  12.2 · Propiedades privadas de verdad
// ------------------------------------------------------------
//
//  Con el numeral, la propiedad es inaccesible desde afuera.
//  No es una convención: el lenguaje la bloquea.

class CuentaBancaria {
  #saldo = 0; // privada

  depositar(monto) {
    if (monto <= 0) throw new Error('El monto tiene que ser positivo');
    this.#saldo += monto;
    return this.#saldo;
  }

  consultarSaldo() {
    return this.#saldo;
  }
}

const cuenta = new CuentaBancaria();
cuenta.depositar(1000);
console.log('\nsaldo:', cuenta.consultarSaldo());

// Esto tira error de sintaxis. Descomentalo y probá:
// console.log(cuenta.#saldo);

// ------------------------------------------------------------
//  12.3 · Métodos estáticos
// ------------------------------------------------------------

class Validador {
  static esLegajoValido(legajo) {
    return Number.isInteger(legajo) && legajo > 0 && legajo < 100000;
  }
}

console.log('\n12345 es válido:', Validador.esLegajoValido(12345));
console.log('-5 es válido:', Validador.esLegajoValido(-5));

// ------------------------------------------------------------
//  12.4 · LA PREGUNTA QUE IMPORTA: ¿cuándo usar clases?
// ------------------------------------------------------------
//
//  Acá viene el consejo que va contra tu instinto de programador
//  orientado a objetos.
//
//  En Node, MUCHO menos de lo que estás acostumbrado.
//
//  Compará estas dos formas de resolver lo mismo:

// Con clase:
class CalculadoraDeNotas {
  constructor(notas) {
    this.notas = notas;
  }
  promedio() {
    return this.notas.reduce((a, b) => a + b, 0) / this.notas.length;
  }
}
console.log('\ncon clase:  ', new CalculadoraDeNotas([8, 7, 9]).promedio());

// Con función:
const promedio = (notas) => notas.reduce((a, b) => a + b, 0) / notas.length;
console.log('con función:', promedio([8, 7, 9]));

// La función es más corta, más fácil de probar y no tiene estado
// que pueda quedar inconsistente.
//
//  CRITERIO PRÁCTICO
//
//    Usá una clase cuando tengas ESTADO que cambia y un conjunto
//    de operaciones que lo modifican de forma coordinada.
//
//    Usá funciones cuando estés transformando datos: entra algo,
//    sale otra cosa, sin guardar nada en el medio.
//
//  La mayor parte del código de una API es del segundo tipo.
//
//  Y una advertencia: `class` en JavaScript parece lo mismo que
//  en tu otro lenguaje pero por debajo funciona distinto. Eso lo
//  vamos a ver en la clase de modelo de objetos, y ahí van a
//  aparecer cosas que hoy no se ven.

// ============================================================
//  PARA PROBAR VOS
// ============================================================
//
//  1. Creá una clase `Sensor` con nombre, ubicación y un array de
//     lecturas. Métodos: registrar(valor), ultima(), promedio().
//
//  2. Hacé que `registrar` lance un error si el valor no es un
//     número. Usá el ErrorDeValidacion del archivo anterior.
//
//  3. Creá `SensorDeTemperatura` que extienda `Sensor` y tenga un
//     método `enAlerta()` que devuelva true si la última lectura
//     supera 30.
//
//  4. Reescribí el punto 1 SIN clases, usando un objeto y
//     funciones sueltas. Compará las dos versiones: ¿cuál te
//     resulta más fácil de probar?
// ============================================================
