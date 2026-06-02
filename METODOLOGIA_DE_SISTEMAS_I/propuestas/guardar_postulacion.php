<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../database/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$puesto_id = (int) ($_POST['puesto_id'] ?? 0);
$nombre    = trim($_POST['nombre']    ?? '');
$apellido  = trim($_POST['apellido']  ?? '');
$dni       = trim($_POST['dni']       ?? '');
$email     = trim($_POST['email']     ?? '');
$telefono  = trim($_POST['telefono']  ?? '');
$exp_anios = (int) ($_POST['experiencia_anios'] ?? 0);
$exp_desc  = trim($_POST['experiencia_descripcion'] ?? '');

if ($puesto_id <= 0)          { echo json_encode(['success'=>false,'message'=>'Puesto no válido.']);              exit; }
if (strlen($nombre)   < 2)    { echo json_encode(['success'=>false,'message'=>'Ingresá tu nombre.']);             exit; }
if (strlen($apellido) < 2)    { echo json_encode(['success'=>false,'message'=>'Ingresá tu apellido.']);           exit; }
if (strlen($dni)      < 7)    { echo json_encode(['success'=>false,'message'=>'DNI inválido.']);                  exit; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success'=>false,'message'=>'Email inválido.']); exit; }
if (strlen($telefono) < 7)    { echo json_encode(['success'=>false,'message'=>'Teléfono inválido.']);             exit; }
if (strlen($exp_desc) < 20)   { echo json_encode(['success'=>false,'message'=>'Describí tu experiencia (mínimo 20 caracteres).']); exit; }

$stmt = $conn->prepare(
    "INSERT INTO postulaciones (puesto_id, nombre, apellido, dni, email, telefono, experiencia_anios, experiencia_descripcion)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('isssssss', $puesto_id, $nombre, $apellido, $dni, $email, $telefono, $exp_anios, $exp_desc);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => '¡Postulación enviada! Nos comunicaremos a la brevedad.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar. Intentá nuevamente.']);
}
$stmt->close();
$conn->close();
