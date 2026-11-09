# Pruebas en navegador de la app (Playwright)

Pruebas de humo de la PWA (`/app/`) en tamaño celular (390×844), con el **Edge instalado en Windows**
(no descarga Chromium). Complementan los tests de integración de PHPUnit (que prueban la API por HTTP):
acá se verifica que las pantallas cargan, navegan y no tiran errores de JavaScript. Están fuera del
sistema para que no se desplieguen.

## Cómo correrlas

1. Base de prueba armada (`educar_test`, ver `CLAUDE.md` del sistema) y servidor levantado sobre ella:

   ```bash
   cd "3ER CUATRIMESTRE/METODOLOGIA_DE_SISTEMAS_I"
   MYSQLDATABASE=educar_test /c/xampp/php/php.exe -S 127.0.0.1:8096
   ```

2. En otra terminal, desde esta carpeta (la primera vez, `npm install`; no descarga navegadores):

   ```bash
   PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1 npm install
   node paso2-ingreso.js http://127.0.0.1:8096     # o: npm run paso2
   ```

   La URL es opcional (por defecto `http://127.0.0.1:8096`; también `APP_URL`). Se puede apuntar a Render.

Cada script imprime `OK` / `FALLA` por caso y deja las capturas en `capturas/` (las fallas como `FALLA-*.png`).
Sale con código 1 si algo falló.

## Notas

- `php -S` en Windows atiende **un pedido por vez** y el navegador abre conexiones de más: un ingreso puede
  tardar 10–25 s. Por eso el tiempo de espera es de 20 s. En Render (Linux, 4 workers) no pasa.
- Cada captura verifica además que la página no sea más ancha que la pantalla (390 px): si hay scroll
  horizontal, el caso falla.
- Los 401/403 de la API que el navegador anota en la consola son esperables y se ignoran; cualquier otro
  error de JavaScript hace fallar el caso.
- Cypress (que está en la raíz del repo) no corre en esta máquina: se cae su proceso de Electron. Ver la
  bitácora (filas 9 y 10).

| Script | Paso | Qué prueba |
|---|---|---|
| `paso2-ingreso.js` | 2 (RFGm01/02) | Docente rechazado, primera visita sin perder lo tipeado, padre con dos hijos, alumno, sesión vencida |
| `paso3-cuotas.js` | 3 (RFGm03/04) | Cuotas con colores y recibo, facturas con detalle y PDF, deuda por concepto, filtro inválido, alumno sin recibo |
