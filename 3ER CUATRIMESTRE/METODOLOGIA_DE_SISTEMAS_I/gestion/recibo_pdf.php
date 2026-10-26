<?php
// GET ?cuota_id=N → recibo de pago en PDF (RFG12). Solo admin; la cuota debe estar pagada.
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../lib/recibo.php';

// Cualquier warning/notice antes del PDF lo corrompería.
ini_set('display_errors', '0');

function recibo_error(int $codigo, string $mensaje): void
{
    http_response_code($codigo);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'admin') {
    recibo_error(403, 'Acceso denegado.');
}

$cuota_id = (int) ($_GET['cuota_id'] ?? 0);
if ($cuota_id <= 0) {
    recibo_error(400, 'Cuota inválida.');
}

require_once __DIR__ . '/../database/db_config.php';

$stmt = $conn->prepare(
    "SELECT cu.id, cu.alumno_id, cu.concepto, cu.mes, cu.anio, cu.importe, cu.recargo,
            cu.fecha_vencimiento, cu.estado, cu.fecha_pago, cu.metodo_pago, cu.referencia_pago,
            a.nombre AS alumno, c.nombre AS curso, c.nivel_educativo,
            r.nombre AS registrado_por
     FROM cuotas cu
     JOIN usuarios a            ON a.id = cu.alumno_id
     LEFT JOIN alumno_curso ac  ON ac.alumno_id = cu.alumno_id
     LEFT JOIN cursos c         ON c.id = ac.curso_id
     LEFT JOIN usuarios r       ON r.id = cu.registrado_por
     WHERE cu.id = ?"
);
$stmt->bind_param('i', $cuota_id);
$stmt->execute();
$cuota = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cuota) {
    $conn->close();
    recibo_error(404, 'La cuota no existe.');
}
if ($cuota['estado'] !== 'pagada') {
    $conn->close();
    recibo_error(409, 'Solo se puede emitir recibo de una cuota pagada.');
}

$stmt = $conn->prepare(
    "SELECT u.nombre FROM padre_alumno pa JOIN usuarios u ON u.id = pa.padre_id
     WHERE pa.alumno_id = ? ORDER BY u.nombre"
);
$stmt->bind_param('i', $cuota['alumno_id']);
$stmt->execute();
$tutores = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'nombre');
$stmt->close();
$conn->close();

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$metodo = $cuota['metodo_pago'];

$pdf = render_recibo([
    'numero'         => sprintf('R-%06d', $cuota['id']),
    'emision'        => date('Y-m-d'),
    'alumno'         => $cuota['alumno'],
    'curso'          => $cuota['curso'] ? "{$cuota['curso']} · Nivel {$cuota['nivel_educativo']}" : '',
    'tutores'        => $tutores,
    'concepto'       => $cuota['concepto'] === 'matricula' ? 'Matrícula' : 'Cuota mensual',
    'periodo'        => ucfirst($meses[$cuota['mes'] - 1] ?? '') . ' ' . $cuota['anio'],
    'importe'        => (float) $cuota['importe'],
    'recargo'        => (float) $cuota['recargo'],
    'vencimiento'    => $cuota['fecha_vencimiento'],
    'fecha_pago'     => $cuota['fecha_pago'],
    // Las cuotas pagadas antes de existir este campo no tienen forma de pago registrada.
    'metodo'         => $metodo ? METODOS_PAGO[$metodo] : 'No registrada',
    'referencia'     => (string) $cuota['referencia_pago'],
    'registrado_por' => (string) $cuota['registrado_por'],
    'institucion'    => [
        'nombre'    => INSTITUCION_NOMBRE,
        'subtitulo' => INSTITUCION_SUBTITULO,
        'localidad' => INSTITUCION_LOCALIDAD,
        'cuit'      => INSTITUCION_CUIT,
        'domicilio' => INSTITUCION_DOMICILIO,
        'telefono'  => INSTITUCION_TELEFONO,
    ],
    'logo'           => __DIR__ . '/../assets/logo.png',
]);

$pdf->Output('I', sprintf('recibo-R-%06d.pdf', $cuota['id']));
