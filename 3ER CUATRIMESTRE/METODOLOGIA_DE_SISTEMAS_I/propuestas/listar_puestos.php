<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../database/db_config.php';

$res   = $conn->query("SELECT id, titulo, descripcion, tipo, urgente FROM puestos_vacantes WHERE activo = 1 ORDER BY urgente DESC, created_at ASC");
$items = [];
if ($res) while ($r = $res->fetch_assoc()) $items[] = $r;
$conn->close();
echo json_encode($items);
