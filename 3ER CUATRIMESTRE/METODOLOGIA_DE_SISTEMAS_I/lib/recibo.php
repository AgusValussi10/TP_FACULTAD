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

/** Colores de la marca para los PDF (RGB). */
const PDF_NARANJA = [249, 115, 22];
const PDF_OSCURO  = [55, 65, 81];
const PDF_GRIS    = [107, 114, 128];
const PDF_BORDE   = [229, 231, 235];
const PDF_FONDO   = [255, 247, 237];

/**
 * Encabezado común de los comprobantes (recibo, factura, comprobante de pago):
 * logo + datos de la institución a la izquierda y una caja naranja con el título,
 * el número y la fecha de emisión a la derecha. Deja una línea naranja en y = 46.
 */
function pdf_encabezado(FPDF $pdf, array $inst, ?string $logo, string $titulo, string $numero, string $emision): void
{
    $y0 = 15;
    $xTexto = 15;
    if ($logo && is_file($logo)) {
        $pdf->Image($logo, 15, $y0, 26);
        $xTexto = 45;
    }

    $pdf->SetXY($xTexto, $y0 + 2);
    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->SetTextColor(...PDF_OSCURO);
    $pdf->Cell(95, 7, pdf_txt($inst['nombre']), 0, 2);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(...PDF_GRIS);
    $pdf->Cell(95, 5, pdf_txt($inst['subtitulo'] . ' – ' . $inst['localidad']), 0, 2);
    foreach (
        array_filter([
        $inst['cuit']      ? 'CUIT: ' . $inst['cuit']   : '',
        $inst['domicilio'] ? $inst['domicilio']         : '',
        $inst['telefono']  ? 'Tel.: ' . $inst['telefono'] : '',
        ]) as $linea
    ) {
        $pdf->Cell(95, 5, pdf_txt($linea), 0, 2);
    }

    $pdf->SetFillColor(...PDF_NARANJA);
    $pdf->Rect(140, $y0, 55, 26, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(140, $y0 + 3);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->Cell(55, 7, pdf_txt($titulo), 0, 2, 'C');
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(55, 6, pdf_txt('N° ' . $numero), 0, 2, 'C');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(55, 6, pdf_txt('Emitido: ' . pdf_fecha($emision)), 0, 2, 'C');

    $pdf->SetDrawColor(...PDF_NARANJA);
    $pdf->SetLineWidth(0.6);
    $pdf->Line(15, 46, 195, 46);
}

/** Barra de título de sección (fondo crema, mayúsculas). */
function pdf_titulo_seccion(FPDF $pdf, string $texto, float $y): void
{
    $pdf->SetFillColor(...PDF_FONDO);
    $pdf->SetTextColor(...PDF_OSCURO);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetXY(15, $y);
    $pdf->Cell(180, 7, pdf_txt('  ' . mb_strtoupper($texto, 'UTF-8')), 0, 0, 'L', true);
}

/** Fila "etiqueta: valor" (el valor puede ocupar varias líneas). */
function pdf_fila(FPDF $pdf, string $etiqueta, string $valor, float $y): void
{
    $pdf->SetXY(15, $y);
    $pdf->SetFont('Helvetica', '', 9.5);
    $pdf->SetTextColor(...PDF_GRIS);
    $pdf->Cell(45, 6.5, pdf_txt($etiqueta), 0, 0);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(...PDF_OSCURO);
    $pdf->MultiCell(135, 6.5, pdf_txt($valor), 0, 'L');
}

/** Pie con una línea y un texto centrado en itálica. */
function pdf_pie(FPDF $pdf, string $texto): void
{
    $pdf->SetDrawColor(...PDF_BORDE);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(15, 270, 195, 270);
    $pdf->SetXY(15, 272);
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->SetTextColor(...PDF_GRIS);
    $pdf->MultiCell(180, 4, pdf_txt($texto), 0, 'C');
}

/** Datos de la institución para los comprobantes (constantes INSTITUCION_* de helpers.php). */
function pdf_institucion(): array
{
    return [
        'nombre'    => INSTITUCION_NOMBRE,
        'subtitulo' => INSTITUCION_SUBTITULO,
        'localidad' => INSTITUCION_LOCALIDAD,
        'cuit'      => INSTITUCION_CUIT,
        'domicilio' => INSTITUCION_DOMICILIO,
        'telefono'  => INSTITUCION_TELEFONO,
    ];
}

/**
 * @param array $d  Claves: numero, emision (Y-m-d), alumno, curso, tutores (string[]),
 *                  concepto, periodo, importe, recargo, vencimiento, fecha_pago,
 *                  metodo, referencia, registrado_por, institucion[nombre, subtitulo,
 *                  localidad, cuit, domicilio, telefono], logo (ruta o null)
 */
function render_recibo(array $d): FPDF
{
    $oscuro = PDF_OSCURO;
    $gris   = PDF_GRIS;
    $borde  = PDF_BORDE;

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetTitle(pdf_txt('Recibo de pago N° ' . $d['numero']));
    $pdf->SetAuthor(pdf_txt($d['institucion']['nombre']));
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    $inst = $d['institucion'];
    pdf_encabezado($pdf, $inst, $d['logo'] ?? null, 'RECIBO DE PAGO', $d['numero'], $d['emision']);

    $titulo = fn (string $texto, float $y) => pdf_titulo_seccion($pdf, $texto, $y);
    $fila = fn (string $etiqueta, string $valor, float $y) => pdf_fila($pdf, $etiqueta, $valor, $y);

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
    pdf_pie($pdf, 'Comprobante de pago emitido por ' . $inst['nombre'] . '. Conserve este recibo como constancia. '
        . 'Documento no válido como factura.');

    return $pdf;
}
