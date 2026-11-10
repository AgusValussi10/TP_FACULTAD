<?php

/** GET admin ?factura_id= → factura en PDF (la que se adjunta en los mails de fin de mes, RFGm07). */

require_once __DIR__ . '/../lib/factura_pdf.php';

ini_set('display_errors', '0'); // cualquier aviso antes del PDF lo corrompería
errores_como_json();
requerir_rol('admin');
require_once __DIR__ . '/../database/db_config.php';

$datos = datos_factura_pdf($conn, (int) ($_GET['factura_id'] ?? 0));
$conn->close();
if (!$datos) {
    responder_json(404, false, null, 'La factura no existe.');
    exit;
}
header('Content-Type: application/pdf');
header('Cache-Control: private, no-store');
render_factura($datos)->Output('I', $datos['numero'] . '.pdf');
