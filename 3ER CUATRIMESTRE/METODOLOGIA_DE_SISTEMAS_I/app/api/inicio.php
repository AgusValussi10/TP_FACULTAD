<?php

/**
 * GET ?alumno_id= → resumen de la pantalla de inicio: deuda facturada, próximo
 * vencimiento, pagos en curso, inscripciones activas y últimas notificaciones.
 * El alumno ve lo suyo (sin alumno_id); el padre, solo a sus hijos.
 */

require_once __DIR__ . '/_base.php';

requerir_metodo('GET');
$sesion = api_sesion();
require_once __DIR__ . '/../../database/db_config.php';
$alumno_id = api_alumno_permitido($conn, $sesion, $_GET['alumno_id'] ?? 0);

$deuda = deuda_de_alumno($conn, $alumno_id);
$deuda['proximo_vencimiento_ar'] = $deuda['proximo_vencimiento'] ? date('d/m/Y', strtotime($deuda['proximo_vencimiento'])) : null;

$stmt = $conn->prepare(
    "SELECT d.nombre, d.horario, u.nombre AS profesor
     FROM inscripciones_deportivas i JOIN deportes d ON d.id = i.deporte_id
     LEFT JOIN usuarios u ON u.id = d.profesor_id
     WHERE i.alumno_id = ? ORDER BY d.nombre"
);
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$deportes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Servicios del mes en curso y del siguiente (las reservas son mensuales).
$hoy = new DateTime('first day of this month');
$sig = (clone $hoy)->modify('+1 month');
$indice_hoy = (int) $hoy->format('Y') * 12 + (int) $hoy->format('n');
$indice_sig = (int) $sig->format('Y') * 12 + (int) $sig->format('n');
$stmt = $conn->prepare(
    "SELECT r.servicio, r.mes, r.anio, rt.nombre AS recorrido
     FROM reservas_servicios r LEFT JOIN recorridos_transporte rt ON rt.id = r.recorrido_id
     WHERE r.alumno_id = ? AND (r.anio * 12 + r.mes) BETWEEN ? AND ?
     ORDER BY r.anio, r.mes, r.servicio"
);
$stmt->bind_param('iii', $alumno_id, $indice_hoy, $indice_sig);
$stmt->execute();
$servicios = array_map(fn ($s) => [
    'servicio' => $s['servicio'] === 'comedor' ? 'Comedor' : 'Transporte',
    'periodo'  => periodo_texto((int) $s['mes'], (int) $s['anio']),
    'detalle'  => $s['recorrido'],
], $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
$stmt->close();

$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM pagos p JOIN facturas f ON f.id = p.factura_id
     WHERE f.alumno_id = ? AND p.estado IN ('pendiente_transferencia', 'en_revision')"
);
$stmt->bind_param('i', $alumno_id);
$stmt->execute();
$pagos_en_curso = (int) $stmt->get_result()->fetch_row()[0];
$stmt->close();

// Notificaciones: el padre ve las suyas sobre ese hijo; el alumno, las que lo mencionan.
$sql = $sesion['rol'] === 'padre'
    ? "SELECT tipo, mensaje, leida, created_at FROM notificaciones WHERE alumno_id = ? AND padre_id = ? ORDER BY created_at DESC, id DESC LIMIT 5"
    : "SELECT tipo, mensaje, leida, created_at FROM notificaciones WHERE alumno_id = ? AND ? > 0 ORDER BY created_at DESC, id DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $alumno_id, $sesion['id']);
$stmt->execute();
$notificaciones = array_map(fn ($n) => [
    'tipo'    => $n['tipo'],
    'mensaje' => $n['mensaje'],
    'fecha'   => date('d/m/Y H:i', strtotime($n['created_at'])),
    'leida'   => (bool) $n['leida'],
], $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
$stmt->close();
$conn->close();

responder_json(200, true, [
    'deuda'          => $deuda,
    'pagos_en_curso' => $pagos_en_curso,
    'deportes'       => $deportes,
    'servicios'      => $servicios,
    'notificaciones' => $notificaciones,
]);
