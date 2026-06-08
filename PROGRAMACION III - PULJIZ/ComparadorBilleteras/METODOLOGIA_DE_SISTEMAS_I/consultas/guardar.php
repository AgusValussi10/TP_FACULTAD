<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../database/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$nombre  = trim($_POST['nombre']  ?? '');
$email   = trim($_POST['email']   ?? '');
$asunto  = trim($_POST['asunto']  ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

if ($nombre === '' || strlen($nombre) < 2) {
    echo json_encode(['success' => false, 'message' => 'Ingresá tu nombre completo.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Ingresá un email válido.']);
    exit;
}
if (strlen($mensaje) < 10) {
    echo json_encode(['success' => false, 'message' => 'El mensaje debe tener al menos 10 caracteres.']);
    exit;
}
if (strlen($nombre) > 100 || strlen($email) > 150 || strlen($asunto) > 200 || strlen($mensaje) > 3000) {
    echo json_encode(['success' => false, 'message' => 'Uno de los campos supera el límite permitido.']);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO consultas (nombre, email, asunto, mensaje) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param('ssss', $nombre, $email, $asunto, $mensaje);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Mensaje enviado. Te responderemos a la brevedad.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje. Intentá nuevamente.']);
}

$stmt->close();
$conn->close();
