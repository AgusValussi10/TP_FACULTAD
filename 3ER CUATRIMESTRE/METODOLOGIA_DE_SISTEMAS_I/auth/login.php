<?php

require_once 'session.php';
header('Content-Type: application/json; charset=utf-8');

require_once '../database/db_config.php';
require_once __DIR__ . '/autenticar.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';
$rol_seleccionado = $_POST['rol'] ?? '';

if ($usuario === '' || $password === '' || $rol_seleccionado === '') {
    echo json_encode(['success' => false, 'message' => 'Completá todos los campos.']);
    exit;
}

// Mapear la opción del select a los roles válidos en la base de datos.
// "Docentes / Personal / Autoridades" incluye también al administrador (rol oculto).
$roles_map = [
    'Docentes / Personal / Autoridades' => ['docente', 'admin', 'enfermeria'],
    'Padres / Tutores'                  => ['padre'],
    'Alumnos'                           => ['alumno'],
];

$roles_db = $roles_map[$rol_seleccionado] ?? null;

if ($roles_db === null) {
    echo json_encode(['success' => false, 'message' => 'Rol no válido.']);
    exit;
}

// Misma verificación que la app (auth/autenticar.php). En la web cualquier
// rechazo muestra el mensaje genérico, como antes.
$auth = autenticar_usuario($conn, $usuario, $password, $roles_db);
if ($auth['estado'] !== 'ok') {
    echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos.']);
    $conn->close();
    exit;
}

$user = $auth['usuario'];

$_SESSION['usuario_id']  = $user['id'];
$_SESSION['nombre']      = $user['nombre'];
$_SESSION['rol']         = $user['rol'];

echo json_encode([
    'success' => true,
    'message' => '¡Bienvenido/a, ' . htmlspecialchars($user['nombre']) . '!',
    'rol'     => $user['rol'],
    'nombre'  => htmlspecialchars($user['nombre']),
]);

$conn->close();
