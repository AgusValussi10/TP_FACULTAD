<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('padre');

require_once __DIR__ . '/../database/db_config.php';

$padre_id = (int) $_SESSION['usuario_id'];
$alumnos  = alumnos_de_padre($conn, $padre_id);

if (empty($alumnos)) {
    echo json_encode(['success' => false, 'message' => 'No hay alumnos vinculados a tu cuenta.']);
    $conn->close();
    exit;
}

$alumno_id = (int) ($_GET['alumno_id'] ?? 0);
if ($alumno_id <= 0 || !alumno_pertenece_a_padre($conn, $alumno_id, $padre_id)) {
    $alumno_id = (int) $alumnos[0]['id'];
}

// Default: mes siguiente al actual.
$hoy = new DateTime('today');
$siguiente = (clone $hoy)->modify('first day of next month');
$mes  = (int) ($_GET['mes']  ?? $siguiente->format('n'));
$anio = (int) ($_GET['anio'] ?? $siguiente->format('Y'));

$plazo_vencido = plazo_vencido_reserva($mes, $anio);

$comedor = false;
$transporte = false;
$stmt = $conn->prepare(
    "SELECT servicio FROM reservas_servicios WHERE alumno_id = ? AND mes = ? AND anio = ? AND estado = 'confirmada'"
);
$stmt->bind_param('iii', $alumno_id, $mes, $anio);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if ($row['servicio'] === 'comedor') $comedor = true;
    if ($row['servicio'] === 'transporte') $transporte = true;
}
$stmt->close();
$conn->close();

echo json_encode([
    'success'       => true,
    'alumnos'       => $alumnos,
    'alumno_id'     => $alumno_id,
    'mes'           => $mes,
    'anio'          => $anio,
    'plazo_vencido' => $plazo_vencido,
    'comedor'       => $comedor,
    'transporte'    => $transporte,
]);
