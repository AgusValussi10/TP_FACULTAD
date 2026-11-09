<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../lib/facturacion.php';

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
// Parte 3: sin efectivo para pagos nuevos (los registros viejos se siguen mostrando).
if (!in_array($metodo, METODOS_PAGO_HABILITADOS, true)) {
    echo json_encode(['success' => false, 'message' => 'Elegí la forma de pago.']);
    exit;
}
if (mb_strlen($referencia) > 60) {
    echo json_encode(['success' => false, 'message' => 'La referencia no puede superar los 60 caracteres.']);
    exit;
}
$referenciaVal = $referencia !== '' ? $referencia : null;
$registrado_por = (int) $_SESSION['usuario_id'];

// marcar_cuota_pagada() también marca pagados sus ítems de factura (Parte 3),
// así la web y la app muestran lo mismo.
try {
    $recargo = marcar_cuota_pagada($conn, $cuota_id, $fecha_pago, $metodo, $referenciaVal, $registrado_por, true);
} catch (ReglaNegocioException $e) {
    $conn->close();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

$conn->close();

echo json_encode(['success' => true, 'recargo_aplicado' => $recargo, 'cuota_id' => $cuota_id]);
