<?php

/**
 * POST admin (tipo, fecha, forzar) → "Ejecutar ahora (prueba)": corre la generación de
 * mails como si hoy fuera "fecha" (modo simulación). Con forzar=1 genera aunque esa fecha
 * no corresponda (para mostrarlo en el testing). RFGm07/RFGm08.
 */

require_once __DIR__ . '/../lib/recordatorios.php';

errores_como_json();
requerir_rol('admin');
requerir_metodo('POST');
require_once __DIR__ . '/../database/db_config.php';

try {
    $r = generar_recordatorios($conn, (string) ($_POST['tipo'] ?? ''), (string) ($_POST['fecha'] ?? ''), ($_POST['forzar'] ?? '') === '1');
    if (!$r['corresponde'] && ($_POST['forzar'] ?? '') !== '1') {
        $mensaje = 'Esa fecha no corresponde a este tipo de mail (fin de mes = último día hábil; aviso = día 20). Marcá "Generar igual" para probarlo.';
    } else {
        $mensaje = "Generados {$r['generados']} mails (modo simulación); {$r['ya_enviados']} ya se habían generado; {$r['sin_deuda']} familias sin deuda.";
    }
    responder_json(200, true, $r, $mensaje);
} catch (ReglaNegocioException $e) {
    responder_json($e->getCode(), false, null, $e->getMessage());
}
$conn->close();
