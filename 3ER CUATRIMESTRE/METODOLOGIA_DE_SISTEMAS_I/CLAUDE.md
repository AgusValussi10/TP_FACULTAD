# CLAUDE.md — Plataforma Educativa "Educar para Transformar"

## Descripción general

Sitio web institucional de una escuela ubicada en Resistencia, Chaco, Argentina. Combina una landing page pública con un sistema de gestión interno multi-rol. El proyecto es un TP universitario (UTN FRRE — Metodología de Sistemas I).

**Stack:** PHP 8.2 · MySQL 5.5 · HTML5 · CSS3 · JavaScript Vanilla (sin frameworks)

**Deploy:** Render (Docker) — https://educar-para-transformar-nw1k.onrender.com/  
**Base de datos:** Filess.io — `o7q9z2.h.filess.io:3307` / DB: `metodologiasistemas2_extratype`  
**Gestor BD:** MySQL Workbench (local) — phpMyAdmin.co no funciona porque bloquea la IP del servidor externo

---

## Estructura de archivos

```
.
├── index.html                  ← Landing page pública (toda la UI en un solo archivo)
├── index.php                   ← Enrutador: inyecta banner de sesión activa sobre index.html
├── lib/
│   ├── fpdf/                   ← FPDF 1.86 (librería PDF sin dependencias, vendorizada; solo fuentes Helvetica)
│   ├── recibo.php              ← render_recibo(): dibuja el recibo de pago en PDF (no toca BD ni sesión)
│   └── facturacion.php         ← Parte 3: tarifas, generación mensual, pagos, comprobantes y su revisión (única fuente de esas reglas)
├── assets/                     ← Imágenes optimizadas en formato AVIF (+ logo.png para los PDF: FPDF no lee AVIF)
│   ├── edificio.avif
│   ├── piscina.avif
│   ├── gimnasio.avif
│   ├── campofutbol.avif
│   └── salacomputacion.avif
├── auth/
│   ├── session.php             ← Inicia sesión PHP (timeout 30 min)
│   ├── login.php               ← Endpoint POST de autenticación → JSON
│   └── logout.php              ← Destruye sesión y redirige a /
├── database/
│   ├── db_config.php           ← Conexión MySQL (lee env vars con fallback a localhost)
│   ├── schema.sql              ← DDL base (tablas públicas: usuarios, solicitudes, noticias, etc.)
│   ├── schema_gestion.sql      ← Sprint 1: cursos, materias, alumno_curso, asistencias, calificaciones
│   ├── schema_sprint2.sql      ← Sprint 2: padre_alumno, reservas_servicios, atenciones_enfermeria, notificaciones; agrega rol 'enfermeria'
│   ├── schema_sprint3.sql      ← Sprint 3: planificaciones, recuperatorios
│   ├── schema_sprint4.sql      ← Sprint 4: sanciones; agrega columna capacidad a cursos
│   ├── schema_sprint5.sql      ← Sprint 5: deportes, inscripciones_deportivas, cuotas; agrega alumno_curso.condicion
│   ├── schema_sprint6.sql      ← Sprint 6 (sin USE): RFG14 columnas de sanciones (días, sin_contacto, anulación)
│   ├── schema_parte3.sql       ← Parte 3 / app móvil (sin USE): tarifas, recorridos_transporte, facturas, factura_items, pagos, pago_items, comprobantes_transferencia; columnas en deportes y reservas_servicios
│   ├── seed_parte3.php         ← Parte 3: tarifas, profesores, inscripciones/reservas, facturas de 3 meses y pagos de demo (idempotente; consola o URL con sesión de admin)
│   ├── schema_legajo.sql       ← alumnos_datos: datos personales del legajo (DNI, nacimiento, contactos, salud)
│   ├── seed_legajo.php         ← Datos personales de demo para los 10 alumnos (idempotente, no pisa ediciones)
│   ├── seed_cuotas.php         ← RFG19: 4 cuotas de demo por alumno con vencimientos relativos a hoy (SOLO por consola; idempotente)
│   ├── setup_demo.php          ← Usuarios base de demo (admin, 2 alumnos, 2 docentes, 2 padres, enfermeria)
│   ├── seed_datos.php          ← Seed masivo: 13 usuarios nuevos + todos los datos de demostración (secciones 1–18)
│   └── seed_parte2.php         ← Continuación del seed desde sección 11 (útil si seed_datos falla a mitad)
├── init_db.php                 ← Corre en el deploy (Render): crea tablas públicas, Sprint 3/4 (planificaciones, recuperatorios, sanciones), Sprint 5, alumnos_datos, consultas_notas; agrega columnas faltantes, elimina materias duplicadas y crea lo de la Parte 3 (bloque propio)
├── portals/
│   ├── portal_admin.php        ← Panel de administración
│   ├── portal_docente.php      ← Portal docente
│   ├── portal_alumno.php       ← Portal alumno
│   ├── portal_padre.php        ← Portal padre/tutor
│   └── portal_enfermeria.php   ← Portal enfermería (RFG10)
├── gestion/
│   ├── helpers.php                    ← Funciones auxiliares compartidas
│   ├── asistencia.php                 ← UI registro de asistencia (docente)
│   ├── asistencia_guardar.php         ← POST → guardar asistencia
│   ├── asistencia_listar.php          ← GET → JSON asistencias
│   ├── calificaciones.php             ← UI carga de notas (docente)
│   ├── calificaciones_guardar.php     ← POST → guardar calificación
│   ├── calificaciones_listar.php      ← GET → JSON calificaciones
│   ├── faltas_resumen.php             ← GET → resumen de faltas por alumno
│   ├── materias_docente.php           ← GET → materias del docente en sesión
│   ├── servicios.php                  ← UI reserva comedor/transporte (padre)
│   ├── servicios_guardar.php          ← POST → guardar reserva de servicio
│   ├── servicios_listar.php           ← GET → JSON reservas
│   ├── enfermeria.php                 ← UI registro de atenciones (enfermería)
│   ├── enfermeria_alumnos.php         ← GET → lista de alumnos para enfermería
│   ├── enfermeria_guardar.php         ← POST → guardar atención
│   ├── planificacion.php              ← UI planificación anual: docente carga/edita; admin ve el control de todas (cargadas/pendientes)
│   ├── planificacion_guardar.php      ← POST docente → guardar planificación
│   ├── planificacion_listar.php       ← GET docente (su materia) / admin (todas las materias del año, filtro curso)
│   ├── recuperatorios.php             ← UI recuperatorios: docente programa/edita/califica/elimina; admin ve todos (solo lectura)
│   ├── recuperatorio_guardar.php      ← POST docente → crear (sin id) o editar (id); estado sale de la nota; notifica al padre
│   ├── recuperatorio_eliminar.php     ← POST docente → eliminar un recuperatorio de sus materias
│   ├── sanciones.php                  ← UI sanciones disciplinarias (admin y docente) — RFG14
│   ├── sanciones_guardar.php          ← POST admin/docente → registrar sanción y notificar al padre
│   ├── sanciones_listar.php           ← GET admin (todas) / docente (sus cursos) → JSON con filtros
│   ├── sanciones_anular.php           ← POST admin → anulación lógica con motivo, notifica al padre
│   ├── recuperatorio_listar.php       ← GET docente (?materia_id) / admin (?curso_id&estado)
│   ├── legajo.php                     ← UI legajo completo (admin/docente), ?alumno_id=N lo abre directo, imprimible
│   ├── legajo_listar.php              ← GET → JSON legajo completo (situación económica solo para admin)
│   ├── consulta_nota_guardar.php      ← POST padre → consulta sobre una calificación (RFG07)
│   ├── consulta_nota_listar.php       ← GET padre (sus consultas) / docente (las de sus materias) → JSON
│   ├── consulta_nota_responder.php    ← POST docente → responder consulta y notificar al padre
│   ├── consultas_notas.php            ← UI docente: ver y responder consultas sobre notas
│   ├── cuotas.php                     ← UI cuotas y pagos (admin) — RFG12
│   ├── cuotas_generar.php / cuotas_listar.php / cuotas_pagar.php / cuotas_recalcular.php
│   ├── cuotas_estado.php              ← GET RFG19: padre/admin ?alumno_id= → cuotas con estado visual y resumen; docente → estados de sus cursos SIN importes
│   ├── cuotas_docente.php             ← UI docente (solo lectura): estado de cada cuota de los alumnos de sus cursos — RFG19
│   ├── layout_admin.php + css/admin.css + js/admin.js ← encabezado, estilos y utilidades JS compartidos de las pantallas admin de la Parte 3
│   ├── tarifas.php / tarifas_listar.php / tarifas_guardar.php   ← Parte 3: valores de cuota, matrícula, comedor, cada deporte y cada recorrido (solo admin)
│   ├── facturacion.php / facturacion_generar.php / facturas_listar.php ← Parte 3: generar el mes (idempotente) y listar facturas con filtros
│   ├── pagos_revisar.php / pagos_listar.php / pago_revisar.php ← Parte 3: ver comprobantes y aprobar/rechazar (con motivo) los pagos por transferencia
│   ├── comprobante_ver.php            ← GET admin (todos) / padre (los de sus hijos) ?id= → archivo del comprobante
│   ├── recibo_pdf.php                 ← GET admin, o padre para cuotas de sus hijos, ?cuota_id= → recibo PDF (solo cuotas pagadas)
│   ├── deportes.php                   ← UI inscripción a instalaciones deportivas — RFG11
│   ├── deportes_guardar.php / deportes_listar.php
│   ├── materias.php / materias_crear.php / materias_asignar.php / materias_listar.php ← ABM de materias y asignación de docentes
│   ├── boletin_listar.php             ← GET → JSON boletín de calificaciones
│   ├── vacantes.php                   ← UI gestión de vacantes (admin)
│   ├── vacantes_actualizar.php        ← POST → actualizar puesto vacante
│   ├── vacantes_listar.php            ← GET → JSON puestos vacantes
│   ├── roles_listar.php               ← GET → JSON roles disponibles
│   ├── alumnos.php                    ← UI ABM de alumnos (admin) — alta/baja/modificación + datos del legajo; ?editar=N abre el modal
│   ├── alumnos_listar.php             ← GET → JSON lista de alumnos con curso
│   ├── alumno_guardar.php             ← POST → crear o actualizar alumno y sus datos personales (alumnos_datos)
│   └── alumno_eliminar.php            ← POST → suspender/reactivar/eliminar alumno
├── noticias/
│   ├── listar.php              ← GET público → JSON de noticias publicadas
│   ├── polling.php             ← GET → JSON para refresh dinámico
│   ├── guardar.php             ← POST admin → crear noticia
│   ├── actualizar.php          ← POST admin → editar noticia
│   ├── cambiar_estado.php      ← POST admin → borrador/publicada/archivada
│   └── eliminar.php            ← POST admin → eliminar noticia
├── inscripciones/
│   ├── guardar.php             ← POST público → crear solicitud
│   ├── cambiar_estado.php      ← POST admin → actualizar estado solicitud
│   ├── admitir.php             ← POST admin → admitir alumno
│   └── polling.php             ← GET → refresh de solicitudes
├── opiniones/
│   ├── guardar.php             ← POST público → enviar opinión
│   ├── listar.php              ← GET público → JSON de opiniones aprobadas
│   ├── polling.php             ← GET → refresh dinámico
│   ├── cambiar_estado.php      ← POST admin → aprobar/rechazar
│   └── eliminar.php            ← POST admin → eliminar
├── consultas/
│   ├── guardar.php             ← POST público → enviar consulta de contacto
│   ├── cambiar_estado.php      ← POST admin → pendiente/leída/respondida/archivada
│   ├── eliminar.php            ← POST admin → eliminar
│   └── polling.php             ← GET admin → refresh de consultas
├── propuestas/
│   ├── listar_puestos.php      ← GET público → JSON de puestos activos
│   ├── guardar_postulacion.php ← POST público → enviar postulación
│   ├── agregar_puesto.php      ← POST admin → crear puesto vacante
│   ├── cambiar_estado_puesto.php      ← POST admin → activar/desactivar puesto
│   ├── cambiar_estado_postulacion.php ← POST admin → gestionar postulación
│   ├── eliminar_postulacion.php       ← POST admin → eliminar postulación
│   └── polling.php             ← GET → refresh de postulaciones
├── config/
│   └── composer.json           ← Requiere PHP >= 8.0
├── ci/
│   ├── preparar_bd.sh          ← Recrea una base de prueba con los schemas en orden + seed (lo usa el pipeline)
│   └── pipeline_local.sh       ← Corre lint + base + tests en local y mide cada etapa
├── tests/                      ← Tests de integración (PHPUnit) por HTTP con sesiones reales: RFG04, RFG05, RFG06, RFG07, RFG16
├── phpunit.xml
├── metricas/
│   ├── metricas_git.py         ← Métricas de entrega desde el historial de git (TP Unidad 4)
│   ├── carga_rf07.py           ← Prueba de carga de RFG07 (padres y docentes concurrentes)
│   ├── proxy_latencia.py       ← Proxy TCP que simula la latencia de la base remota
│   └── volumen_datos.php       ← Carga un ciclo lectivo de datos en la base de PRUEBA
├── .dockerignore               ← Deja tests/, ci/ y metricas/ fuera de la imagen (php -S sirve toda la carpeta)
└── package.json                ← Dev dependency: sharp ^0.34.5 (conversión de imágenes)
```

