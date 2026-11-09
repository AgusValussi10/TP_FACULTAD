<?php

/**
 * seed_parte3.php — Datos de demo de la Parte 3 (app móvil): tarifas, profesor
 * responsable por deporte, inscripciones y reservas de ejemplo, facturas de los
 * últimos 3 meses (mes-2, mes-1 y el actual) y pagos en distintos estados.
 *
 * Se corre después de seed_cuotas.php (usa sus cuotas). Idempotente:
 * - tarifas, inscripciones y reservas: INSERT IGNORE (no pisa lo que cargó el admin);
 * - facturas: generar_facturacion() no toca las que ya existen;
 * - pagos: solo se crean en facturas que todavía no tienen ninguno.
 *
 * Por consola:  cd database && php seed_parte3.php
 * Por URL (producción, una sola vez): /database/seed_parte3.php con sesión de admin.
 *
 * Demo: laura.martinez queda con DOS hijos (ana.garcia y matias.vega);
 * diego.fernandez sigue con uno (carlos.lopez).
 */

if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/../auth/session.php';
    if (($_SESSION['rol'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Solo el administrador puede cargar los datos de demo.');
    }
    session_write_close();
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/../lib/facturacion.php';
require_once __DIR__ . '/../lib/fpdf/fpdf.php';

/** id de un usuario de demo (0 si no existe). */
function id_usuario(mysqli $conn, string $usuario): int
{
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    $id = (int) ($stmt->get_result()->fetch_row()[0] ?? 0);
    $stmt->close();
    return $id;
}

/** Primer día del mes desplazado $offset meses respecto del actual. */
function mes_relativo(int $offset): DateTime
{
    $d = new DateTime('first day of this month');
    $d->modify("$offset month");
    return $d;
}

// ── 1. Tarifas (valores de demo; el admin los cambia en gestion/tarifas.php) ──
$tarifas = [
    'cuota:Inicial' => 38000, 'cuota:Primario' => 42000, 'cuota:Secundario' => 45000,
    'matricula:Inicial' => 50000, 'matricula:Primario' => 55000, 'matricula:Secundario' => 60000,
    'comedor' => 32000,
    'transporte:1' => 15000, 'transporte:2' => 18000, 'transporte:3' => 20000, 'transporte:4' => 24000,
];
$precioDeporte = ['Atletismo' => 9000, 'Natación' => 14000, 'Fútbol' => 10000, 'Artes Marciales' => 12000, 'Vóleibol' => 9500, 'Danza' => 11000];
$profesor      = ['Atletismo' => 'roberto.silva', 'Natación' => 'patricia.aguirre', 'Fútbol' => 'ernesto.castillo',
                  'Artes Marciales' => 'roberto.silva', 'Vóleibol' => 'ernesto.castillo', 'Danza' => 'maria.rodriguez'];

$deportes = [];
foreach ($conn->query("SELECT id, nombre FROM deportes")->fetch_all(MYSQLI_ASSOC) as $d) {
    $deportes[$d['nombre']] = (int) $d['id'];
}
foreach ($precioDeporte as $nombre => $precio) {
    if (isset($deportes[$nombre])) {
        $tarifas['deporte:' . $deportes[$nombre]] = $precio;
    }
}
$ins = $conn->prepare("INSERT IGNORE INTO tarifas (clave, valor, actualizado_en) VALUES (?, ?, NOW())");
foreach ($tarifas as $clave => $valor) {
    $v = (float) $valor;
    $ins->bind_param('sd', $clave, $v);
    $ins->execute();
}
$ins->close();

// ── 2. Profesor responsable de cada deporte (solo si no tiene) ──
$upd = $conn->prepare("UPDATE deportes SET profesor_id = ? WHERE nombre = ? AND profesor_id IS NULL");
foreach ($profesor as $deporte => $docente) {
    $docente_id = id_usuario($conn, $docente);
    if ($docente_id) {
        $upd->bind_param('is', $docente_id, $deporte);
        $upd->execute();
    }
}
$upd->close();

// ── 3. Familias, inscripciones y reservas de demo ──
$ana    = id_usuario($conn, 'ana.garcia');
$matias = id_usuario($conn, 'matias.vega');
$carlos = id_usuario($conn, 'carlos.lopez');
$laura  = id_usuario($conn, 'laura.martinez');
$diego  = id_usuario($conn, 'diego.fernandez');
$admin  = id_usuario($conn, 'admin');

if ($laura && $matias) {
    $conn->query("INSERT IGNORE INTO padre_alumno (padre_id, alumno_id) VALUES ($laura, $matias)");
}

// Inscriptos hace 4 meses: se facturan en los 3 meses de demo.
$desde = mes_relativo(-4)->format('Y-m-d 10:00:00');
$ins = $conn->prepare("INSERT IGNORE INTO inscripciones_deportivas (alumno_id, deporte_id, created_at) VALUES (?, ?, ?)");
foreach ([[$ana, 'Natación'], [$ana, 'Vóleibol'], [$matias, 'Fútbol'], [$carlos, 'Danza']] as [$alumno, $deporte]) {
    if ($alumno && isset($deportes[$deporte])) {
        $ins->bind_param('iis', $alumno, $deportes[$deporte], $desde);
        $ins->execute();
    }
}
$ins->close();

// Reservas de transporte viejas sin recorrido (anteriores a la Parte 3): se les asigna el 1.
$conn->query("UPDATE reservas_servicios SET recorrido_id = 1 WHERE servicio = 'transporte' AND recorrido_id IS NULL");

$ins = $conn->prepare(
    "INSERT IGNORE INTO reservas_servicios (alumno_id, servicio, mes, anio, estado, recorrido_id) VALUES (?, ?, ?, ?, 'confirmada', ?)"
);
$reservas = [[$ana, 'comedor', null], [$ana, 'transporte', 2], [$matias, 'comedor', null], [$carlos, 'transporte', 4]];
for ($offset = -2; $offset <= 1; $offset++) {
    $periodo = mes_relativo($offset);
    $mes  = (int) $periodo->format('n');
    $anio = (int) $periodo->format('Y');
    foreach ($reservas as [$alumno, $servicio, $recorrido]) {
        if ($alumno) {
            $ins->bind_param('isiii', $alumno, $servicio, $mes, $anio, $recorrido);
            $ins->execute();
        }
    }
}
$ins->close();

// ── 4. Facturas de mes-2, mes-1 y el mes actual ──
$generadas = 0;
foreach ([-2, -1, 0] as $offset) {
    $periodo = mes_relativo($offset);
    $r = generar_facturacion($conn, (int) $periodo->format('n'), (int) $periodo->format('Y'), $admin ?: null);
    $generadas += $r['creadas'];
    foreach ($r['avisos'] as $aviso) {
        echo "Aviso: $aviso" . PHP_EOL;
    }
}

// ── 5. Pagos de demo (solo en facturas sin pagos) ──

/** PDF chico que hace de comprobante de transferencia de demo. */
function comprobante_demo(string $numero, float $monto): string
{
    $pdf = new FPDF('P', 'mm', 'A5');
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Comprobante de transferencia (DEMO)', 0, 1);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 8, 'Referencia: ' . $numero, 0, 1);
    $pdf->Cell(0, 8, 'Importe: $ ' . number_format($monto, 2, ',', '.'), 0, 1);
    $pdf->Cell(0, 8, 'Fecha: ' . date('d/m/Y'), 0, 1);
    return $pdf->Output('S');
}

/**
 * Crea un pago con los ítems pendientes de la factura del alumno en ese mes
 * (filtrados por tipo si se indica) y lo deja en el estado pedido.
 */
function pago_demo(mysqli $conn, int $alumno, int $padre, int $admin, int $offset, string $estado, array $tipos = []): void
{
    $periodo = mes_relativo($offset);
    $mes  = (int) $periodo->format('n');
    $anio = (int) $periodo->format('Y');
    $stmt = $conn->prepare(
        "SELECT f.id FROM facturas f WHERE f.alumno_id = ? AND f.mes = ? AND f.anio = ?
         AND NOT EXISTS (SELECT 1 FROM pagos p WHERE p.factura_id = f.id)"
    );
    $stmt->bind_param('iii', $alumno, $mes, $anio);
    $stmt->execute();
    $factura_id = (int) ($stmt->get_result()->fetch_row()[0] ?? 0);
    $stmt->close();
    if (!$factura_id) {
        return;
    }
    $items = $conn->query("SELECT id, tipo, importe FROM factura_items WHERE factura_id = $factura_id AND estado = 'pendiente'")->fetch_all(MYSQLI_ASSOC);
    if ($tipos) {
        $items = array_filter($items, fn ($it) => in_array($it['tipo'], $tipos, true));
    }
    if (!$items) {
        return;
    }
    $pago_id = crear_pago($conn, $factura_id, $padre, array_column($items, 'id'));
    if ($estado === 'pendiente_transferencia') {
        return;
    }
    $total = round(array_sum(array_column($items, 'importe')), 2);
    $numero = numero_pago($pago_id);
    registrar_comprobante($conn, $pago_id, $padre, "transferencia-$numero.pdf", 'application/pdf', comprobante_demo($numero, $total), $total, date('Y-m-d'));
    if ($estado === 'aprobado') {
        aprobar_pago($conn, $pago_id, $admin);
    } elseif ($estado === 'rechazado') {
        rechazar_pago($conn, $pago_id, $admin, 'El comprobante no se lee bien. Volvé a subirlo, por favor.');
    }
}

if ($ana && $laura && $admin) {
    pago_demo($conn, $ana, $laura, $admin, -2, 'aprobado');                              // mes-2: pagada
    pago_demo($conn, $ana, $laura, $admin, -1, 'aprobado', ['deporte']);                 // mes-1: parcial
    pago_demo($conn, $ana, $laura, $admin, 0, 'en_revision', ['cuota', 'comedor']);      // mes actual: en revisión
}
if ($matias && $laura) {
    pago_demo($conn, $matias, $laura, $admin, 0, 'pendiente_transferencia', ['cuota']); // emitido, falta transferir
}
if ($carlos && $diego && $admin) {
    pago_demo($conn, $carlos, $diego, $admin, -1, 'rechazado');                          // rechazado con motivo
    pago_demo($conn, $carlos, $diego, $admin, 0, 'en_revision');                         // en revisión
}

$facturas = (int) $conn->query("SELECT COUNT(*) FROM facturas")->fetch_row()[0];
$pagos    = (int) $conn->query("SELECT COUNT(*) FROM pagos")->fetch_row()[0];
$conn->close();

echo "Listo: $generadas facturas nuevas ($facturas en total), $pagos pagos de demo." . PHP_EOL;
