<?php
require_once __DIR__ . '/helpers.php';

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

$inscriptos = [];
$sin_cupo   = [];

$stmtDeporte = $conn->prepare(
    "SELECT d.nombre, d.cupo_maximo, COUNT(insc.alumno_id) AS inscriptos_actuales,
            EXISTS(SELECT 1 FROM inscripciones_deportivas WHERE deporte_id = d.id AND alumno_id = ?) AS ya_inscripto
     FROM deportes d
     LEFT JOIN inscripciones_deportivas insc ON insc.deporte_id = d.id
     WHERE d.id = ?
     GROUP BY d.id, d.nombre, d.cupo_maximo"
);
$stmtInsert = $conn->prepare("INSERT IGNORE INTO inscripciones_deportivas (alumno_id, deporte_id) VALUES (?, ?)");

foreach ($deportes as $deporte_id) {
    $deporte_id = (int) $deporte_id;
    if ($deporte_id <= 0) {
        continue;
    }

    $stmtDeporte->bind_param('ii', $alumno_id, $deporte_id);
    $stmtDeporte->execute();
    $deporte = $stmtDeporte->get_result()->fetch_assoc();

    if (!$deporte) {
        continue;
    }

    if ((bool) $deporte['ya_inscripto']) {
        continue;
    }

    if ((int) $deporte['inscriptos_actuales'] >= (int) $deporte['cupo_maximo']) {
        $sin_cupo[] = $deporte['nombre'];
        continue;
    }

    $stmtInsert->bind_param('ii', $alumno_id, $deporte_id);
    $stmtInsert->execute();
    $inscriptos[] = $deporte['nombre'];
}

$stmtDeporte->close();
$stmtInsert->close();
$conn->close();

echo json_encode(['success' => true, 'inscriptos' => $inscriptos, 'sin_cupo' => $sin_cupo]);
