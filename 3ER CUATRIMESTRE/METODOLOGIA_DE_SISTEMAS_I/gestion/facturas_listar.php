<?php

/**
 * GET admin → facturas con sus ítems. Filtros: mes, anio, estado
 * (pendiente / parcial / pagada / vencida) y q (nombre del alumno). Parte 3.
 */

require_once __DIR__ . '/../lib/facturacion.php';

errores_como_json();
requerir_rol('admin');
requerir_metodo('GET');
require_once __DIR__ . '/../database/db_config.php';

$mes    = (int) ($_GET['mes'] ?? 0);
$anio   = (int) ($_GET['anio'] ?? 0);
$estado = (string) ($_GET['estado'] ?? '');
$q      = '%' . trim((string) ($_GET['q'] ?? '')) . '%';

$estado_db = array_key_exists($estado, ESTADOS_FACTURA) ? $estado : '';
$vencidas  = $estado === 'vencida' ? 1 : 0;
$stmt = $conn->prepare(
    "SELECT f.id, f.mes, f.anio, f.fecha_emision, f.fecha_vencimiento, f.total, f.pagado, f.estado,
            u.nombre AS alumno, c.nombre AS curso
     FROM facturas f
     JOIN usuarios u ON u.id = f.alumno_id
     LEFT JOIN alumno_curso ac ON ac.alumno_id = f.alumno_id
     LEFT JOIN cursos c ON c.id = ac.curso_id
     WHERE (? = 0 OR f.mes = ?) AND (? = 0 OR f.anio = ?) AND (? = '' OR f.estado = ?)
       AND (? = 0 OR (f.estado <> 'pagada' AND f.fecha_vencimiento < CURDATE()))
       AND u.nombre LIKE ?
     ORDER BY f.anio DESC, f.mes DESC, u.nombre
     LIMIT 300"
);
$stmt->bind_param('iiiissis', $mes, $mes, $anio, $anio, $estado_db, $estado_db, $vencidas, $q);
$stmt->execute();
$facturas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$items = [];
if ($facturas) {
    $ids = implode(',', array_map(fn ($f) => (int) $f['id'], $facturas));
    $res = $conn->query("SELECT id, factura_id, tipo, descripcion, importe, estado FROM factura_items WHERE factura_id IN ($ids) ORDER BY id");
    foreach ($res->fetch_all(MYSQLI_ASSOC) as $it) {
        $items[(int) $it['factura_id']][] = [
            'id'          => (int) $it['id'],
            'tipo'        => TIPOS_ITEM[$it['tipo']],
            'descripcion' => $it['descripcion'],
            'importe'     => (float) $it['importe'],
            'estado'      => $it['estado'],
        ];
    }
}

$hoy  = date('Y-m-d');
$data = array_map(fn ($f) => [
    'id'           => (int) $f['id'],
    'numero'       => numero_factura((int) $f['id']),
    'alumno'       => $f['alumno'],
    'curso'        => $f['curso'],
    'periodo'      => periodo_texto((int) $f['mes'], (int) $f['anio']),
    'emision'      => date('d/m/Y', strtotime($f['fecha_emision'])),
    'vencimiento'  => date('d/m/Y', strtotime($f['fecha_vencimiento'])),
    'vencida'      => $f['estado'] !== 'pagada' && $f['fecha_vencimiento'] < $hoy,
    'total'        => (float) $f['total'],
    'pagado'       => (float) $f['pagado'],
    'estado'       => $f['estado'],
    'estado_texto' => ESTADOS_FACTURA[$f['estado']],
    'items'        => $items[(int) $f['id']] ?? [],
], $facturas);

responder_json(200, true, $data);
$conn->close();
