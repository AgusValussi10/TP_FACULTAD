<?php

/** POST admin (mes, anio) → genera la facturación del mes. Idempotente. Parte 3. */

require_once __DIR__ . '/../lib/facturacion.php';

errores_como_json();
requerir_rol('admin');
requerir_metodo('POST');
require_once __DIR__ . '/../database/db_config.php';

$mes  = (int) ($_POST['mes'] ?? 0);
$anio = (int) ($_POST['anio'] ?? 0);

try {
    $r = generar_facturacion($conn, $mes, $anio, (int) $_SESSION['usuario_id']);
    $msg = 'Facturación de ' . periodo_texto($mes, $anio) . ": {$r['creadas']} creadas, {$r['existentes']} ya existían"
        . ($r['sin_items'] ? ", {$r['sin_items']} alumnos sin conceptos para facturar" : '') . '.';
    responder_json(200, true, $r, $msg);
} catch (ReglaNegocioException $e) {
    responder_json($e->getCode(), false, null, $e->getMessage());
}
$conn->close();
