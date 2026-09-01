<?php
require_once '../auth/session.php';
header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once '../database/db_config.php';

/**
 * Crea el usuario del alumno admitido, validando que el nombre de usuario no
 * exista todavía. Devuelve ['error' => null] si se creó correctamente, o
 * ['error' => string] con el motivo si algo falló.
 */
function crearUsuarioAlumno(mysqli $conn, string $usuario, string $password, string $nombre): array
{
    $check = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $check->bind_param('s', $usuario);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        return ['error' => 'El nombre de usuario ya existe. Elegí otro.'];
    }
    $check->close();

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $rol  = 'alumno';
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, password_hash, rol) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $nombre, $usuario, $hash, $rol);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['error' => 'Error al crear el usuario.'];
    }
    $stmt->close();

    return ['error' => null];
}

/** Marca la solicitud de inscripción como admitida. */
function marcarSolicitudAdmitida(mysqli $conn, int $id): void
{
    $upd = $conn->prepare("UPDATE solicitudes_inscripcion SET estado = 'admitido' WHERE id = ?");
    $upd->bind_param('i', $id);
    $upd->execute();
    $upd->close();
}

$id       = (int)($_POST['id']      ?? 0);
$usuario  = trim($_POST['usuario']  ?? '');
$password = trim($_POST['password'] ?? '');
$nombre   = trim($_POST['nombre']   ?? '');

if (!$id || !$usuario || !$password || !$nombre) {
    echo json_encode(['success' => false, 'message' => 'Completá todos los campos.']);
    exit;
}

if (strlen($password) < 4) {
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 4 caracteres.']);
    exit;
}

$resultadoUsuario = crearUsuarioAlumno($conn, $usuario, $password, $nombre);
if ($resultadoUsuario['error']) {
    echo json_encode(['success' => false, 'message' => $resultadoUsuario['error']]);
    $conn->close();
    exit;
}

marcarSolicitudAdmitida($conn, $id);

$conn->close();

echo json_encode(['success' => true, 'usuario' => $usuario]);
