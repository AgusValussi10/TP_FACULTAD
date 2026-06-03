-- =========================================================
--  Base de datos: educar_db
--  Ejecutar en phpMyAdmin o con: mysql -u root -p < database.sql
-- =========================================================

CREATE DATABASE IF NOT EXISTS educar_db
  CHARACTER SET utf8
  COLLATE utf8_general_ci;

USE educar_db;

-- Tabla de usuarios (alumnos, docentes y padres)
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    usuario       VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol           ENUM('alumno', 'docente', 'padre', 'admin') NOT NULL,
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Si la tabla ya existe, agregar 'admin' al ENUM:
-- ALTER TABLE usuarios MODIFY COLUMN rol ENUM('alumno','docente','padre','admin') NOT NULL;

-- =========================================================
--  NOTA: No insertes contraseñas en texto plano.
--  Usá el archivo setup_demo.php para crear usuarios de prueba
--  con contraseñas correctamente encriptadas (bcrypt).
-- =========================================================

-- Opiniones enviadas desde el formulario público (admin las aprueba/rechaza)
CREATE TABLE IF NOT EXISTS opiniones (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL DEFAULT 'Anónimo',
    texto      TEXT NOT NULL,
    mes        TINYINT UNSIGNED NOT NULL,
    anio       SMALLINT UNSIGNED NOT NULL,
    estado     ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Puestos vacantes iniciales
INSERT IGNORE INTO puestos_vacantes (titulo, descripcion, tipo, urgente) VALUES
('Docente de Nivel Inicial',  'Formación en educación inicial, experiencia mínima 2 años.', 'Tiempo completo', 1),
('Profesor/a de Inglés',      'Certificación internacional requerida (IELTS / TOEFL / Cambridge).', 'Part-time', 1),
('Coordinador/a Deportivo',   'Licenciatura en Educación Física, experiencia en gestión deportiva.', 'Tiempo completo', 0),
('Psicopedagogo/a',           'Para el Servicio de Apoyo Estudiantil. Matrícula profesional vigente.', 'Tiempo completo', 0),
('Personal de Enfermería',    'Licenciado/a en Enfermería con habilitación provincial vigente.', 'Tiempo completo', 0);

-- Opiniones de ejemplo (admin puede aprobar/rechazar desde el panel)
INSERT IGNORE INTO opiniones (nombre, texto, mes, anio, estado) VALUES
('María González',  'Excelente propuesta educativa. La integración de deportes, idiomas y tecnología en la currícula es exactamente lo que las familias de Resistencia necesitaban. Esperamos con ansias el inicio en 2027.', 4, 2026, 'pendiente'),
('Carlos Ramírez',  'La jornada extendida es una gran ventaja. La propuesta de bienestar estudiantil y el apoyo pedagógico individualizado marcan una diferencia real.', 3, 2026, 'pendiente'),
('Laura Fernández', 'Me parece muy completa la oferta académica. El hecho de que incluya nivel inicial, primario y secundario en el mismo centro hace todo mucho más cómodo para las familias.', 5, 2026, 'pendiente'),
('Roberto Díaz',    'La infraestructura que están planificando es impresionante. Piscina, gimnasio, sala de computación moderna... claramente pensaron en el desarrollo integral de los chicos.', 2, 2026, 'pendiente');

CREATE TABLE IF NOT EXISTS noticias (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    titulo      VARCHAR(200)  NOT NULL,
    resumen     VARCHAR(400)  NOT NULL DEFAULT '',
    contenido   TEXT          NOT NULL,
    categoria   ENUM('institucional','academica','deportiva','cultural','general') NOT NULL DEFAULT 'general',
    imagen_url  VARCHAR(300)  NOT NULL DEFAULT '',
    estado      ENUM('borrador','publicada','archivada') NOT NULL DEFAULT 'borrador',
    fecha_pub   DATE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Postulaciones de ejemplo (visibles en el panel admin > Propuestas de trabajo)
INSERT INTO postulaciones (puesto_id, nombre, apellido, dni, email, telefono, experiencia_anios, experiencia_descripcion, estado)
SELECT 1,'Ana','Ruiz','32415678','ana.ruiz@gmail.com','3624-555001',3,
  'Trabajé 3 años en jardín maternal privado. Manejo de grupos de 3 a 5 años, planificación de actividades lúdico-educativas y atención temprana.',
  'pendiente'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM postulaciones WHERE dni='32415678');

INSERT INTO postulaciones (puesto_id, nombre, apellido, dni, email, telefono, experiencia_anios, experiencia_descripcion, estado)
SELECT 2,'Martín','López','28963412','martin.lopez@outlook.com','3624-555002',5,
  'Certificación Cambridge CAE. Cinco años dictando inglés en instituciones privadas niveles primario y secundario. Metodología comunicativa.',
  'revisado'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM postulaciones WHERE dni='28963412');

INSERT INTO postulaciones (puesto_id, nombre, apellido, dni, email, telefono, experiencia_anios, experiencia_descripcion, estado)
SELECT 3,'Sofía','García','35102847','sofia.garcia@yahoo.com','3624-555003',8,
  'Lic. en Educación Física. 8 años como coordinadora de actividades deportivas extracurriculares. Gestión de torneos intercolegiales y planificación anual.',
  'seleccionado'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM postulaciones WHERE dni='35102847');

INSERT INTO postulaciones (puesto_id, nombre, apellido, dni, email, telefono, experiencia_anios, experiencia_descripcion, estado)
SELECT 4,'Diego','Torres','37841205','diego.torres@gmail.com','3624-555004',2,
  'Recién graduado en Psicopedagogía. Pasantía de 1 año en escuela primaria, apoyo a alumnos con dificultades de aprendizaje y evaluaciones diagnósticas.',
  'pendiente'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM postulaciones WHERE dni='37841205');

INSERT INTO postulaciones (puesto_id, nombre, apellido, dni, email, telefono, experiencia_anios, experiencia_descripcion, estado)
SELECT 5,'Camila','Romero','30578964','camila.romero@hotmail.com','3624-555005',4,
  'Lic. en Enfermería con habilitación provincial vigente. Experiencia en enfermería escolar, primeros auxilios, control de vacunación y atención pediátrica básica.',
  'pendiente'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM postulaciones WHERE dni='30578964');

-- Solicitudes de inscripción enviadas desde el formulario público
CREATE TABLE IF NOT EXISTS solicitudes_inscripcion (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nombre_alumno    VARCHAR(100) NOT NULL,
    apellido_alumno  VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE         NOT NULL,
    nivel_educativo  ENUM('Inicial','Primario','Secundario') NOT NULL,
    nombre_tutor     VARCHAR(100) NOT NULL,
    telefono         VARCHAR(30)  NOT NULL,
    email            VARCHAR(150) NOT NULL,
    comentarios      TEXT,
    estado           ENUM('pendiente','contactado','admitido','rechazado') NOT NULL DEFAULT 'pendiente',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
