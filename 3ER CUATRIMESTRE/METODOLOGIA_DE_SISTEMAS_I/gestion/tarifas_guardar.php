<?php

/** POST admin (clave, valor) → fija el valor de una tarifa. Solo el admin fija valores (consigna). */

require_once __DIR__ . '/../lib/facturacion.php';

errores_como_json();
requerir_rol('admin');
requerir_metodo('POST');
require_once __DIR__ . '/../database/db_config.php';

$clave = trim($_POST['clave'] ?? '');
$valor = round((float) str_replace(',', '.', (string) ($_POST['valor'] ?? '0')), 2);

try {
    guardar_tarifa($conn, $clave, $valor, (int) $_SESSION['usuario_id']);
    responder_json(200, true, ['clave' => $clave, 'valor' => $valor], 'Tarifa guardada.');
} catch (ReglaNegocioException $e) {
    responder_json($e->getCode(), false, null, $e->getMessage());
}
$conn->close();
