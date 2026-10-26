<?php
// Generación del recibo de pago en PDF (FPDF, ver lib/fpdf/).
// Solo dibuja: recibe los datos ya armados, no toca la base ni la sesión.

define('FPDF_FONTPATH', __DIR__ . '/fpdf/font/');
require_once __DIR__ . '/fpdf/fpdf.php';

/** FPDF trabaja en Windows-1252; convierte desde UTF-8 para que salgan bien tildes y la ñ. */
function pdf_txt(?string $s): string
{
    return iconv('UTF-8', 'windows-1252//TRANSLIT', (string) $s) ?: '';
}

function pdf_monto(float $n): string
{
    return '$ ' . number_format($n, 2, ',', '.');
}

function pdf_fecha(?string $iso): string
{
    return $iso ? date('d/m/Y', strtotime($iso)) : '—';
}

/**
 * @param array $d  Claves: numero, emision (Y-m-d), alumno, curso, tutores (string[]),
 *                  concepto, periodo, importe, recargo, vencimiento, fecha_pago,
 *                  metodo, referencia, registrado_por, institucion[nombre, subtitulo,
 *                  localidad, cuit, domicilio, telefono], logo (ruta o null)
 */
function render_recibo(array $d): FPDF
{
    $naranja = [249, 115, 22];
    $oscuro  = [55, 65, 81];
    $gris    = [107, 114, 128];
    $borde   = [229, 231, 235];
    $fondo   = [255, 247, 237];

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetTitle(pdf_txt('Recibo de pago N° ' . $d['numero']));
    $pdf->SetAuthor(pdf_txt($d['institucion']['nombre']));
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    $inst = $d['institucion'];

    // --- Encabezado: logo + institución (izquierda), recibo (derecha) -----------------
    $y0 = 15;
    $xTexto = 15;
    if (!empty($d['logo']) && is_file($d['logo'])) {
        $pdf->Image($d['logo'], 15, $y0, 26);
        $xTexto = 45;
    }

    $pdf->SetXY($xTexto, $y0 + 2);
    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->SetTextColor(...$oscuro);
    $pdf->Cell(95, 7, pdf_txt($inst['nombre']), 0, 2);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(...$gris);
    $pdf->Cell(95, 5, pdf_txt($inst['subtitulo'] . ' – ' . $inst['localidad']), 0, 2);
    foreach (array_filter([
        $inst['cuit']      ? 'CUIT: ' . $inst['cuit']          : '',
        $inst['domicilio'] ? $inst['domicilio']                  : '',
        $inst['telefono']  ? 'Tel.: ' . $inst['telefono']        : '',
    ]) as $linea) {
        $pdf->Cell(95, 5, pdf_txt($linea), 0, 2);
    }

    // Caja del recibo
    $pdf->SetFillColor(...$naranja);
    $pdf->Rect(140, $y0, 55, 26, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(140, $y0 + 3);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->Cell(55, 7, 'RECIBO DE PAGO', 0, 2, 'C');
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(55, 6, pdf_txt('N° ' . $d['numero']), 0, 2, 'C');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(55, 6, pdf_txt('Emitido: ' . pdf_fecha($d['emision'])), 0, 2, 'C');

    $pdf->SetDrawColor(...$naranja);
    $pdf->SetLineWidth(0.6);
    $pdf->Line(15, 46, 195, 46);

    // --- Helpers de sección -----------------------------------------------------------
    $titulo = function (string $texto, float $y) use ($pdf, $oscuro, $fondo) {
        $pdf->SetFillColor(...$fondo);
        $pdf->SetTextColor(...$oscuro);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetXY(15, $y);
        $pdf->Cell(180, 7, pdf_txt('  ' . mb_strtoupper($texto, 'UTF-8')), 0, 0, 'L', true);
    };
    $fila = function (string $etiqueta, string $valor, float $y) use ($pdf, $oscuro, $gris) {
        $pdf->SetXY(15, $y);
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(...$gris);
        $pdf->Cell(45, 6.5, pdf_txt($etiqueta), 0, 0);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(...$oscuro);
        $pdf->MultiCell(135, 6.5, pdf_txt($valor), 0, 'L');
    };

    // --- Datos del alumno y tutor -----------------------------------------------------
    $y = 54;
    $titulo('Datos del alumno', $y);
    $y += 9;
    $fila('Alumno/a', $d['alumno'], $y);
    $y = $pdf->GetY();
    $fila('Curso', $d['curso'] ?: 'Sin curso asignado', $y);
    $y = $pdf->GetY();
    $fila('Tutor/es', $d['tutores'] ? implode(', ', $d['tutores']) : 'Sin tutor vinculado', $y);
    $y = $pdf->GetY() + 6;

    // --- Detalle del pago (tabla) -----------------------------------------------------
    $titulo('Detalle del pago', $y);
    $y += 9;
    $cols = [['Concepto', 50, 'L'], ['Período', 40, 'L'], ['Importe', 30, 'R'], ['Recargo por mora', 32, 'R'], ['Subtotal', 28, 'R']];
    $pdf->SetXY(15, $y);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(...$gris);
    $pdf->SetDrawColor(...$borde);
    $pdf->SetLineWidth(0.3);
    foreach ($cols as [$h, $w, $al]) {
        $pdf->Cell($w, 7, pdf_txt($h), 'B', 0, $al);
    }
    $y += 7;
    $pdf->SetXY(15, $y);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(...$oscuro);
    $subtotal = $d['importe'] + $d['recargo'];
    $celdas = [$d['concepto'], $d['periodo'], pdf_monto($d['importe']), $d['recargo'] > 0 ? pdf_monto($d['recargo']) : '—', pdf_monto($subtotal)];
    foreach ($cols as $i => [$h, $w, $al]) {
        $pdf->Cell($w, 9, pdf_txt($celdas[$i]), 'B', 0, $al);
    }
    $y += 9 + 6;

    // --- Información del pago ---------------------------------------------------------
    $titulo('Información del pago', $y);
    $y += 9;
    $fila('Forma de pago', $d['metodo'], $y);
    $y = $pdf->GetY();
    if ($d['referencia'] !== '') {
        $fila('Referencia / Nº operación', $d['referencia'], $y);
        $y = $pdf->GetY();
    }
    $fila('Fecha de pago', pdf_fecha($d['fecha_pago']), $y);
    $y = $pdf->GetY();
    $fila('Fecha de vencimiento', pdf_fecha($d['vencimiento']), $y);
    $y = $pdf->GetY();
    $fila('Registrado por', $d['registrado_por'] ?: '—', $y);
    $y = $pdf->GetY() + 8;

    // --- Total ------------------------------------------------------------------------
    $pdf->SetFillColor(...$oscuro);
    $pdf->Rect(105, $y, 90, 16, 'F');
    $pdf->SetXY(105, $y + 2);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(90, 5, 'TOTAL ABONADO', 0, 2, 'C');
    $pdf->SetFont('Helvetica', 'B', 15);
    $pdf->Cell(90, 7, pdf_monto($subtotal), 0, 0, 'C');
    $y += 16 + 30;

    // --- Firma ------------------------------------------------------------------------
    $pdf->SetDrawColor(...$gris);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(125, $y, 195, $y);
    $pdf->SetXY(125, $y + 1);
    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetTextColor(...$gris);
    $pdf->Cell(70, 5, pdf_txt('Firma y sello de la institución'), 0, 0, 'C');

    // --- Pie --------------------------------------------------------------------------
    $pdf->SetDrawColor(...$borde);
    $pdf->Line(15, 270, 195, 270);
    $pdf->SetXY(15, 272);
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->MultiCell(180, 4, pdf_txt(
        'Comprobante de pago emitido por ' . $inst['nombre'] . '. Conserve este recibo como constancia. '
        . 'Documento no válido como factura.'
    ), 0, 'C');

    return $pdf;
}
