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
        if ($res = $conn->store_result()) $res->free();
    } while ($conn->more_results() && $conn->next_result());
}

if ($conn->errno) {
    fwrite(STDERR, "Schema error: " . $conn->error . PHP_EOL);
    exit(1);
}

// Columnas agregadas en sprints posteriores. Los schema_sprintN.sql usan
// "ADD COLUMN IF NOT EXISTS", que es sintaxis de MariaDB y falla en MySQL,
// así que se verifican acá contra information_schema (idempotente).
$columnas = [
    ['solicitudes_inscripcion', 'nivel_anterior_aprobado', "TINYINT(1) NULL DEFAULT NULL AFTER comentarios"],
    ['cursos',                  'capacidad',               "TINYINT UNSIGNED NOT NULL DEFAULT 30"],
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

// Materias duplicadas: "materias" no tenía clave única, así que el
// INSERT IGNORE de seed_datos.php duplicaba todo cada vez que se corría.
// Se conserva la de menor id por (nombre, curso_id), se le reasignan los
// registros de las copias y recién después se borran las copias (sus FKs
// son ON DELETE CASCADE). Si una fila choca con una única ya existente en la
// materia conservada (UPDATE IGNORE), queda en la copia y se descarta.
if (tabla_existe($conn, 'materias')) {
    $conservadas = "SELECT MIN(id) AS keep_id, nombre, curso_id FROM materias GROUP BY nombre, curso_id";

    foreach (['asistencias', 'calificaciones', 'planificaciones', 'recuperatorios'] as $tablaRef) {
        if (!tabla_existe($conn, $tablaRef)) continue;
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

    if (!$conn->query(
        "DELETE d FROM materias d
         JOIN ($conservadas) k ON k.nombre = d.nombre AND k.curso_id = d.curso_id
         WHERE d.id <> k.keep_id"
    )) {
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

echo "DB schema OK" . PHP_EOL;
$conn->close();
