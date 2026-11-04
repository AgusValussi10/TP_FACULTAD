<?php

// POST admin → anula una sanción (baja lógica: queda en el legajo, tachada) y avisa a la familia (RFG14).
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('admin');
errores_como_json();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

$id     = (int) ($_POST['id'] ?? 0);
$motivo = trim($_POST['motivo'] ?? '');
$admin  = (int) $_SESSION['usuario_id'];

$largo = mb_strlen($motivo);
if ($largo < 10 || $largo > 500) {
    echo json_encode(['success' => false, 'message' => 'El motivo de la anulación debe tener entre 10 y 500 caracteres.']);
    exit;
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "SELECT s.alumno_id, s.tipo, s.fecha, s.anulada, u.nombre
         FROM sanciones s JOIN usuarios u ON u.id = s.alumno_id
         WHERE s.id = ? FOR UPDATE"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$s || (int) $s['anulada'] === 1) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $s ? 'La sanción ya estaba anulada.' : 'La sanción no existe.']);
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE sanciones SET anulada = 1, motivo_anulacion = ?, anulada_por = ?, anulada_en = NOW() WHERE id = ?"
    );
    $stmt->bind_param('sii', $motivo, $admin, $id);
    $stmt->execute();
    $stmt->close();

    $alumno_id = (int) $s['alumno_id'];
    $mensaje = 'Se anuló la sanción (' . TIPOS_SANCION[$s['tipo']] . ') del ' . date('d/m/Y', strtotime($s['fecha']))
             . " de {$s['nombre']}. Motivo: $motivo";
    $stmt = $conn->prepare("SELECT padre_id FROM padre_alumno WHERE alumno_id = ?");
    $stmt->bind_param('i', $alumno_id);
    $stmt->execute();
    $padres = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($padres as $p) {
        crear_notificacion($conn, (int) $p['padre_id'], $alumno_id, 'sancion', $mensaje);
    }
    $stmt->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    throw $e;
}
$conn->close();

echo json_encode([
    'success' => true,
    'message' => $padres ? 'Sanción anulada. Se avisó a la familia.' : 'Sanción anulada (el alumno no tiene padre/tutor vinculado).',
]);
