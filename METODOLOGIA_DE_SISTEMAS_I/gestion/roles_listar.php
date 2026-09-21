<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('admin');

require_once __DIR__ . '/../database/db_config.php';

// RFG15: listado de usuarios con sus roles para auditoría de accesos.
$result = $conn->query(
    "SELECT id, nombre, usuario, rol, activo,
            DATE_FORMAT(created_at, '%d/%m/%Y') AS fecha_alta
     FROM usuarios
     ORDER BY rol, nombre"
);

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $row['activo'] = (bool) $row['activo'];
    $usuarios[] = $row;
}
$conn->close();

// Resumen por rol
$resumen = [];
foreach ($usuarios as $u) {
    $resumen[$u['rol']] = ($resumen[$u['rol']] ?? 0) + 1;
}

echo json_encode([
    'success'  => true,
    'usuarios' => $usuarios,
    'resumen'  => $resumen,
]);
