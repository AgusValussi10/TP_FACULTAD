<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('enfermeria');

require_once __DIR__ . '/../database/db_config.php';

$res = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'alumno' ORDER BY nombre");

$alumnos = [];
while ($row = $res->fetch_assoc()) {
    $alumnos[] = $row;
}

echo json_encode(['success' => true, 'alumnos' => $alumnos]);

$conn->close();
