// // ============================================================
// //  6. OBJETOS Y ARRAYS
// // ============================================================
// //
// //  Ejecutalo:  node a-objetos.js
// //
// //  Acá está la diferencia más grande con el lenguaje que usaste.
// // ============================================================

// // ------------------------------------------------------------
// //  6.1 · Un objeto no necesita una clase
// // ------------------------------------------------------------
// //
// //  En tu otro lenguaje, para tener un objeto con nombre y legajo
// //  tenías que declarar una clase primero.
// //
// //  Acá lo escribís y ya está:

// const alumno = {
//   nombre: 'Ana',
//   legajo: 12345,
//   activo: true,
// };

// console.log(alumno);
// console.log(alumno.nombre);
// console.log(alumno['nombre']); // otra forma, útil cuando la clave es variable

// // Agregar una propiedad que no existía:
// alumno.email = 'ana@frre.utn.edu.ar';
// console.log(alumno);

// // Borrar una:
// delete alumno.activo;
// console.log(alumno);

// // Nada de esto requiere declarar nada. El objeto es una bolsa de
// // pares clave-valor que se arma y se modifica en el momento.
// //
// // ¿Es bueno? Es flexible y peligroso al mismo tiempo. Un error de
// // tipeo crea una propiedad nueva en lugar de dar error:

// alumno.nombrre = 'typo';
// console.log('\nel typo creó una propiedad nueva:', alumno.nombrre);
// console.log('y la original sigue ahí:', alumno.nombre);

// // ------------------------------------------------------------
// //  6.2 · Objetos anidados
// // ------------------------------------------------------------

// const curso = {
//   materia: 'Programación IV',
//   comision: 1,
//   docente: {
//     nombre: 'Emilio',
//     cargo: 'Profesor',
//   },
//   dias: ['miércoles'],
// };

// console.log('\n' + curso.docente.nombre);
// console.log(curso.dias[0]);

// // Si accedés a algo que no existe, da undefined:
// console.log('curso.aula:', curso.aula);

// // Pero si accedés DENTRO de algo que no existe, explota:
// // console.log(curso.aula.numero);
// //   TypeError: Cannot read properties of undefined (reading 'numero')

// // Para eso existe el operador `?.` (encadenamiento opcional):
// console.log('curso.aula?.numero:', curso.aula?.numero); // undefined, sin error

// // Se lee: "si `aula` existe, dame `numero`; si no, dame undefined".
// // En código real que consume APIs esto se usa todo el tiempo.

// // ------------------------------------------------------------
// //  6.3 · Arrays
// // ------------------------------------------------------------

// const notas = [7, 4, 9, 2, 10];

// console.log('\nprimer elemento:', notas[0]);
// console.log('cantidad:', notas.length);
// console.log('último:', notas[notas.length - 1]);
// console.log('último (forma corta):', notas.at(-1));

// notas.push(6); // agregar al final
// console.log('después del push:', notas);

// // Un array puede tener cosas de distinto tipo (aunque casi nunca
// // conviene). Esto es legal:
// const mezcla = [1, 'dos', true, { a: 3 }, [4, 5]];
// console.log('mezcla:', mezcla);

// // ------------------------------------------------------------
// //  6.4 · Los métodos que reemplazan a los bucles
// // ------------------------------------------------------------
// //
// //  Esto es lo que más vas a usar en el resto de la materia.
// //  Los tres son la misma idea: recibir una FUNCIÓN y aplicarla.
// //  (¿Se acuerdan de "una función es un valor"? Acá se usa.)

// const numeros = [1, 2, 3, 4, 5];

// // MAP: transforma cada elemento. Devuelve un array NUEVO del
// // mismo largo.
// const dobles = numeros.map((n) => n * 2);
// console.log('\nmap   →', dobles);
// console.log('el original no cambió:', numeros);

// // FILTER: se queda con los que cumplen la condición. Array nuevo,
// // más corto o igual.
// const pares = numeros.filter((n) => n % 2 === 0);
// console.log('filter →', pares);

// // FIND: devuelve el PRIMER elemento que cumple, o undefined.
// const primerMayorA3 = numeros.find((n) => n > 3);
// console.log('find   →', primerMayorA3);

// // REDUCE: combina todo en un solo valor.
// const suma = numeros.reduce((acumulado, n) => acumulado + n, 0);
// console.log('reduce →', suma);

