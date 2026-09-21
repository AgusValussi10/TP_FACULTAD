<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol(['admin', 'docente']);

require_once __DIR__ . '/../database/db_config.php';

// Devuelve todos los cursos con su capacidad y alumnos actuales inscriptos.
$result = $conn->query(
    "SELECT c.id, c.nombre, c.nivel_educativo, c.capacidad,
            COUNT(ac.alumno_id) AS inscriptos,
            (c.capacidad - COUNT(ac.alumno_id)) AS vacantes_disponibles
     FROM cursos c
     LEFT JOIN alumno_curso ac ON ac.curso_id = c.id
     GROUP BY c.id, c.nombre, c.nivel_educativo, c.capacidad
     ORDER BY c.nivel_educativo, c.nombre"
);

$cursos = [];
while ($row = $result->fetch_assoc()) {
    $row['capacidad']           = (int) $row['capacidad'];
    $row['inscriptos']          = (int) $row['inscriptos'];
    $row['vacantes_disponibles']= (int) $row['vacantes_disponibles'];
    $row['tiene_vacante']       = $row['vacantes_disponibles'] > 0;
    $cursos[] = $row;
}
$conn->close();

echo json_encode(['success' => true, 'cursos' => $cursos]);
