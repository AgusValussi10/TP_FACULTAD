<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json; charset=utf-8');
requerir_rol(['admin']);
require_once __DIR__ . '/../database/db_config.php';

$res = $conn->query(
    "SELECT u.id, u.nombre, u.usuario, u.activo,
            c.nombre AS curso, ac.curso_id
     FROM usuarios u
     LEFT JOIN alumno_curso ac ON ac.alumno_id = u.id
     LEFT JOIN cursos c ON c.id = ac.curso_id
     WHERE u.rol = 'alumno'
     ORDER BY u.activo DESC, u.nombre"
);
$alumnos = [];
if ($res) while ($r = $res->fetch_assoc()) $alumnos[] = $r;
$conn->close();
echo json_encode(['success' => true, 'alumnos' => $alumnos]);
