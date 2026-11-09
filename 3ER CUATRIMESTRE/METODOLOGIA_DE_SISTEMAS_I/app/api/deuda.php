<?php

/**
 * GET ?alumno_id=&desde=AAAA-MM&hasta=AAAA-MM → historial de deuda discriminado por
 * concepto y mes: facturado, pagado, en proceso de pago y adeudado (RFGm04).
 */

require_once __DIR__ . '/_base.php';

requerir_metodo('GET');
$sesion = api_sesion();
require_once __DIR__ . '/../../database/db_config.php';
$alumno_id = api_alumno_permitido($conn, $sesion, $_GET['alumno_id'] ?? 0);

try {
    responder_json(200, true, deuda_por_concepto($conn, $alumno_id, $_GET['desde'] ?? null, $_GET['hasta'] ?? null));
} catch (ReglaNegocioException $e) {
    responder_json($e->getCode(), false, null, $e->getMessage());
}
$conn->close();