Pipeline CI/CD: `.github/workflows/ci-educar.yml` (en la raíz del repo) — 1 · Lint PHP → 2 · Tests RFG07
(MariaDB 10.11 como servicio) → 3 · Build de la imagen Docker; en `main` publica
`ghcr.io/agusvalussi10/educar-para-transformar:sha-<commit>` y `:latest`. Render debe tener
Auto-Deploy = "After CI Checks Pass". El PR también pasa por SonarCloud (Quality Gate sobre el código nuevo).

**Tests de integración** (`phpunit.xml`, 77 tests): `AdmisionTest` (RFG04), `PlanificacionTest` (RFG05),
`RecuperatoriosTest` (RFG06), `ConsultasNotasTest` (RFG07), `SancionesTest` (RFG14), `CuotasEstadoTest` (RFG19)
`LegajoTest` (RFG16) y `FacturacionTest` y `RecargoAppTest` (Parte 3: generación, revisión de pagos, cobro web sincronizado, recargo al pagar por la app, permisos). `tests/bootstrap.php` carga `lib/facturacion.php` para armar escenarios sin endpoint. Usan `tests/HttpClient.php`
(un "navegador" con cookies por instancia) y `tests/Db.php` (preparar/verificar datos); `tests/bootstrap.php` levanta
`php -S` en el puerto 8099. Usan fechas relativas a hoy y limpian sus propios datos, así que se pueden repetir sin
rearmar la base.

**Correrlos en local SIEMPRE sobre una base aparte** (`ci/preparar_bd.sh` hace `DROP DATABASE` de `$MYSQLDATABASE`;
sin la variable, borra `educar_db`):
```bash
MYSQLHOST=127.0.0.1 MYSQLUSER=root MYSQLDATABASE=educar_test bash ci/pipeline_local.sh   # lint + base + tests
MYSQLHOST=127.0.0.1 MYSQLUSER=root MYSQLDATABASE=educar_test phpunit                      # solo tests, base ya armada
```
`preparar_bd.sh` quita la línea `USE educar_db;` de los `schema_sprint*.sql` al aplicarlos (antes, con otra base,
esos schemas y sus seeds terminaban en `educar_db`).

**En Windows con XAMPP** (Git Bash): XAMPP trae **PHP 8.0.30** y MariaDB 10.4, fuera del PATH. CI y Render usan 8.2;
por eso `db_config.php` fija `mysqli_report(... STRICT)` (excepciones, como el default de 8.1+). Herramientas:
- Composer en `~/bin/composer` (wrapper de `composer.phar` con `/c/xampp/php/php.exe`); `phpcs` 3.13 y
  `phpunit` 9.6 instalados con `composer global require` en `%APPDATA%\Composer\vendor\bin`.
