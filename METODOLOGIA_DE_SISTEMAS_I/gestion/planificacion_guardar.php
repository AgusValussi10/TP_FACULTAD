<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('docente');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

$materia_id   = (int) ($_POST['materia_id'] ?? 0);
$anio         = (int) ($_POST['anio'] ?? 0);
$contenidos   = trim($_POST['contenidos'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

if ($materia_id <= 0 || $anio < 2024 || $anio > 2100 || strlen($contenidos) < 20) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos. Los contenidos deben tener al menos 20 caracteres.']);
    exit;
}

$docente_id = (int) $_SESSION['usuario_id'];
$materia = materia_valida_para_docente($conn, $materia_id, $docente_id);
if (!$materia) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'La materia no pertenece al docente.']);
    exit;
}

$obs = $observaciones !== '' ? $observaciones : null;

$stmt = $conn->prepare(
    "INSERT INTO planificaciones (materia_id, anio, contenidos, observaciones)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE contenidos = VALUES(contenidos), observaciones = VALUES(observaciones)"
);
$stmt->bind_param('iiss', $materia_id, $anio, $contenidos, $obs);
$ok = $stmt->execute();
$stmt->close();
$conn->close();

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Planificación guardada correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la planificación.']);
}
