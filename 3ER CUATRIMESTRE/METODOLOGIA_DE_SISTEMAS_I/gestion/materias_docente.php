<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('docente');

require_once __DIR__ . '/../database/db_config.php';

$docente_id = (int) $_SESSION['usuario_id'];

$stmt = $conn->prepare(
    "SELECT m.id, m.nombre, c.nombre AS curso_nombre
     FROM materias m
     JOIN cursos c ON c.id = m.curso_id
     WHERE m.docente_id = ?
     ORDER BY c.nombre, m.nombre"
);
$stmt->bind_param('i', $docente_id);
$stmt->execute();
$res = $stmt->get_result();

$materias = [];
while ($row = $res->fetch_assoc()) {
    $materias[] = $row;
}

echo json_encode(['success' => true, 'materias' => $materias]);

$stmt->close();
$conn->close();
