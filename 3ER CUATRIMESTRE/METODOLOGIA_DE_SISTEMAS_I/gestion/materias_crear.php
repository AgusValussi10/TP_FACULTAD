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

$nombre     = trim($_POST['nombre'] ?? '');
$curso_id   = (int) ($_POST['curso_id'] ?? 0);
$docente_id = (int) ($_POST['docente_id'] ?? 0);

if ($nombre === '' || $curso_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Completá el nombre y el curso.']);
    exit;
}

$check = $conn->prepare("SELECT id FROM cursos WHERE id = ?");
$check->bind_param('i', $curso_id);
$check->execute();
$cursoExiste = $check->get_result()->num_rows > 0;
$check->close();
if (!$cursoExiste) {
    echo json_encode(['success' => false, 'message' => 'El curso seleccionado no existe.']);
    $conn->close();
    exit;
}

if ($docente_id > 0) {
    $check = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'docente' AND activo = 1");
    $check->bind_param('i', $docente_id);
    $check->execute();
    $docenteExiste = $check->get_result()->num_rows > 0;
    $check->close();
    if (!$docenteExiste) {
        echo json_encode(['success' => false, 'message' => 'El docente seleccionado no es válido.']);
        $conn->close();
        exit;
    }
} else {
    $docente_id = null;
}

$stmt = $conn->prepare("INSERT INTO materias (nombre, curso_id, docente_id) VALUES (?, ?, ?)");
$stmt->bind_param('sii', $nombre, $curso_id, $docente_id);

if (!$stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Error al crear la materia.']);
    $conn->close();
    exit;
}
$stmt->close();
$conn->close();

echo json_encode(['success' => true]);
