<?php

/**
 * POST multipart (pago_id, monto, fecha, archivo) → carga un comprobante de transferencia
 * para un pago (RFGm06). Un pago (y por lo tanto una factura) admite varios comprobantes.
 * El tipo real del archivo se valida en el servidor (finfo) y el nombre se sanitiza.
 * El pago pasa a "en revisión" hasta que el admin lo aprueba o rechaza. Solo el padre.
 */

require_once __DIR__ . '/_base.php';

requerir_metodo('POST');
$sesion = api_sesion();
api_solo_padre($sesion);
require_once __DIR__ . '/../../database/db_config.php';

$pago_id = (int) ($_POST['pago_id'] ?? 0);
$monto   = round((float) str_replace(',', '.', (string) ($_POST['monto'] ?? '0')), 2);
$fecha   = (string) ($_POST['fecha'] ?? '');

api_responder(function () use ($conn, $pago_id, $monto, $fecha, $sesion) {
    pago_de_padre($conn, $pago_id, $sesion['id']); // 403 si no es de sus hijos
    if ($monto <= 0 || $monto > 9999999) {
        throw new ReglaNegocioException('Ingresá el monto que transferiste.', 422);
    }
    $dia = DateTime::createFromFormat('!Y-m-d', $fecha);
    if (!$dia || $dia->format('Y-m-d') !== $fecha || $fecha > date('Y-m-d') || $fecha < date('Y-m-d', strtotime('-1 year'))) {
        throw new ReglaNegocioException('Ingresá la fecha de la transferencia (no puede ser futura).', 422);
    }
    [$mime, $nombre, $contenido] = validar_comprobante($_FILES['archivo'] ?? null, comprobante_max_bytes($conn));
    registrar_comprobante($conn, $pago_id, $sesion['id'], $nombre, $mime, $contenido, $monto, $fecha);
    return pago_de_padre($conn, $pago_id, $sesion['id']);
}, 'Recibimos tu comprobante. La escuela lo va a revisar y te avisamos.');
$conn->close();
