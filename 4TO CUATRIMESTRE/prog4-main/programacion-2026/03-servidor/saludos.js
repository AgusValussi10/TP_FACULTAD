// ============================================================
//  UN MÓDULO PROPIO
// ============================================================
//
//  Este archivo no se ejecuta solo. Otro archivo lo pide con
//  require y usa lo que exporta.
// ============================================================

const formal = (nombre) => `Buenos días, ${nombre}.`;

const informal = (nombre) => `¿Qué tal, ${nombre}?`;

// Esta función NO se exporta: queda privada del módulo.
const secreta = () => 'nadie de afuera me puede llamar';

// Lo que se pone acá es lo que ve quien haga require de este archivo.
module.exports = { formal, informal };

// Es equivalente a escribir:
//     module.exports = { formal: formal, informal: informal };
//
// Cuando la clave y la variable se llaman igual, se puede escribir
// una sola vez. Esa abreviatura se usa en todos lados.
