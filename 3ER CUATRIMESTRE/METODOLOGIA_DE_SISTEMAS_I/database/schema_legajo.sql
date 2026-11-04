-- =========================================================
--  Legajo completo del alumno (RFG16, ampliación nov/2026)
--
--  Datos personales del alumno: hasta ahora "usuarios" solo guardaba nombre y
--  usuario, así que el legajo no tenía DNI, fecha de nacimiento ni contactos.
--  Relación 1:1 con usuarios (rol = 'alumno'). Todos los campos son opcionales
--  para no romper a los alumnos ya cargados.
--
--  En producción la crea init_db.php. En local, después de schema_sprint5.sql:
--    mysql --default-character-set=utf8 -u root educar_db < database/schema_legajo.sql
--  y para los datos de demo: php database/seed_legajo.php
-- =========================================================

CREATE TABLE IF NOT EXISTS alumnos_datos (
    alumno_id           INT PRIMARY KEY,
    dni                 VARCHAR(20)  NULL,
    fecha_nacimiento    DATE         NULL,
    domicilio           VARCHAR(200) NULL,
    telefono            VARCHAR(30)  NULL,
    email               VARCHAR(150) NULL,
    tutor_nombre        VARCHAR(100) NULL,   -- tutor declarado en la inscripción (puede no tener usuario "padre")
    contacto_emergencia VARCHAR(150) NULL,   -- nombre y teléfono
    obra_social         VARCHAR(100) NULL,
    observaciones_salud TEXT         NULL,   -- alergias, medicación, etc. (lo necesitan enfermería y docentes)
    solicitud_id        INT          NULL,   -- solicitud_inscripcion de la que salió el alumno, si la hay
    actualizado_en      DATETIME     NULL DEFAULT NULL,
    UNIQUE KEY uq_alumno_dni (dni),
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
