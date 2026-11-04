-- =========================================================
--  Sprint 6 — Educar para Transformar
--
--  RFG14 (RN13): registro de sanciones disciplinarias desde el sistema.
--  La tabla sanciones existe desde Sprint 4; acá se agregan los días de
--  suspensión y la marca "sin contacto" (alumno sin padre vinculado, como el
--  flujo 6.A de enfermería) y la anulación lógica (sin borrar el registro).
--
--  Sin "USE": se aplica sobre la base elegida. En producción lo hace
--  init_db.php (verifica cada columna antes de agregarla). A mano, una vez:
--    mysql --default-character-set=utf8 -u root <base> < database/schema_sprint6.sql
-- =========================================================

-- RFG14: días de suspensión y aviso pendiente a la familia.
ALTER TABLE sanciones
  ADD COLUMN dias_suspension  TINYINT UNSIGNED NULL DEFAULT NULL,
  ADD COLUMN sin_contacto     TINYINT(1) NOT NULL DEFAULT 0;

-- RFG14: anulación lógica (la sanción queda en el legajo, tachada). DATETIME y no
-- TIMESTAMP: la tabla ya tiene created_at TIMESTAMP y MySQL 5.5 no admite dos con
-- CURRENT_TIMESTAMP.
ALTER TABLE sanciones
  ADD COLUMN anulada          TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN motivo_anulacion VARCHAR(500) NULL DEFAULT NULL,
  ADD COLUMN anulada_por      INT NULL DEFAULT NULL,
  ADD COLUMN anulada_en       DATETIME NULL DEFAULT NULL;
