<?php
require_once '../auth/session.php';
if (($_SESSION['rol'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['error'=>'Forbidden']); exit; }
require_once '../database/db_config.php';

$postulaciones = [];
$res = $conn->query(
    "SELECT p.id, p.nombre, p.apellido, p.dni, p.email, p.telefono,
            p.experiencia_anios, p.experiencia_descripcion, p.estado,
            DATE_FORMAT(p.created_at,'%d/%m/%Y %H:%i') AS fecha,
            v.titulo AS puesto_titulo
     FROM postulaciones p
     JOIN puestos_vacantes v ON v.id = p.puesto_id
     ORDER BY FIELD(p.estado,'pendiente','revisado','seleccionado','rechazado'), p.created_at DESC"
);
if ($res) while ($r = $res->fetch_assoc()) $postulaciones[] = $r;
$conn->close();

header('Content-Type: application/json');
echo json_encode(['postulaciones' => $postulaciones]);
