-- =========================================================
--  Sprint 5 (RFG11/RFG12/RFG13)
--
--  Ejecutar DESPUÉS de schema_sprint4.sql.
--    mysql --default-character-set=utf8 -u root educar_db < database/schema_sprint5.sql
--
--  NO usar "ADD COLUMN IF NOT EXISTS" (incompatible con MySQL 5.5 de producción).
-- =========================================================

USE educar_db;

-- RFG11: catálogo de deportes/instalaciones deportivas
CREATE TABLE IF NOT EXISTS deportes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(60) NOT NULL,
    horario     VARCHAR(100) NOT NULL,
    cupo_maximo TINYINT UNSIGNED NOT NULL DEFAULT 20,
    UNIQUE KEY uq_deporte (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- RFG11: inscripción de un alumno a un deporte (RN10 no fija un máximo de
-- deportes por alumno, solo cupo y horario por actividad)
CREATE TABLE IF NOT EXISTS inscripciones_deportivas (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id  INT NOT NULL,
    deporte_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inscripcion_deporte (alumno_id, deporte_id),
    FOREIGN KEY (alumno_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (deporte_id) REFERENCES deportes(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- RFG12: matrícula y cuotas
CREATE TABLE IF NOT EXISTS cuotas (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id         INT NOT NULL,
    concepto          ENUM('matricula','cuota') NOT NULL DEFAULT 'cuota',
    mes               TINYINT UNSIGNED NOT NULL,
    anio              SMALLINT UNSIGNED NOT NULL,
    importe           DECIMAL(10,2) NOT NULL,
    recargo           DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha_vencimiento DATE NOT NULL,
    estado            ENUM('pendiente','pagada') NOT NULL DEFAULT 'pendiente',
    fecha_pago        DATE NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cuota (alumno_id, concepto, mes, anio),
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- RFG13 (RN12): condición de inscripción por deuda
ALTER TABLE alumno_curso
  ADD COLUMN condicion ENUM('regular','pendiente_regularizacion') NOT NULL DEFAULT 'regular';

-- Seed: catálogo de deportes (los mismos 6 ya listados en la página pública, index.html)
INSERT IGNORE INTO deportes (nombre, horario, cupo_maximo) VALUES
('Atletismo', 'Lunes y Miércoles 16:00–17:30', 20),
('Natación', 'Martes y Jueves 15:00–16:00', 16),
('Fútbol', 'Lunes, Miércoles y Viernes 17:00–18:30', 22),
('Artes Marciales', 'Martes y Jueves 17:00–18:00', 18),
('Vóleibol', 'Miércoles y Viernes 16:00–17:30', 18),
('Danza', 'Lunes y Jueves 15:30–16:30', 20);
