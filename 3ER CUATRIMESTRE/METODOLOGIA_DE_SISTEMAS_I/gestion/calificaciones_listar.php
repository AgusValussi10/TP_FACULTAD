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
    "SELECT u.id AS alumno_id, u.nombre, c.nota, c.fecha_evaluacion
     FROM alumno_curso ac
     JOIN usuarios u ON u.id = ac.alumno_id
     LEFT JOIN calificaciones c ON c.alumno_id = u.id AND c.materia_id = ? AND c.evaluacion = ?
     WHERE ac.curso_id = ?
     ORDER BY u.nombre"
);
$stmt->bind_param('isi', $materia_id, $evaluacion, $materia['curso_id']);
$stmt->execute();
$res = $stmt->get_result();

// Recuperatorios ya fijados en la materia, por alumno y período.
$recups = [];
$stmtR = $conn->prepare("SELECT alumno_id, periodo, fecha FROM recuperatorios WHERE materia_id = ?");
$stmtR->bind_param('i', $materia_id);
$stmtR->execute();
$resR = $stmtR->get_result();
while ($r = $resR->fetch_assoc()) {
    $recups[(int) $r['alumno_id']][$r['periodo']] = $r['fecha'];
}
$stmtR->close();

$alumnos = [];
while ($row = $res->fetch_assoc()) {
    $row['fecha_recuperatorio'] = null;
    if ($row['nota'] !== null && (int) $row['nota'] < NOTA_APROBACION && $row['fecha_evaluacion']) {
        $periodo = periodo_desde_fecha($row['fecha_evaluacion']);
        $row['fecha_recuperatorio'] = $recups[(int) $row['alumno_id']][$periodo] ?? null;
    }
    unset($row['fecha_evaluacion']);
    $alumnos[] = $row;
}

echo json_encode(['success' => true, 'materia' => $materia, 'alumnos' => $alumnos]);

$stmt->close();
$conn->close();
