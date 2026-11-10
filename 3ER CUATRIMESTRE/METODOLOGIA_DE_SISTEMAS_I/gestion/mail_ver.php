<?php

/**
 * GET admin ?id= → cuerpo HTML de un mail generado, para la vista previa (iframe con
 * sandbox). El HTML lo arma armar_mail() con todo el texto escapado; además la CSP no
 * deja ejecutar scripts ni cargar nada externo.
 */

require_once __DIR__ . '/helpers.php';

if (($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/../database/db_config.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT cuerpo_html FROM mails_enviados WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$html = $stmt->get_result()->fetch_row()[0] ?? null;
$stmt->close();
$conn->close();

if ($html === null) {
    http_response_code(404);
    exit;
}
header('Content-Type: text/html; charset=utf-8');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
header('X-Content-Type-Options: nosniff');
echo $html;
