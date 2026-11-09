<?php

/**
 * GET admin (?reporte=, desde=AAAA-MM, hasta=AAAA-MM, formato=json|csv) → uno de los
 * reportes de pagos (RFGm12). Con formato=csv descarga el archivo.
 */

require_once __DIR__ . '/../lib/reportes.php';

errores_como_json();
requerir_rol('admin');
requerir_metodo('GET');
require_once __DIR__ . '/../database/db_config.php';

$reporte = (string) ($_GET['reporte'] ?? '');
$desde   = (string) ($_GET['desde'] ?? '');
$hasta   = (string) ($_GET['hasta'] ?? '');

try {
    $datos = reporte_pagos($conn, $reporte, $desde, $hasta);
} catch (ReglaNegocioException $e) {
    responder_json($e->getCode(), false, null, $e->getMessage());
    $conn->close();
    exit;
}
$conn->close();

if (($_GET['formato'] ?? 'json') === 'csv') {
    enviar_csv($datos, "reporte-$reporte-$desde-a-$hasta.csv");
    exit;
}
responder_json(200, true, $datos);
