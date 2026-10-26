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

$cuota_id   = (int) ($_POST['cuota_id'] ?? 0);
$fecha_pago = trim($_POST['fecha_pago'] ?? '') ?: date('Y-m-d');
$metodo     = trim($_POST['metodo_pago'] ?? '');
$referencia = trim($_POST['referencia_pago'] ?? '');

if ($cuota_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_pago)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}
if (!array_key_exists($metodo, METODOS_PAGO)) {
    echo json_encode(['success' => false, 'message' => 'Elegí la forma de pago.']);
    exit;
}
if (mb_strlen($referencia) > 60) {
    echo json_encode(['success' => false, 'message' => 'La referencia no puede superar los 60 caracteres.']);
    exit;
}
$referenciaVal = $referencia !== '' ? $referencia : null;
$registrado_por = (int) $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT alumno_id, importe, fecha_vencimiento, estado FROM cuotas WHERE id = ?");
$stmt->bind_param('i', $cuota_id);
$stmt->execute();
$cuota = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cuota) {
    echo json_encode(['success' => false, 'message' => 'La cuota no existe.']);
    $conn->close();
    exit;
}

if ($cuota['estado'] !== 'pendiente') {
    echo json_encode(['success' => false, 'message' => 'Esa cuota ya fue registrada como pagada.']);
    $conn->close();
    exit;
}

$recargo = calcular_recargo((float) $cuota['importe'], $cuota['fecha_vencimiento'], $fecha_pago);

$upd = $conn->prepare(
    "UPDATE cuotas
     SET estado = 'pagada', recargo = ?, fecha_pago = ?, metodo_pago = ?, referencia_pago = ?, registrado_por = ?
     WHERE id = ? AND estado = 'pendiente'"
);
$upd->bind_param('dsssii', $recargo, $fecha_pago, $metodo, $referenciaVal, $registrado_por, $cuota_id);
$upd->execute();
$upd->close();

actualizar_condicion_regularizacion($conn, (int) $cuota['alumno_id']);

$conn->close();

echo json_encode(['success' => true, 'recargo_aplicado' => $recargo, 'cuota_id' => $cuota_id]);
