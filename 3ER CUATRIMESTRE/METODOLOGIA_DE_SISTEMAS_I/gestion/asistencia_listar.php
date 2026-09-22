<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('docente');

require_once __DIR__ . '/../database/db_config.php';

$materia_id = (int) ($_GET['materia_id'] ?? 0);
$fecha      = trim($_GET['fecha'] ?? '');

if ($materia_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
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
    "SELECT u.id AS alumno_id, u.nombre, a.estado
     FROM alumno_curso ac
     JOIN usuarios u ON u.id = ac.alumno_id
     LEFT JOIN asistencias a ON a.alumno_id = u.id AND a.materia_id = ? AND a.fecha = ?
     WHERE ac.curso_id = ?
     ORDER BY u.nombre"
);
$stmt->bind_param('isi', $materia_id, $fecha, $materia['curso_id']);
$stmt->execute();
$res = $stmt->get_result();

$alumnos = [];
$ya_cargada = false;
while ($row = $res->fetch_assoc()) {
    if ($row['estado'] !== null) {
        $ya_cargada = true;
    }
    $alumnos[] = $row;
}

echo json_encode(['success' => true, 'materia' => $materia, 'alumnos' => $alumnos, 'ya_cargada' => $ya_cargada]);

$stmt->close();
$conn->close();
