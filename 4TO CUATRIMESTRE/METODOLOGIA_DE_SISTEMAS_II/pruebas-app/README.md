# Pruebas E2E de la app (Cypress)

Las pantallas de la app móvil (`/app/`) se prueban con **Cypress**, la herramienta E2E del equipo (la misma de
`cypress/e2e/rfg04_admision.cy.js`). Los specs están en la raíz del repo, en `cypress/e2e/app_*.cy.js`, y corren
en tamaño celular (390×844). Complementan los tests de integración de PHPUnit, que prueban la API por HTTP.

## 1. Base y servidor

Base de prueba `educar_test` (nunca `educar_db`), armada con el script del sistema:

```bash
cd "3ER CUATRIMESTRE/METODOLOGIA_DE_SISTEMAS_I"
MYSQLHOST=127.0.0.1 MYSQLUSER=root MYSQLDATABASE=educar_test bash ci/preparar_bd.sh
```

Servidor sobre esa base en el puerto que espera `cypress.config.js` (`http://localhost:8080`):

```bash
cd "3ER CUATRIMESTRE/METODOLOGIA_DE_SISTEMAS_I"
MYSQLDATABASE=educar_test /c/xampp/php/php.exe -S localhost:8080
```

Conviene rearmar la base antes de correr los specs si antes se corrió PHPUnit (los tests dejan datos propios).

## 2. Correr los specs

En otra terminal, desde la **raíz del repo** (`TP_FACULTAD/`):

```bash
npx cypress run --browser edge --spec "cypress/e2e/app_*.cy.js"
```

Se usa **Edge** porque en esta máquina el navegador interno de Cypress (Electron) se cae al arrancar.
Las capturas de los casos fallidos quedan en `cypress/screenshots/` (está en el `.gitignore`).

| Spec | Paso | Qué prueba |
|---|---|---|
| `app_ingreso.cy.js` | 2 (RFGm01/02) | Docente rechazado, primera visita sin perder lo tipeado, padre con dos hijos (inicio, cambio de hijo, cuenta, validación de contraseña, cierre de sesión), alumno sin selector, sesión vencida |
| `app_cuotas_facturas.cy.js` | 3 (RFGm03/04) | Cuotas (vencidas en rojo, por vencer en amarillo, pagadas con recibo), facturas con filtro de fechas, detalle y PDF, historial de deuda por ítem |

**Pendiente post-entrega:** el spec del Paso 4 (pagos y comprobantes) quedó a medias (2/3, sin volver a correr
después de un arreglo) y no está en el repo. El Paso 4 está cubierto por los tests de PHPUnit (`AppPagosTest`). La pantalla de
Inscripciones (Paso 5) tampoco tiene spec ni revisión en navegador; su lógica la cubre `AppInscripcionesTest`.

## Notas

- `php -S` en Windows atiende **un pedido por vez** y el navegador abre conexiones de más: un ingreso puede tardar
  10–25 s. Por eso los specs de la app suben los tiempos de espera **en el propio spec** (no en la configuración
  general). En Render (Linux, 4 workers) no pasa.
- Cada spec verifica además que la página no tenga scroll horizontal en 390 px.
