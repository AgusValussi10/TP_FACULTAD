<?php

$host    = getenv('MYSQLHOST')     ?: 'localhost';
$dbname  = getenv('MYSQLDATABASE') ?: 'educar_db';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_port = (int)(getenv('MYSQLPORT') ?: 3306);

// Errores de mysqli como excepciones: es el comportamiento por defecto desde PHP 8.1
// (el que usan el CI y Render). Se fija acá para que en PHP 8.0 (XAMPP) se comporte igual.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $db_user, $db_pass, $dbname, $db_port);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

$conn->set_charset('utf8');
$conn->query("SET time_zone = '-03:00'");
date_default_timezone_set('America/Argentina/Buenos_Aires');
