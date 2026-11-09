<?php

/**
 * GET ?id= → devuelve el archivo de un comprobante de transferencia.
 * Admin: cualquiera. Padre: solo los de pagos de sus hijos (403 si no, exista o no).
 * Parte 3 (RFGm06).
 */

require_once __DIR__ . '/../lib/facturacion.php';

errores_como_json();
requerir_rol(['admin', 'padre']);
requerir_metodo('GET');
require_once __DIR__ . '/../database/db_config.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare(
    "SELECT c.nombre_archivo, c.mime, c.archivo, f.alumno_id
     FROM comprobantes_transferencia c
     JOIN pagos p ON p.id = c.pago_id
     JOIN facturas f ON f.id = p.factura_id
     WHERE c.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$comp = $stmt->get_result()->fetch_assoc();
$stmt->close();

$es_padre = $_SESSION['rol'] === 'padre';
if (!$comp || ($es_padre && !alumno_pertenece_a_padre($conn, (int) $comp['alumno_id'], (int) $_SESSION['usuario_id']))) {
    responder_json($es_padre ? 403 : 404, false, null, $es_padre ? 'Acceso denegado.' : 'El comprobante no existe.');
    $conn->close();
    exit;
}
$conn->close();

// El mime se validó con finfo al subirlo (solo JPG, PNG o PDF) y el nombre se sanitizó;
// nosniff evita que el navegador lo reinterprete como otro tipo.
header('Content-Type: ' . $comp['mime']);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . $comp['nombre_archivo'] . '"');
header('Cache-Control: private, no-store');
echo $comp['archivo'];
