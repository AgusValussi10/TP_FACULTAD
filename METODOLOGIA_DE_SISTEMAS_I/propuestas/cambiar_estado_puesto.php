<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Acceso denegado.']); exit; }
require_once '../database/db_config.php';

$id     = (int) ($_POST['id']     ?? 0);
$activo = (int) ($_POST['activo'] ?? 0);
if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'ID inválido.']); exit; }

$stmt = $conn->prepare("UPDATE puestos_vacantes SET activo = ? WHERE id = ?");
$stmt->bind_param('ii', $activo, $id);
echo json_encode(['success' => $stmt->execute()]);
$stmt->close(); $conn->close();
