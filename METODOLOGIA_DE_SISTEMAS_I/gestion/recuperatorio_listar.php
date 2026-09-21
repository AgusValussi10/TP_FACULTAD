<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('docente');

require_once __DIR__ . '/../database/db_config.php';

$materia_id = (int) ($_GET['materia_id'] ?? 0);
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

// Promedio de cada alumno del curso en esta materia
$stmt = $conn->prepare(
    "SELECT u.id AS alumno_id, u.nombre,
            ROUND(AVG(c.nota), 2) AS promedio,
            COUNT(c.id) AS evaluaciones,
            r.id AS recup_id, r.periodo, DATE_FORMAT(r.fecha, '%d/%m/%Y') AS recup_fecha,
            r.turno, r.estado AS recup_estado
     FROM alumno_curso ac
     JOIN usuarios u ON u.id = ac.alumno_id
     LEFT JOIN calificaciones c ON c.alumno_id = ac.alumno_id AND c.materia_id = ?
     LEFT JOIN recuperatorios r ON r.alumno_id = ac.alumno_id AND r.materia_id = ?
     WHERE ac.curso_id = ?
     GROUP BY u.id, u.nombre, r.id, r.periodo, r.fecha, r.turno, r.estado
     ORDER BY u.nombre"
);
$stmt->bind_param('iii', $materia_id, $materia_id, $materia['curso_id']);
$stmt->execute();
$alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Marcar quién necesita recuperatorio (promedio < NOTA_APROBACION o sin notas)
foreach ($alumnos as &$a) {
    $a['promedio']      = $a['promedio'] !== null ? (float) $a['promedio'] : null;
    $a['evaluaciones']  = (int) $a['evaluaciones'];
    $a['necesita_recup']= $a['promedio'] !== null && $a['promedio'] < NOTA_APROBACION;
}
unset($a);

echo json_encode([
    'success' => true,
    'materia' => $materia['nombre'],
    'alumnos' => $alumnos,
    'nota_aprobacion' => NOTA_APROBACION,
]);
