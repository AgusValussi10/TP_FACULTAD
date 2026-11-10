<?php

/** GET ?pago_id= → comprobante de pago en PDF: número, ítems, total y datos de transferencia (RFGm05). Solo el padre. */

require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../lib/factura_pdf.php';

ini_set('display_errors', '0'); // cualquier aviso antes del PDF lo corrompería
requerir_metodo('GET');
$sesion = api_sesion();
api_solo_padre($sesion);
require_once __DIR__ . '/../../database/db_config.php';

try {
    $pago = pago_de_padre($conn, (int) ($_GET['pago_id'] ?? 0), $sesion['id']);
} catch (ReglaNegocioException $e) {
    responder_json($e->getCode(), false, null, $e->getMessage());
    $conn->close();
    exit;
}
$conn->close();

$pdf = render_comprobante_pago($pago + ['institucion' => pdf_institucion(), 'logo' => __DIR__ . '/../../assets/logo.png']);
header('Content-Type: application/pdf');
header('Cache-Control: private, no-store');
$pdf->Output('I', $pago['numero'] . '.pdf');
