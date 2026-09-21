<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('docente');

require_once __DIR__ . '/../database/db_config.php';

$materia_id = (int) ($_GET['materia_id'] ?? 0);
$evaluacion = trim($_GET['evaluacion'] ?? '');

if ($materia_id <= 0 || $evaluacion === '') {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
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
    "SELECT u.id AS alumno_id, u.nombre, c.nota
     FROM alumno_curso ac
     JOIN usuarios u ON u.id = ac.alumno_id
     LEFT JOIN calificaciones c ON c.alumno_id = u.id AND c.materia_id = ? AND c.evaluacion = ?
     WHERE ac.curso_id = ?
     ORDER BY u.nombre"
);
$stmt->bind_param('isi', $materia_id, $evaluacion, $materia['curso_id']);
$stmt->execute();
$res = $stmt->get_result();

$alumnos = [];
while ($row = $res->fetch_assoc()) {
    $alumnos[] = $row;
}

echo json_encode(['success' => true, 'materia' => $materia, 'alumnos' => $alumnos]);

$stmt->close();
$conn->close();
