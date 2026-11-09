<?php

/**
 * GET ?factura_id= → factura en PDF (RFGm04). El padre, solo las de sus hijos; el
 * alumno, solo las suyas. 403 si no corresponde, exista o no (no se revelan ids).
 */

require_once __DIR__ . '/_base.php';
require_once __DIR__ . '/../../lib/factura_pdf.php';

ini_set('display_errors', '0'); // cualquier aviso antes del PDF lo corrompería
requerir_metodo('GET');
$sesion = api_sesion();
require_once __DIR__ . '/../../database/db_config.php';

$factura_id = (int) ($_GET['factura_id'] ?? 0);
$stmt = $conn->prepare(
    "SELECT f.alumno_id, a.nombre AS alumno, c.nombre AS curso
     FROM facturas f JOIN usuarios a ON a.id = f.alumno_id
     LEFT JOIN alumno_curso ac ON ac.alumno_id = f.alumno_id
     LEFT JOIN cursos c ON c.id = ac.curso_id
     WHERE f.id = ?"
);
$stmt->bind_param('i', $factura_id);
$stmt->execute();
$cab = $stmt->get_result()->fetch_assoc();
$stmt->close();

$propio = $cab && ($sesion['rol'] === 'alumno'
    ? (int) $cab['alumno_id'] === $sesion['id']
    : alumno_pertenece_a_padre($conn, (int) $cab['alumno_id'], $sesion['id']));
if (!$propio) {
    responder_json(403, false, null, 'La factura no corresponde a tu cuenta.');
    $conn->close();
    exit;
}

$alumno_id = (int) $cab['alumno_id'];
$factura = null;
foreach (facturas_de_alumno($conn, $alumno_id) as $f) {
    if ($f['id'] === $factura_id) {
        $factura = $f;
        break;
    }
}
$stmt = $conn->prepare("SELECT u.nombre FROM padre_alumno pa JOIN usuarios u ON u.id = pa.padre_id WHERE pa.alumno_id = ? ORDER BY u.nombre");
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$tutores = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'nombre');
$stmt->close();
$conn->close();

$pdf = render_factura([
    'numero'       => $factura['numero'],
    'emision'      => $factura['emision'],
    'vencimiento'  => $factura['vencimiento'],
    'periodo'      => $factura['periodo'],
    'alumno'       => $cab['alumno'],
    'curso'        => $cab['curso'],
    'tutores'      => $tutores,
    'estado_texto' => $factura['estado_texto'],
    'items'        => $factura['items'],
    'total'        => $factura['total'],
    'pagado'       => $factura['pagado'],
    'en_revision'  => $factura['en_revision'],
    'saldo'        => $factura['saldo'],
    'pagos'        => array_map(fn ($p) => [
        'numero' => $p['numero'], 'fecha' => $p['fecha'], 'estado_texto' => $p['estado_texto'], 'total' => $p['total'],
    ], $factura['pagos']),
    'institucion'  => pdf_institucion(),
    'logo'         => __DIR__ . '/../../assets/logo.png',
]);

header('Content-Type: application/pdf');
header('Cache-Control: private, no-store');
$pdf->Output('I', $factura['numero'] . '.pdf');
