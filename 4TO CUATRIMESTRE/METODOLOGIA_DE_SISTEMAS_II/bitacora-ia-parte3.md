# Bitácora de uso de IA — Parte 3 (App Móvil)

Metodología de Sistemas II · UTN FRRe · Grupo 14 "Bros System"
Sistema: "Educar para Transformar" (`3ER CUATRIMESTRE/METODOLOGIA_DE_SISTEMAS_I/`)

## Registro

| Nº | Problema | Prompt utilizado | Herramienta de IA | Respuesta de IA (resumen) | ¿Funcionó? (Sí/Parcialmente/No) | Modificaciones realizadas | Resultado |
|---|---|---|---|---|---|---|---|
| 1 | Diagnóstico del sistema existente y diseño del modelo de datos de la app (facturas, pagos, comprobantes, tarifas, recorridos) sin duplicar lo que ya existe (cuotas, deportes, reservas). | "Paso 0 — Diagnóstico y diseño (sin código)": leer `cuotas`, `deportes`, `reservas_servicios`, `padre_alumno`, login y sesión; proponer modelo de datos, estructura de la PWA y riesgos para el 11/11. | Claude Code (Claude Opus 5.5) | Revisó el repo y los PR (no hay PR abiertos; #13–#21 mergeados). Propuso reutilizar `cuotas` (el ítem de factura apunta a la cuota), estructurar el horario de los deportes en días + franja, guardar comprobantes en la base (el disco de Render se borra), tarifas sin historial (el importe queda congelado en el ítem de la factura) y PWA servida desde `app/` con API en `app/api/`. Marcó riesgos: plazo de 2 días, `max_allowed_packet`/espacio de Filess.io para los archivos, conflicto con el registro de pagos en efectivo de la web y la regla vieja "sin máximo de deportes" (RFG11). | Parcialmente | El equipo ajustó 3 puntos: el recargo debe salir de `cuotas.recargo` (no de un cálculo nuevo), sacar el efectivo de los pagos nuevos sin romper los registros viejos, y renovar la cookie de sesión solo en la API de la app. Se aceptaron los recortes (reportes y mails, después del 11/11). | Modelo aprobado con ajustes; asunciones documentadas en CLAUDE.md. |
| 2 | Modelo de datos de la Parte 3 en producción (MySQL 5.5, sin `ADD COLUMN IF NOT EXISTS`, base con otro nombre) sin romper el arranque de Render. | "Paso 1: tablas, `init_db.php`, `schema_parte3.sql` sin `USE` y seed idempotente." | Claude Code (Claude Opus 5.5) | Creó `schema_parte3.sql` y un bloque propio en `init_db.php` que verifica columnas contra `information_schema`. Las tarifas tienen la clave como PK (una sola clave única, para no repetir el bug de `ON DUPLICATE KEY` con dos únicas del 03/11). Un solo TIMESTAMP por tabla. | Sí | Ninguna. Se probó correr `init_db.php` dos veces seguidas sobre `educar_test`. | `ci/preparar_bd.sh` arma la base con el modelo nuevo; `init_db.php` es idempotente. |
| 3 | Facturar sin duplicar la deuda que ya existe en `cuotas` (RFG12/13/19) y que la web y la app muestren lo mismo. | "El ítem cuota referencia a la fila de cuotas; al pagarse, la cuota se marca pagada como hoy." | Claude Code (Claude Opus 5.5) | Concentró las reglas en `lib/facturacion.php`. `marcar_cuota_pagada()` es el único punto que marca una cuota pagada: la usan la aprobación del pago y el cobro del admin en la web (`cuotas_pagar.php`), y además actualiza el ítem y recalcula la factura. El recargo se guarda una sola vez en `cuotas.recargo` y se factura como ítem del mes siguiente. | Sí | Se agregó un bloqueo: el admin no puede cobrar por la web una cuota que tiene un pago por transferencia en revisión (se encontró al revisar el diseño, no en un test). | Test "el cobro de la web marca el ítem y no acepta efectivo" en verde. |
| 4 | Pantallas del admin (tarifas, facturación, pagos a revisar) sin copiar las ~100 líneas de CSS que repite cada pantalla de `gestion/` (SonarCloud penaliza la duplicación). | "Pantallas del admin en gestion/ … agregalas a la barra del portal admin." | Claude Code (Claude Opus 5.5) | Hizo un layout común (`layout_admin.php`), `css/admin.css` y `js/admin.js` (escape de HTML, formato de pesos, fetch con `{success, data, message}`). | Parcialmente | El primer intento de crear los endpoints con un heredoc de Bash falló por las comillas; se escribieron archivo por archivo. En tarifas se reemplazó un `<label hidden>` (no lo leen los lectores de pantalla) por `aria-label`. | 3 pantallas funcionando y enlazadas desde el portal admin. |
| 5 | Tests de facturación (`FacturacionTest`): generar dos veces, suma de ítems, aprobar y rechazar, permisos. | "Tests: generar dos veces no duplica; la factura suma bien; aprobar marca ítems, factura y cuota; rechazar no marca nada; solo el admin." | Claude Code (Claude Opus 5.5) | 9 tests por HTTP con sesiones reales, sobre un período sin datos (marzo del año siguiente) que se limpia antes y después. | Parcialmente | Corrido solo, pasaba. Con la suite completa falló "la factura suma bien" (esperaba $68.500 y había $69.500): `CuotasEstadoTest` deja a ana con una cuota impaga vencida, y la factura le sumaba **correctamente** el recargo. Se corrigió lo que esperaba el test (suma los recargos), no el código. `phpcs` marcó un error de formato en `cuotas_pagar.php` y se arregló con `phpcbf`. | 74/74 tests en verde, dos corridas seguidas sin rearmar la base. |
| 6 | Error de regla detectado por el equipo en la revisión: con el diseño del Paso 1, una cuota vencida pagada por la app antes de la facturación siguiente quedaba **sin recargo** (RN11), mientras que la web sí lo cobra. | "Al crear un pago que incluya una cuota vencida sin recargo facturado: calculalo con la misma función del Sprint 5, guardalo en cuotas.recargo y agregalo como ítem a esa misma factura… con su test." | Claude Code (Claude Opus 5.5) | Agregó `agregar_recargo_a_factura()` en `lib/facturacion.php`, la llamó desde `crear_pago()` y factorizó `item_de_recargo()` para que la facturación mensual y el pago usen el mismo cálculo. El test `RecargoAppTest` cubre que el recargo aparece una sola vez y que la facturación siguiente no lo repite. | Sí | La asunción original de la IA ("si se paga antes, no lleva recargo") era incorrecta y la marcó el equipo, no la IA. Para confirmar que el test detecta el error, se sacó el arreglo a propósito: el test falló ($40.000 en lugar de $44.000). | 77/77 tests en verde; asunción corregida en CLAUDE.md. |

## Decisión de tecnología

El Plan de Trabajo preveía React Native + Expo. Se cambia a una **PWA** servida por el mismo backend PHP (carpeta `app/`):
- mismo origen que la web: reutiliza la sesión, `helpers.php`, la base y el deploy, sin CORS ni tokens y sin duplicar datos (la consigna pide integrarse "evitando duplicación e inconsistencias");
- una sola base de código para Android, iOS y escritorio, sin tiendas;
- entra en el plazo (testing 11/11).

## Notas para el informe

### Qué existía en la web
- Cuotas y matrícula por alumno (RFG12) cargadas a mano por el admin, con recargo por mora y recibo en PDF; estado de cuotas para padre, docente y admin (RFG19).
- Inscripción a deportes con cupo (RFG11, horario en texto y sin profesor responsable) y reserva mensual de comedor/transporte con plazo del día 25 (RFG09), sin recorridos ni valores.
- No había tarifas, facturas, pagos por transferencia ni comprobantes.

### Qué se agregó en la app
- 

### Qué datos comparten
- 

### Cómo se comunican
- 

### Qué cambió en el backend
- Tablas nuevas: tarifas, recorridos_transporte, facturas, factura_items, pagos, pago_items, comprobantes_transferencia; columnas nuevas en deportes (profesor, días, franja) y reservas_servicios (recorrido).
- `lib/facturacion.php` concentra las reglas; pantallas nuevas del admin: Tarifas, Facturación y Pagos a revisar.
- El cobro del admin en la web ya no ofrece efectivo y quedó sincronizado con la factura.

### Cómo se mantienen sincronizados
- Una sola base y una sola fila de deuda: el ítem de factura apunta a la cuota; `marcar_cuota_pagada()` actualiza la cuota, el ítem y la factura en la misma transacción, la use la web o la app.