- El PATH va en este orden: `%APPDATA%/Composer/vendor/bin` **antes** de `/c/xampp/php`, porque XAMPP trae un
  PHPUnit viejo de PEAR que no anda en PHP 8. Más `/c/xampp/mysql/bin` para el cliente `mysql`.
- Pre-commit (Husky 9, `.husky/pre-commit` en la raíz del repo): corre `php -l` y `phpcs` con `phpcs.xml` sobre los PHP
  del sistema que entran al commit. Se activa con `npm install` en la raíz (`core.hooksPath = .husky/_`). Husky carga
  `~/.config/husky/init.sh` antes de cada hook: ahí se exporta el PATH de arriba. Errores marcados con `[x]` se
  corrigen con `phpcbf`. No usar `--no-verify`.

Servidor: `start.sh` levanta `php -S` con `PHP_CLI_SERVER_WORKERS=4` (configurable en Render).
`requerir_rol()` cierra la sesión (`session_write_close()`) apenas valida el rol: los endpoints de
gestión solo la leen; si un endpoint nuevo necesita ESCRIBIR en `$_SESSION`, no usar `requerir_rol()`
o reabrirla con `session_start()`.

---

## Base de datos

### Conexión (`database/db_config.php`)

Lee variables de entorno con fallback a localhost:

```
Host:     MYSQLHOST     → localhost           (prod: o7q9z2.h.filess.io)
Database: MYSQLDATABASE → educar_db           (prod: metodologiasistemas2_extratype)
User:     MYSQLUSER     → root                (prod: metodologiasistemas2_extratype)
Password: MYSQLPASSWORD → (vacío)
Port:     MYSQLPORT     → 3306                (prod: 3307)
Charset:  utf8
Timezone: America/Argentina/Buenos_Aires (-03:00)
```

Activa `mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)` antes de conectar (default desde PHP 8.1; se fija
para que PHP 8.0 se comporte igual). El error de conexión se captura con `try/catch` y responde HTTP 500 + JSON.

### Acceso con MySQL Workbench

