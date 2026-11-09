<?php

/**
 * Reportes de pagos para el admin (RFGm12). Toda la agregación es SQL (GROUP BY);
 * PHP solo da formato. El período es un rango de meses 'AAAA-MM' (inclusive):
 * - ingresos: por fecha de APROBACIÓN del pago (lo que efectivamente entró);
 * - el resto: por el mes FACTURADO.
 */

require_once __DIR__ . '/facturacion.php';

const REPORTES_PAGOS = [
    'ingresos'    => 'Ingresos por período (pagos aprobados)',
    'completos'   => 'Pagos completos por año y alumno (facturas pagadas)',
    'incompletos' => 'Pagos incompletos por año y alumno (facturas con saldo)',
    'deportes'    => 'Pagos por deporte, nivel, horario y profesor',
    'transporte'  => 'Pagos por recorrido de transporte',
];

/**
 * @return array{titulo:string, columnas:string[], filas:array<int, array>, totales:?array}
 *         Las columnas que empiezan con "$" son importes.
 */
function reporte_pagos(mysqli $conn, string $reporte, string $desde, string $hasta): array
{
    if (!isset(REPORTES_PAGOS[$reporte])) {
        throw new ReglaNegocioException('Reporte inválido.', 422);
    }
    $min = indice_periodo($desde);
    $max = indice_periodo($hasta);
    if ($min === null || $max === null || $min > $max) {
        throw new ReglaNegocioException('Elegí un período válido (desde ≤ hasta).', 422);
    }

    switch ($reporte) {
        case 'ingresos':
            // [primer día de "desde", primer día del mes siguiente a "hasta")
            $inicio = sprintf('%04d-%02d-01', intdiv($min - 1, 12), ($min - 1) % 12 + 1);
            $fin = sprintf('%04d-%02d-01', intdiv($max, 12), $max % 12 + 1);
            $sql = "SELECT YEAR(p.revisado_en) AS anio, MONTH(p.revisado_en) AS mes, COUNT(*) AS pagos, SUM(p.total) AS total
                    FROM pagos p
                    WHERE p.estado = 'aprobado' AND p.revisado_en >= ? AND p.revisado_en < ?
                    GROUP BY YEAR(p.revisado_en), MONTH(p.revisado_en)
                    ORDER BY anio, mes";
            $filas = consulta_reporte($conn, $sql, 'ss', [$inicio, $fin]);
            $columnas = ['Mes', 'Pagos aprobados', '$ Total'];
            $filas = array_map(fn ($f) => [periodo_texto((int) $f['mes'], (int) $f['anio']), (int) $f['pagos'], (float) $f['total']], $filas);
            break;

        case 'completos':
        case 'incompletos':
            $condicion = $reporte === 'completos' ? "f.estado = 'pagada'" : "f.estado <> 'pagada'";
            $sql = "SELECT f.anio, u.nombre AS alumno, COUNT(*) AS facturas,
                           SUM(f.total) AS total, SUM(f.pagado) AS pagado, SUM(f.total - f.pagado) AS saldo
                    FROM facturas f JOIN usuarios u ON u.id = f.alumno_id
                    WHERE $condicion AND (f.anio * 12 + f.mes) BETWEEN ? AND ?
                    GROUP BY f.anio, f.alumno_id, u.nombre
                    ORDER BY f.anio, u.nombre";
            $filas = consulta_reporte($conn, $sql, 'ii', [$min, $max]);
            if ($reporte === 'completos') {
                $columnas = ['Año', 'Alumno', 'Facturas', '$ Pagado'];
                $filas = array_map(fn ($f) => [(int) $f['anio'], $f['alumno'], (int) $f['facturas'], (float) $f['pagado']], $filas);
            } else {
                $columnas = ['Año', 'Alumno', 'Facturas', '$ Facturado', '$ Pagado', '$ Saldo'];
                $filas = array_map(fn ($f) => [
                    (int) $f['anio'], $f['alumno'], (int) $f['facturas'], (float) $f['total'], (float) $f['pagado'], (float) $f['saldo'],
                ], $filas);
            }
            break;

        case 'deportes':
            $sql = "SELECT d.nombre AS deporte, COALESCE(c.nivel_educativo, 'Sin curso') AS nivel, d.horario,
                           COALESCE(pr.nombre, 'Sin profesor') AS profesor, COUNT(*) AS cuotas,
                           SUM(fi.importe) AS facturado,
                           SUM(CASE WHEN fi.estado = 'pagado' THEN fi.importe ELSE 0 END) AS pagado
                    FROM factura_items fi
                    JOIN facturas f ON f.id = fi.factura_id
                    JOIN deportes d ON d.id = fi.deporte_id
                    LEFT JOIN usuarios pr ON pr.id = d.profesor_id
                    LEFT JOIN alumno_curso ac ON ac.alumno_id = f.alumno_id
                    LEFT JOIN cursos c ON c.id = ac.curso_id
                    WHERE fi.tipo = 'deporte' AND (f.anio * 12 + f.mes) BETWEEN ? AND ?
                    GROUP BY d.id, d.nombre, c.nivel_educativo, d.horario, pr.nombre
                    ORDER BY d.nombre, nivel";
            $filas = consulta_reporte($conn, $sql, 'ii', [$min, $max]);
            $columnas = ['Deporte', 'Nivel', 'Horario', 'Profesor', 'Cuotas', '$ Facturado', '$ Pagado'];
            $filas = array_map(fn ($f) => [
                $f['deporte'], $f['nivel'], $f['horario'], $f['profesor'], (int) $f['cuotas'], (float) $f['facturado'], (float) $f['pagado'],
            ], $filas);
            break;

        default: // transporte
            $sql = "SELECT rt.nombre AS recorrido, COUNT(*) AS cuotas, SUM(fi.importe) AS facturado,
                           SUM(CASE WHEN fi.estado = 'pagado' THEN fi.importe ELSE 0 END) AS pagado
                    FROM factura_items fi
                    JOIN facturas f ON f.id = fi.factura_id
                    JOIN recorridos_transporte rt ON rt.id = fi.recorrido_id
                    WHERE fi.tipo = 'transporte' AND (f.anio * 12 + f.mes) BETWEEN ? AND ?
                    GROUP BY rt.id, rt.nombre
                    ORDER BY rt.id";
            $filas = consulta_reporte($conn, $sql, 'ii', [$min, $max]);
            $columnas = ['Recorrido', 'Cuotas', '$ Facturado', '$ Pagado'];
            $filas = array_map(fn ($f) => [$f['recorrido'], (int) $f['cuotas'], (float) $f['facturado'], (float) $f['pagado']], $filas);
    }

    // Totales de las columnas de importes (y de cantidades) para la última fila.
    $totales = null;
    if ($filas) {
        $totales = array_fill(0, count($columnas), '');
        $totales[0] = 'Total';
        foreach ($columnas as $i => $col) {
            if ($i > 0 && is_numeric($filas[0][$i]) && !in_array($col, ['Año'], true)) {
                $totales[$i] = round(array_sum(array_column($filas, $i)), 2);
            }
        }
    }
    return ['titulo' => REPORTES_PAGOS[$reporte], 'columnas' => $columnas, 'filas' => $filas, 'totales' => $totales];
}

function consulta_reporte(mysqli $conn, string $sql, string $tipos, array $parametros): array
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$parametros);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $filas;
}

/** Envía el reporte como CSV (UTF-8 con BOM y ";" como separador, para que Excel en español lo abra bien). */
function enviar_csv(array $reporte, string $archivo): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $archivo . '"');
    header('Cache-Control: private, no-store');
    $salida = fopen('php://output', 'w');
    fwrite($salida, "\xEF\xBB\xBF");
    $numero = fn ($v) => is_float($v) ? number_format($v, 2, ',', '') : $v;
    fputcsv($salida, array_map(fn ($c) => ltrim($c, '$ '), $reporte['columnas']), ';');
    foreach ($reporte['filas'] as $fila) {
        fputcsv($salida, array_map($numero, $fila), ';');
    }
    if ($reporte['totales']) {
        fputcsv($salida, array_map($numero, $reporte['totales']), ';');
    }
    fclose($salida);
}
