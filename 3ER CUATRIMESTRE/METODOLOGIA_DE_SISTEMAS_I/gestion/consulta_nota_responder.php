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

$docente_id = (int) $_SESSION['usuario_id'];
$id         = (int) ($_POST['id'] ?? 0);
$respuesta  = trim($_POST['respuesta'] ?? '');

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Consulta inválida.']);
    exit;
}
$largo = mb_strlen($respuesta);
if ($largo < 2 || $largo > 1000) {
    echo json_encode(['success' => false, 'message' => 'La respuesta debe tener entre 2 y 1000 caracteres.']);
    exit;
}

// La consulta debe ser sobre una nota de una materia de este docente.
$stmt = $conn->prepare(
    "SELECT cn.padre_id, cn.alumno_id, m.nombre AS materia, c.evaluacion
     FROM consultas_notas cn
     JOIN calificaciones c ON c.id = cn.calificacion_id
     JOIN materias m       ON m.id = c.materia_id
     WHERE cn.id = ? AND m.docente_id = ?"
);
$stmt->bind_param('ii', $id, $docente_id);
$stmt->execute();
$consulta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$consulta) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'La consulta no corresponde a tus materias.']);
    $conn->close();
    exit;
}

$stmt = $conn->prepare(
    "UPDATE consultas_notas SET respuesta = ?, estado = 'respondida', respondida_at = NOW() WHERE id = ?"
);
$stmt->bind_param('si', $respuesta, $id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    // Aviso al padre en sus notificaciones (RFG10).
    crear_notificacion(
        $conn,
        (int) $consulta['padre_id'],
        (int) $consulta['alumno_id'],
        'consulta_nota',
        "El docente respondió tu consulta sobre {$consulta['materia']} ({$consulta['evaluacion']})."
    );
}
$conn->close();

echo json_encode($ok
    ? ['success' => true, 'message' => 'Respuesta enviada.']
    : ['success' => false, 'message' => 'No se pudo guardar la respuesta.']);
