<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('docente');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

$materia_id = (int) ($_POST['materia_id'] ?? 0);
$fecha      = trim($_POST['fecha'] ?? '');
$estados    = json_decode($_POST['estados'] ?? '', true);

if ($materia_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !is_array($estados) || empty($estados)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

if ($fecha > date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'No se puede cargar asistencia con fecha futura.']);
    exit;
}

$docente_id = (int) $_SESSION['usuario_id'];
$materia = materia_valida_para_docente($conn, $materia_id, $docente_id);
if (!$materia) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'La materia no pertenece al docente.']);
    exit;
}

// Solo se aceptan alumnos que efectivamente cursan la materia.
$alumnos_curso = [];
$stmt = $conn->prepare("SELECT alumno_id FROM alumno_curso WHERE curso_id = ?");
$stmt->bind_param('i', $materia['curso_id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $alumnos_curso[(int) $row['alumno_id']] = true;
}
$stmt->close();

$estados_validos = ['presente', 'ausente', 'tarde'];

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO asistencias (alumno_id, materia_id, fecha, estado)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE estado = VALUES(estado)"
    );

    $guardados = 0;
    foreach ($estados as $alumno_id => $estado) {
        $alumno_id = (int) $alumno_id;
        if (!isset($alumnos_curso[$alumno_id]) || !in_array($estado, $estados_validos, true)) {
            continue;
        }
        $stmt->bind_param('iiss', $alumno_id, $materia_id, $fecha, $estado);
        $stmt->execute();
        $guardados++;
    }
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'guardados' => $guardados]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al guardar la asistencia.']);
}

$conn->close();
