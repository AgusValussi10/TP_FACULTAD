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

$titulo     = trim($_POST['titulo']    ?? '');
$resumen    = trim($_POST['resumen']   ?? '');
$contenido  = trim($_POST['contenido'] ?? '');
$categoria  = trim($_POST['categoria'] ?? 'general');
$imagen_url = trim($_POST['imagen_url'] ?? '');
$estado     = trim($_POST['estado']    ?? 'borrador');
$fecha_pub  = trim($_POST['fecha_pub'] ?? '');

if (mb_strlen($titulo) < 3 || mb_strlen($titulo) > 200) {
    echo json_encode(['success' => false, 'message' => 'El título debe tener entre 3 y 200 caracteres.']);
    exit;
}
if (mb_strlen($contenido) < 10) {
    echo json_encode(['success' => false, 'message' => 'El contenido debe tener al menos 10 caracteres.']);
    exit;
}

$categorias_validas = ['institucional', 'academica', 'deportiva', 'cultural', 'general'];
if (!in_array($categoria, $categorias_validas)) $categoria = 'general';

$estados_validos = ['borrador', 'publicada', 'archivada'];
if (!in_array($estado, $estados_validos)) $estado = 'borrador';

if ($imagen_url !== '' && !filter_var($imagen_url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'La URL de imagen no es válida.']);
    exit;
}

$fecha_pub_value = null;
if ($fecha_pub !== '') {
    $d = DateTime::createFromFormat('Y-m-d', $fecha_pub);
    if ($d && $d->format('Y-m-d') === $fecha_pub) {
        $fecha_pub_value = $fecha_pub;
    }
}

$stmt = $conn->prepare(
    "INSERT INTO noticias (titulo, resumen, contenido, categoria, imagen_url, estado, fecha_pub)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('sssssss', $titulo, $resumen, $contenido, $categoria, $imagen_url, $estado, $fecha_pub_value);

if ($stmt->execute()) {
    $id = $conn->insert_id;
    $res = $conn->query(
        "SELECT id, titulo, resumen, contenido, categoria, imagen_url, estado,
                DATE_FORMAT(fecha_pub,'%d/%m/%Y') AS fecha_pub_fmt,
                DATE_FORMAT(created_at,'%d/%m/%Y %H:%i') AS fecha,
                UNIX_TIMESTAMP(created_at) AS ts
         FROM noticias WHERE id = $id"
    );
    $noticia = $res ? $res->fetch_assoc() : ['id' => $id];
    echo json_encode(['success' => true, 'noticia' => $noticia]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la noticia.']);
}

$stmt->close();
$conn->close();
