<?php
$host    = getenv('MYSQLHOST')     ?: 'localhost';
$dbname  = getenv('MYSQLDATABASE') ?: 'educar_db';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_port = (int)(getenv('MYSQLPORT') ?: 3306);

$conn = new mysqli($host, $db_user, $db_pass, $dbname, $db_port);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

$conn->set_charset('utf8');
