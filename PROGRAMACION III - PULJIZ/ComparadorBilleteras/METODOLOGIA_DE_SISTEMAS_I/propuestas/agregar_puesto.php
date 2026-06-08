<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Acceso denegado.']); exit; }
require_once '../database/db_config.php';

$titulo     = trim($_POST['titulo']      ?? '');
$descripcion= trim($_POST['descripcion'] ?? '');
$tipo       = trim($_POST['tipo']        ?? 'Tiempo completo');
$urgente    = ($_POST['urgente'] ?? '0') === '1' ? 1 : 0;

if (strlen($titulo) < 3)      { echo json_encode(['success'=>false,'message'=>'Ingresá un título válido.']); exit; }
if (strlen($descripcion) < 10){ echo json_encode(['success'=>false,'message'=>'Ingresá una descripción.']);  exit; }
if (!in_array($tipo, ['Tiempo completo','Part-time','Medio turno'])) $tipo = 'Tiempo completo';

$stmt = $conn->prepare("INSERT INTO puestos_vacantes (titulo, descripcion, tipo, urgente) VALUES (?, ?, ?, ?)");
$stmt->bind_param('sssi', $titulo, $descripcion, $tipo, $urgente);
if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'id'=>$conn->insert_id, 'titulo'=>$titulo, 'descripcion'=>$descripcion, 'tipo'=>$tipo, 'urgente'=>$urgente]);
} else {
    echo json_encode(['success'=>false,'message'=>'Error al guardar.']);
}
$stmt->close(); $conn->close();
