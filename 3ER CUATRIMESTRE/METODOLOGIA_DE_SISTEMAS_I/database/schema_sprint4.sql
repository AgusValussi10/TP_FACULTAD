-- =========================================================
--  Sprint 4 (RFG08/RFG15/RFG16)
--
--  Ejecutar DESPUÉS de schema_sprint3.sql.
--    mysql --default-character-set=utf8 -u root educar_db < database/schema_sprint4.sql
-- =========================================================

USE educar_db;

-- RFG08: capacidad máxima de alumnos por curso.
-- Se agrega a la tabla cursos existente.
ALTER TABLE cursos
  ADD COLUMN IF NOT EXISTS capacidad TINYINT UNSIGNED NOT NULL DEFAULT 30 AFTER nivel_educativo;

-- RFG16: legajo completo del alumno — tabla de sanciones disciplinarias
-- (se usa también en RFG14/Sprint 6, pero el legajo ya la necesita para mostrarla).
CREATE TABLE IF NOT EXISTS sanciones (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id   INT NOT NULL,
    tipo        ENUM('apercibimiento','suspension','otra') NOT NULL,
    descripcion TEXT NOT NULL,
    fecha       DATE NOT NULL,
    registrado_por INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumno_id)       REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (registrado_por)  REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
