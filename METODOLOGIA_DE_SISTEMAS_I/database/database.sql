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

-- Opiniones de ejemplo (admin puede aprobar/rechazar desde el panel)
INSERT IGNORE INTO opiniones (nombre, texto, mes, anio, estado) VALUES
('María González',  'Excelente propuesta educativa. La integración de deportes, idiomas y tecnología en la currícula es exactamente lo que las familias de Resistencia necesitaban. Esperamos con ansias el inicio en 2027.', 4, 2026, 'pendiente'),
('Carlos Ramírez',  'La jornada extendida es una gran ventaja. La propuesta de bienestar estudiantil y el apoyo pedagógico individualizado marcan una diferencia real.', 3, 2026, 'pendiente'),
('Laura Fernández', 'Me parece muy completa la oferta académica. El hecho de que incluya nivel inicial, primario y secundario en el mismo centro hace todo mucho más cómodo para las familias.', 5, 2026, 'pendiente'),
('Roberto Díaz',    'La infraestructura que están planificando es impresionante. Piscina, gimnasio, sala de computación moderna... claramente pensaron en el desarrollo integral de los chicos.', 2, 2026, 'pendiente');

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