Filess.io bloquea conexiones desde IPs de servidores externos (phpMyAdmin.co falla con error #1045).
Usar **MySQL Workbench** instalado localmente — conecta desde la IP del desarrollador.

Datos de conexión:
- **Hostname:** `o7q9z2.h.filess.io`
- **Port:** `3307`
- **Username:** `metodologiasistemas2_extratype`
- **Default Schema:** `metodologiasistemas2_extratype`
- **Password:** guardar en Vault al crear la conexión

### Tablas

**`usuarios`**
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| nombre | VARCHAR(100) | Nombre completo |
| usuario | VARCHAR(50) UNIQUE | Username de login |
| password_hash | VARCHAR(255) | bcrypt via password_hash() |
| rol | ENUM | 'alumno', 'docente', 'padre', 'admin', 'enfermeria' (agregado Sprint 2) |
| activo | TINYINT(1) | 1 = habilitado, 0 = suspendido |
| created_at | TIMESTAMP | |

**`solicitudes_inscripcion`**
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| nombre_alumno | VARCHAR(100) | |
| apellido_alumno | VARCHAR(100) | |
| fecha_nacimiento | DATE | Rango permitido: 1920–2025 |
| nivel_educativo | ENUM | 'Inicial', 'Primario', 'Secundario' |
| nombre_tutor | VARCHAR(100) | |
| telefono | VARCHAR(30) | |
| email | VARCHAR(150) | |
| comentarios | TEXT | Opcional |
| nivel_anterior_aprobado | TINYINT(1) NULL | Confirmación requerida antes de admitir (RFG04, Sprint 2) |
| estado | ENUM | 'pendiente', 'contactado', 'admitido', 'rechazado' |
| created_at | TIMESTAMP | |

**`noticias`**
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| titulo | VARCHAR(200) | |
| resumen | VARCHAR(400) | Preview en cards |
| contenido | TEXT | HTML permitido |
| categoria | ENUM | 'institucional', 'academica', 'deportiva', 'cultural', 'general' |
| imagen_url | VARCHAR(300) | URL http/https o ruta assets/ |
| estado | ENUM | 'borrador', 'publicada', 'archivada' |
| fecha_pub | DATE | Nullable |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP |

**`opiniones`**
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| nombre | VARCHAR(100) | Default 'Anónimo' |
| texto | TEXT | 10–2000 chars |
| mes | TINYINT UNSIGNED | Mes de envío |
| anio | SMALLINT UNSIGNED | Año de envío |
| estado | ENUM | 'pendiente', 'aprobado', 'rechazado' |
| created_at | TIMESTAMP | |

**`consultas`**
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| nombre | VARCHAR(100) | |
| email | VARCHAR(150) | |
| asunto | VARCHAR(200) | |
| mensaje | TEXT | 10–3000 chars |
| estado | ENUM | 'pendiente', 'leida', 'respondida', 'archivada' |
| created_at | TIMESTAMP | |

**`puestos_vacantes`**
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| titulo | VARCHAR(150) | |
| descripcion | TEXT | |
| tipo | VARCHAR(50) | Ej: 'Tiempo completo' |
| urgente | TINYINT(1) | Aparece primero si = 1 |
| activo | TINYINT(1) | Solo los activos se listan públicamente |
| created_at | TIMESTAMP | |

**`postulaciones`**
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| puesto_id | INT FK | → puestos_vacantes(id) ON DELETE CASCADE |
| nombre | VARCHAR(100) | Solo letras |
| apellido | VARCHAR(100) | Solo letras |
| dni | VARCHAR(20) | Mín 7 chars |
| email | VARCHAR(150) | |
| telefono | VARCHAR(30) | Mín 7 chars |
| experiencia_anios | TINYINT UNSIGNED | |
| experiencia_descripcion | TEXT | Mín 20 chars |
| estado | ENUM | 'pendiente', 'revisado', 'seleccionado', 'rechazado' |
| created_at | TIMESTAMP | |

### Tablas de gestión académica (Sprint 1–4)

**`cursos`** — `schema_gestion.sql`
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| nombre | VARCHAR(20) UNIQUE | Ej: '3°A' |
| nivel_educativo | ENUM | 'Inicial', 'Primario', 'Secundario' |
| capacidad | TINYINT UNSIGNED | Default 30 (agregado Sprint 4) |

**`materias`** — `schema_gestion.sql`
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| nombre | VARCHAR(100) | |
| curso_id | INT FK | → cursos(id) ON DELETE CASCADE |
| docente_id | INT FK NULL | → usuarios(id) ON DELETE SET NULL |

**`alumno_curso`** — `schema_gestion.sql`
| Campo | Tipo | Notas |
|---|---|---|
| alumno_id | INT PK FK | → usuarios(id) — un alumno tiene un único curso |
| curso_id | INT FK | → cursos(id) |

**`asistencias`** — `schema_gestion.sql`
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| alumno_id | INT FK | → usuarios(id) |
| materia_id | INT FK | → materias(id) |
| fecha | DATE | |
| estado | ENUM | 'presente', 'ausente', 'tarde' |
| — | UNIQUE | (alumno_id, materia_id, fecha) — sobrescribible |

**`calificaciones`** — `schema_gestion.sql`
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| alumno_id | INT FK | → usuarios(id) |
| materia_id | INT FK | → materias(id) |
| evaluacion | VARCHAR(150) | Nombre del examen/trabajo |
| nota | TINYINT UNSIGNED | |
| fecha_evaluacion | DATE | |
| — | UNIQUE | (alumno_id, materia_id, evaluacion) — sobrescribible |

**`padre_alumno`** — `schema_sprint2.sql`
| Campo | Tipo | Notas |
|---|---|---|
| padre_id | INT PK FK | → usuarios(id) |
| alumno_id | INT PK FK | → usuarios(id) |

**`reservas_servicios`** — `schema_sprint2.sql`
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| alumno_id | INT FK | → usuarios(id) |
| servicio | ENUM | 'comedor', 'transporte' |
| mes | TINYINT UNSIGNED | |
| anio | SMALLINT UNSIGNED | |
| estado | ENUM | 'confirmada' |
| — | UNIQUE | (alumno_id, servicio, mes, anio) |

**`atenciones_enfermeria`** — `schema_sprint2.sql`
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| alumno_id | INT FK | → usuarios(id) |
| motivo | VARCHAR(200) | |
| hora | TIME | |
| observaciones | TEXT NULL | |
| atendido_por | INT FK | → usuarios(id) |
| created_at | TIMESTAMP | |

**`notificaciones`** — `schema_sprint2.sql`
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| padre_id | INT FK NULL | → usuarios(id) |
| alumno_id | INT FK | → usuarios(id) |
| tipo | VARCHAR(50) | |
| mensaje | TEXT | |
| leida | TINYINT(1) | Default 0 |
| created_at | TIMESTAMP | |

**`planificaciones`** — `schema_sprint3.sql`
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| materia_id | INT FK | → materias(id) ON DELETE CASCADE |
| anio | SMALLINT UNSIGNED | |
| contenidos | TEXT | |
| observaciones | TEXT NULL | |
| actualizado_en | DATETIME NULL | Última edición (DATETIME y no TIMESTAMP: MySQL 5.5 admite un solo TIMESTAMP con CURRENT_TIMESTAMP) |
| — | UNIQUE | (materia_id, anio) — un plan por materia/año |

**`recuperatorios`** — `schema_sprint3.sql`
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| alumno_id | INT FK | → usuarios(id) |
| materia_id | INT FK | → materias(id) |
| periodo | ENUM | '1er Trimestre', '2do Trimestre', '3er Trimestre', 'Anual' |
| fecha | DATE NULL | |
| turno | ENUM NULL | 'mañana', 'tarde' |
| estado | ENUM | 'pendiente', 'aprobado', 'desaprobado' — lo calcula el backend a partir de la nota |
| nota | TINYINT NULL | Nota del recuperatorio (agregada nov/2026) |
| — | UNIQUE | (alumno_id, materia_id, periodo) |

**`consultas_notas`** — `schema_sprint3.sql` (RFG07)
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| calificacion_id | INT FK | → calificaciones(id) ON DELETE CASCADE |
| padre_id | INT FK | → usuarios(id) |
| alumno_id | INT FK | → usuarios(id) |
| mensaje | TEXT | Consulta del padre (10–1000 chars) |
| respuesta | TEXT NULL | Respuesta del docente |
| estado | ENUM | 'pendiente', 'respondida' |
| respondida_at | TIMESTAMP NULL | |

**`deportes`**, **`inscripciones_deportivas`**, **`cuotas`** — `schema_sprint5.sql`
- `deportes`: nombre UNIQUE, horario, cupo_maximo (6 deportes de seed).
- `inscripciones_deportivas`: UNIQUE (alumno_id, deporte_id).
- `cuotas`: alumno_id, concepto ('matricula'/'cuota'), mes, anio, importe, recargo, fecha_vencimiento, estado ('pendiente'/'pagada'), fecha_pago, metodo_pago (ENUM efectivo/transferencia/tarjeta_debito/tarjeta_credito/otro, NULL en pagos anteriores al recibo), referencia_pago (opcional, 60 chars), registrado_por (admin que cobró) — UNIQUE (alumno_id, concepto, mes, anio).
- **Estado visual (RFG19, no se guarda):** `estado_visual_cuota()` lo calcula desde `estado` y `fecha_vencimiento`: `pagada`; `impaga` (pendiente y vencida); `proxima_a_vencer` (pendiente que vence en `DIAS_AVISO_VENCIMIENTO` = 7 días o menos); `pendiente`. El modelo de `cuotas` no cambió.
- `alumno_curso.condicion`: ENUM 'regular' / 'pendiente_regularizacion' (RFG13).

**`alumnos_datos`** — `schema_legajo.sql` (lo crea `init_db.php`)
Datos personales del legajo, 1:1 con `usuarios` (rol alumno). Todo opcional: dni (UNIQUE), fecha_nacimiento,
domicilio, telefono, email, tutor_nombre, contacto_emergencia, obra_social, observaciones_salud, solicitud_id
(solicitud de inscripción de origen), actualizado_en. Se completa al admitir (fecha de nacimiento, tutor, teléfono y
email salen de la solicitud) y desde el ABM de alumnos (DNI, domicilio, salud, etc.).

**`sanciones`** — `schema_sprint4.sql` (+ columnas de `schema_sprint6.sql`, RFG14; las agrega `init_db.php`)
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| alumno_id | INT FK | → usuarios(id) |
| tipo | ENUM | 'apercibimiento', 'suspension', 'otra' |
| descripcion | TEXT | |
| fecha | DATE | |
| registrado_por | INT FK | → usuarios(id) |
| created_at | TIMESTAMP | |
| dias_suspension | TINYINT UNSIGNED NULL | Solo si tipo = suspension (1–`SUSPENSION_DIAS_MAX`) |
| sin_contacto | TINYINT(1) | 1 = el alumno no tenía padre vinculado: administración avisa por otro medio |
| anulada | TINYINT(1) | Anulación lógica (la sanción sigue en el legajo, tachada) |
| motivo_anulacion | VARCHAR(500) NULL | |
| anulada_por | INT NULL | Admin que la anuló |
| anulada_en | DATETIME NULL | DATETIME: la tabla ya tiene un TIMESTAMP (MySQL 5.5) |

---

## Autenticación y sesiones

### Login (`auth/login.php`)

- Método: POST
- Parámetros: `usuario`, `password`, `rol` (texto del select del frontend)
- Mapeo de rol seleccionado → roles válidos en BD:

```php
'Docentes / Personal / Autoridades' => ['docente', 'admin']  // admin queda oculto aquí
'Padres / Tutores'                  => ['padre']
'Alumnos'                           => ['alumno']
```

- Desde Sprint 2 el ENUM de `usuarios.rol` incluye también `'enfermeria'`
- Query usa `rol IN (...)` para manejar múltiples roles por opción
- Contraseña verificada con `password_verify()`
- Responde JSON: `{ success, message, rol, nombre }`
- En sesión: `$_SESSION['usuario_id']`, `$_SESSION['nombre']`, `$_SESSION['rol']`

### Session (`auth/session.php`)

- Timeout: 1800 segundos (30 minutos)
- Cookie lifetime: 1800 segundos

### Banner de sesión activa (`index.php`)

Cuando hay sesión iniciada, `index.php` inyecta un banner sticky encima de `index.html` con:
- Nombre del usuario y rol actual
- Botón "Ir a mi portal" → `portals/portal_{rol}.php`
- Botón "Cerrar sesión" → `auth/logout.php`

### Logout (`auth/logout.php`)

Destruye la sesión completa y redirige a `/`.

---

## Roles: permisos y restricciones

### Público (sin login)

**Puede:**
- Ver toda la landing page (quiénes somos, niveles, galería, bienestar, noticias publicadas, empleo)
- Enviar solicitud de inscripción → `inscripciones/guardar.php`
- Enviar postulación a puesto vacante → `propuestas/guardar_postulacion.php`
- Enviar opinión (queda en estado "pendiente") → `opiniones/guardar.php`
- Enviar consulta de contacto → `consultas/guardar.php`
- Iniciar sesión → `auth/login.php`

**No puede:**
- Acceder a ningún portal
- Ver solicitudes, opiniones no aprobadas, consultas, postulaciones
- Gestionar contenido de ningún tipo

---

### Rol: `admin`

**Accede a:** `portals/portal_admin.php`
**Login como:** "Docentes / Personal / Autoridades" (rol oculto en el frontend)

**Puede:**
- **Inscripciones:** ver todas las solicitudes con sus estados, cambiar estado (pendiente → contactado → admitido/rechazado), admitir alumnos
- **Opiniones:** ver todas (pendientes, aprobadas, rechazadas), aprobar, rechazar, eliminar
- **Consultas:** ver todas, cambiar estado (pendiente/leída/respondida/archivada), eliminar
- **Noticias:** crear, editar, cambiar estado (borrador/publicada/archivada), eliminar
- **Puestos vacantes:** crear nuevos puestos, activar/desactivar puestos
- **Postulaciones:** ver todas, cambiar estado (pendiente/revisado/seleccionado/rechazado), eliminar
- **Sistema (barra de accesos rápidos):** ABM de alumnos, legajo (con situación económica), vacantes por curso, materias, cuotas y pagos, sanciones
- **Sanciones (RFG14):** registrar a cualquier alumno, listar todas con filtros y anularlas con motivo (`gestion/sanciones.php`)
- **Cuotas (RFG12/RFG19):** generar, registrar pagos y recibos; en el detalle de cada alumno ve el estado de cada cuota (impaga / próxima a vencer / pendiente / pagada) con badges y filtro por estado
- **Recibos:** al registrar un pago elige la forma de pago (obligatoria) y una referencia opcional; puede descargar el recibo en PDF al instante y reimprimirlo después desde la lista de cuotas

**No puede (por diseño del TP):**
- Gestionar usuarios directamente desde el portal (no hay CRUD de usuarios en la UI)

---

### Rol: `docente`

**Accede a:** `portals/portal_docente.php`
**Login como:** "Docentes / Personal / Autoridades"

**Puede:**
- Ver solicitudes de inscripción (últimas 50, solo lectura)
- Registrar y editar asistencia de sus alumnos por materia (`gestion/asistencia.php`)
- Cargar y editar calificaciones por materia (`gestion/calificaciones.php`)
- Gestionar planificación anual por materia (`gestion/planificacion.php`)
- Gestionar exámenes recuperatorios por materia (`gestion/recuperatorios.php`)
- Ver y responder consultas de padres sobre notas de sus materias (`gestion/consultas_notas.php`)
- Registrar sanciones a alumnos de los cursos donde dicta y ver las de esos cursos (`gestion/sanciones.php`); no puede anularlas
- Ver el estado de cada cuota de los alumnos de sus cursos, **sin importes** (`gestion/cuotas_docente.php`, RFG19). No registra pagos ni emite recibos: decisión de diseño por RN14 (cobrar es tarea administrativa)

**No puede:**
- Cambiar estados de solicitudes ni contenido del sitio
- Acceder a opiniones, consultas ni postulaciones
- Ver información de alumnos fuera de sus materias asignadas

---

### Rol: `alumno`

**Accede a:** `portals/portal_alumno.php`
**Login como:** "Alumnos"

**Puede:**
- Ver su horario semanal (Lunes–Viernes con materias y horarios)
- Ver sus calificaciones por materia desde BD (`gestion/boletin_listar.php`)
- Ver su asistencia desde BD (`gestion/asistencia_listar.php`)
- Ver sus recuperatorios pendientes (`gestion/recuperatorio_listar.php`)
- Ver aviso de "pendiente de regularización" si tiene cuotas vencidas más de 60 días

**No puede:**
- Ver información de otros alumnos
- Acceder a módulos de gestión
- Modificar ningún dato

---

### Rol: `padre`

**Accede a:** `portals/portal_padre.php`
**Login como:** "Padres / Tutores"

**Puede:**
- Ver información del hijo/hija vinculado via `padre_alumno`
- Ver notas por materia desde BD
- Ver asistencia desde BD
- Ver comunicados institucionales
- Reservar comedor/transporte mensual (`gestion/servicios.php`)
- Ver notificaciones de enfermería
- Consultar una calificación al docente y ver la respuesta (RFG07, botón "Consultar" en el boletín)
- Inscribir al hijo en instalaciones deportivas (`gestion/deportes.php`)
- Ver las sanciones de sus hijos en la card "Convivencia" (las anuladas, tachadas con el motivo) y recibir la notificación al registrarse o anularse
- Ver las cuotas de sus hijos en la card "Cuotas" (RFG19): impagas en rojo y próximas a vencer en amarillo arriba, con el total (las impagas incluyen el recargo que correspondería hoy); pagadas plegadas, con botón "Recibo" (PDF)
- Ver aviso de pendiente de regularización por cuotas vencidas

**No puede:**
- Modificar ningún dato
- Ver información de otros alumnos o familias

---

### Rol: `enfermeria`

**Accede a:** `portals/portal_enfermeria.php`
**Login como:** "Docentes / Personal / Autoridades"

**Puede:**
- Registrar atenciones médicas de alumnos (`gestion/enfermeria.php`)
- Buscar alumnos por nombre/DNI
- Ver historial de atenciones del día

**No puede:**
- Acceder a calificaciones, asistencia ni gestión académica
- Ver información administrativa

---

## Landing page (index.html)

### Secciones (en orden)

| ID / Sección | Descripción |
|---|---|
| Header sticky | Logo, nav horizontal, botón "Ingresar" |
| `#inicio` | Hero con headline, subtítulo, 2 CTAs, stats (3 niveles, 8+ deportes, etc.) |
| `#quienes` | Misión, visión y 4 valores institucionales |
| Galería | Grid de 5 imágenes AVIF de instalaciones |
| `#niveles` | 5 cards: Inicial, Primario, Secundario, Idiomas, Apoyo Estudiantil |
| `#bienestar` | Tabs: Deportes / Instalaciones / Servicios |
| `#inscripcion` | Formulario de 8 campos para solicitar inscripción |
| `#noticias` | Grid de cards con noticias publicadas + paginación + modal de detalle |
| `#empleo` | Lista de puestos vacantes + modal de postulación |
| `#opiniones` | Formulario de opinión + grid de opiniones aprobadas + paginación |
| `#contacto` | Info de contacto + formulario |
| Footer | Links, legal, copyright |

### Modal de login

- Select de rol (3 opciones visibles; admin queda dentro de "Docentes / Personal / Autoridades")
- Campos: usuario/DNI + contraseña
- Link "Recuperar acceso" (modal separado)

### Dependencias externas

```html
<!-- Google Fonts -->
Merriweather (400, 700, 900) + Nunito (400, 600, 700, 800, 900)
URL: https://fonts.googleapis.com/css2?family=Merriweather:...&family=Nunito:...
```

No hay librerías JS externas. Todo el JavaScript es vanilla puro en el propio `index.html`.

---

## JavaScript (funciones principales en index.html)

| Función | Propósito |
|---|---|
| `abrirLogin(event)` | Abre el modal de login |
| `realizarLogin()` | POST a auth/login.php, maneja respuesta y redirección |
| `abrirRecuperar(event)` | Abre modal de recuperación de acceso |
| `cerrarLogin(event)` | Cierra modal de login al hacer click en overlay |
| `cerrarRecuperar(event)` | Cierra modal de recuperación |
| `toggleMenu()` | Abre/cierra menú hamburger (mobile) |
| `cerrarMenu()` | Cierra el drawer de navegación mobile |
| `enviarInscripcion()` | Valida y envía formulario de inscripción |
| `abrirNoticia(el)` | Abre modal con contenido completo de noticia |
| `cerrarNoticia()` | Cierra modal de noticia |
| `notCambiarPagina(dir)` | Paginación de noticias |
| `enviarPostulacion()` | Valida y envía postulación de empleo |
| `enviarOpinion()` | Valida y envía opinión |
| `opCambiarPagina(dir)` | Paginación de opiniones |
| `enviarConsulta()` | Valida y envía formulario de contacto |

### Validaciones en tiempo real

Todos los formularios aplican clases CSS dinámicas sobre los campos:
- `campo-error` → borde rojo + mensaje debajo
- `campo-ok` → borde verde

---

## Tema visual

```css
--naranja:           #F97316   /* Color principal */
--naranja-claro:     #FB923C
--naranja-oscuro:    #EA580C
--naranja-btn:       #C2410C
--naranja-btn-hover: #9A3412
--naranja-bg:        #FFF7ED   /* Fondo secciones */
--gris-oscuro:       #374151
--gris-texto:        #111827
--gris-suave:        #F9FAFB
--blanco:            #FFFFFF
--borde:             #E5E7EB
--sombra:            0 4px 18px rgba(249,115,22,.13)
Footer bg:           #1e293b
```

**Fuentes:** Merriweather (títulos) + Nunito (cuerpo)

**Responsive:**
- Mobile: < 480px — grid 1 columna, nav drawer
- Tablet: < 768px — grid ajustado, hamburger activado
- Desktop: max-width 1200px para contenido

---

## Seguridad

- **Prepared statements** con `bind_param()` en todas las queries → sin SQL injection
- **`password_verify()`** para autenticación → contraseñas nunca en texto plano
- **`htmlspecialchars()`** en outputs → prevención básica de XSS
- **Verificación de rol en sesión** al inicio de cada portal y cada endpoint de gestión
- **ENUMs en BD** para estados → valores inválidos rechazados a nivel de base de datos
- **HTTP 403** en endpoints de admin cuando el rol no coincide
- **HTTP 405** en endpoints que solo aceptan POST cuando llega GET

---

## Flujos principales

**Inscripción pública:**
1. Usuario completa formulario en `#inscripcion`
2. JS valida campos (regex + longitud)
3. POST → `inscripciones/guardar.php`
4. Inserta en `solicitudes_inscripcion` con estado `'pendiente'`
5. Respuesta JSON → mensaje de éxito/error en pantalla

**Login de administrador:**
1. Selecciona "Docentes / Personal / Autoridades" en el modal
2. Ingresa usuario y contraseña de una cuenta con rol `admin`
3. POST → `auth/login.php` busca con `rol IN ('docente', 'admin')`
4. Sesión iniciada con `rol = 'admin'`, redirige a `portals/portal_admin.php`

**Gestión de noticias:**
1. Admin crea noticia (estado `'borrador'`) desde su portal
2. POST → `noticias/guardar.php`
3. Cuando está lista, cambia estado a `'publicada'`
4. `noticias/listar.php` la devuelve al público en el próximo render

**Moderación de opiniones:**
1. Visitante envía opinión → estado `'pendiente'`
2. Admin la ve en su portal
3. La aprueba → estado `'aprobado'` → aparece en `#opiniones`
4. La rechaza → estado `'rechazado'` → no se muestra nunca

**Gestión de empleo:**
1. Admin crea puesto desde su portal → `propuestas/agregar_puesto.php`
2. El puesto aparece en `#empleo` (activo=1)
3. Visitante se postula → `propuestas/guardar_postulacion.php`
4. Admin revisa postulaciones y cambia estado (revisado/seleccionado/rechazado)

---

## Estado actual del proyecto

### Sprints (26/10/2026)

| Sprint | RFs | Estado | Responsable |
|---|---|---|---|
| Sprint 1 | RFG01/02/03 | ✅ Completo | Fabrizio |
| Sprint 2 | RFG04/09/10 | ✅ Completo (validación RF04 implementada en `admitir.php`) | Fabrizio |
| Sprint 3 | RFG05/06/07 | ✅ Completo (RF07: consultas padre → docente) | Agustín |
| Sprint 4 | RFG08/15/16 | ✅ Completo (legajo con situación económica) | Agustín |
| Sprint 5 | RFG11/12/13 | 🟡 Implementado (deportes, cuotas/mora, regularización) — falta validar en producción | Fabrizio |
| Sprint 6 | RFG14/17/18 | 🟡 En curso: RFG14 sanciones (ramas `feat/rfg14a-sanciones-registro` y `feat/rfg14b-sanciones-anulacion`); RFG17 y RFG18 pendientes | Fabrizio |
| Sprint 7 | RFG19 (desafío) | 🟡 Implementado (estado de cuotas para padre, docente y admin; recibo para el padre) en `feat/rfg19a-cuotas-padre` y `feat/rfg19b-cuotas-docente-admin` — PR sin mergear | Fabrizio |

> Verificación (26/10/2026): se probó en local con PHP 8.2 + MariaDB de XAMPP (`php.exe` dentro de `xampp/php`, fuera del PATH) los flujos de pago/recibo, consultas sobre notas, legajo e `init_db.php`. No se probó en Render ni con los datos del seed.

### Estado por portal

- **Portal admin:** inscripciones (con validación de edad/nivel anterior y de vacantes, y asignación a curso), noticias, opiniones, consultas, empleo, legajo (con situación económica), vacantes, ABM de alumnos (`gestion/alumnos.php`), cuotas y pagos, materias. Accesos rápidos al sistema visibles arriba del portal (barra oscura antes de las pestañas).
- **Portal docente:** asistencia, calificaciones, planificación anual, recuperatorios, consultas sobre notas, sanciones, estado de cuotas (sin importes) — todos conectados a BD
- **Portal alumno:** boletín por trimestre, asistencia, recuperatorios, aviso de regularización — conectados a BD
- **Portal padre:** boletín con consulta al docente (RF07), asistencia, servicios (comedor/transporte), deportes, notificaciones, convivencia (sanciones), cuotas con estado y recibo, aviso de regularización — conectados a BD
- **Portal enfermería:** registro de atenciones conectado a BD
- **Landing pública:** completamente funcional
- **Recuperación de contraseña:** modal presente en UI pero sin implementar

### Bugs y pendientes conocidos

| # | Descripción | RF/HU relacionado | Prioridad |
|---|---|---|---|
| 1 | Sin implementar: RFG17 (reportes) y RFG18 (licencias y suplencias) | RFG17/18 | Pendiente |
| 2 | Recuperación de contraseña: modal sin backend | — | Media |
| 3 | Validar en producción los cambios nuevos: tabla `consultas_notas` (se crea en `init_db.php` solo si ya existe `calificaciones`; si no, correr `schema_sprint3.sql`) y legajo con cuotas | RFG07/RFG16 | Alta |
| 4 | Constantes asumidas sin confirmar con el equipo: `EDAD_MINIMA_INICIAL`, `LIMITE_FALTAS`, `PORCENTAJE_RECARGO_MORA` (10%), `DIAS_LIMITE_REGULARIZACION` (60), `DIA_LIMITE_RESERVA_SERVICIOS` (25) | varios | Media |
| 6 | Reglas de recuperatorios asumidas (lunes a viernes, sin fechas pasadas, estado por nota) — confirmar con el equipo | RFG06 | Media |
| 7 | El padre ve los recuperatorios solo por notificaciones: no tiene una sección propia en su portal (el alumno sí la tiene) | RFG06 | Baja |
| 8 | Cypress (`cypress/e2e/rfg04_admision.cy.js`) no se corrió en la verificación del PR #13 | RFG04 | Baja |
| 9 | Reglas asumidas de sanciones (confirmar): hasta 30 días después del hecho (`SANCION_DIAS_ATRAS_MAX`), suspensión de 1 a 30 días (`SUSPENSION_DIAS_MAX`), solo el admin anula | RFG14 | Media |
| 10 | Asunciones de RFG19 (confirmar): "próxima a vencer" = vence en 7 días o menos (`DIAS_AVISO_VENCIMIENTO`); el docente solo ve el estado, sin importes ni registro de pagos (RN14); el total de una impaga incluye el recargo que correspondería hoy | RFG19 | Media |
| 5 | `seed_datos.php` no es idempotente con materias (la clave única `uq_materia_curso` y la limpieza las aplica `init_db.php`) | Seed/BD | Baja |

### Parte 3 — App móvil (MSII, rama `feat/parte3-base`, 09/11/2026)

**Decisión de tecnología:** el Plan de Trabajo decía React Native + Expo; se hace una **PWA** servida por este mismo
backend (carpeta `app/`, API JSON en `app/api/`). Motivos: mismo origen que la web (reutiliza sesión, `helpers.php`,
base y deploy, sin CORS ni tokens y sin duplicar datos, como pide la consigna), una sola base de código para Android,
iOS y escritorio sin tiendas, y entra en el plazo del testing (11/11). Bitácora de IA:
`4TO CUATRIMESTRE/METODOLOGIA_DE_SISTEMAS_II/bitacora-ia-parte3.md` (fuera del sistema, no se despliega).

**Modelo (Paso 1):** `tarifas` (PK `clave`: `cuota:<nivel>`, `matricula:<nivel>`, `comedor`, `deporte:<id>`,
`transporte:<id>`), `recorridos_transporte` (4), `deportes.profesor_id/dias/hora_inicio/hora_fin`,
`reservas_servicios.recorrido_id`, `facturas` (1 por alumno y mes, N° `F-` + id), `factura_items` (FK a cuota,
deporte o recorrido), `pagos` + `pago_items` (N° `P-` + id), `comprobantes_transferencia` (MEDIUMBLOB en la base:
el disco de Render se borra en cada deploy). Todas las reglas están en `lib/facturacion.php`.

**Sincronización con la web:** el ítem cuota/matrícula apunta a la fila de `cuotas`. `marcar_cuota_pagada()` es el único
lugar que marca una cuota pagada: la usan la aprobación del pago por transferencia y `cuotas_pagar.php` (cobro del admin),
y siempre marca también el ítem de la factura y la recalcula. Así RFG12/13/19 y la app muestran lo mismo. Si la cuota
tiene un pago en revisión, el cobro por la web se rechaza.

**Asunciones de la Parte 3 (confirmar con el equipo):**
- **1 pago = 1 factura** (un hijo y un mes); con dos hijos, dos pagos.
- **Deportes desde el mes siguiente:** se facturan los inscriptos antes del día 1 del mes facturado, todos los meses
  mientras sigan inscriptos; la baja borra la inscripción (los reportes salen de `factura_items`).
- **Comedor con valor único** para todos los niveles; transporte según el recorrido reservado para el mes.
- **Tarifas sin historial:** una fila por concepto; el importe queda congelado en el ítem de la factura.
- **Recargo solo sobre la cuota mensual** vencida (RN11). Su importe es `cuotas.recargo`: si estaba en 0 se calcula una
  única vez con `calcular_recargo()` (Sprint 5) y se guarda ahí. Entra como ítem de factura en lo que pase primero:
  (a) el padre paga esa cuota por la app → `crear_pago()` llama a `agregar_recargo_a_factura()` y el recargo va a la
  MISMA factura y al mismo pago (lo ve en el total antes de confirmar); (b) se genera la facturación del mes siguiente.
  Un solo ítem de recargo por cuota: nunca se factura dos veces (`RecargoAppTest`).
- **Sin efectivo** (consigna): `METODOS_PAGO_HABILITADOS` lo excluye para pagos nuevos; `METODOS_PAGO` sigue completo
  para mostrar bien los cobros viejos.
- Generar la facturación de nuevo no modifica facturas existentes (las reservas cierran el día 25 del mes anterior).
- Ítems de un pago activo quedan `en_revision`; si el admin rechaza, vuelven a `pendiente`.
- **Tamaño de comprobantes:** `max_allowed_packet` es 1 MB en el MariaDB local (y probablemente en Filess.io); el
  límite real lo fija el Paso 4 (la app comprime la foto antes de subirla).

**Producción (después del merge):** el deploy corre `init_db.php`; después abrir una vez `/database/seed_parte3.php`
con sesión de admin (carga tarifas y datos de demo). `laura.martinez` queda con dos hijos (ana.garcia y matias.vega).

### Sesión 03/11/2026 — rama `fix/planificaciones-recuperatorios-legajo`

Lo que se vio en clase: "planificaciones y recuperatorios no andan" y "el legajo no muestra toda la info del alumno".

Causas encontradas y arreglos:
1. **Admin rebotaba a la landing:** el portal admin tiene botones a Planificaciones y Recuperatorios, pero esas páginas
   solo aceptaban rol docente (`header('Location: /')`). Ahora el admin entra a una vista propia: control de
   planificaciones por año/curso (cargadas vs. pendientes, ver contenidos) y listado de todos los recuperatorios con filtros.
2. **Tablas faltantes en Render:** `planificaciones`, `recuperatorios` y `sanciones` solo se creaban corriendo
   `schema_sprint3/4.sql` a mano (que hacen `USE educar_db`). Si faltaban, los endpoints tiraban excepción. Ahora las crea
   `init_db.php` en cada deploy (idempotente, probado sobre una base sin ellas).
3. **Errores invisibles:** en planificación el mensaje estaba dentro del formulario oculto (si fallaba "Cargar" no se veía
   nada) y los endpoints no usaban `errores_como_json()`. Corregido en planificación, recuperatorios y legajo.
4. **Recuperatorios del docente rediseñado:** el listado repetía al alumno una vez por recuperatorio y cambiar el período
   creaba otro registro en lugar de editar. Ahora: formulario para programar/editar, tabla de recuperatorios con
   Editar/Eliminar, tabla de alumnos con promedio y botón "+ Programar". Reglas (asumidas, confirmar con el equipo):
   lunes a viernes; al programar/reprogramar no se aceptan fechas pasadas; la nota exige fecha ya ocurrida; el estado sale
   de la nota (`NOTA_APROBACION`); uno por alumno/materia/período. Se notifica al padre al programar, reprogramar y al
   cargar la nota (`tipo = 'recuperatorio'`). Resaltados en rojo los pendientes con fecha pasada (falta la nota).
5. **Legajo completo:** nueva tabla `alumnos_datos` + campos en el ABM de alumnos. El legajo muestra indicadores
   (promedio, % asistencia, materias en riesgo, faltas, recuperatorios, sanciones, deuda), datos personales, familia y
   contactos (tutor declarado + padres con usuario), salud (obra social, observaciones, enfermería), rendimiento por
   materia con docente, calificaciones con trimestre, asistencia con presentes/tardes/faltas/%/semáforo, recuperatorios
   con nota, sanciones, deportes y servicios, consultas de notas y notificaciones, y situación económica (solo admin).
   Botones "Imprimir / PDF" y "Editar datos"; el ABM tiene botón "Legajo" por alumno.

**Para producción (Render) después del merge:** el deploy corre `init_db.php` solo; después abrir una vez
`/database/seed_legajo.php` para cargar los datos personales de demo. Probado en local con PHP 8.3 + MariaDB 10.11
(flujos de admin y docente en Chromium con Playwright).

**Verificación en Windows (03/11/2026, PR #13, sin mergear):** pipeline local sobre `educar_test` (PHP 8.0 + MariaDB
10.4 de XAMPP) y CI (PHP 8.2 + MariaDB 10.11): 42 tests OK; SonarCloud OK; prueba manual completa de la lista del PR OK.
Lo que apareció al verificar (commits aparte en la misma rama):
- **ABM pisaba el legajo de otro alumno con DNI repetido:** `INSERT … ON DUPLICATE KEY UPDATE` sobre `alumnos_datos`
  (dos claves únicas: `alumno_id` y `uq_alumno_dni`) actualizaba la fila del dueño del DNI y le borraba los datos.
  Ahora `SELECT … FOR UPDATE` + `UPDATE`/`INSERT` explícito; el DNI repetido da error y rollback. **No usar
  `ON DUPLICATE KEY UPDATE` en tablas con más de una clave única.**
- `ci/preparar_bd.sh` aplicaba los `schema_sprint*.sql` sobre `educar_db` aunque se pidiera otra base (`USE educar_db`).
- `db_config.php` con `mysqli_report` para PHP 8.0; `phpcs` corregido en los archivos del fix; `<label for>` en el
  formulario de Gestión de Alumnos (SonarCloud lo marcaba como bug de accesibilidad).
- Tests nuevos: `RecuperatoriosTest`, `PlanificacionTest`, `LegajoTest` (ver "Tests de integración" arriba).
- Cypress (`cypress/e2e/`) no se corrió en esta verificación.

### Próximos pasos (cierre de sesión 26/10/2026)

Hecho en esta sesión (commits `f6ca033`, `c217b15`, `d142656`): RF07 (consultas padre → docente), legajo con situación económica, recibo de pago en PDF con forma de pago. El recibo se probó y genera bien el PDF.

Por prioridad:
1. **Sprint 6 (RFG17/18/19):** sin empezar. El repo no tiene el enunciado de esos requisitos; hay que pedirlo al equipo o al documento del TP antes de implementar.
2. **Recuperación de contraseña:** el modal "Recuperar acceso" de `index.html` no tiene backend.
3. **Verificar en Render** tras el push: que `init_db.php` agregó `cuotas.metodo_pago/referencia_pago/registrado_por` y creó `consultas_notas`; probar un pago con recibo, una consulta de nota y el legajo. Si `init_db.php` falla, correr a mano el `ALTER` de `schema_sprint5.sql` y el bloque de `consultas_notas` de `schema_sprint3.sql`.
4. **Mejoras opcionales del recibo:** numeración correlativa propia (hoy `R-` + id de cuota), importe en letras, envío por mail. (La descarga del padre desde su portal ya está, RFG19.)
5. Confirmar con el equipo las constantes asumidas de `helpers.php` (recargo 10%, 60 días de regularización, edad mínima 3, 15 faltas, día 25 para reservas).

Ojo con git: el repo real está en `TP_FACULTAD/`, no en esta carpeta. Si aparece un `.git` dentro de `METODOLOGIA_DE_SISTEMAS_I/` (pasó una vez, de origen desconocido) los commits van a ese repo anidado y no al real; borrarlo antes de commitear. Aún no se hizo `git push` de los commits de esta sesión.

Herramientas locales: PHP 8.0.30 y MariaDB 10.4 de XAMPP (`xampp/php/php.exe`, `xampp/mysql`) sirven para lintear y probar (ver "En Windows con XAMPP" arriba); el `educar_db` local es de desarrollo, para pruebas usar `educar_test`.

### Notas técnicas importantes

**Portal admin — pestañas vs. gestión del sistema:** Las pestañas del portal (Inscripciones, Opiniones, Propuestas, Consultas, Noticias) gestionan contenido de la landing page. La barra oscura sobre las pestañas contiene accesos rápidos al sistema (ABM alumnos, legajo, vacantes, materias, etc.). No mezclar.

**Legajo (`gestion/legajo.php` + `legajo_listar.php`):** muestra calificaciones, asistencia, recuperatorios, sanciones, enfermería, servicios y, desde Sprint 5, la situación económica (cuotas, recargos, deuda total) y la condición de regularización. La situación económica solo se envía al rol `admin` (para el docente `cuotas` y `deuda_total` son `null` y la sección no se muestra).

**Recibo de pago (PDF):** `cuotas_pagar.php` exige `metodo_pago` (válido según `METODOS_PAGO` en `helpers.php`) y guarda referencia y `registrado_por`. `recibo_pdf.php` arma los datos (alumno, curso, tutores vía `padre_alumno`, concepto, período, importe, recargo, fechas, forma de pago, quién cobró) y `lib/recibo.php` los dibuja con el logo (`assets/logo.png`). N° de recibo = `R-` + id de cuota con 6 dígitos (un pago por cuota). Los datos de la institución (nombre, CUIT, domicilio, teléfono) salen de las constantes `INSTITUCION_*` de `helpers.php`. Es un comprobante interno, no factura. FPDF trabaja en Windows-1252, por eso `pdf_txt()` convierte desde UTF-8; usar `mb_strtoupper` y no `strtoupper` para pasar a mayúsculas con tildes. Lo emiten el admin y, desde RFG19, el padre para las cuotas pagadas de sus hijos (`alumno_pertenece_a_padre`; ante una cuota ajena responde 403 aunque no exista, para no revelar ids). Una cuota impaga da 409.

**Consultas sobre notas (RFG07):** `boletin_listar.php` devuelve el `id` de cada calificación; el padre consulta con `consulta_nota_guardar.php` (una sola consulta pendiente por calificación), el docente responde con `consulta_nota_responder.php` y se crea una notificación (`tipo = 'consulta_nota'`) para el padre.

**`init_db.php` y deploy:** corre en cada deploy. `consultas_notas` tiene FK a `calificaciones` (que crean los schemas manuales, no `init_db`), por eso se crea únicamente si esa tabla existe; así el arranque no falla con `exit(1)`.

**Boletín (`gestion/boletin_listar.php`):** agrupa calificaciones por trimestre usando el mes de `fecha_evaluacion` — meses 3–5 = 1er Trim, 6–8 = 2do Trim, 9–12 = 3er Trim. No hay columna `periodo` en `calificaciones`.

**Admitir alumno (`inscripciones/admitir.php`):** valida vacantes disponibles en el curso antes de crear el usuario. Si se envía `curso_id` por POST, inserta en `alumno_curso` automáticamente al admitir.

**FK enfermería:** `atenciones_enfermeria.atendido_por` referencia `usuarios.id`. Si `sandra.benitez` no existe en la BD, las inserciones fallan. `schema_sprint2.sql` debe correr ANTES de `setup_demo.php` para que el ENUM `'enfermeria'` esté disponible.

**Estado de cuotas (RFG19):** `cuotas_de_alumno($conn, $alumno_id, ?$anio)` en `helpers.php` es el único lugar que arma las cuotas de un alumno para mostrar (patrón Repository; pensada también para la API de la app móvil). Devuelve cada cuota con `estado_visual`, `estado_texto`, importe, recargo, total, vencimiento, fecha y forma de pago, y un `resumen` (`total_adeudado`, `total_vencido`, `impagas`, `proximas_a_vencer`, `proximo_vencimiento`). La usan el portal del padre y `cuotas_estado.php`; `cuotas_listar.php` (admin) agrega `estado_visual` con `estado_visual_cuota()`. El docente recibe solo período + estado (sin importes) de los alumnos de sus cursos: las del año en curso y las impagas de años anteriores. `seed_cuotas.php` usa vencimientos relativos a hoy (no el día 10) para que en la demo siempre haya cuotas de todos los estados.

**Constantes en `gestion/helpers.php`:**
- `EDAD_MINIMA_INICIAL = 3`
- `LIMITE_FALTAS = 15`
- `NOTA_APROBACION = 6`
- `DIA_VENCIMIENTO_CUOTA = 10`, `PORCENTAJE_RECARGO_MORA = 10`, `DIAS_LIMITE_REGULARIZACION = 60`
- `SANCION_DIAS_ATRAS_MAX = 30`, `SUSPENSION_DIAS_MAX = 30` (RFG14)
- `DIAS_AVISO_VENCIMIENTO = 7`, `ESTADOS_CUOTA`, `MESES` (RFG19)

### Credenciales de demo

| Usuario | Contraseña | Rol |
|---|---|---|
| admin | admin | admin |
| maria.rodriguez | docente123 | docente |
| roberto.silva | docente456 | docente |
| patricia.aguirre | docente789 | docente |
| ernesto.castillo | docente321 | docente |
| ana.garcia | alumno123 | alumno |
| carlos.lopez | alumno456 | alumno |
| sofia.herrera | alumno789 | alumno |
| miguel.torres | alumno321 | alumno |
| valentina.paz | alumno654 | alumno |
| ezequiel.romero | alumno987 | alumno |
| luciana.campos | alumno111 | alumno |
| matias.vega | alumno222 | alumno |
| agustina.molina | alumno333 | alumno |
| bruno.flores | alumno444 | alumno |
| laura.martinez | padre123 | padre |
| diego.fernandez | padre456 | padre |
| carlos.herrera | padre789 | padre |
| marta.torres | padre321 | padre |
| fernando.paz | padre654 | padre |
| sandra.benitez | enfermeria123 | enfermeria |

### Orden de ejecución de schemas en BD nueva

1. `database/schema.sql`
2. `database/schema_sprint2.sql` (primera pasada — agrega rol `'enfermeria'` al ENUM)
3. `database/setup_demo.php` (crea usuarios base via navegador)
4. `database/schema_gestion.sql`
5. `database/schema_sprint2.sql` (segunda pasada — seed padre_alumno, idempotente)
6. `database/schema_sprint3.sql`
7. `database/schema_sprint4.sql`
8. `database/seed_datos.php` (seed masivo via navegador — secciones 1–18)
   - Si falla en la sección 11: correr `database/seed_parte2.php` para continuar desde ahí
9. `database/schema_sprint5.sql` (deportes, cuotas, condición) — en producción lo cubre `init_db.php`
10. `database/schema_sprint6.sql` (columnas de sanciones, RFG14) — en producción lo cubre `init_db.php`
    - Datos de demo por consola: `cd database && php seed_legajo.php && php seed_cuotas.php && php seed_parte3.php` (`ci/preparar_bd.sh` ya los corre). `seed_cuotas.php` **no** se abre por URL: crea deudas ficticias.
11. La tabla `consultas_notas` está al final de `schema_sprint3.sql`; si ese schema ya se corrió antes, ejecutar solo ese bloque (o dejar que `init_db.php` la cree)
12. `database/schema_parte3.sql` (Parte 3: app móvil) y después `php init_db.php`; datos de demo con `seed_parte3.php` (después de `seed_cuotas.php`)
