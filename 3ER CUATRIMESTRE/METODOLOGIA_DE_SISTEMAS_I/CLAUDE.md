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
├── assets/                     ← Imágenes optimizadas en formato AVIF
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
│   ├── setup_demo.php          ← Usuarios base de demo (admin, 2 alumnos, 2 docentes, 2 padres, enfermeria)
│   ├── seed_datos.php          ← Seed masivo: 13 usuarios nuevos + todos los datos de demostración (secciones 1–18)
│   └── seed_parte2.php         ← Continuación del seed desde sección 11 (útil si seed_datos falla a mitad)
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
│   ├── planificacion.php              ← UI planificación anual (docente)
│   ├── planificacion_guardar.php      ← POST → guardar planificación
│   ├── planificacion_listar.php       ← GET → JSON planificaciones
│   ├── recuperatorios.php             ← UI gestión de recuperatorios (docente)
│   ├── recuperatorio_guardar.php      ← POST → guardar recuperatorio
│   ├── recuperatorio_listar.php       ← GET → JSON recuperatorios
│   ├── legajo.php                     ← UI legajo completo del alumno (admin/docente)
│   ├── legajo_listar.php              ← GET → JSON datos de legajo
│   ├── boletin_listar.php             ← GET → JSON boletín de calificaciones
│   ├── vacantes.php                   ← UI gestión de vacantes (admin)
│   ├── vacantes_actualizar.php        ← POST → actualizar puesto vacante
│   ├── vacantes_listar.php            ← GET → JSON puestos vacantes
│   ├── roles_listar.php               ← GET → JSON roles disponibles
│   ├── alumnos.php                    ← UI ABM de alumnos (admin) — alta/baja/modificación
│   ├── alumnos_listar.php             ← GET → JSON lista de alumnos con curso
│   ├── alumno_guardar.php             ← POST → crear o actualizar alumno
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
└── package.json                ← Dev dependency: sharp ^0.34.5 (conversión de imágenes)
```

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
| estado | ENUM | 'pendiente', 'aprobado', 'desaprobado' |
| — | UNIQUE | (alumno_id, materia_id, periodo) |

**`sanciones`** — `schema_sprint4.sql`
| Campo | Tipo | Notas |
|---|---|---|
| id | INT PK AUTO | |
| alumno_id | INT FK | → usuarios(id) |
| tipo | ENUM | 'apercibimiento', 'suspension', 'otra' |
| descripcion | TEXT | |
| fecha | DATE | |
| registrado_por | INT FK | → usuarios(id) |
| created_at | TIMESTAMP | |

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

### Sprints (21/09/2026)

| Sprint | RFs | Estado | Responsable |
|---|---|---|---|
| Sprint 1 | RFG01/02/03 | ✅ Completo | Fabrizio |
| Sprint 2 | RFG04/09/10 | ⚠️ Parcial (ver bugs) | Fabrizio |
| Sprint 3 | RFG05/06/07 | ⚠️ Parcial (ver bugs) | Agustín |
| Sprint 4 | RFG08/15/16 | ⚠️ Parcial (ver bugs) | Agustín |
| Sprint 5 | RFG11/12/13 | ❌ Pendiente | Fabrizio |
| Sprint 6 | RFG17/18/19 | ❌ Pendiente | — |

### Estado por portal

- **Portal admin:** inscripciones (con validación de vacantes y asignación a curso), noticias, opiniones, consultas, empleo, legajo, vacantes, ABM de alumnos (`gestion/alumnos.php`). Accesos rápidos al sistema visibles arriba del portal (barra oscura antes de las pestañas).
- **Portal docente:** asistencia, calificaciones, planificación anual, recuperatorios — todos conectados a BD
- **Portal alumno:** boletín de calificaciones por trimestre, asistencia, recuperatorios — conectados a BD
- **Portal padre:** boletín, asistencia, servicios (comedor/transporte), notificaciones — conectados a BD. Falta: consulta sobre calificación (RF07)
- **Portal enfermería:** registro de atenciones conectado a BD
- **Landing pública:** completamente funcional
- **Recuperación de contraseña:** modal presente en UI pero sin implementar

### Bugs y pendientes conocidos

| # | Descripción | RF/HU relacionado | Prioridad |
|---|---|---|---|
| 1 | Validar requisitos de inscripción desde el portal (HU6) — falta validación en backend | HU6 | Alta |
| 2 | RF04 incompleto — falta implementación completa de validación de edad/nivel al admitir | RFG04 | Alta |
| 3 | Materias duplicadas en seed: "Matemática 2B" y "Lengua y Literatura 2B" aparecen dos veces | Seed/BD | Media |
| 4 | RF07 incompleto — el padre no puede consultar/preguntar sobre una calificación desde su portal | RFG07 | Alta |
| 5 | Vacantes por curso (`gestion/vacantes.php`): al modificar la capacidad no se refresca la página automáticamente, lo que puede mostrar datos inconsistentes | RFG08 | Media |
| 6 | RF11 no implementado — reserva de instalaciones deportivas | RFG11 | Pendiente Sprint 5 |
| 7 | RF12 no implementado — pagos, matrícula y mora | RFG12 | Pendiente Sprint 5 |
| 8 | RF13 no implementado — condición pendiente de regularización | RFG13 | Pendiente Sprint 5 |
| 9 | RF16 incompleto — legajo del alumno no muestra situación económica (depende de Sprint 5/RF12) | RFG16 | Bloqueado por RF12 |
| 10 | RF17/18/19 no implementados | RFG17/18/19 | Pendiente Sprint 6 |

### Notas técnicas importantes

**Portal admin — pestañas vs. gestión del sistema:** Las pestañas del portal (Inscripciones, Opiniones, Propuestas, Consultas, Noticias) gestionan contenido de la landing page. La barra oscura sobre las pestañas contiene accesos rápidos al sistema (ABM alumnos, legajo, vacantes, materias, etc.). No mezclar.

**Legajo (`gestion/legajo.php` + `legajo_listar.php`):** Las referencias a `cuotas` (tabla) y `alumno_curso.condicion` (columna) son de Sprint 5 y fueron eliminadas temporalmente. El legajo muestra calificaciones, asistencia, recuperatorios, sanciones, enfermería y servicios. Se reintegrará situación económica cuando se implemente RF12.

**Boletín (`gestion/boletin_listar.php`):** agrupa calificaciones por trimestre usando el mes de `fecha_evaluacion` — meses 3–5 = 1er Trim, 6–8 = 2do Trim, 9–12 = 3er Trim. No hay columna `periodo` en `calificaciones`.

**Admitir alumno (`inscripciones/admitir.php`):** valida vacantes disponibles en el curso antes de crear el usuario. Si se envía `curso_id` por POST, inserta en `alumno_curso` automáticamente al admitir.

**FK enfermería:** `atenciones_enfermeria.atendido_por` referencia `usuarios.id`. Si `sandra.benitez` no existe en la BD, las inserciones fallan. `schema_sprint2.sql` debe correr ANTES de `setup_demo.php` para que el ENUM `'enfermeria'` esté disponible.

**Constantes en `gestion/helpers.php`:**
- `EDAD_MINIMA_INICIAL = 3`
- `LIMITE_FALTAS = 15`
- `NOTA_APROBACION = 6`

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
