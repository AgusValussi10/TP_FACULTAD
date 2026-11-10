<?php

/** GET admin (?tipo=) → mails generados (sin el cuerpo), más nuevos primero. RFGm07/RFGm08. */

require_once __DIR__ . '/../lib/recordatorios.php';

errores_como_json();
requerir_rol('admin');
requerir_metodo('GET');
require_once __DIR__ . '/../database/db_config.php';

$tipo = (string) ($_GET['tipo'] ?? '');
$tipo = isset(TIPOS_MAIL[$tipo]) ? $tipo : '';
$stmt = $conn->prepare(
    "SELECT m.id, m.tipo, m.mes, m.anio, m.destinatario, m.asunto, m.adjuntos, m.total, m.estado, m.fecha_simulada, m.created_at,
            u.nombre AS padre
     FROM mails_enviados m JOIN usuarios u ON u.id = m.padre_id
     WHERE (? = '' OR m.tipo = ?) ORDER BY m.created_at DESC, m.id DESC LIMIT 200"
);
$stmt->bind_param('ss', $tipo, $tipo);
$stmt->execute();
$mails = array_map(fn ($m) => [
    'id'           => (int) $m['id'],
    'tipo'         => TIPOS_MAIL[$m['tipo']],
    'periodo'      => periodo_texto((int) $m['mes'], (int) $m['anio']),
    'padre'        => $m['padre'],
    'destinatario' => $m['destinatario'] ?: 'Sin email en el legajo',
    'asunto'       => $m['asunto'],
    'adjuntos'     => $m['adjuntos'] ? array_map(fn ($id) => ['id' => (int) $id, 'numero' => numero_factura((int) $id)], explode(',', $m['adjuntos'])) : [],
    'total'        => (float) $m['total'],
    'estado'       => $m['estado'],
    'fecha'        => date('d/m/Y', strtotime($m['fecha_simulada'])),
], $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
$stmt->close();
$conn->close();

responder_json(200, true, $mails);
