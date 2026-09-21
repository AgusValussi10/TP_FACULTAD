-- =========================================================
--  Módulo Alumnos — Sprint 1 (RFG01/RFG02/RFG03)
--  Ejecutar DESPUÉS de schema.sql y database/setup_demo.php.
--
--  IMPORTANTE: importar con charset UTF-8 explícito para que
--  "Matemática" y "3°A" no se corrompan:
--    mysql --default-character-set=utf8 -u root educar_db < database/schema_gestion.sql
-- =========================================================

CREATE TABLE IF NOT EXISTS cursos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(20) NOT NULL,
    nivel_educativo ENUM('Inicial', 'Primario', 'Secundario') NOT NULL,
    UNIQUE (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS materias (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    curso_id   INT NOT NULL,
    docente_id INT NULL,
    FOREIGN KEY (curso_id)   REFERENCES cursos(id)   ON DELETE CASCADE,
    FOREIGN KEY (docente_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Un alumno tiene un único curso activo (alumno_id es PK, no solo índice).
CREATE TABLE IF NOT EXISTS alumno_curso (
    alumno_id INT NOT NULL PRIMARY KEY,
    curso_id  INT NOT NULL,
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id)  REFERENCES cursos(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- UNIQUE(alumno_id, materia_id, fecha): es lo que permite sobrescribir
-- (INSERT ... ON DUPLICATE KEY UPDATE) si la fecha ya fue cargada. RFG02/HU1.
CREATE TABLE IF NOT EXISTS asistencias (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id  INT NOT NULL,
    materia_id INT NOT NULL,
    fecha      DATE NOT NULL,
    estado     ENUM('presente', 'ausente', 'tarde') NOT NULL,
    UNIQUE (alumno_id, materia_id, fecha),
    FOREIGN KEY (alumno_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- UNIQUE(alumno_id, materia_id, evaluacion): una nota por alumno+evaluación,
-- también sobrescribible. RFG03/HU3.
CREATE TABLE IF NOT EXISTS calificaciones (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id        INT NOT NULL,
    materia_id       INT NOT NULL,
    evaluacion       VARCHAR(150) NOT NULL,
    nota             TINYINT UNSIGNED NOT NULL,
    fecha_evaluacion DATE NOT NULL,
    UNIQUE (alumno_id, materia_id, evaluacion),
    FOREIGN KEY (alumno_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- =========================================================
--  Seed demo: curso 3°A (Secundario), Matemática y Lengua y
--  Literatura a cargo de maria.rodriguez (creada por
--  database/setup_demo.php), con ana.garcia y carlos.lopez
--  matriculados.
-- =========================================================

INSERT INTO cursos (nombre, nivel_educativo)
SELECT '3°A', 'Secundario'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cursos WHERE nombre = '3°A');

INSERT INTO materias (nombre, curso_id, docente_id)
SELECT 'Matemática', c.id, u.id
FROM cursos c JOIN usuarios u ON u.usuario = 'maria.rodriguez'
WHERE c.nombre = '3°A'
  AND NOT EXISTS (SELECT 1 FROM materias WHERE nombre = 'Matemática' AND curso_id = c.id);

INSERT INTO materias (nombre, curso_id, docente_id)
SELECT 'Lengua y Literatura', c.id, u.id
FROM cursos c JOIN usuarios u ON u.usuario = 'maria.rodriguez'
WHERE c.nombre = '3°A'
  AND NOT EXISTS (SELECT 1 FROM materias WHERE nombre = 'Lengua y Literatura' AND curso_id = c.id);

INSERT INTO alumno_curso (alumno_id, curso_id)
SELECT u.id, c.id
FROM usuarios u JOIN cursos c ON c.nombre = '3°A'
WHERE u.usuario = 'ana.garcia'
  AND NOT EXISTS (SELECT 1 FROM alumno_curso WHERE alumno_id = u.id);

INSERT INTO alumno_curso (alumno_id, curso_id)
SELECT u.id, c.id
FROM usuarios u JOIN cursos c ON c.nombre = '3°A'
WHERE u.usuario = 'carlos.lopez'
  AND NOT EXISTS (SELECT 1 FROM alumno_curso WHERE alumno_id = u.id);
