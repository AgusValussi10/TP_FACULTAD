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
