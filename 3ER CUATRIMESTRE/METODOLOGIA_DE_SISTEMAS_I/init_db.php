<?php

$host    = getenv('MYSQLHOST')     ?: 'localhost';
$dbname  = getenv('MYSQLDATABASE') ?: 'educar_db';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_port = (int)(getenv('MYSQLPORT') ?: 3306);

$conn = new mysqli($host, $db_user, $db_pass, $dbname, $db_port);
if ($conn->connect_error) {
    fwrite(STDERR, "DB connection failed: " . $conn->connect_error . PHP_EOL);
    exit(1);
}

$schema = file_get_contents(__DIR__ . '/database/schema.sql');
if ($conn->multi_query($schema)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
}

if ($conn->errno) {
    fwrite(STDERR, "Schema error: " . $conn->error . PHP_EOL);
    exit(1);
}

// Sprint 5 (RFG11/RFG12): mismas tablas que database/schema_sprint5.sql, que no
// se puede correr tal cual en producción (hace "USE educar_db" y la base de
// Render tiene otro nombre).
$conn->set_charset('utf8');
$tablasSprint5 = [
    "CREATE TABLE IF NOT EXISTS deportes (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        nombre      VARCHAR(60) NOT NULL,
        horario     VARCHAR(100) NOT NULL,
        cupo_maximo TINYINT UNSIGNED NOT NULL DEFAULT 20,
        UNIQUE KEY uq_deporte (nombre)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    "CREATE TABLE IF NOT EXISTS inscripciones_deportivas (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        alumno_id  INT NOT NULL,
        deporte_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_inscripcion_deporte (alumno_id, deporte_id),
        FOREIGN KEY (alumno_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (deporte_id) REFERENCES deportes(id)  ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    "CREATE TABLE IF NOT EXISTS cuotas (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    // Catálogo de deportes (los mismos 6 de la página pública, index.html).
    "INSERT IGNORE INTO deportes (nombre, horario, cupo_maximo) VALUES
        ('Atletismo',       'Lunes y Miércoles 16:00–17:30',            20),
        ('Natación',        'Martes y Jueves 15:00–16:00',              16),
        ('Fútbol',          'Lunes, Miércoles y Viernes 17:00–18:30',   22),
        ('Artes Marciales', 'Martes y Jueves 17:00–18:00',              18),
        ('Vóleibol',        'Miércoles y Viernes 16:00–17:30',          18),
        ('Danza',           'Lunes y Jueves 15:30–16:30',               20)",
];
foreach ($tablasSprint5 as $sql) {
    if (!$conn->query($sql)) {
        fwrite(STDERR, "Error en tablas de Sprint 5: " . $conn->error . PHP_EOL);
        exit(1);
    }
}

/** true si la tabla existe en la base actual. */
function tabla_existe(mysqli $conn, string $tabla): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->bind_param('s', $tabla);
    $stmt->execute();
    $existe = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    return $existe;
}

