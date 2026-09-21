<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../database/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$texto  = trim($_POST['texto']  ?? '');

if ($nombre === '') $nombre = 'Anónimo';

if (strlen($nombre) > 100) {
    echo json_encode(['success' => false, 'message' => 'El nombre es demasiado largo.']);
    exit;
}
if (strlen($texto) < 10) {
    echo json_encode(['success' => false, 'message' => 'La opinión debe tener al menos 10 caracteres.']);
    exit;
}
if (strlen($texto) > 2000) {
    echo json_encode(['success' => false, 'message' => 'La opinión no puede superar los 2000 caracteres.']);
    exit;
}

$mes  = (int) date('n');
$anio = (int) date('Y');

$stmt = $conn->prepare(
    "INSERT INTO opiniones (nombre, texto, mes, anio) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param('ssii', $nombre, $texto, $mes, $anio);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Opinión enviada. Será publicada una vez aprobada.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar la opinión. Intentá nuevamente.']);
}

$stmt->close();
$conn->close();