// // Comparación honesta con el bucle de toda la vida:
// //
// //     let dobles = [];
// //     for (let i = 0; i < numeros.length; i++) {
// //       dobles.push(numeros[i] * 2);
// //     }
// //
// //     const dobles = numeros.map(n => n * 2);
// //
// // Hacen lo mismo. El segundo dice QUÉ querés, no CÓMO recorrer.

// // Se pueden encadenar:
// const alumnos = [
//   { nombre: 'Ana', nota: 8 },
//   { nombre: 'Beto', nota: 4 },
//   { nombre: 'Carla', nota: 9 },
//   { nombre: 'Diego', nota: 2 },
// ];

// const aprobados = alumnos
//   .filter((a) => a.nota >= 6)
//   .map((a) => a.nombre);

// console.log('\naprobados:', aprobados);

// // ------------------------------------------------------------
// //  6.5 · Desestructuración: sacar cosas de adentro
// // ------------------------------------------------------------
// //
// //  Se ve raro la primera vez y después no podés vivir sin ella.

// const { nombre, legajo } = alumno;
// console.log('\nnombre:', nombre, '| legajo:', legajo);

// // Es equivalente a:
// //     const nombre = alumno.nombre;
// //     const legajo = alumno.legajo;

// // También funciona con arrays, por posición:
// const [primera, segunda] = ['a', 'b', 'c'];
// console.log('primera:', primera, '| segunda:', segunda);

// // Y en los parámetros de una función. Esto lo vas a ver mucho:
// const describir = ({ nombre, nota }) => `${nombre} sacó ${nota}`;
// console.log(describir({ nombre: 'Ana', nota: 8 }));

// // ------------------------------------------------------------
// //  6.6 · El operador de propagación (...)
// // ------------------------------------------------------------

// const original = { nombre: 'Ana', legajo: 123 };

// // Copiar un objeto agregándole algo:
// const conEmail = { ...original, email: 'ana@utn.edu.ar' };
// console.log('\ncopia con email:', conEmail);

// // Copiar cambiando un valor (el último gana):
// const conOtroLegajo = { ...original, legajo: 999 };
// console.log('legajo cambiado:', conOtroLegajo);
// console.log('el original intacto:', original);

// // Juntar arrays:
// const a = [1, 2];
// const b = [3, 4];
// console.log('arrays juntos:', [...a, ...b]);

// // ------------------------------------------------------------
// //  6.7 · JSON
// // ------------------------------------------------------------
// //
// //  Un objeto de JavaScript NO es JSON. JSON es texto.
// //  La conversión es explícita y en los dos sentidos.

// const objeto = { nombre: 'Ana', nota: 8 };

// const texto = JSON.stringify(objeto);
// console.log('\nJSON.stringify →', texto);
// console.log('el tipo ahora es:', typeof texto);

// const devuelta = JSON.parse(texto);
// console.log('JSON.parse     →', devuelta);
// console.log('el tipo ahora es:', typeof devuelta);

// // Con formato legible (útil para depurar):
// console.log(JSON.stringify(objeto, null, 2));

// // Esto es lo que va a viajar entre tu servidor y sus clientes
// // durante todo el cuatrimestre.

// // ============================================================
// //  PARA PROBAR VOS
// // ============================================================
// //
// //  Con este array:
// //
// //    const productos = [
// //      { nombre: 'Teclado', precio: 15000, stock: 3 },
// //      { nombre: 'Mouse',   precio:  8000, stock: 0 },
// //      { nombre: 'Monitor', precio: 90000, stock: 5 },
// //      { nombre: 'Cable',   precio:  2000, stock: 0 },
// //    ];
// //
// //  1. Sacá un array solo con los nombres.
// //  2. Sacá los productos que tienen stock.
// //  3. Calculá el valor total del inventario (precio * stock, sumado).
// //  4. Encontrá el primer producto que cueste más de 50000.
// //  5. Creá un array nuevo donde todos los precios tengan 10% de
// //     aumento, sin modificar el original.
// //  6. Convertí el resultado del punto 5 a JSON con formato legible.
// // ============================================================


const persona = { 
  nombre: 'Ana', 
  edad: 25, 
  ciudad: 'Buenos Aires' 
};

persona.direccion = { calle: 'Falsa', numero: 123 };
const texto = JSON.stringify(persona);
console.log(texto);
console.log(JSON.parse(texto));