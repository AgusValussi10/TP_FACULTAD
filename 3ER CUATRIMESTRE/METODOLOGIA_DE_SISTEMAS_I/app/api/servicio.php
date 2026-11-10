<?php

/**
 * POST (alumno_id, servicio = comedor | transporte, mes, anio, accion = reservar | cancelar,
 * recorrido_id para transporte) → reserva o cancela comedor (RFGm10) o transporte por
 * recorrido (RFGm11) para un mes, dentro del plazo del día 25 del mes anterior. Solo el padre.
 */

require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../lib/inscripciones.php';

requerir_metodo('POST');
$sesion = api_sesion();
api_solo_padre($sesion);
require_once __DIR__ . '/../../database/db_config.php';
$alumno_id = api_alumno_permitido($conn, $sesion, $_POST['alumno_id'] ?? 0);
$servicio  = (string) ($_POST['servicio'] ?? '');
$mes       = (int) ($_POST['mes'] ?? 0);
$anio      = (int) ($_POST['anio'] ?? 0);
$accion    = (string) ($_POST['accion'] ?? '');

try {
    if ($accion === 'reservar') {
        $recorrido = isset($_POST['recorrido_id']) ? (int) $_POST['recorrido_id'] : null;
        reservar_servicio($conn, $alumno_id, $servicio, $mes, $anio, $recorrido);
        responder_json(200, true, null, 'Listo: reserva confirmada para ' . periodo_texto($mes, $anio) . '.');
    } elseif ($accion === 'cancelar') {
        cancelar_servicio($conn, $alumno_id, $servicio, $mes, $anio);
        responder_json(200, true, null, 'Listo: cancelaste la reserva de ' . periodo_texto($mes, $anio) . '.');
    } else {
        responder_json(422, false, null, 'Acción inválida.');
    }
} catch (ReglaNegocioException $e) {
    responder_json($e->getCode(), false, null, $e->getMessage());
}
$conn->close();
