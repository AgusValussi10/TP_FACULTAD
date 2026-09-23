// ============================================================
//  4.A  Por que la jerarquia se rompe: el problema es de diseño
// ============================================================
'use strict';
// Arrancamos con la jerarquia que cualquiera dibujaria en el pizarron
// para el sistema de telemetria de la materia.

class Dispositivo {
  constructor(id) { this.id = id; }
  leer() { throw new Error('abstracto'); }
}

class DispositivoConectado extends Dispositivo {
  constructor(id, url) { super(id); this.url = url; }
  async enviar(datos) { return `POST ${this.url}: ${JSON.stringify(datos)}`; }
}

class SensorTemperatura extends DispositivoConectado {
  leer() { return { t: 21.5 }; }
}

class SensorHumedad extends DispositivoConectado {
  leer() { return { h: 60 }; }
}

// Todo bien. Hasta que llega el requerimiento numero cuatro:
//
//   "el sensor de temperatura del deposito no tiene red: guarda en
//    tarjeta SD y alguien la busca una vez por semana"
//
// Ese sensor es un SensorTemperatura, pero NO es un DispositivoConectado.
// La jerarquia ya decidio que todo sensor de temperatura tiene url.
//
// Las tres salidas habituales, todas malas:
//
//  1. Pasar url = null y que `enviar` tire error.
//     -> viola el principio de sustitucion: el tipo miente.
//  2. Duplicar la rama: SensorTemperaturaOffline extends Dispositivo.
//     -> la logica de lectura queda duplicada en dos ramas.
//  3. Subir `enviar` a Dispositivo y que a veces no haga nada.
//     -> ahora TODOS los dispositivos cargan una capacidad que no tienen.

console.log('--- Sintoma 1: el metodo que existe pero no funciona');
class SensorOffline extends DispositivoConectado {
  constructor(id) { super(id, null); }
  leer() { return { t: 19.0 }; }
  async enviar() { throw new Error('este dispositivo no tiene red'); }
}
const lista = [new SensorTemperatura('S-01', 'http://a'), new SensorOffline('S-02')];
for (const d of lista) {
  console.log(d.id, 'es DispositivoConectado?', d instanceof DispositivoConectado);
}
// Los dos dan true. El tipo dejo de servir para decidir. Cualquier codigo
// que haga `if (d instanceof DispositivoConectado) d.enviar(...)` esta roto.

console.log('\n--- Sintoma 2: la clase base se llena de "por si acaso"');
// Cada requerimiento nuevo empuja algo hacia arriba. En seis meses
// Dispositivo tiene doce metodos y ninguna instancia usa mas de cuatro.

console.log('\n--- Sintoma 3: el problema del diamante');
// "Necesito un dispositivo que envie por red Y guarde en disco."
// No hay herencia multiple. Se copia y pega, o se hace una jerarquia
// combinatoria: Conectado, ConDisco, ConectadoConDisco, ConectadoConDiscoYBateria...
// Con n capacidades independientes hacen falta 2^n clases.

console.log(`
El diagnostico, en una frase:
  la herencia obliga a decidir la clasificacion ANTES de conocer todos los
  casos, y esa decision despues no se puede cambiar sin tocar todo.
  Las capacidades de un objeto real no forman un arbol: forman un conjunto.
`);

// ------------------------------------------------------------
//  PARA PROBAR VOS
//  1. Escribi las clases que harian falta para cubrir estas cuatro
//     capacidades independientes: red, disco, bateria, cifrado.
//     Cuantas son? Ahora agrega una quinta.
//  2. Cual de las tres "salidas malas" viste en un sistema real?
// ------------------------------------------------------------
