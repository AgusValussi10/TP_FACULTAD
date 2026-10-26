<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('padre');
errores_como_json();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

$padre_id        = (int) $_SESSION['usuario_id'];
$calificacion_id = (int) ($_POST['calificacion_id'] ?? 0);
$mensaje         = trim($_POST['mensaje'] ?? '');

if ($calificacion_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Calificación inválida.']);
    exit;
}
$largo = mb_strlen($mensaje);
if ($largo < 10 || $largo > 1000) {
    echo json_encode(['success' => false, 'message' => 'La consulta debe tener entre 10 y 1000 caracteres.']);
    exit;
}

// La calificación debe ser de un alumno vinculado a este padre.
$stmt = $conn->prepare("SELECT alumno_id FROM calificaciones WHERE id = ?");
$stmt->bind_param('i', $calificacion_id);
$stmt->execute();
$cal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cal || !alumno_pertenece_a_padre($conn, (int) $cal['alumno_id'], $padre_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No podés consultar esa calificación.']);
    $conn->close();
    exit;
}
$alumno_id = (int) $cal['alumno_id'];

// Evita acumular consultas repetidas: una sola pendiente por calificación.
$stmt = $conn->prepare(
    "SELECT 1 FROM consultas_notas WHERE calificacion_id = ? AND padre_id = ? AND estado = 'pendiente'"
);
$stmt->bind_param('ii', $calificacion_id, $padre_id);
$stmt->execute();
$yaPendiente = $stmt->get_result()->num_rows > 0;
$stmt->close();
if ($yaPendiente) {
    echo json_encode(['success' => false, 'message' => 'Ya tenés una consulta pendiente sobre esta calificación.']);
    $conn->close();
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO consultas_notas (calificacion_id, padre_id, alumno_id, mensaje) VALUES (?, ?, ?, ?)"
);
$stmt->bind_param('iiis', $calificacion_id, $padre_id, $alumno_id, $mensaje);
$ok = $stmt->execute();
$stmt->close();
$conn->close();

echo json_encode($ok
    ? ['success' => true, 'message' => 'Consulta enviada al docente.']
    : ['success' => false, 'message' => 'No se pudo enviar la consulta.']);
