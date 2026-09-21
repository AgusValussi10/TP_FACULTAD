-- =============================================================
--  Migración: login real de admin + columnas de auditoría
--  Ejecutar sobre una BD brasilpagos ya existente (no recrea nada).
-- =============================================================

USE brasilpagos;

-- Cuentas del panel admin (fijas: solo estas dos, sin endpoint de alta)
CREATE TABLE IF NOT EXISTS admin_usuarios (
  id             INT           UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario        VARCHAR(50)   NOT NULL UNIQUE,
  password_hash  VARCHAR(255)  NOT NULL,
  nombre_visible VARCHAR(100)  NOT NULL,
  creado_en      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Contraseña de ambas: "admin" (hasheada con bcrypt, salt rounds 10)
INSERT INTO admin_usuarios (usuario, password_hash, nombre_visible) VALUES
('fabri', '$2b$10$eAzB06w8R0upl.ZFyHBO9eGUo5JyfHLlwN8t4U7S017fnMVX7IlQq', 'Fabri'),
('agus',  '$2b$10$hwEMzMmKrk5ZTyGMOO6Nt.ccxecd49SfUpCxcHJRg3DlCNkjANo9e', 'Agus');

-- Auditoría en billeteras
ALTER TABLE billeteras
  ADD COLUMN actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER creado_en,
  ADD COLUMN modificado_por VARCHAR(50) NULL AFTER actualizado_en;

-- Auditoría en cotizaciones
ALTER TABLE cotizaciones
  ADD COLUMN modificado_por VARCHAR(50) NULL AFTER registrado_en;
