<?php
require_once 'session.php';
header('Content-Type: application/json; charset=utf-8');

require_once '../database/db_config.php';

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
    'Docentes / Personal / Autoridades' => ['docente', 'admin'],
    'Padres / Tutores'                  => ['padre'],
    'Alumnos'                           => ['alumno'],
];

$roles_db = $roles_map[$rol_seleccionado] ?? null;

if ($roles_db === null) {
    echo json_encode(['success' => false, 'message' => 'Rol no válido.']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($roles_db), '?'));
$stmt = $conn->prepare(
    "SELECT id, nombre, password_hash, rol FROM usuarios WHERE usuario = ? AND rol IN ($placeholders) AND activo = 1"
);
$stmt->bind_param(str_repeat('s', 1 + count($roles_db)), $usuario, ...$roles_db);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos.']);
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos.']);
    $stmt->close();
    $conn->close();
    exit;
}

// Login exitoso
$_SESSION['usuario_id']  = $user['id'];
$_SESSION['nombre']      = $user['nombre'];
$_SESSION['rol']         = $user['rol'];

echo json_encode([
    'success' => true,
    'message' => '¡Bienvenido/a, ' . htmlspecialchars($user['nombre']) . '!',
    'rol'     => $user['rol'],
    'nombre'  => htmlspecialchars($user['nombre']),
]);

$stmt->close();
$conn->close();
