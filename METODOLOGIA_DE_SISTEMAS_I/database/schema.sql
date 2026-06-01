CREATE TABLE IF NOT EXISTS usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    usuario       VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol           ENUM('alumno', 'docente', 'padre') NOT NULL,
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
