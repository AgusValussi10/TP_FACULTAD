<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('padre');

require_once __DIR__ . '/../database/db_config.php';

$padre_id = (int) $_SESSION['usuario_id'];
$alumnos  = alumnos_de_padre($conn, $padre_id);

if (empty($alumnos)) {
    echo json_encode(['success' => false, 'message' => 'No hay alumnos vinculados a tu cuenta.']);
    $conn->close();
    exit;
}

$alumno_id = (int) ($_GET['alumno_id'] ?? 0);
if ($alumno_id <= 0 || !alumno_pertenece_a_padre($conn, $alumno_id, $padre_id)) {
    $alumno_id = (int) $alumnos[0]['id'];
}

$stmt = $conn->prepare(
    "SELECT d.id, d.nombre, d.horario, d.cupo_maximo,
            COUNT(insc.alumno_id) AS inscriptos,
            (d.cupo_maximo - COUNT(insc.alumno_id)) AS disponibles,
            EXISTS(
                SELECT 1 FROM inscripciones_deportivas id2
                WHERE id2.deporte_id = d.id AND id2.alumno_id = ?
            ) AS ya_inscripto
     FROM deportes d
     LEFT JOIN inscripciones_deportivas insc ON insc.deporte_id = d.id
     GROUP BY d.id, d.nombre, d.horario, d.cupo_maximo
     ORDER BY d.nombre"
);
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$res = $stmt->get_result();

$deportes = [];
while ($row = $res->fetch_assoc()) {
    $row['cupo_maximo']   = (int) $row['cupo_maximo'];
    $row['inscriptos']    = (int) $row['inscriptos'];
    $row['disponibles']   = (int) $row['disponibles'];
    $row['ya_inscripto']  = (bool) $row['ya_inscripto'];
    $deportes[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode([
    'success'   => true,
    'alumnos'   => $alumnos,
    'alumno_id' => $alumno_id,
    'deportes'  => $deportes,
]);
