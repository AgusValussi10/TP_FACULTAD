<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }
require_once '../database/db_config.php';

$stats = ['total'=>0,'pendiente'=>0,'leida'=>0,'respondida'=>0,'archivada'=>0];
$res = $conn->query("SELECT estado, COUNT(*) AS cnt FROM consultas GROUP BY estado");
if ($res) while ($r = $res->fetch_assoc()) { $stats[$r['estado']] = (int)$r['cnt']; $stats['total'] += (int)$r['cnt']; }

$consultas = [];
$res2 = $conn->query(
    "SELECT id, nombre, email, asunto, mensaje, estado,
            DATE_FORMAT(created_at,'%d/%m/%Y %H:%i') AS fecha,
            UNIX_TIMESTAMP(created_at) AS ts
     FROM consultas
     ORDER BY FIELD(estado,'pendiente','leida','respondida','archivada'), created_at DESC"
);
if ($res2) while ($r = $res2->fetch_assoc()) $consultas[] = $r;
$conn->close();

header('Content-Type: application/json');
echo json_encode(['stats' => $stats, 'consultas' => $consultas]);
