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

$materia_id = (int) ($_POST['materia_id'] ?? 0);
$docente_id = (int) ($_POST['docente_id'] ?? 0);

if ($materia_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$check = $conn->prepare("SELECT id FROM materias WHERE id = ?");
$check->bind_param('i', $materia_id);
$check->execute();
$materiaExiste = $check->get_result()->num_rows > 0;
$check->close();
if (!$materiaExiste) {
    echo json_encode(['success' => false, 'message' => 'La materia no existe.']);
    $conn->close();
    exit;
}

if ($docente_id > 0) {
    $check = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'docente' AND activo = 1");
    $check->bind_param('i', $docente_id);
    $check->execute();
    $existe = $check->get_result()->num_rows > 0;
    $check->close();
    if (!$existe) {
        echo json_encode(['success' => false, 'message' => 'El docente seleccionado no es válido.']);
        $conn->close();
        exit;
    }
    $stmt = $conn->prepare("UPDATE materias SET docente_id = ? WHERE id = ?");
    $stmt->bind_param('ii', $docente_id, $materia_id);
} else {
    $stmt = $conn->prepare("UPDATE materias SET docente_id = NULL WHERE id = ?");
    $stmt->bind_param('i', $materia_id);
}

$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'message' => $docente_id > 0 ? 'Docente asignado.' : 'Materia sin docente asignado.']);
