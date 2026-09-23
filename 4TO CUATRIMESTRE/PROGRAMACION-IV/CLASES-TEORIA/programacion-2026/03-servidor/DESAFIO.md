# Desafío · Rompé la API del de al lado

**Bloque 14 · Trabajo en parejas · Sin buscar en internet, sin asistentes de IA**

---

## Por qué este desafío es distinto

Escribir un endpoint lo puede hacer cualquiera con ayuda. **Romper el código de
otra persona, en vivo, en veinte minutos, no.**

En la segunda parte de este desafío vas a tener que pensar como alguien que
quiere que tu compañero falle. Eso no se delega.

---

## Etapa 1 · Cada uno escribe su API

**Trabajás solo. Archivo nuevo: `reservas.js`.**

La Facultad necesita un sistema para reservar el laboratorio. Tu API tiene que
tener dos rutas:

```
GET  /reservas     devuelve todas las reservas
POST /reservas     crea una reserva nueva
```

Una reserva se manda así:

```json
{
  "aula": "Lab 3",
  "responsable": "Ana Gómez",
  "duracionHoras": 2,
  "cantidadPersonas": 25
}
```

### Las reglas del negocio

| Campo | Regla |
|---|---|
| `aula` | Texto obligatorio, entre 3 y 30 caracteres |
| `responsable` | Texto obligatorio, entre 3 y 50 caracteres |
| `duracionHoras` | Número obligatorio, entre 1 y 8 |
| `cantidadPersonas` | Número entero obligatorio, entre 1 y 40 |

Si todo está bien, la API guarda la reserva con un `id` y una `fechaCreacion`, y
devuelve **201** con la reserva creada.

Si algo está mal, devuelve **400** con un mensaje que explique qué está mal.

### Lo único que se te pide además

> **Tu servidor no se puede caer. Nunca. Pase lo que pase.**

Mandale lo que le mandes, tiene que seguir vivo y seguir respondiendo.

**Tenés 25 minutos.** No busques en internet. Usá los archivos de la clase si
necesitás recordar algo.

---

## Etapa 2 · Rompé la del compañero

**Ahora cambian de máquina.** Vos te sentás en la máquina del de al lado, con su
código corriendo, y él en la tuya.

**Tu objetivo: hacer que su API haga algo que no debería.**

Hay tres niveles de victoria, de menor a mayor:

🥉 **Bronce** — Lograr que acepte una reserva que viola las reglas.

🥈 **Plata** — Lograr que devuelva un 500 o un error feo en lugar de un 400
limpio.

🥇 **Oro** — **Matar el servidor.** Que el proceso termine y deje de responder a
todo el mundo.

### Anotá cada intento

Llevá una planilla así. Es el entregable de esta etapa:

| # | Qué mandé | Qué esperaba | Qué pasó | Nivel |
|---|---|---|---|---|
| 1 | `{"aula":"ab",...}` | 400 | 400 ✓ | — |
| 2 | ... | | | |

**Tenés 20 minutos.** Probá al menos diez cosas distintas.

### Si no se te ocurre nada

Estas preguntas son pistas, no respuestas. Pensá qué pasaría en cada caso:

- ¿Qué pasa si un campo obligatorio viene, pero vale `null`?
- ¿Qué pasa si mandás el número como texto: `"duracionHoras": "2"`?
- ¿Qué pasa si mandás `2.5` personas?
- ¿Qué pasa si mandás un número negativo? ¿Y cero?
- ¿Qué pasa si el nombre son solo espacios en blanco?
- ¿Qué pasa si mandás el cuerpo vacío?
- ¿Qué pasa si mandás algo que **es** JSON válido pero **no es** un objeto?
- ¿Qué pasa si mandás un campo que no está en las reglas?
- ¿Qué pasa si el texto tiene diez mil caracteres?
- ¿Qué pasa si mandás `1e999`?

**No leas la etapa 3 hasta terminar esta.**

---

## Etapa 3 · Arreglá lo tuyo

Volvés a tu máquina. Tu compañero te entrega la planilla con todo lo que te
rompió.

**Arreglá cada cosa.** Y por cada arreglo, anotá en un comentario del código:

```js
// Arreglado: rompía cuando el cuerpo era `null`.
// Causa: JSON.parse('null') devuelve null, y leer una propiedad de
// null lanza TypeError, que nadie atrapaba, y eso mata el proceso.
```

**Tenés 15 minutos.**

---

## Etapa 4 · Puesta en común

Cada pareja cuenta **el mejor ataque que encontró**. Los anotamos en el
pizarrón.

La pregunta con la que cerramos: de todos los ataques del pizarrón, ¿cuántos se
te habrían ocurrido escribiendo el código solo, sin nadie tratando de romperlo?

---

## Qué se entrega

1. Tu `reservas.js` arreglado, con los comentarios de cada corrección.
2. La planilla de ataques que le hiciste a tu compañero.
3. Tres renglones respondiendo: **¿cuál de los ataques que recibiste te
   sorprendió más, y por qué no se te había ocurrido?**

---

## Cómo se corrige

No se corrige por cuántos ataques resististe en la etapa 1. Se corrige por esto:

| | |
|---|---|
| **Buscaste en serio** | Diez intentos distintos, no tres variaciones de lo mismo |
| **Entendiste la causa** | Los comentarios explican *por qué* fallaba, no solo *qué* cambiaste |
| **Arreglaste bien** | La corrección no rompe otra cosa |
| **Reflexionaste** | La respuesta final es honesta, no una frase de compromiso |

Alguien cuya API se cayó en la etapa 2 y que entendió exactamente por qué, sacó
más provecho que alguien que no fue atacado con ganas.
