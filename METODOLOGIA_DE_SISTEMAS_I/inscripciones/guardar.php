<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../database/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$nombre    = trim($_POST['nombre_alumno']    ?? '');
$apellido  = trim($_POST['apellido_alumno']  ?? '');
$fecha     = trim($_POST['fecha_nacimiento'] ?? '');
$nivel     = trim($_POST['nivel_educativo']  ?? '');
$tutor     = trim($_POST['nombre_tutor']     ?? '');
$telefono  = trim($_POST['telefono']         ?? '');
$email     = trim($_POST['email']            ?? '');
$comentarios = trim($_POST['comentarios']    ?? '');

// Validar campos obligatorios
if (!$nombre || !$apellido || !$fecha || !$nivel || !$tutor || !$telefono || !$email) {
    echo json_encode(['success' => false, 'message' => 'Completá todos los campos obligatorios.']);
    exit;
}

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El correo electrónico no es válido.']);
    exit;
}

// Validar nivel
$niveles_validos = ['Inicial', 'Primario', 'Secundario'];
if (!in_array($nivel, $niveles_validos, true)) {
    echo json_encode(['success' => false, 'message' => 'Nivel educativo no válido.']);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO solicitudes_inscripcion
        (nombre_alumno, apellido_alumno, fecha_nacimiento, nivel_educativo, nombre_tutor, telefono, email, comentarios)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('ssssssss', $nombre, $apellido, $fecha, $nivel, $tutor, $telefono, $email, $comentarios);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => '¡Solicitud enviada! Nos comunicaremos con usted en breve.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la solicitud. Intentá nuevamente.']);
}

$stmt->close();
$conn->close();
