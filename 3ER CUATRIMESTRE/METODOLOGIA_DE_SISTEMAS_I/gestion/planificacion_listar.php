<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('docente');

require_once __DIR__ . '/../database/db_config.php';

$materia_id = (int) ($_GET['materia_id'] ?? 0);
$anio       = (int) ($_GET['anio'] ?? date('Y'));

if ($materia_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Materia inválida.']);
    exit;
}

$docente_id = (int) $_SESSION['usuario_id'];
$materia = materia_valida_para_docente($conn, $materia_id, $docente_id);
if (!$materia) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'La materia no pertenece al docente.']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT contenidos, observaciones, DATE_FORMAT(created_at, '%d/%m/%Y') AS fecha_carga
     FROM planificaciones WHERE materia_id = ? AND anio = ?"
);
$stmt->bind_param('ii', $materia_id, $anio);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'plan' => $plan]);
