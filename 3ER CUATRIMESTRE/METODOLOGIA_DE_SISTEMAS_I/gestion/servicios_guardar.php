<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../lib/inscripciones.php';

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

// Mismas reglas que la app (lib/inscripciones.php): plazo del día 25 (RN08), mes no
// facturado y, para el transporte, un recorrido (RFGm11).
$recorrido_id = (int) ($_POST['recorrido_id'] ?? 0);
$confirmados = [];
try {
    foreach (array_intersect(['comedor', 'transporte'], $servicios) as $servicio) {
        reservar_servicio($conn, $alumno_id, $servicio, $mes, $anio, $servicio === 'transporte' ? $recorrido_id : null);
        $confirmados[] = $servicio;
    }
    echo json_encode(['success' => true, 'confirmados' => $confirmados]);
} catch (ReglaNegocioException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'confirmados' => $confirmados]);
}

$conn->close();
