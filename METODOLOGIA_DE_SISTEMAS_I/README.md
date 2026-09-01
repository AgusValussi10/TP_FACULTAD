# Educar para Transformar - Portal educativo

Sistema web para la gestion y comunicacion de un centro educativo: inscripciones online, noticias institucionales, opiniones de la comunidad, consultas de contacto, busqueda laboral y portales privados por rol (Administrador, Docente, Alumno, Padre/Tutor).

## Descripcion

La aplicacion combina un sitio institucional publico (noticias, informacion del centro, formulario de inscripcion y de contacto) con un backoffice privado donde el equipo administrativo gestiona las solicitudes de inscripcion, publica noticias, modera opiniones y mensajes, y administra las busquedas laborales. Cada usuario autenticado accede a un portal distinto segun su rol.

## Como ejecutar el programa

Requisitos: PHP 8.0+ con la extension mysqli, y un servidor MySQL/MariaDB (por ejemplo, via XAMPP).

1. Clona el repositorio dentro de la carpeta que sirve tu servidor local (por ejemplo, C:\xampp\htdocs\).
2. Inicia Apache y MySQL desde el panel de XAMPP.
3. Crea la base de datos educar_db e importa el esquema y los datos iniciales:
   ```
   mysql -u root educar_db < database/schema.sql
   ```
   (o ejecuta database/setup_demo.php desde el navegador para cargar datos de ejemplo).
4. La conexion usa por defecto localhost / usuario root / sin contrasena (ver database/db_config.php). Si tu entorno usa otras credenciales, definilas como variables de entorno (MYSQLHOST, MYSQLDATABASE, MYSQLUSER, MYSQLPASSWORD, MYSQLPORT).
5. Abrir http://localhost/METODOLOGIA_DE_SISTEMAS_I/ en el navegador.

Tambien puede ejecutarse sin Apache, usando el servidor embebido de PHP:
```
php -d extension=mysqli -S 0.0.0.0:8000
```

## Funcionalidades principales

- Sitio publico: noticias institucionales, formulario de inscripcion, formulario de opiniones y de consultas/contacto, listado de busquedas laborales con postulacion online.
- Autenticacion por roles (auth/): login, recuperacion de acceso y sesion por rol (admin, docente, alumno, padre).
- Portal Administrador (portals/portal_admin.php): gestion de solicitudes de inscripcion (admitir/rechazar), noticias (publicar/editar/archivar), opiniones y consultas recibidas, y busquedas laborales/postulaciones.
- Portales Docente, Alumno y Padre/Tutor: vistas especificas para cada rol dentro de la institucion.
- Modulo de empleos (propuestas/): publicacion de puestos vacantes y gestion de postulaciones.

## Integrantes

- Fabrizio Rios
- Agustin Valussi

