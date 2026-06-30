-- =============================================================
--  BrasilPagos — Script de creación de base de datos
--  Motor: MySQL 8.x
--  Encoding: UTF-8
-- =============================================================

DROP DATABASE IF EXISTS brasilpagos;
CREATE DATABASE brasilpagos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE brasilpagos;

-- =============================================================
--  TABLA: usuarios
--  Almacena las cuentas de usuario de la app.
-- =============================================================
CREATE TABLE usuarios (
  id               INT           UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre           VARCHAR(100)  NOT NULL,
  email            VARCHAR(255)  NOT NULL UNIQUE,
  password_hash    VARCHAR(255)  NOT NULL,
  pais_residencia  CHAR(2)       NOT NULL DEFAULT 'AR' COMMENT 'ISO 3166-1 alpha-2',
  moneda_base      CHAR(3)       NOT NULL DEFAULT 'ARS' COMMENT 'ISO 4217',
  email_verificado TINYINT(1)   NOT NULL DEFAULT 0,
  creado_en        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================================
--  TABLA: billeteras  (MAESTRO)
--  Catálogo de billeteras virtuales argentinas.
-- =============================================================
CREATE TABLE billeteras (
  id               INT           UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre           VARCHAR(100)  NOT NULL UNIQUE,
  iniciales        CHAR(3)       NOT NULL,
  color_hex        CHAR(7)       NOT NULL COMMENT 'Color de marca en formato #RRGGBB',
  descripcion      VARCHAR(255)  NOT NULL,
  url_oficial      VARCHAR(255)  NOT NULL,
  rating_promedio  DECIMAL(3,1)  NOT NULL DEFAULT 0.0,
  cantidad_resenas INT           UNSIGNED NOT NULL DEFAULT 0,
  activa           TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================
--  TABLA: billetera_paises  (DETALLE de billeteras)
--  Países destino que soporta cada billetera.
-- =============================================================
CREATE TABLE billetera_paises (
  id             INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  billetera_id   INT          UNSIGNED NOT NULL,
  codigo_pais    CHAR(2)      NOT NULL COMMENT 'ISO 3166-1 alpha-2',
  metodo_pago    VARCHAR(50)  NOT NULL DEFAULT 'PIX' COMMENT 'PIX, transferencia, etc.',
  CONSTRAINT fk_bpaises_billetera FOREIGN KEY (billetera_id)
    REFERENCES billeteras (id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_billetera_pais (billetera_id, codigo_pais)
);

-- =============================================================
--  TABLA: billetera_monedas  (DETALLE de billeteras)
--  Monedas que acepta cada billetera.
-- =============================================================
CREATE TABLE billetera_monedas (
  id            INT      UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  billetera_id  INT      UNSIGNED NOT NULL,
  moneda        CHAR(3)  NOT NULL COMMENT 'ISO 4217',
  CONSTRAINT fk_bmonedas_billetera FOREIGN KEY (billetera_id)
    REFERENCES billeteras (id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_billetera_moneda (billetera_id, moneda)
);

-- =============================================================
--  TABLA: billetera_condiciones  (DETALLE de billeteras)
--  Límites operativos y comisiones por billetera.
-- =============================================================
CREATE TABLE billetera_condiciones (
  id                  INT            UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  billetera_id        INT            UNSIGNED NOT NULL UNIQUE,
  comision_pct        DECIMAL(5,2)   NOT NULL DEFAULT 0.00 COMMENT 'Porcentaje de comisión',
  limite_diario_brl   DECIMAL(10,2)  NOT NULL COMMENT 'Límite diario en BRL',
  limite_mensual_brl  DECIMAL(10,2)  NOT NULL COMMENT 'Límite mensual en BRL',
  tiempo_estimado     VARCHAR(50)    NOT NULL DEFAULT 'Instantáneo',
  detalle_comision    TEXT,
  CONSTRAINT fk_bcond_billetera FOREIGN KEY (billetera_id)
    REFERENCES billeteras (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- =============================================================
--  TABLA: billetera_pros_contras  (DETALLE de billeteras)
--  Lista de ventajas y desventajas de cada billetera.
-- =============================================================
CREATE TABLE billetera_pros_contras (
  id            INT           UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  billetera_id  INT           UNSIGNED NOT NULL,
  tipo          ENUM('pro','contra') NOT NULL,
  descripcion   VARCHAR(255)  NOT NULL,
  orden         TINYINT       UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_bpc_billetera FOREIGN KEY (billetera_id)
    REFERENCES billeteras (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- =============================================================
--  TABLA: billetera_requisitos  (DETALLE de billeteras)
--  Requisitos para poder usar cada billetera.
-- =============================================================
CREATE TABLE billetera_requisitos (
  id            INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  billetera_id  INT          UNSIGNED NOT NULL,
  descripcion   VARCHAR(255) NOT NULL,
  orden         TINYINT      UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_breq_billetera FOREIGN KEY (billetera_id)
    REFERENCES billeteras (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- =============================================================
--  TABLA: cotizaciones  (DETALLE de billeteras — historial)
--  Tipo de cambio ARS/BRL registrado para cada billetera.
--  Permite llevar historial temporal de cotizaciones.
-- =============================================================
CREATE TABLE cotizaciones (
  id             INT            UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  billetera_id   INT            UNSIGNED NOT NULL,
  moneda_origen  CHAR(3)        NOT NULL DEFAULT 'ARS',
  moneda_destino CHAR(3)        NOT NULL DEFAULT 'BRL',
  tasa           DECIMAL(12,4)  NOT NULL COMMENT 'ARS por unidad de moneda destino',
  registrado_en  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cotiz_billetera FOREIGN KEY (billetera_id)
    REFERENCES billeteras (id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_cotiz_billetera_fecha (billetera_id, registrado_en DESC)
);

-- =============================================================
--  TABLA: resenas  (DETALLE de billeteras)
--  Opiniones de usuarios sobre cada billetera.
-- =============================================================
CREATE TABLE resenas (
  id            INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  billetera_id  INT          UNSIGNED NOT NULL,
  usuario_id    INT          UNSIGNED,
  autor_nombre  VARCHAR(100) NOT NULL COMMENT 'Nombre visible (puede ser anónimo)',
  calificacion  TINYINT      UNSIGNED NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
  comentario    TEXT,
  fecha_resena  DATE         NOT NULL,
  creado_en     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_resena_billetera FOREIGN KEY (billetera_id)
    REFERENCES billeteras (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_resena_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- =============================================================
--  TABLA: alertas  (MAESTRO — pertenece a usuario)
--  Alertas de precio configuradas por cada usuario.
-- =============================================================
CREATE TABLE alertas (
  id             INT            UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id     INT            UNSIGNED NOT NULL,
  billetera_id   INT            UNSIGNED NOT NULL,
  moneda_origen  CHAR(3)        NOT NULL DEFAULT 'ARS',
  moneda_destino CHAR(3)        NOT NULL DEFAULT 'BRL',
  condicion      ENUM('supera','baja_de') NOT NULL,
  valor_objetivo DECIMAL(12,4)  NOT NULL,
  activa         TINYINT(1)    NOT NULL DEFAULT 1,
  disparada_en   DATETIME,
  creado_en      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_alerta_usuario   FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_alerta_billetera FOREIGN KEY (billetera_id)
    REFERENCES billeteras (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- =============================================================
--  TABLA: historial_consultas  (DETALLE de usuario)
--  Cada búsqueda de cotización realizada por un usuario.
-- =============================================================
CREATE TABLE historial_consultas (
  id                   INT            UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id           INT            UNSIGNED,
  monto                DECIMAL(12,2)  NOT NULL,
  moneda_destino       CHAR(3)        NOT NULL DEFAULT 'BRL',
  mejor_billetera_id   INT            UNSIGNED,
  mejor_tasa           DECIMAL(12,4),
  total_ars            DECIMAL(14,2),
  consultado_en        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_hist_usuario   FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_hist_billetera FOREIGN KEY (mejor_billetera_id)
    REFERENCES billeteras (id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- =============================================================
--  TABLA: favoritos  (DETALLE de usuario)
--  Billeteras marcadas como favoritas por cada usuario.
-- =============================================================
CREATE TABLE favoritos (
  id            INT      UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id    INT      UNSIGNED NOT NULL,
  billetera_id  INT      UNSIGNED NOT NULL,
  creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fav_usuario   FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_fav_billetera FOREIGN KEY (billetera_id)
    REFERENCES billeteras (id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_usuario_billetera (usuario_id, billetera_id)
);
