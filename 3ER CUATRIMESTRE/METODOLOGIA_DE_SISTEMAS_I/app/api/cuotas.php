<?php

/**
 * GET ?alumno_id= → cuotas del alumno con su estado (impaga / próxima a vencer /
 * pendiente / pagada), vencimiento, importe, recargo y total (RFGm03).
 * Reutiliza cuotas_de_alumno() de RFG19: la web y la app muestran lo mismo.
 */

require_once __DIR__ . '/_base.php';

requerir_metodo('GET');
$sesion = api_sesion();
require_once __DIR__ . '/../../database/db_config.php';
$alumno_id = api_alumno_permitido($conn, $sesion, $_GET['alumno_id'] ?? 0);

$datos = cuotas_de_alumno($conn, $alumno_id);
$datos['resumen']['proximo_vencimiento_ar'] = $datos['resumen']['proximo_vencimiento']
    ? date('d/m/Y', strtotime($datos['resumen']['proximo_vencimiento'])) : null;
$conn->close();

responder_json(200, true, $datos);
