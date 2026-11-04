<?php

/**
 * seed_cuotas.php — Cuotas de demostración para probar RFG19 (estado de cuotas).
 *
 * A cada alumno con curso le genera 4 cuotas mensuales (mes-2 … mes+1) con
 * vencimientos RELATIVOS A HOY, para que siempre se vean todos los estados:
 *   mes-2: pagada (vencía hace 40 días)
 *   mes-1: vencía hace 10 días → pagada si el id del alumno es par, IMPAGA si es impar
 *   mes:   vence en 4 días     → PRÓXIMA A VENCER
 *   mes+1: vence en 34 días    → pendiente
 * Los vencimientos no siguen DIA_VENCIMIENTO_CUOTA a propósito: son datos de demo.
 *
 * Solo por consola (crea deudas ficticias; no debe abrirse por URL en producción):
 *   cd database && php seed_cuotas.php
 * Es idempotente: INSERT IGNORE sobre la clave única (alumno, concepto, mes, año).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo se ejecuta por consola.');
}

require_once 'db_config.php';
require_once __DIR__ . '/../gestion/helpers.php';
$conn->set_charset('utf8');

const IMPORTE_DEMO = 45000.00;

$admin_id = (int) ($conn->query("SELECT id FROM usuarios WHERE rol = 'admin' ORDER BY id LIMIT 1")->fetch_row()[0] ?? 0);
$alumnos  = $conn->query(
    "SELECT u.id FROM usuarios u JOIN alumno_curso ac ON ac.alumno_id = u.id WHERE u.rol = 'alumno' ORDER BY u.id"
)->fetch_all(MYSQLI_ASSOC);

// [desplazamiento de mes, días hasta el vencimiento, pagada (true / false / 'par' según el id)]
$plan = [[-2, -40, true], [-1, -10, 'par'], [0, 4, false], [1, 34, false]];

$insert = $conn->prepare(
    "INSERT IGNORE INTO cuotas
        (alumno_id, concepto, mes, anio, importe, recargo, fecha_vencimiento, estado, fecha_pago, metodo_pago, registrado_por)
     VALUES (?, 'cuota', ?, ?, ?, 0, ?, ?, ?, ?, ?)"
);
$creadas = 0;
foreach ($alumnos as $a) {
    $alumno_id = (int) $a['id'];
    foreach ($plan as [$mes_offset, $dias, $pagada]) {
        $periodo = new DateTime('first day of this month');
        $periodo->modify("$mes_offset month");
        $mes         = (int) $periodo->format('n');
        $anio        = (int) $periodo->format('Y');
        $vencimiento = date('Y-m-d', strtotime("$dias days"));
        $es_pagada   = $pagada === 'par' ? $alumno_id % 2 === 0 : $pagada;
        $estado      = $es_pagada ? 'pagada' : 'pendiente';
        $fecha_pago  = $es_pagada ? date('Y-m-d', strtotime(($dias - 2) . ' days')) : null;
        $metodo      = $es_pagada ? 'efectivo' : null;
        $registro    = $es_pagada ? $admin_id : null;
        $importe     = IMPORTE_DEMO;
        $insert->bind_param('iiidssssi', $alumno_id, $mes, $anio, $importe, $vencimiento, $estado, $fecha_pago, $metodo, $registro);
        $insert->execute();
        $creadas += $insert->affected_rows;
    }
    actualizar_condicion_regularizacion($conn, $alumno_id);
}
$insert->close();
$conn->close();

echo "Listo: $creadas cuotas de demo creadas para " . count($alumnos) . " alumnos." . PHP_EOL;
