<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

header('Content-Type: application/json');

require_once '../database/db_config.php';

$stats = ['total' => 0, 'borrador' => 0, 'publicada' => 0, 'archivada' => 0];
$res = $conn->query("SELECT estado, COUNT(*) AS cnt FROM noticias GROUP BY estado");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $stats[$r['estado']] = (int)$r['cnt'];
        $stats['total'] += (int)$r['cnt'];
    }
}

$noticias = [];
$res2 = $conn->query(
    "SELECT id, titulo, resumen, contenido, categoria, imagen_url, estado,
            DATE_FORMAT(fecha_pub,'%d/%m/%Y') AS fecha_pub_fmt,
            DATE_FORMAT(created_at,'%d/%m/%Y %H:%i') AS fecha,
            UNIX_TIMESTAMP(created_at) AS ts
     FROM noticias
     ORDER BY FIELD(estado,'publicada','borrador','archivada'), created_at DESC"
);
if ($res2) {
    while ($r = $res2->fetch_assoc()) $noticias[] = $r;
}

$conn->close();
echo json_encode(['success' => true, 'stats' => $stats, 'noticias' => $noticias]);
