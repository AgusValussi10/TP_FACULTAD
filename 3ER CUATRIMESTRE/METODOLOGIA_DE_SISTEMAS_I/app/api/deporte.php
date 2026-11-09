<?php

/**
 * POST (alumno_id, deporte_id, accion = inscribir | baja) → inscribe o da de baja a un hijo en
 * un deporte (RFGm09): hasta 2 a la vez, con cupo y sin choque de horario. Solo el padre.
 */

require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../lib/inscripciones.php';

requerir_metodo('POST');
$sesion = api_sesion();
api_solo_padre($sesion);
require_once __DIR__ . '/../../database/db_config.php';
$alumno_id  = api_alumno_permitido($conn, $sesion, $_POST['alumno_id'] ?? 0);
$deporte_id = (int) ($_POST['deporte_id'] ?? 0);
$accion     = (string) ($_POST['accion'] ?? '');

try {
    if ($accion === 'inscribir') {
        $nombre = inscribir_deporte($conn, $alumno_id, $deporte_id);
        responder_json(200, true, null, "Listo: quedó inscripto en $nombre. Se cobra desde el mes que viene.");
    } elseif ($accion === 'baja') {
        baja_deporte($conn, $alumno_id, $deporte_id);
        responder_json(200, true, null, 'Listo: lo diste de baja. Deja de cobrarse desde el mes que viene.');
    } else {
        responder_json(422, false, null, 'Acción inválida.');
    }
} catch (ReglaNegocioException $e) {
    responder_json($e->getCode(), false, null, $e->getMessage());
}
$conn->close();
