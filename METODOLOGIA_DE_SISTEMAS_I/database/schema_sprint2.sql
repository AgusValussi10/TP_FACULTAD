-- =========================================================
--  Módulo Alumnos/Padres/Enfermería — Sprint 2 (RFG04/RFG09/RFG10)
--
--  ORDEN DE EJECUCIÓN (importante — mismo cuidado que ya requería
--  schema_gestion.sql, que debe correr DESPUÉS de setup_demo.php):
--    1) database/schema.sql
--    2) database/schema_sprint2.sql   <- este archivo, PRIMERA pasada
--       (agrega el rol 'enfermeria' al ENUM de usuarios; sin esto,
--       setup_demo.php no puede crear a sandra.benitez con ese rol)
--    3) database/setup_demo.php
--    4) database/schema_gestion.sql
--    5) database/schema_sprint2.sql   <- SEGUNDA pasada (idempotente)
--       para poblar el seed de padre_alumno, que necesita que los
--       usuarios demo (laura.martinez, ana.garcia, etc.) ya existan.
--
--    mysql --default-character-set=utf8 -u root educar_db < database/schema_sprint2.sql
-- =========================================================

USE educar_db;

-- Nuevo rol 'enfermeria' para RFG10.
ALTER TABLE usuarios MODIFY COLUMN rol ENUM('alumno','docente','padre','admin','enfermeria') NOT NULL;

-- RFG04: el admin debe poder confirmar "aprobó el nivel anterior" antes de admitir.
-- IF NOT EXISTS (extensión de MariaDB) permite volver a ejecutar este script sin error.
ALTER TABLE solicitudes_inscripcion
  ADD COLUMN IF NOT EXISTS nivel_anterior_aprobado TINYINT(1) NULL DEFAULT NULL AFTER comentarios;

-- RFG09/RFG10: vínculo padre/tutor <-> alumno (no existía en el esquema).
CREATE TABLE IF NOT EXISTS padre_alumno (
    padre_id  INT NOT NULL,
    alumno_id INT NOT NULL,
    PRIMARY KEY (padre_id, alumno_id),
    FOREIGN KEY (padre_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- RFG09: reserva mensual de comedor/transporte.
CREATE TABLE IF NOT EXISTS reservas_servicios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id  INT NOT NULL,
    servicio   ENUM('comedor','transporte') NOT NULL,
    mes        TINYINT UNSIGNED NOT NULL,
    anio       SMALLINT UNSIGNED NOT NULL,
    estado     ENUM('confirmada') NOT NULL DEFAULT 'confirmada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reserva (alumno_id, servicio, mes, anio),
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- RFG10: atenciones de enfermería.
CREATE TABLE IF NOT EXISTS atenciones_enfermeria (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id     INT NOT NULL,
    motivo        VARCHAR(200) NOT NULL,
    hora          TIME NOT NULL,
    observaciones TEXT,
    atendido_por  INT NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumno_id)    REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (atendido_por) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- RFG10: notificación automática al padre/tutor (in-app; no hay servicio de mail
-- configurado en el proyecto todavía, así que se implementa como notificación
-- interna visible en el Portal Familias — dejar comentado como asunción).
CREATE TABLE IF NOT EXISTS notificaciones (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    padre_id   INT NULL,
    alumno_id  INT NOT NULL,
    tipo       VARCHAR(50) NOT NULL,
    mensaje    TEXT NOT NULL,
    leida      TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (padre_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Seed: vincular a los padres demo con sus alumnos demo (requiere que
-- setup_demo.php ya haya corrido).
INSERT IGNORE INTO padre_alumno (padre_id, alumno_id)
SELECT p.id, a.id FROM usuarios p, usuarios a
WHERE p.usuario = 'laura.martinez' AND a.usuario = 'ana.garcia';

INSERT IGNORE INTO padre_alumno (padre_id, alumno_id)
SELECT p.id, a.id FROM usuarios p, usuarios a
WHERE p.usuario = 'diego.fernandez' AND a.usuario = 'carlos.lopez';
