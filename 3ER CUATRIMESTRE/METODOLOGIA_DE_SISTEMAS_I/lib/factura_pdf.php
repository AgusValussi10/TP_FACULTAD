<?php

// Factura mensual en PDF (Parte 3, RFGm04). Mismo estilo que el recibo (lib/recibo.php).
// Solo dibuja: recibe los datos ya armados, no toca la base ni la sesión.

require_once __DIR__ . '/recibo.php';

/** Tabla de ítems: [encabezado, ancho, alineación] y una fila por ítem. Devuelve la y final. */
function pdf_tabla(FPDF $pdf, array $columnas, array $filas, float $y): float
{
    $pdf->SetXY(15, $y);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(...PDF_GRIS);
    $pdf->SetDrawColor(...PDF_BORDE);
    $pdf->SetLineWidth(0.3);
    foreach ($columnas as [$titulo, $ancho, $alineacion]) {
        $pdf->Cell($ancho, 7, pdf_txt($titulo), 'B', 0, $alineacion);
    }
    $y += 7;
    $pdf->SetFont('Helvetica', '', 9.5);
    $pdf->SetTextColor(...PDF_OSCURO);
    foreach ($filas as $fila) {
        $pdf->SetXY(15, $y);
        foreach ($columnas as $i => [, $ancho, $alineacion]) {
            $pdf->Cell($ancho, 7.5, pdf_txt($fila[$i]), 'B', 0, $alineacion);
        }
        $y += 7.5;
    }
    return $y;
}

/**
 * @param array $d  Claves: numero, emision, vencimiento, periodo, alumno, curso, tutores (string[]),
 *                  estado_texto, items[{descripcion, tipo_texto, estado_texto, importe}],
 *                  total, pagado, en_revision, saldo, pagos[{numero, fecha, estado_texto, total}],
 *                  institucion (pdf_institucion()), logo (ruta o null)
 */
function render_factura(array $d): FPDF
{
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetTitle(pdf_txt('Factura N° ' . $d['numero']));
    $pdf->SetAuthor(pdf_txt($d['institucion']['nombre']));
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();
    pdf_encabezado($pdf, $d['institucion'], $d['logo'] ?? null, 'FACTURA', $d['numero'], $d['emision']);

    $y = 54;
    pdf_titulo_seccion($pdf, 'Datos', $y);
    $y += 9;
    foreach (
        [
            ['Alumno/a', $d['alumno']],
            ['Curso', $d['curso'] ?: 'Sin curso asignado'],
            ['Tutor/es', $d['tutores'] ? implode(', ', $d['tutores']) : 'Sin tutor vinculado'],
            ['Período', $d['periodo']],
            ['Vencimiento', pdf_fecha($d['vencimiento'])],
            ['Estado', $d['estado_texto']],
        ] as [$etiqueta, $valor]
    ) {
        pdf_fila($pdf, $etiqueta, $valor, $y);
        $y = $pdf->GetY();
    }

    $y += 6;
    pdf_titulo_seccion($pdf, 'Detalle', $y);
    $filas = array_map(fn ($it) => [$it['descripcion'], $it['estado_texto'], pdf_monto($it['importe'])], $d['items']);
    $y = pdf_tabla($pdf, [['Concepto', 110, 'L'], ['Estado', 35, 'L'], ['Importe', 35, 'R']], $filas, $y + 9);

    // Totales a la derecha.
    $y += 4;
    $totales = [['Total facturado', $d['total']], ['Pagado', $d['pagado']]];
    if ($d['en_revision'] > 0) {
        $totales[] = ['En proceso de pago', $d['en_revision']];
    }
    foreach ($totales as [$etiqueta, $monto]) {
        $pdf->SetXY(105, $y);
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(...PDF_GRIS);
        $pdf->Cell(55, 6.5, pdf_txt($etiqueta), 0, 0, 'R');
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(...PDF_OSCURO);
        $pdf->Cell(35, 6.5, pdf_monto($monto), 0, 0, 'R');
        $y += 6.5;
    }
    $y += 2;
    $pdf->SetFillColor(...PDF_OSCURO);
    $pdf->Rect(105, $y, 90, 16, 'F');
    $pdf->SetXY(105, $y + 2);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(90, 5, 'SALDO A PAGAR', 0, 2, 'C');
    $pdf->SetFont('Helvetica', 'B', 15);
    $pdf->Cell(90, 7, pdf_monto($d['saldo']), 0, 0, 'C');
    $y += 24;

    if ($d['pagos']) {
        pdf_titulo_seccion($pdf, 'Pagos registrados', $y);
        $filas = array_map(fn ($p) => [$p['numero'], $p['fecha'], $p['estado_texto'], pdf_monto($p['total'])], $d['pagos']);
        pdf_tabla($pdf, [['Comprobante', 40, 'L'], ['Fecha', 35, 'L'], ['Estado', 70, 'L'], ['Importe', 35, 'R']], $filas, $y + 9);
    }

    pdf_pie($pdf, 'Factura mensual emitida por ' . $d['institucion']['nombre'] . '. Se paga solo por transferencia bancaria '
        . 'desde la app. Comprobante interno, no válido como factura fiscal.');
    return $pdf;
}
