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

echo "DB schema OK" . PHP_EOL;
$conn->close();
