# Educar para Transformar

Sitio web institucional de una escuela ubicada en Resistencia, Chaco, Argentina.  
TP universitario — UTN FRRE · Metodología de Sistemas I.

## Links

- **Sitio web:** https://educar-para-transformar-nw1k.onrender.com/
- **Panel admin:** https://educar-para-transformar-nw1k.onrender.com/portals/portal_admin.php
- **Portal docente:** https://educar-para-transformar-nw1k.onrender.com/portals/portal_docente.php
- **Portal alumno:** https://educar-para-transformar-nw1k.onrender.com/portals/portal_alumno.php
- **Portal padre/tutor:** https://educar-para-transformar-nw1k.onrender.com/portals/portal_padre.php
- **Base de datos (phpMyAdmin):** https://adminer.cleverapps.io/?server=bb27k4j4tjl9twuspaie-mysql.services.clever-cloud.com&username=u7jj80olqkv5yaos&db=bb27k4j4tjl9twuspaie

## Accesos de prueba

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin` | `admin` | Administrador |
| `maria.rodriguez` | `docente123` | Docente |
| `ana.garcia` | `alumno123` | Alumno |
| `laura.martinez` | `padre123` | Padre/Tutor |

## Stack

- PHP 8.2 · MySQL 5.5 · HTML5 · CSS3 · JavaScript Vanilla
- Deploy: Render (Docker) + Clever Cloud (MySQL)

## Deploy

El sitio se despliega automáticamente en Render con cada push a `main`.  
La base de datos está en Clever Cloud (`bb27k4j4tjl9twuspaie-mysql.services.clever-cloud.com`).

## Servicios utilizados

- **Render:** https://dashboard.render.com
- **Clever Cloud (DB):** https://console.clever-cloud.com
- **phpMyAdmin (Clever):** https://adminer.cleverapps.io
