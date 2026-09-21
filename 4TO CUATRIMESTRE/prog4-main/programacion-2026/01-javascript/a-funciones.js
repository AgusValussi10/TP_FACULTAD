

// const saludar = (nombre) => `Hola, ${nombre}`;

// console.log(saludar("Emilio", 21, "Chaco"))

// const cosas = [20, "French y Ayacucho", (x) => x * 2]

// console.log(cosas[2](5)) // 10

function operacion(valor) {
  return valor * 2;
}
console.log(operacion(6));

const aplicarDosVeces = (fn, valor) => fn(fn(valor));


console.log(aplicarDosVeces(operacion, 3));

