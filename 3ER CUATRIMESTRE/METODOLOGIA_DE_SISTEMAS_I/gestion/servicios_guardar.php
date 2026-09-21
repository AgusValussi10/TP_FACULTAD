<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('padre');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

$padre_id  = (int) $_SESSION['usuario_id'];
$alumno_id = (int) ($_POST['alumno_id'] ?? 0);
$mes       = (int) ($_POST['mes'] ?? 0);
$anio      = (int) ($_POST['anio'] ?? 0);
$servicios = $_POST['servicios'] ?? [];

if ($alumno_id <= 0 || $mes < 1 || $mes > 12 || $anio < 2000 || !is_array($servicios) || empty($servicios)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

if (!alumno_pertenece_a_padre($conn, $alumno_id, $padre_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'El alumno no está vinculado a tu cuenta.']);
    $conn->close();
    exit;
}

if (plazo_vencido_reserva($mes, $anio)) {
    echo json_encode(['success' => false, 'message' =>
        'El plazo para reservar este mes venció. Comunicate con administración para gestionarlo.']);
    $conn->close();
    exit;
}

$servicios_validos = ['comedor', 'transporte'];
$confirmados = [];

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO reservas_servicios (alumno_id, servicio, mes, anio, estado)
         VALUES (?, ?, ?, ?, 'confirmada')
         ON DUPLICATE KEY UPDATE estado = 'confirmada'"
    );

    foreach ($servicios as $servicio) {
        if (!in_array($servicio, $servicios_validos, true)) {
            continue;
        }
        $stmt->bind_param('isii', $alumno_id, $servicio, $mes, $anio);
        $stmt->execute();
        $confirmados[] = $servicio;
    }
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'confirmados' => $confirmados]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al guardar la reserva.']);
}

$conn->close();
