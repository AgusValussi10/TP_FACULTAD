<?php

/** GET admin (?estado=en_revision por defecto; vacío = todos) → pagos con ítems y comprobantes (sin el archivo). Parte 3. */

require_once __DIR__ . '/../lib/facturacion.php';

errores_como_json();
requerir_rol('admin');
requerir_metodo('GET');
require_once __DIR__ . '/../database/db_config.php';

$estado = (string) ($_GET['estado'] ?? 'en_revision');
$estado = array_key_exists($estado, ESTADOS_PAGO) ? $estado : '';

$stmt = $conn->prepare(
    "SELECT p.id, p.factura_id, p.total, p.estado, p.motivo_rechazo, p.created_at, p.revisado_en,
            f.mes, f.anio, a.nombre AS alumno, pa.nombre AS padre, r.nombre AS revisor
     FROM pagos p
     JOIN facturas f ON f.id = p.factura_id
     JOIN usuarios a ON a.id = f.alumno_id
     JOIN usuarios pa ON pa.id = p.padre_id
     LEFT JOIN usuarios r ON r.id = p.revisado_por
     WHERE (? = '' OR p.estado = ?)
     ORDER BY p.created_at DESC, p.id DESC
     LIMIT 200"
);
$stmt->bind_param('ss', $estado, $estado);
$stmt->execute();
$pagos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$items = [];
$comprobantes = [];
if ($pagos) {
    $ids = implode(',', array_map(fn ($p) => (int) $p['id'], $pagos));
    $res = $conn->query(
        "SELECT pi.pago_id, fi.descripcion, pi.importe FROM pago_items pi
         JOIN factura_items fi ON fi.id = pi.factura_item_id WHERE pi.pago_id IN ($ids) ORDER BY fi.id"
    );
    foreach ($res->fetch_all(MYSQLI_ASSOC) as $it) {
        $items[(int) $it['pago_id']][] = ['descripcion' => $it['descripcion'], 'importe' => (float) $it['importe']];
    }
    $res = $conn->query(
        "SELECT id, pago_id, nombre_archivo, mime, tamanio, monto_declarado, fecha_transferencia
         FROM comprobantes_transferencia WHERE pago_id IN ($ids) ORDER BY id"
    );
    foreach ($res->fetch_all(MYSQLI_ASSOC) as $c) {
        $comprobantes[(int) $c['pago_id']][] = [
            'id'     => (int) $c['id'],
            'nombre' => $c['nombre_archivo'],
            'mime'   => $c['mime'],
            'kb'     => (int) ceil($c['tamanio'] / 1024),
            'monto'  => (float) $c['monto_declarado'],
            'fecha'  => date('d/m/Y', strtotime($c['fecha_transferencia'])),
        ];
    }
}

$data = array_map(function ($p) use ($items, $comprobantes) {
    $comp = $comprobantes[(int) $p['id']] ?? [];
    return [
        'id'             => (int) $p['id'],
        'numero'         => numero_pago((int) $p['id']),
        'factura'        => numero_factura((int) $p['factura_id']),
        'periodo'        => periodo_texto((int) $p['mes'], (int) $p['anio']),
        'alumno'         => $p['alumno'],
        'padre'          => $p['padre'],
        'total'          => (float) $p['total'],
        'declarado'      => round(array_sum(array_column($comp, 'monto')), 2),
        'estado'         => $p['estado'],
        'estado_texto'   => ESTADOS_PAGO[$p['estado']],
        'motivo_rechazo' => $p['motivo_rechazo'],
        'creado'         => date('d/m/Y H:i', strtotime($p['created_at'])),
        'revisado'       => $p['revisado_en'] ? date('d/m/Y H:i', strtotime($p['revisado_en'])) . ' · ' . $p['revisor'] : null,
        'items'          => $items[(int) $p['id']] ?? [],
        'comprobantes'   => $comp,
    ];
}, $pagos);

responder_json(200, true, $data);
$conn->close();
