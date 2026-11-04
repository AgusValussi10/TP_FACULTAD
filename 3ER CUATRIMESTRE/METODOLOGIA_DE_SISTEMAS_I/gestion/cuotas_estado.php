<?php

// GET → estado de las cuotas (RFG19).
//  - padre: ?alumno_id= de un hijo → cuotas con importes, recargo, total y resumen de deuda.
//  - admin: ?alumno_id= de cualquier alumno → lo mismo.
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol(['padre', 'admin']);
errores_como_json();

require_once __DIR__ . '/../database/db_config.php';

$rol        = $_SESSION['rol'];
$usuario_id = (int) $_SESSION['usuario_id'];

$alumno_id = (int) ($_GET['alumno_id'] ?? 0);
if ($alumno_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Parámetro alumno_id requerido.']);
    exit;
}
if ($rol === 'padre' && !alumno_pertenece_a_padre($conn, $alumno_id, $usuario_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Solo podés ver las cuotas de tus hijos.']);
    exit;
}

$datos = cuotas_de_alumno($conn, $alumno_id);
$conn->close();

echo json_encode(['success' => true, 'dias_aviso' => DIAS_AVISO_VENCIMIENTO] + $datos);
