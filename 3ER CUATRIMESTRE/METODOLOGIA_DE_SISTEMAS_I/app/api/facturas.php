<?php

/**
 * GET ?alumno_id=&desde=AAAA-MM&hasta=AAAA-MM → facturas del alumno en ese rango de
 * períodos, con ítems, pagos y comprobantes (RFGm04). Sin desde/hasta: todas.
 */

require_once __DIR__ . '/_base.php';

requerir_metodo('GET');
$sesion = api_sesion();
require_once __DIR__ . '/../../database/db_config.php';
$alumno_id = api_alumno_permitido($conn, $sesion, $_GET['alumno_id'] ?? 0);

try {
    $facturas = facturas_de_alumno($conn, $alumno_id, $_GET['desde'] ?? null, $_GET['hasta'] ?? null);
    responder_json(200, true, $facturas);
} catch (ReglaNegocioException $e) {
    responder_json($e->getCode(), false, null, $e->getMessage());
}
$conn->close();
