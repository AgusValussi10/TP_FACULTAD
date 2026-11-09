-- =========================================================
--  Parte 3 (App móvil, MSII): tarifas, recorridos de transporte,
--  facturación mensual, pagos por transferencia y comprobantes.
--
--  Sin "USE": se aplica sobre la base elegida (local: educar_test / educar_db).
--    mysql --default-character-set=utf8 -u root educar_test < database/schema_parte3.sql
--  En producción lo cubre init_db.php (bloque "Parte 3"), que es la fuente
--  que corre en cada deploy; este archivo documenta el modelo y sirve en local.
--
--  Requiere: usuarios, cuotas, deportes, reservas_servicios (schemas anteriores).
--  MySQL 5.5: un solo TIMESTAMP con CURRENT_TIMESTAMP por tabla (el resto, DATETIME).
-- =========================================================

-- RFGm11: los 4 recorridos del transporte escolar.
CREATE TABLE IF NOT EXISTS recorridos_transporte (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(60)  NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    UNIQUE KEY uq_recorrido (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO recorridos_transporte (id, nombre, descripcion) VALUES
(1, 'Recorrido 1 · Centro',        'Plaza 25 de Mayo, Av. Alberdi, Av. Sarmiento hasta la escuela'),
(2, 'Recorrido 2 · Norte',         'Villa Prosperidad, Av. Edison, Av. Alvear'),
(3, 'Recorrido 3 · Sur',           'Villa Don Enrique, Av. Laprida, Av. Las Heras'),
(4, 'Recorrido 4 · Barranqueras',  'Barranqueras, Av. 9 de Julio, Av. Italia');

-- Valores que fija el admin. Una fila por concepto (sin historial: el importe
-- queda congelado en cada ítem de factura, así que cambiar una tarifa no toca
-- lo ya facturado). La clave es la PK, p. ej. 'cuota:Primario', 'deporte:3'.
CREATE TABLE IF NOT EXISTS tarifas (
    clave           VARCHAR(40)   NOT NULL PRIMARY KEY,
    valor           DECIMAL(10,2) NOT NULL,
    actualizado_en  DATETIME      NULL DEFAULT NULL,
    actualizado_por INT           NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- RFGm09: profesor responsable y horario estructurado (días + franja) para
-- detectar conflictos. "horario" (texto) se mantiene para mostrar.
ALTER TABLE deportes
    ADD COLUMN profesor_id INT NULL DEFAULT NULL,
    ADD COLUMN dias        SET('lun','mar','mie','jue','vie','sab') NULL DEFAULT NULL,
    ADD COLUMN hora_inicio TIME NULL DEFAULT NULL,
    ADD COLUMN hora_fin    TIME NULL DEFAULT NULL,
    ADD CONSTRAINT fk_deporte_profesor FOREIGN KEY (profesor_id) REFERENCES usuarios(id) ON DELETE SET NULL;

UPDATE deportes SET dias = 'lun,mie',     hora_inicio = '16:00', hora_fin = '17:30' WHERE nombre = 'Atletismo'       AND dias IS NULL;
UPDATE deportes SET dias = 'mar,jue',     hora_inicio = '15:00', hora_fin = '16:00' WHERE nombre = 'Natación'        AND dias IS NULL;
UPDATE deportes SET dias = 'lun,mie,vie', hora_inicio = '17:00', hora_fin = '18:30' WHERE nombre = 'Fútbol'          AND dias IS NULL;
UPDATE deportes SET dias = 'mar,jue',     hora_inicio = '17:00', hora_fin = '18:00' WHERE nombre = 'Artes Marciales' AND dias IS NULL;
UPDATE deportes SET dias = 'mie,vie',     hora_inicio = '16:00', hora_fin = '17:30' WHERE nombre = 'Vóleibol'        AND dias IS NULL;
UPDATE deportes SET dias = 'lun,jue',     hora_inicio = '15:30', hora_fin = '16:30' WHERE nombre = 'Danza'           AND dias IS NULL;

-- RFGm11: recorrido elegido para el transporte de ese mes (NULL en comedor).
ALTER TABLE reservas_servicios
    ADD COLUMN recorrido_id INT NULL DEFAULT NULL,
    ADD CONSTRAINT fk_reserva_recorrido FOREIGN KEY (recorrido_id) REFERENCES recorridos_transporte(id);

-- Factura mensual por alumno (número = 'F-' + id). "Vencida" no se guarda:
-- se calcula con fecha_vencimiento, como el estado visual de las cuotas.
CREATE TABLE IF NOT EXISTS facturas (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id         INT NOT NULL,
    mes               TINYINT UNSIGNED  NOT NULL,
    anio              SMALLINT UNSIGNED NOT NULL,
    fecha_emision     DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    total             DECIMAL(10,2) NOT NULL DEFAULT 0,
    pagado            DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado            ENUM('pendiente','parcial','pagada') NOT NULL DEFAULT 'pendiente',
    generada_por      INT NULL DEFAULT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_factura (alumno_id, mes, anio),
    FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Ítems de la factura. Las FK explícitas (cuota/deporte/recorrido) sirven a
-- los reportes; el ítem cuota/matrícula apunta a la fila de "cuotas" (no se
-- duplica la deuda) y el recargo sale de cuotas.recargo.
CREATE TABLE IF NOT EXISTS factura_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    factura_id   INT NOT NULL,
    tipo         ENUM('cuota','matricula','deporte','comedor','transporte','recargo') NOT NULL,
    clave        VARCHAR(40)  NOT NULL,
    cuota_id     INT NULL DEFAULT NULL,
    deporte_id   INT NULL DEFAULT NULL,
    recorrido_id INT NULL DEFAULT NULL,
    descripcion  VARCHAR(150) NOT NULL,
    importe      DECIMAL(10,2) NOT NULL,
    estado       ENUM('pendiente','en_revision','pagado') NOT NULL DEFAULT 'pendiente',
    UNIQUE KEY uq_item (factura_id, clave),
    FOREIGN KEY (factura_id)   REFERENCES facturas(id)              ON DELETE CASCADE,
    FOREIGN KEY (cuota_id)     REFERENCES cuotas(id)                ON DELETE SET NULL,
    FOREIGN KEY (deporte_id)   REFERENCES deportes(id)              ON DELETE SET NULL,
    FOREIGN KEY (recorrido_id) REFERENCES recorridos_transporte(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Comprobante de pago que emite el sistema (número = 'P-' + id). 1 pago = 1 factura.
CREATE TABLE IF NOT EXISTS pagos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    factura_id     INT NOT NULL,
    padre_id       INT NOT NULL,
    total          DECIMAL(10,2) NOT NULL,
    estado         ENUM('pendiente_transferencia','en_revision','aprobado','rechazado','anulado')
                   NOT NULL DEFAULT 'pendiente_transferencia',
    motivo_rechazo VARCHAR(255) NULL DEFAULT NULL,
    revisado_por   INT NULL DEFAULT NULL,
    revisado_en    DATETIME NULL DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (padre_id)   REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS pago_items (
    pago_id         INT NOT NULL,
    factura_item_id INT NOT NULL,
    importe         DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (pago_id, factura_item_id),
    FOREIGN KEY (pago_id)         REFERENCES pagos(id)         ON DELETE CASCADE,
    FOREIGN KEY (factura_item_id) REFERENCES factura_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- RFGm06: comprobantes de transferencia (varios por pago y, por lo tanto, por
-- factura). El archivo va en la base porque el disco de Render se borra en
-- cada deploy. El estado de revisión es el del pago.
CREATE TABLE IF NOT EXISTS comprobantes_transferencia (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    pago_id             INT NOT NULL,
    nombre_archivo      VARCHAR(120) NOT NULL,
    mime                VARCHAR(50)  NOT NULL,
    tamanio             INT UNSIGNED NOT NULL,
    archivo             MEDIUMBLOB   NOT NULL,
    monto_declarado     DECIMAL(10,2) NOT NULL,
    fecha_transferencia DATE NOT NULL,
    subido_por          INT NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pago_id) REFERENCES pagos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
