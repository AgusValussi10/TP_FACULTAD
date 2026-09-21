<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('enfermeria');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

$alumno_id     = (int) ($_POST['alumno_id'] ?? 0);
$motivo        = trim($_POST['motivo'] ?? '');
$hora          = trim($_POST['hora'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

if ($alumno_id <= 0 || $motivo === '' || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
    echo json_encode(['success' => false, 'message' => 'Completá el alumno, el motivo y la hora.']);
    exit;
}

$atendido_por = (int) $_SESSION['usuario_id'];

$stmt = $conn->prepare(
    "INSERT INTO atenciones_enfermeria (alumno_id, motivo, hora, observaciones, atendido_por)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param('isssi', $alumno_id, $motivo, $hora, $observaciones, $atendido_por);
$stmt->execute();
$stmt->close();

// Notificar a los padres/tutores vinculados (RFG10). Flujo alternativo 6.A:
// si no hay ninguno vinculado, igual se guarda la atención.
$padres = [];
$stmt = $conn->prepare("SELECT padre_id FROM padre_alumno WHERE alumno_id = ?");
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $padres[] = (int) $row['padre_id'];
}
$stmt->close();

$sin_contacto = empty($padres);

if (!$sin_contacto) {
    $fecha = date('d/m/Y');
    $mensaje = "Tu hijo/a fue atendido/a en enfermería el {$fecha} a las {$hora}. Motivo: {$motivo}.";
    foreach ($padres as $padre_id) {
        crear_notificacion($conn, $padre_id, $alumno_id, 'enfermeria', $mensaje);
    }
}

$conn->close();

echo json_encode(['success' => true, 'sin_contacto' => $sin_contacto]);
