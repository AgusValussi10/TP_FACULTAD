<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json; charset=utf-8');
requerir_rol(['admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
require_once __DIR__ . '/../database/db_config.php';

$id       = (int)($_POST['id'] ?? 0);
$nombre   = trim($_POST['nombre'] ?? '');
$usuario  = trim($_POST['usuario'] ?? '');
$password = trim($_POST['password'] ?? '');
$curso_id = (int)($_POST['curso_id'] ?? 0);

if (!$nombre || !$usuario) {
    echo json_encode(['success' => false, 'message' => 'Nombre y usuario son obligatorios.']);
    exit;
}

if ($id === 0) {
    if (!$password) {
        echo json_encode(['success' => false, 'message' => 'La contraseña es obligatoria para crear un alumno.']);
        exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, password_hash, rol, activo) VALUES (?, ?, ?, 'alumno', 1)");
    $stmt->bind_param('sss', $nombre, $usuario, $hash);
    if (!$stmt->execute()) {
        $msg = $conn->errno === 1062 ? 'El nombre de usuario ya existe.' : 'Error al crear el alumno.';
        echo json_encode(['success' => false, 'message' => $msg]);
        $stmt->close(); $conn->close(); exit;
    }
    $nuevo_id = $conn->insert_id;
    $stmt->close();
    if ($curso_id > 0) {
        $stmt = $conn->prepare("INSERT IGNORE INTO alumno_curso (alumno_id, curso_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $nuevo_id, $curso_id);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['success' => true, 'message' => 'Alumno creado correctamente.', 'id' => $nuevo_id]);
} else {
    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, usuario=?, password_hash=? WHERE id=? AND rol='alumno'");
        $stmt->bind_param('sssi', $nombre, $usuario, $hash, $id);
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, usuario=? WHERE id=? AND rol='alumno'");
        $stmt->bind_param('ssi', $nombre, $usuario, $id);
    }
    if (!$stmt->execute()) {
        $msg = $conn->errno === 1062 ? 'El nombre de usuario ya existe.' : 'Error al actualizar el alumno.';
        echo json_encode(['success' => false, 'message' => $msg]);
        $stmt->close(); $conn->close(); exit;
    }
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM alumno_curso WHERE alumno_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    if ($curso_id > 0) {
        $stmt = $conn->prepare("INSERT INTO alumno_curso (alumno_id, curso_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $id, $curso_id);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['success' => true, 'message' => 'Alumno actualizado correctamente.']);
}
$conn->close();
