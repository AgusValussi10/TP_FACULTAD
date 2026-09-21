<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }
require_once '../database/db_config.php';

$stats = ['total'=>0,'pendiente'=>0,'aprobado'=>0,'rechazado'=>0];
$res = $conn->query("SELECT estado, COUNT(*) AS cnt FROM opiniones GROUP BY estado");
if ($res) while ($r = $res->fetch_assoc()) { $stats[$r['estado']] = (int)$r['cnt']; $stats['total'] += (int)$r['cnt']; }

$opiniones = [];
$res2 = $conn->query(
    "SELECT id, nombre, texto, mes, anio, estado,
            DATE_FORMAT(created_at,'%d/%m/%Y %H:%i') AS fecha,
            UNIX_TIMESTAMP(created_at) AS ts
     FROM opiniones
     ORDER BY FIELD(estado,'pendiente','aprobado','rechazado'), created_at DESC"
);
if ($res2) while ($r = $res2->fetch_assoc()) $opiniones[] = $r;
$conn->close();

header('Content-Type: application/json');
echo json_encode(['stats' => $stats, 'opiniones' => $opiniones]);
