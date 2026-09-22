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

$alumno_id = (int) ($_POST['alumno_id'] ?? 0);
$concepto  = trim($_POST['concepto'] ?? '');
$mes       = (int) ($_POST['mes'] ?? 0);
$anio      = (int) ($_POST['anio'] ?? 0);
$importe   = (float) ($_POST['importe'] ?? 0);

$conceptos_validos = ['matricula', 'cuota'];

if ($alumno_id <= 0 || !in_array($concepto, $conceptos_validos, true) || $mes < 1 || $mes > 12 || $anio < 2000 || $importe <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$fecha_vencimiento = fecha_vencimiento_cuota($mes, $anio);

$stmt = $conn->prepare(
    "INSERT INTO cuotas (alumno_id, concepto, mes, anio, importe, fecha_vencimiento)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('isiids', $alumno_id, $concepto, $mes, $anio, $importe, $fecha_vencimiento);

if (!$stmt->execute()) {
    $stmt->close();
    if ($conn->errno === 1062) {
        echo json_encode(['success' => false, 'message' => 'Ya existe una cuota para ese concepto/período.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al generar la cuota.']);
    }
    $conn->close();
    exit;
}
$stmt->close();

actualizar_condicion_regularizacion($conn, $alumno_id);

$conn->close();

echo json_encode(['success' => true]);
