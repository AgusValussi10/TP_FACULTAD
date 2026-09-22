<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json; charset=utf-8');
requerir_rol(['admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
require_once __DIR__ . '/../database/db_config.php';

$id     = (int)($_POST['id'] ?? 0);
$accion = $_POST['accion'] ?? '';

if ($id <= 0 || !in_array($accion, ['suspender', 'reactivar', 'eliminar'], true)) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
    exit;
}

if ($accion === 'eliminar') {
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND rol = 'alumno'");
} elseif ($accion === 'suspender') {
    $stmt = $conn->prepare("UPDATE usuarios SET activo = 0 WHERE id = ? AND rol = 'alumno'");
} else {
    $stmt = $conn->prepare("UPDATE usuarios SET activo = 1 WHERE id = ? AND rol = 'alumno'");
}
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();
$conn->close();

$msgs = ['suspender' => 'Alumno suspendido.', 'reactivar' => 'Alumno reactivado.', 'eliminar' => 'Alumno eliminado.'];
echo json_encode(['success' => true, 'message' => $msgs[$accion]]);
