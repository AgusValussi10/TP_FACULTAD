<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

$res = $conn->query("SELECT DISTINCT alumno_id FROM alumno_curso");

$actualizados = 0;
while ($row = $res->fetch_assoc()) {
    actualizar_condicion_regularizacion($conn, (int) $row['alumno_id']);
    $actualizados++;
}

$conn->close();

echo json_encode(['success' => true, 'actualizados' => $actualizados]);
