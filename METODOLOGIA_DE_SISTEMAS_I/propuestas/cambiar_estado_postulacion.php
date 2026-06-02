<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Acceso denegado.']); exit; }
require_once '../database/db_config.php';

$id     = (int) ($_POST['id']     ?? 0);
$estado = trim($_POST['estado']   ?? '');
if ($id <= 0 || !in_array($estado, ['pendiente','revisado','seleccionado','rechazado'])) {
    echo json_encode(['success'=>false,'message'=>'Datos inválidos.']); exit;
}
$stmt = $conn->prepare("UPDATE postulaciones SET estado = ? WHERE id = ?");
$stmt->bind_param('si', $estado, $id);
echo json_encode(['success' => $stmt->execute()]);
$stmt->close(); $conn->close();
