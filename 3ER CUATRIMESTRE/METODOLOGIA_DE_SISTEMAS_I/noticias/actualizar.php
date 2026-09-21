<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once '../database/db_config.php';
require_once 'validar_noticia.php';

$id         = (int)($_POST['id'] ?? 0);
$titulo     = trim($_POST['titulo']    ?? '');
$resumen    = trim($_POST['resumen']   ?? '');
$contenido  = trim($_POST['contenido'] ?? '');
$categoria  = trim($_POST['categoria'] ?? 'general');
$imagen_url = trim($_POST['imagen_url'] ?? '');
$estado     = trim($_POST['estado']    ?? 'borrador');
$fecha_pub  = trim($_POST['fecha_pub'] ?? '');

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit;
}

$validacion = validarNoticia(compact('titulo', 'contenido', 'categoria', 'estado', 'imagen_url', 'fecha_pub'));
if ($validacion['error']) {
    echo json_encode(['success' => false, 'message' => $validacion['error']]);
    exit;
}
$categoria        = $validacion['categoria'];
$estado           = $validacion['estado'];
$fecha_pub_value  = $validacion['fecha_pub_value'];

$stmt = $conn->prepare(
    "UPDATE noticias
     SET titulo=?, resumen=?, contenido=?, categoria=?, imagen_url=?, estado=?, fecha_pub=?
     WHERE id=?"
);
$stmt->bind_param('sssssssi', $titulo, $resumen, $contenido, $categoria, $imagen_url, $estado, $fecha_pub_value, $id);

if ($stmt->execute() && $stmt->affected_rows >= 0) {
    echo json_encode([
        'success' => true,
        'noticia' => [
            'id'         => $id,
            'titulo'     => $titulo,
            'resumen'    => $resumen,
            'contenido'  => $contenido,
            'categoria'  => $categoria,
            'imagen_url' => $imagen_url,
            'estado'     => $estado,
            'fecha_pub'  => $fecha_pub_value,
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la noticia.']);
}

$stmt->close();
$conn->close();
