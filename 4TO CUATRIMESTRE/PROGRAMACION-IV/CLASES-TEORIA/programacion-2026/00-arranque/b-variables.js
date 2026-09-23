// ============================================================
//  2. VARIABLES: const y let
// ============================================================
//
//  Ejecutalo:  node b-variables.js
//
//  En el lenguaje que vos usaste, declarabas el tipo:
//      int edad = 20;
//      String nombre = "Ana";
//
//  En JavaScript no se declara el tipo. Se declara si la variable
//  puede cambiar o no.
// ============================================================

const nombre = 'Ana'; // const  = no se puede reasignar
let edad = 20; // let    = sí se puede reasignar


console.log(nombre, edad);

edad = 21; // permitido
console.log('Cumplió años:', edad);

// Esto tira error. Descomentalo y probá:
// nombre = 'Beatriz';
//   TypeError: Assignment to constant variable.

// ------------------------------------------------------------
//  REGLA PRÁCTICA
// ------------------------------------------------------------
//
//  Usá `const` SIEMPRE.
//  Cambialo a `let` solamente cuando el compilador te obligue.
//
//  No es una regla de estilo: una variable que no cambia es una
//  variable menos de la que preocuparse cuando leés el código.
//
//  Existe una tercera forma, `var`, que es la vieja y tiene un
//  comportamiento distinto. La vamos a ver más adelante, en el
//  contexto donde ese comportamiento importa. Por ahora: no la uses.

// ------------------------------------------------------------
//  OJO: const NO significa inmutable
// ------------------------------------------------------------
//
//  Esta es la primera trampa real del lenguaje, y confunde a
//  todo el mundo que viene de otro lado.

const persona = { nombre: 'Ana', edad: 20 };

persona.edad = 21; // ESTO SÍ SE PUEDE
persona.direccion = {
  calle: "French 599",
  ciudad: "Resistencia"
}

if(persona.direccion.codigoPostal){
console.log(persona.direccion.codigoPostal);

}


// persona = { nombre: 'Otra' };   // ESTO NO. Descomentá y probá.

// Por qué: `const` protege la ETIQUETA, no el CONTENIDO.
//
//     const persona ──────► { nombre: 'Ana', edad: 20 }
//           ▲                          ▲
//           │                          │
//     esta flecha                 esto sí se puede
//     no se puede                 modificar
//     cambiar de destino
//
// La variable no puede apuntar a otro objeto. El objeto al que
// apunta sí puede cambiar por dentro.

// ============================================================
//  PARA PROBAR VOS
// ============================================================
//
//  1. Creá una constante `materia` con el nombre de esta materia.
//  2. Creá una variable `clase` con el valor 1, imprimila,
//     sumale 1 e imprimila de nuevo.
//  3. Creá un objeto `alumno` con `const` que tenga nombre y legajo.
//     Cambiale el legajo. ¿Funcionó? Explicá por qué.
// ============================================================
