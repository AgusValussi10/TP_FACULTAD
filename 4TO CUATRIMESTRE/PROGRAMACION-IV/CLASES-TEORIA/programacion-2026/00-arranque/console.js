console.log(x)
var x = 5;

console.log(y)
let y = 10;

console.log(sumar(2,3))
function sumar(a, b) {
  return a + b;
}


declarada() 
function declarada() {
  console.log("Función declarada");
}

asignada()
let asignada = function() {
  console.log("Función asignada");
}

var a;
var b;
function testing() {
  if(false) {
    a = 5;
    b = 10;
  }
  console.log(a);
  console.log(b);
}

testing();