<?php

/** GET ?pago_id= → detalle de un pago del padre: estado, ítems, comprobantes y datos de transferencia (RFGm05/06). */

require_once __DIR__ . '/_base.php';

requerir_metodo('GET');
$sesion = api_sesion();
api_solo_padre($sesion);
require_once __DIR__ . '/../../database/db_config.php';

$pago_id = (int) ($_GET['pago_id'] ?? 0);
api_responder(function () use ($conn, $pago_id, $sesion) {
    $pago = pago_de_padre($conn, $pago_id, $sesion['id']);
    $pago['max_kb'] = (int) floor(comprobante_max_bytes($conn) / 1024);
    return $pago;
});
$conn->close();
