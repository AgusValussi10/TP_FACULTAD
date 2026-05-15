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
    rol           ENUM('alumno', 'docente', 'padre') NOT NULL,
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =========================================================
--  NOTA: No insertes contraseñas en texto plano.
--  Usá el archivo setup_demo.php para crear usuarios de prueba
--  con contraseñas correctamente encriptadas (bcrypt).
-- =========================================================