// Sprint 3/4 (RFG05/RFG06/RFG16): planificaciones, recuperatorios y sanciones.
// Antes solo se creaban corriendo schema_sprint3/4.sql a mano (que hacen
// "USE educar_db" y no sirven tal cual en Render); si faltaban, los endpoints
// tiraban excepción y la pantalla quedaba "sin andar". Dependen de materias.
if (tabla_existe($conn, 'materias')) {
    $tablasSprint34 = [
        "CREATE TABLE IF NOT EXISTS planificaciones (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            materia_id     INT NOT NULL,
            anio           SMALLINT UNSIGNED NOT NULL,
            contenidos     TEXT NOT NULL,
            observaciones  TEXT,
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en DATETIME NULL DEFAULT NULL,
            UNIQUE KEY uq_plan (materia_id, anio),
            FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS recuperatorios (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            alumno_id  INT NOT NULL,
            materia_id INT NOT NULL,
            periodo    ENUM('1er Trimestre','2do Trimestre','3er Trimestre','Anual') NOT NULL DEFAULT 'Anual',
            fecha      DATE NULL,
            turno      ENUM('mañana','tarde') NULL,
            estado     ENUM('pendiente','aprobado','desaprobado') NOT NULL DEFAULT 'pendiente',
            nota       TINYINT UNSIGNED NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_recup (alumno_id, materia_id, periodo),
            FOREIGN KEY (alumno_id)  REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    ];
    foreach ($tablasSprint34 as $sql) {
        if (!$conn->query($sql)) {
            fwrite(STDERR, "Error en tablas de Sprint 3: " . $conn->error . PHP_EOL);
            exit(1);
        }
    }
}
$tablasUsuarios = [
    "CREATE TABLE IF NOT EXISTS sanciones (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        alumno_id      INT NOT NULL,
        tipo           ENUM('apercibimiento','suspension','otra') NOT NULL,
        descripcion    TEXT NOT NULL,
        fecha          DATE NOT NULL,
        registrado_por INT NOT NULL,
        created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (alumno_id)      REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    // Legajo completo: datos personales del alumno (database/schema_legajo.sql).
    "CREATE TABLE IF NOT EXISTS alumnos_datos (
        alumno_id           INT PRIMARY KEY,
        dni                 VARCHAR(20)  NULL,
        fecha_nacimiento    DATE         NULL,
        domicilio           VARCHAR(200) NULL,
        telefono            VARCHAR(30)  NULL,
        email               VARCHAR(150) NULL,
        tutor_nombre        VARCHAR(100) NULL,
        contacto_emergencia VARCHAR(150) NULL,
        obra_social         VARCHAR(100) NULL,
        observaciones_salud TEXT         NULL,
        solicitud_id        INT          NULL,
        actualizado_en      DATETIME     NULL DEFAULT NULL,
        UNIQUE KEY uq_alumno_dni (dni),
        FOREIGN KEY (alumno_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
];
foreach ($tablasUsuarios as $sql) {
    if (!$conn->query($sql)) {
        fwrite(STDERR, "Error en sanciones/alumnos_datos: " . $conn->error . PHP_EOL);
        exit(1);
    }
}

// Columnas agregadas en sprints posteriores. Los schema_sprintN.sql usan
// "ADD COLUMN IF NOT EXISTS", que es sintaxis de MariaDB y falla en MySQL,
// así que se verifican acá contra information_schema (idempotente).
$columnas = [
    ['solicitudes_inscripcion', 'nivel_anterior_aprobado', "TINYINT(1) NULL DEFAULT NULL AFTER comentarios"],
    ['cursos',                  'capacidad',               "TINYINT UNSIGNED NOT NULL DEFAULT 30"],
    ['alumno_curso',            'condicion',               "ENUM('regular','pendiente_regularizacion') NOT NULL DEFAULT 'regular'"],
    // Recibo de pago: forma de pago, referencia opcional y quién registró el cobro.
    ['cuotas',                  'metodo_pago',             "ENUM('efectivo','transferencia','tarjeta_debito','tarjeta_credito','otro') NULL DEFAULT NULL"],
    ['cuotas',                  'referencia_pago',         "VARCHAR(60) NULL DEFAULT NULL"],
    ['cuotas',                  'registrado_por',          "INT NULL DEFAULT NULL"],
    // Planificación: fecha de la última edición (DATETIME por compatibilidad con MySQL 5.5).
    ['planificaciones',         'actualizado_en',          "DATETIME NULL DEFAULT NULL"],
    // Recuperatorio: nota obtenida (el estado aprobado/desaprobado sale de ella).
    ['recuperatorios',          'nota',                    "TINYINT UNSIGNED NULL DEFAULT NULL AFTER estado"],
    // RFG14 (Sprint 6): sanciones con días de suspensión y aviso pendiente a la familia.
    ['sanciones',               'dias_suspension',         "TINYINT UNSIGNED NULL DEFAULT NULL"],
    ['sanciones',               'sin_contacto',            "TINYINT(1) NOT NULL DEFAULT 0"],
    // RFG14b: anulación lógica de sanciones (DATETIME: la tabla ya tiene un TIMESTAMP).
    ['sanciones',               'anulada',                 "TINYINT(1) NOT NULL DEFAULT 0"],
    ['sanciones',               'motivo_anulacion',        "VARCHAR(500) NULL DEFAULT NULL"],
    ['sanciones',               'anulada_por',             "INT NULL DEFAULT NULL"],
    ['sanciones',               'anulada_en',              "DATETIME NULL DEFAULT NULL"],
];
foreach ($columnas as [$tabla, $columna, $definicion]) {
    $stmt = $conn->prepare(
        "SELECT
            (SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?) AS tabla,
            (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?) AS columna"
    );
    $stmt->bind_param('sss', $tabla, $tabla, $columna);
    $stmt->execute();
    $existe = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existe['tabla'] && !$existe['columna']) {
        if (!$conn->query("ALTER TABLE `$tabla` ADD COLUMN `$columna` $definicion")) {
            fwrite(STDERR, "Error agregando $tabla.$columna: " . $conn->error . PHP_EOL);
            exit(1);
        }
        echo "Columna agregada: $tabla.$columna" . PHP_EOL;
    }
}

// RFG07 (Sprint 3): consultas del padre sobre una calificación. Tiene FK a
// calificaciones, que crea schema_gestion.sql (no init_db), así que solo se
// crea si esa tabla ya existe; si no, se omite sin romper el arranque.
if (tabla_existe($conn, 'calificaciones') && !tabla_existe($conn, 'consultas_notas')) {
    $ok = $conn->query(
        "CREATE TABLE consultas_notas (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            calificacion_id INT NOT NULL,
            padre_id        INT NOT NULL,
            alumno_id       INT NOT NULL,
            mensaje         TEXT NOT NULL,
            respuesta       TEXT NULL,
            estado          ENUM('pendiente','respondida') NOT NULL DEFAULT 'pendiente',
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            respondida_at   TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (calificacion_id) REFERENCES calificaciones(id) ON DELETE CASCADE,
            FOREIGN KEY (padre_id)        REFERENCES usuarios(id)       ON DELETE CASCADE,
            FOREIGN KEY (alumno_id)       REFERENCES usuarios(id)       ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
    );
    if (!$ok) {
        fwrite(STDERR, "Error creando consultas_notas: " . $conn->error . PHP_EOL);
        exit(1);
    }
    echo "Tabla creada: consultas_notas" . PHP_EOL;
}

// Materias duplicadas: "materias" no tenía clave única, así que el
// INSERT IGNORE de seed_datos.php duplicaba todo cada vez que se corría.
// Se conserva la de menor id por (nombre, curso_id), se le reasignan los
// registros de las copias y recién después se borran las copias (sus FKs
// son ON DELETE CASCADE). Si una fila choca con una única ya existente en la
// materia conservada (UPDATE IGNORE), queda en la copia y se descarta.
if (tabla_existe($conn, 'materias')) {
    $conservadas = "SELECT MIN(id) AS keep_id, nombre, curso_id FROM materias GROUP BY nombre, curso_id";

    foreach (['asistencias', 'calificaciones', 'planificaciones', 'recuperatorios'] as $tablaRef) {
        if (!tabla_existe($conn, $tablaRef)) {
            continue;
        }
        $ok = $conn->query(
            "UPDATE IGNORE `$tablaRef` t
             JOIN materias d ON d.id = t.materia_id
             JOIN ($conservadas) k ON k.nombre = d.nombre AND k.curso_id = d.curso_id
             SET t.materia_id = k.keep_id
             WHERE d.id <> k.keep_id"
        );
        if (!$ok) {
            fwrite(STDERR, "Error reasignando materias en $tablaRef: " . $conn->error . PHP_EOL);
            exit(1);
        }
    }

    if (
        !$conn->query(
            "DELETE d FROM materias d
         JOIN ($conservadas) k ON k.nombre = d.nombre AND k.curso_id = d.curso_id
         WHERE d.id <> k.keep_id"
        )
    ) {
        fwrite(STDERR, "Error borrando materias duplicadas: " . $conn->error . PHP_EOL);
        exit(1);
    }
    if ($conn->affected_rows > 0) {
        echo "Materias duplicadas eliminadas: {$conn->affected_rows}" . PHP_EOL;
    }

    $res = $conn->query(
        "SELECT 1 FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'materias' AND INDEX_NAME = 'uq_materia_curso'"
    );
    if (!$res->fetch_row()) {
        if (!$conn->query("ALTER TABLE materias ADD UNIQUE KEY uq_materia_curso (nombre, curso_id)")) {
            fwrite(STDERR, "Error agregando clave única a materias: " . $conn->error . PHP_EOL);
            exit(1);
        }
        echo "Clave única agregada: materias(nombre, curso_id)" . PHP_EOL;
    }
}

// Parte 3 (App móvil, MSII): tarifas, recorridos, facturas, pagos y
// comprobantes. Mismo modelo que database/schema_parte3.sql, en versión
// idempotente. Depende de cuotas/deportes (Sprint 5, arriba) y de
// reservas_servicios (schema_sprint2.sql); sin esta última se omite.
function columna_existe(mysqli $conn, string $tabla, string $columna): bool
{
    $stmt = $conn->prepare(
        "SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->bind_param('ss', $tabla, $columna);
    $stmt->execute();
    $existe = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    return $existe;
}

function ejecutar_o_salir(mysqli $conn, string $sql, string $contexto): void
{
    if (!$conn->query($sql)) {
        fwrite(STDERR, "Error en $contexto: " . $conn->error . PHP_EOL);
        exit(1);
    }
}

if (tabla_existe($conn, 'reservas_servicios')) {
    $tablasParte3 = [
        "CREATE TABLE IF NOT EXISTS recorridos_transporte (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            nombre      VARCHAR(60)  NOT NULL,
            descripcion VARCHAR(255) NOT NULL,
            activo      TINYINT(1)   NOT NULL DEFAULT 1,
            UNIQUE KEY uq_recorrido (nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "INSERT IGNORE INTO recorridos_transporte (id, nombre, descripcion) VALUES
            (1, 'Recorrido 1 · Centro',       'Plaza 25 de Mayo, Av. Alberdi, Av. Sarmiento hasta la escuela'),
            (2, 'Recorrido 2 · Norte',        'Villa Prosperidad, Av. Edison, Av. Alvear'),
            (3, 'Recorrido 3 · Sur',          'Villa Don Enrique, Av. Laprida, Av. Las Heras'),
            (4, 'Recorrido 4 · Barranqueras', 'Barranqueras, Av. 9 de Julio, Av. Italia')",
        "CREATE TABLE IF NOT EXISTS tarifas (
            clave           VARCHAR(40)   NOT NULL PRIMARY KEY,
            valor           DECIMAL(10,2) NOT NULL,
            actualizado_en  DATETIME      NULL DEFAULT NULL,
            actualizado_por INT           NULL DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS facturas (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS factura_items (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS pagos (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS pago_items (
            pago_id         INT NOT NULL,
            factura_item_id INT NOT NULL,
            importe         DECIMAL(10,2) NOT NULL,
            PRIMARY KEY (pago_id, factura_item_id),
            FOREIGN KEY (pago_id)         REFERENCES pagos(id)         ON DELETE CASCADE,
            FOREIGN KEY (factura_item_id) REFERENCES factura_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        "CREATE TABLE IF NOT EXISTS comprobantes_transferencia (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        // RFGm07/RFGm08: mails de fin de mes y del día 20 (modo simulación).
        "CREATE TABLE IF NOT EXISTS mails_enviados (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            padre_id       INT NOT NULL,
            tipo           ENUM('fin_de_mes','dia_20') NOT NULL,
            mes            TINYINT UNSIGNED  NOT NULL,
            anio           SMALLINT UNSIGNED NOT NULL,
            destinatario   VARCHAR(150) NULL DEFAULT NULL,
            asunto         VARCHAR(200) NOT NULL,
            cuerpo_html    MEDIUMTEXT NOT NULL,
            adjuntos       VARCHAR(255) NULL DEFAULT NULL,
            total          DECIMAL(10,2) NOT NULL,
            estado         ENUM('simulado','enviado','error') NOT NULL DEFAULT 'simulado',
            error          VARCHAR(255) NULL DEFAULT NULL,
            fecha_simulada DATE NOT NULL,
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_mail (padre_id, tipo, mes, anio),
            FOREIGN KEY (padre_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
    ];
    foreach ($tablasParte3 as $sql) {
        ejecutar_o_salir($conn, $sql, 'tablas de Parte 3');
    }

    if (!columna_existe($conn, 'deportes', 'profesor_id')) {
        ejecutar_o_salir($conn, "ALTER TABLE deportes
            ADD COLUMN profesor_id INT NULL DEFAULT NULL,
            ADD COLUMN dias        SET('lun','mar','mie','jue','vie','sab') NULL DEFAULT NULL,
            ADD COLUMN hora_inicio TIME NULL DEFAULT NULL,
            ADD COLUMN hora_fin    TIME NULL DEFAULT NULL,
            ADD CONSTRAINT fk_deporte_profesor FOREIGN KEY (profesor_id) REFERENCES usuarios(id) ON DELETE SET NULL", 'deportes (Parte 3)');
        echo "Columnas agregadas: deportes.profesor_id/dias/hora_inicio/hora_fin" . PHP_EOL;
    }
    // Horario estructurado de los 6 deportes del catálogo (solo si todavía no se cargó).
    $horarios = [
        ['Atletismo', 'lun,mie', '16:00', '17:30'], ['Natación', 'mar,jue', '15:00', '16:00'],
        ['Fútbol', 'lun,mie,vie', '17:00', '18:30'], ['Artes Marciales', 'mar,jue', '17:00', '18:00'],
        ['Vóleibol', 'mie,vie', '16:00', '17:30'], ['Danza', 'lun,jue', '15:30', '16:30'],
    ];
    $stmtHorario = $conn->prepare(
        "UPDATE deportes SET dias = ?, hora_inicio = ?, hora_fin = ? WHERE nombre = ? AND dias IS NULL"
    );
    foreach ($horarios as [$deporte, $dias, $desde, $hasta]) {
        $stmtHorario->bind_param('ssss', $dias, $desde, $hasta, $deporte);
        $stmtHorario->execute();
    }
    $stmtHorario->close();

    if (!columna_existe($conn, 'reservas_servicios', 'recorrido_id')) {
        ejecutar_o_salir($conn, "ALTER TABLE reservas_servicios
            ADD COLUMN recorrido_id INT NULL DEFAULT NULL,
            ADD CONSTRAINT fk_reserva_recorrido FOREIGN KEY (recorrido_id) REFERENCES recorridos_transporte(id)", 'reservas_servicios (Parte 3)');
        echo "Columna agregada: reservas_servicios.recorrido_id" . PHP_EOL;
    }
}

echo "DB schema OK" . PHP_EOL;
$conn->close();
