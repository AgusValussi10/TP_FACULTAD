CREATE TABLE IF NOT EXISTS usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    usuario       VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol           ENUM('alumno', 'docente', 'padre', 'admin') NOT NULL,
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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

CREATE TABLE IF NOT EXISTS puestos_vacantes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    titulo      VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,
    tipo        VARCHAR(50)  NOT NULL DEFAULT 'Tiempo completo',
    urgente     TINYINT(1)   NOT NULL DEFAULT 0,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS postulaciones (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    puesto_id               INT NOT NULL,
    nombre                  VARCHAR(100) NOT NULL,
    apellido                VARCHAR(100) NOT NULL,
    dni                     VARCHAR(20)  NOT NULL,
    email                   VARCHAR(150) NOT NULL,
    telefono                VARCHAR(30)  NOT NULL,
    experiencia_anios       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    experiencia_descripcion TEXT NOT NULL,
    estado                  ENUM('pendiente','revisado','seleccionado','rechazado') NOT NULL DEFAULT 'pendiente',
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (puesto_id) REFERENCES puestos_vacantes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS opiniones (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL DEFAULT 'Anónimo',
    texto      TEXT NOT NULL,
    mes        TINYINT UNSIGNED NOT NULL,
    anio       SMALLINT UNSIGNED NOT NULL,
    estado     ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS consultas (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    asunto     VARCHAR(200) NOT NULL DEFAULT '',
    mensaje    TEXT NOT NULL,
    estado     ENUM('pendiente','leida','respondida','archivada') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
