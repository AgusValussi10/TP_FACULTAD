<?php
require_once 'session.php';
header('Content-Type: application/json; charset=utf-8');
require_once '../database/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$accion = $_POST['accion'] ?? '';

if ($accion === 'verificar') {
    $usuario = trim($_POST['usuario'] ?? '');
    $nombre  = trim($_POST['nombre'] ?? '');

    if (!$usuario || !$nombre) {
        echo json_encode(['success' => false, 'message' => 'Completá todos los campos.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? AND nombre = ? AND activo = 1");
    $stmt->bind_param('ss', $usuario, $nombre);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Los datos no coinciden con ningún usuario registrado.']);
        $stmt->close();
        $conn->close();
        exit;
    }

    $_SESSION['recuperar_usuario'] = $usuario;

    echo json_encode(['success' => true]);
    $stmt->close();
    $conn->close();

} elseif ($accion === 'cambiar') {
    $usuario   = trim($_POST['usuario'] ?? '');
    $nueva_pwd = $_POST['nueva_password'] ?? '';

    if (empty($_SESSION['recuperar_usuario']) || $_SESSION['recuperar_usuario'] !== $usuario) {
        echo json_encode(['success' => false, 'message' => 'Sesión de recuperación inválida. Volvé a verificar tu identidad.']);
        exit;
    }

    if (strlen($nueva_pwd) < 6) {
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']);
        exit;
    }

    $hash = password_hash($nueva_pwd, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE usuarios SET password_hash = ? WHERE usuario = ? AND activo = 1");
    $stmt->bind_param('ss', $hash, $usuario);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la contraseña.']);
        $stmt->close();
        $conn->close();
        exit;
    }

    unset($_SESSION['recuperar_usuario']);
    echo json_encode(['success' => true, 'message' => '¡Contraseña actualizada correctamente!']);
    $stmt->close();
    $conn->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
}
