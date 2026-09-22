<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('admin');

require_once __DIR__ . '/../database/db_config.php';

$materias = [];
$res = $conn->query(
    "SELECT m.id, m.nombre, m.curso_id, c.nombre AS curso_nombre,
            m.docente_id, u.nombre AS docente_nombre
     FROM materias m
     JOIN cursos c ON c.id = m.curso_id
     LEFT JOIN usuarios u ON u.id = m.docente_id
     ORDER BY c.nombre, m.nombre"
);
while ($row = $res->fetch_assoc()) {
    $materias[] = $row;
}

$cursos = [];
$res = $conn->query("SELECT id, nombre, nivel_educativo FROM cursos ORDER BY nivel_educativo, nombre");
while ($row = $res->fetch_assoc()) {
    $cursos[] = $row;
}

$docentes = [];
$res = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'docente' AND activo = 1 ORDER BY nombre");
while ($row = $res->fetch_assoc()) {
    $docentes[] = $row;
}

$conn->close();

echo json_encode(['success' => true, 'materias' => $materias, 'cursos' => $cursos, 'docentes' => $docentes]);
