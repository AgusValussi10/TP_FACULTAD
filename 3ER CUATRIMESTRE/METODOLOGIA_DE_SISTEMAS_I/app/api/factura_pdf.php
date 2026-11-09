<?php

/**
 * GET ?factura_id= → factura en PDF (RFGm04). El padre, solo las de sus hijos; el
 * alumno, solo las suyas. 403 si no corresponde, exista o no (no se revelan ids).
 */

require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../lib/factura_pdf.php';

ini_set('display_errors', '0'); // cualquier aviso antes del PDF lo corrompería
requerir_metodo('GET');
$sesion = api_sesion();
require_once __DIR__ . '/../../database/db_config.php';

$datos = datos_factura_pdf($conn, (int) ($_GET['factura_id'] ?? 0));
$propio = $datos && ($sesion['rol'] === 'alumno'
    ? $datos['alumno_id'] === $sesion['id']
    : alumno_pertenece_a_padre($conn, $datos['alumno_id'], $sesion['id']));
$conn->close();
if (!$propio) {
    responder_json(403, false, null, 'La factura no corresponde a tu cuenta.');
    exit;
}

$pdf = render_factura($datos);
header('Content-Type: application/pdf');
header('Cache-Control: private, no-store');
$pdf->Output('I', $datos['numero'] . '.pdf');
