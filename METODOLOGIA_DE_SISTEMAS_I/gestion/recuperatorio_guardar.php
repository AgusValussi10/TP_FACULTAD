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

$alumno_id  = (int) ($_POST['alumno_id'] ?? 0);
$materia_id = (int) ($_POST['materia_id'] ?? 0);
$periodo    = trim($_POST['periodo'] ?? '');
$fecha      = trim($_POST['fecha'] ?? '');
$turno      = trim($_POST['turno'] ?? '');
$estado     = trim($_POST['estado'] ?? 'pendiente');

$periodos_validos = ['1er Trimestre', '2do Trimestre', '3er Trimestre', 'Anual'];
$turnos_validos   = ['mañana', 'tarde'];
$estados_validos  = ['pendiente', 'aprobado', 'desaprobado'];

if ($alumno_id <= 0 || $materia_id <= 0 || !in_array($periodo, $periodos_validos, true)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}
if ($fecha !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['success' => false, 'message' => 'Fecha inválida.']);
    exit;
}
if ($turno !== '' && !in_array($turno, $turnos_validos, true)) {
    echo json_encode(['success' => false, 'message' => 'Turno inválido.']);
    exit;
}
if (!in_array($estado, $estados_validos, true)) {
    echo json_encode(['success' => false, 'message' => 'Estado inválido.']);
    exit;
}

$docente_id = (int) $_SESSION['usuario_id'];
$materia = materia_valida_para_docente($conn, $materia_id, $docente_id);
if (!$materia) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'La materia no pertenece al docente.']);
    exit;
}

// Verificar que el alumno pertenezca al curso de la materia
$stmt = $conn->prepare("SELECT 1 FROM alumno_curso WHERE alumno_id = ? AND curso_id = ?");
$stmt->bind_param('ii', $alumno_id, $materia['curso_id']);
$stmt->execute();
$ok = $stmt->get_result()->num_rows > 0;
$stmt->close();
if (!$ok) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'El alumno no pertenece al curso de la materia.']);
    exit;
}

$fechaVal = $fecha !== '' ? $fecha : null;
$turnoVal = $turno !== '' ? $turno : null;

$stmt = $conn->prepare(
    "INSERT INTO recuperatorios (alumno_id, materia_id, periodo, fecha, turno, estado)
     VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE fecha = VALUES(fecha), turno = VALUES(turno), estado = VALUES(estado)"
);
$stmt->bind_param('iissss', $alumno_id, $materia_id, $periodo, $fechaVal, $turnoVal, $estado);
$guardado = $stmt->execute();
$stmt->close();
$conn->close();

if ($guardado) {
    echo json_encode(['success' => true, 'message' => 'Recuperatorio guardado correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el recuperatorio.']);
}
