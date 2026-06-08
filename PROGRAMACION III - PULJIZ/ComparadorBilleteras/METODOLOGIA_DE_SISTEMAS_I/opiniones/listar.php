<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../database/db_config.php';

$res = $conn->query(
    "SELECT id, nombre, texto, mes, anio
     FROM opiniones
     WHERE estado = 'aprobado'
     ORDER BY anio DESC, mes DESC, created_at DESC"
);

$items = [];
if ($res) {
    while ($r = $res->fetch_assoc()) $items[] = $r;
}
$conn->close();

echo json_encode($items);
