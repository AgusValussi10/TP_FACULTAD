<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../lib/inscripciones.php';

header('Content-Type: application/json; charset=utf-8');
errores_como_json();
requerir_rol('padre');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

$padre_id  = (int) $_SESSION['usuario_id'];
$alumno_id = (int) ($_POST['alumno_id'] ?? 0);
$deportes  = $_POST['deportes'] ?? [];

if ($alumno_id <= 0 || !is_array($deportes) || empty($deportes)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

if (!alumno_pertenece_a_padre($conn, $alumno_id, $padre_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'El alumno no está vinculado a tu cuenta.']);
    $conn->close();
    exit;
}

// Mismas reglas que la app (lib/inscripciones.php): cupo, máximo de deportes
// simultáneos (RFGm09) y conflicto de horario. Un deporte rechazado no frena a los demás.
$inscriptos = [];
$rechazados = [];
foreach ($deportes as $deporte_id) {
    $deporte_id = (int) $deporte_id;
    if ($deporte_id <= 0) {
        continue;
    }
    try {
        $inscriptos[] = inscribir_deporte($conn, $alumno_id, $deporte_id);
    } catch (ReglaNegocioException $e) {
        $rechazados[] = $e->getMessage();
    }
}
$conn->close();

// "sin_cupo" se mantiene por compatibilidad con la pantalla anterior: ahora trae el motivo de cada rechazo.
echo json_encode(['success' => true, 'inscriptos' => $inscriptos, 'rechazados' => $rechazados, 'sin_cupo' => $rechazados]);
