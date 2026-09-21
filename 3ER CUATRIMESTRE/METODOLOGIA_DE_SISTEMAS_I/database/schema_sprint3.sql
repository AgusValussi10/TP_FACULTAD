-- =========================================================
--  Sprint 3 (RFG05/RFG07/RFG06)
--
--  Ejecutar DESPUÉS de schema_sprint2.sql y setup_demo.php.
--    mysql --default-character-set=utf8 -u root educar_db < database/schema_sprint3.sql
-- =========================================================

USE educar_db;

-- RFG05: planificación anual del docente por materia.
-- UNIQUE(materia_id, anio): un plan por materia por año, sobrescribible.
CREATE TABLE IF NOT EXISTS planificaciones (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    materia_id    INT NOT NULL,
    anio          SMALLINT UNSIGNED NOT NULL,
    contenidos    TEXT NOT NULL,
    observaciones TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_plan (materia_id, anio),
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- RFG06: turno de examen recuperatorio por alumno + materia + período.
-- UNIQUE(alumno_id, materia_id, periodo): un recuperatorio activo por período.
CREATE TABLE IF NOT EXISTS recuperatorios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id  INT NOT NULL,
    materia_id INT NOT NULL,
    periodo    ENUM('1er Trimestre','2do Trimestre','3er Trimestre','Anual') NOT NULL DEFAULT 'Anual',
    fecha      DATE NULL,
    turno      ENUM('mañana','tarde') NULL,
    estado     ENUM('pendiente','aprobado','desaprobado') NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_recup (alumno_id, materia_id, periodo),
    FOREIGN KEY (alumno_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
