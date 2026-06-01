<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once '../database/db_config.php';

$id     = (int)($_POST['id']    ?? 0);
$estado = trim($_POST['estado'] ?? '');

$estados_validos = ['pendiente', 'contactado', 'admitido', 'rechazado'];
if (!$id || !in_array($estado, $estados_validos, true)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$stmt = $conn->prepare("UPDATE solicitudes_inscripcion SET estado = ? WHERE id = ?");
$stmt->bind_param('si', $estado, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'estado' => $estado]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
}

$stmt->close();
$conn->close();
