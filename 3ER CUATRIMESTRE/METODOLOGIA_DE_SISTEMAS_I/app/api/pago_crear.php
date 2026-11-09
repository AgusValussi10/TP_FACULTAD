<?php

/**
 * POST (factura_id, items[]) → el padre elige ítems pendientes de una factura de su hijo y
 * el sistema emite el comprobante de pago con los datos de transferencia (RFGm05).
 * Si incluye una cuota vencida, se le suma su recargo (RN11). Solo el padre.
 */

require_once __DIR__ . '/_base.php';

requerir_metodo('POST');
$sesion = api_sesion();
api_solo_padre($sesion);
require_once __DIR__ . '/../../database/db_config.php';

$factura_id = (int) ($_POST['factura_id'] ?? 0);
$items = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];

api_responder(function () use ($conn, $factura_id, $items, $sesion) {
    $pago_id = crear_pago($conn, $factura_id, $sesion['id'], $items);
    return pago_de_padre($conn, $pago_id, $sesion['id']);
}, 'Listo: generamos tu comprobante de pago. Transferí el total y cargá el comprobante.');
$conn->close();
