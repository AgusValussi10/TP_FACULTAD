<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../database/db_config.php';

$res = $conn->query(
    "SELECT id, titulo, resumen, contenido, categoria, imagen_url,
            DATE_FORMAT(fecha_pub, '%d/%m/%Y') AS fecha_fmt
     FROM noticias
     WHERE estado = 'publicada'
     ORDER BY fecha_pub DESC, created_at DESC"
);

$items = [];
if ($res) {
    while ($r = $res->fetch_assoc()) $items[] = $r;
}
$conn->close();

echo json_encode($items);
