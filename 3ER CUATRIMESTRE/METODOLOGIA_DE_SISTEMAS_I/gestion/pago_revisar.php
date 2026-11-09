<?php

/**
 * POST admin (pago_id, accion = aprobar | rechazar, motivo) → revisa un pago por
 * transferencia. Aprobar marca ítems, factura y cuota; rechazar libera los ítems.
 * En los dos casos se notifica al padre. Parte 3 (RFGm06).
 */

require_once __DIR__ . '/../lib/facturacion.php';

errores_como_json();
requerir_rol('admin');
requerir_metodo('POST');
require_once __DIR__ . '/../database/db_config.php';

$pago_id  = (int) ($_POST['pago_id'] ?? 0);
$accion   = $_POST['accion'] ?? '';
$admin_id = (int) $_SESSION['usuario_id'];

try {
    if ($accion === 'aprobar') {
        aprobar_pago($conn, $pago_id, $admin_id);
        responder_json(200, true, ['pago_id' => $pago_id], 'Pago ' . numero_pago($pago_id) . ' aprobado. Se avisó a la familia.');
    } elseif ($accion === 'rechazar') {
        rechazar_pago($conn, $pago_id, $admin_id, (string) ($_POST['motivo'] ?? ''));
        responder_json(200, true, ['pago_id' => $pago_id], 'Pago ' . numero_pago($pago_id) . ' rechazado. Se avisó a la familia.');
    } else {
        responder_json(422, false, null, 'Acción inválida.');
    }
} catch (ReglaNegocioException $e) {
    responder_json($e->getCode(), false, null, $e->getMessage());
}
$conn->close();
