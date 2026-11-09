<?php

/** POST (pago_id) → el padre anula un pago que todavía no tiene comprobante: los ítems quedan pendientes otra vez. */

require_once __DIR__ . '/_base.php';

requerir_metodo('POST');
$sesion = api_sesion();
api_solo_padre($sesion);
require_once __DIR__ . '/../../database/db_config.php';

$pago_id = (int) ($_POST['pago_id'] ?? 0);
api_responder(function () use ($conn, $pago_id, $sesion) {
    anular_pago($conn, $pago_id, $sesion['id']);
    return pago_de_padre($conn, $pago_id, $sesion['id']);
}, 'Anulaste el pago: los ítems quedaron pendientes otra vez.');
$conn->close();
