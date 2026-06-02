<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once '../database/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$id     = (int) ($_POST['id']     ?? 0);
$estado = trim($_POST['estado'] ?? '');

if ($id <= 0 || !in_array($estado, ['aprobado', 'rechazado', 'pendiente'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$stmt = $conn->prepare("UPDATE opiniones SET estado = ? WHERE id = ?");
$stmt->bind_param('si', $estado, $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontró la opinión o no hubo cambios.']);
}

$stmt->close();
$conn->close();
