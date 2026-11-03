<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('docente');
errores_como_json();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

$id = (int) ($_POST['id'] ?? 0);
$docente_id = (int) $_SESSION['usuario_id'];

// Solo se borra si la materia del recuperatorio es del docente en sesión.
$stmt = $conn->prepare(
    "DELETE r FROM recuperatorios r
     JOIN materias m ON m.id = r.materia_id
     WHERE r.id = ? AND m.docente_id = ?"
);
$stmt->bind_param('ii', $id, $docente_id);
$stmt->execute();
$borrados = $stmt->affected_rows;
$stmt->close();
$conn->close();

echo json_encode($borrados > 0
    ? ['success' => true, 'message' => 'Recuperatorio eliminado.']
    : ['success' => false, 'message' => 'No se encontró el recuperatorio o no es de tus materias.']);
