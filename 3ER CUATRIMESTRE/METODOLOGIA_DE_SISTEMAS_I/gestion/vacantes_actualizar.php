<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

$curso_id   = (int) ($_POST['curso_id'] ?? 0);
$capacidad  = (int) ($_POST['capacidad'] ?? 0);

if ($curso_id <= 0 || $capacidad < 1 || $capacidad > 100) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos. Capacidad entre 1 y 100.']);
    exit;
}

// Verificar que la nueva capacidad no sea menor a los inscriptos actuales
$stmt = $conn->prepare(
    "SELECT COUNT(ac.alumno_id) AS inscriptos FROM alumno_curso ac WHERE ac.curso_id = ?"
);
$stmt->bind_param('i', $curso_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ((int) $row['inscriptos'] > $capacidad) {
    echo json_encode([
        'success' => false,
        'message' => "No se puede reducir la capacidad a {$capacidad}: hay {$row['inscriptos']} alumnos inscriptos.",
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE cursos SET capacidad = ? WHERE id = ?");
$stmt->bind_param('ii', $capacidad, $curso_id);
$ok = $stmt->execute();
$stmt->close();
$conn->close();

if ($ok && $conn->affected_rows !== 0 || $ok) {
    echo json_encode(['success' => true, 'message' => 'Capacidad actualizada.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Curso no encontrado.']);
}
