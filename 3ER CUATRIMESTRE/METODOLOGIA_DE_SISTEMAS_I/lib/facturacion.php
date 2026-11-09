<?php

/**
 * Parte 3 (App móvil, MSII) — facturación mensual, pagos por transferencia y su
 * revisión. Es el único lugar con estas reglas: lo usan las pantallas del admin
 * (gestion/), la API de la app (app/api/), el seed y los tests.
 *
 * Reglas y asunciones (ver CLAUDE.md, "Parte 3"):
 * - Una factura por alumno y mes; vence el DIA_VENCIMIENTO_CUOTA (RN11).
 * - El ítem cuota/matrícula apunta a la fila de "cuotas": no se duplica la deuda.
 *   Cuando ese ítem se paga, la cuota se marca pagada (la web y la app muestran lo mismo).
 * - Recargo: solo sobre la cuota mensual impaga y vencida de un mes ANTERIOR; va como
 *   ítem en la factura siguiente y su importe es cuotas.recargo (si estaba en 0 se
 *   calcula una sola vez con calcular_recargo() y se guarda ahí). Nunca se factura dos veces.
 * - Deportes: se cobran desde el mes siguiente a la inscripción, mientras siga inscripto.
 * - Comedor con valor único; transporte según el recorrido reservado para el mes.
 * - Tarifas sin historial: el importe queda congelado en el ítem de la factura.
 * - 1 pago = 1 factura. Un ítem en un pago activo queda "en_revision" y no se puede volver a pagar.
 * - Las funciones que no abren transacción propia (marcar_cuota_pagada, recalcular_factura)
 *   pueden llamarse dentro de otra.
 */

require_once __DIR__ . '/../gestion/helpers.php';

const NIVELES_EDUCATIVOS = ['Inicial', 'Primario', 'Secundario'];

const TIPOS_ITEM = [
    'cuota'      => 'Cuota',
    'matricula'  => 'Matrícula',
    'deporte'    => 'Deporte',
    'comedor'    => 'Comedor',
    'transporte' => 'Transporte',
    'recargo'    => 'Recargo',
];

const ESTADOS_FACTURA = ['pendiente' => 'Pendiente', 'parcial' => 'Pago parcial', 'pagada' => 'Pagada'];

const ESTADOS_PAGO = [
    'pendiente_transferencia' => 'Pendiente de transferencia',
    'en_revision'             => 'En revisión',
    'aprobado'                => 'Aprobado',
    'rechazado'               => 'Rechazado',
    'anulado'                 => 'Anulado',
];

/** Error de una regla de negocio: el mensaje se le muestra al usuario; el código es el HTTP. */
class ReglaNegocioException extends RuntimeException
{
}

/** Respuesta JSON uniforme de los endpoints de la Parte 3: {success, data, message}. */
function responder_json(int $http, bool $success, $data = null, string $message = ''): void
{
    http_response_code($http);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message], JSON_UNESCAPED_UNICODE);
}

/** Corta con 405 si el método HTTP no es el esperado. */
function requerir_metodo(string $metodo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $metodo) {
        responder_json(405, false, null, 'Método no permitido.');
        exit;
    }
}

function numero_factura(int $id): string
{
    return sprintf('F-%06d', $id);
}

function numero_pago(int $id): string
{
    return sprintf('P-%06d', $id);
}

function formato_pesos(float $importe): string
{
    return '$ ' . number_format($importe, 2, ',', '.');
}

function periodo_texto(int $mes, int $anio): string
{
    return MESES[$mes - 1] . ' ' . $anio;
}

// ── Tarifas ─────────────────────────────────────────────────────────────

/** clave => valor de todas las tarifas cargadas. */
function tarifas_valores(mysqli $conn): array
{
    $valores = [];
    foreach ($conn->query("SELECT clave, valor FROM tarifas")->fetch_all(MYSQLI_ASSOC) as $t) {
        $valores[$t['clave']] = (float) $t['valor'];
    }
    return $valores;
}

/**
 * Todos los conceptos que llevan tarifa (salen de los catálogos: niveles,
 * deportes y recorridos), con su valor actual o null si falta cargarlo.
 */
function tarifas_catalogo(mysqli $conn): array
{
    $cargadas = [];
    foreach ($conn->query("SELECT clave, valor, actualizado_en FROM tarifas")->fetch_all(MYSQLI_ASSOC) as $t) {
        $cargadas[$t['clave']] = $t;
    }
    $filas = [];
    $agregar = function (string $clave, string $grupo, string $descripcion, string $detalle = '') use (&$filas, $cargadas) {
        $t = $cargadas[$clave] ?? null;
        $filas[] = [
            'clave'          => $clave,
            'grupo'          => $grupo,
            'descripcion'    => $descripcion,
            'detalle'        => $detalle,
            'valor'          => $t ? (float) $t['valor'] : null,
            'actualizado_en' => $t['actualizado_en'] ?? null,
        ];
    };

    foreach (NIVELES_EDUCATIVOS as $nivel) {
        $agregar("cuota:$nivel", 'Cuota', "Cuota mensual · $nivel");
        $agregar("matricula:$nivel", 'Matrícula', "Matrícula · $nivel");
    }
    $agregar('comedor', 'Comedor', 'Comedor (mensual, valor único)');
    $deportes = $conn->query(
        "SELECT d.id, d.nombre, d.horario, u.nombre AS profesor
         FROM deportes d LEFT JOIN usuarios u ON u.id = d.profesor_id ORDER BY d.nombre"
    )->fetch_all(MYSQLI_ASSOC);
    foreach ($deportes as $d) {
        $agregar('deporte:' . $d['id'], 'Deporte', $d['nombre'], $d['horario'] . ($d['profesor'] ? ' · Prof. ' . $d['profesor'] : ''));
    }
    $recorridos = $conn->query("SELECT id, nombre, descripcion FROM recorridos_transporte WHERE activo = 1 ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    foreach ($recorridos as $r) {
        $agregar('transporte:' . $r['id'], 'Transporte', $r['nombre'], $r['descripcion']);
    }
    return $filas;
}

/** Guarda el valor de una tarifa del catálogo (solo admin: lo valida el endpoint). */
function guardar_tarifa(mysqli $conn, string $clave, float $valor, int $admin_id): void
{
    if (!in_array($clave, array_column(tarifas_catalogo($conn), 'clave'), true)) {
        throw new ReglaNegocioException('Concepto de tarifa inválido.', 422);
    }
    if ($valor <= 0 || $valor > 9999999) {
        throw new ReglaNegocioException('El valor tiene que ser mayor a cero.', 422);
    }
    // La clave es la única clave de la tabla (PK), así que ON DUPLICATE KEY es seguro acá.
    $stmt = $conn->prepare(
        "INSERT INTO tarifas (clave, valor, actualizado_en, actualizado_por) VALUES (?, ?, NOW(), ?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor), actualizado_en = NOW(), actualizado_por = VALUES(actualizado_por)"
    );
    $stmt->bind_param('sdi', $clave, $valor, $admin_id);
    $stmt->execute();
    $stmt->close();
}

// ── Generación mensual ──────────────────────────────────────────────────

/**
 * Genera las facturas del mes para todos los alumnos activos con curso.
 * Idempotente: si el alumno ya tiene factura de ese mes, no se toca.
 *
 * @return array{creadas:int, existentes:int, sin_items:int, avisos:string[]}
 */
function generar_facturacion(mysqli $conn, int $mes, int $anio, ?int $usuario_id = null): array
{
    if ($mes < 1 || $mes > 12 || $anio < 2000 || $anio > 2100) {
        throw new ReglaNegocioException('Período inválido.', 422);
    }
    $tarifas = tarifas_valores($conn);
    $alumnos = $conn->query(
        "SELECT u.id, u.nombre, c.nivel_educativo
         FROM usuarios u
         JOIN alumno_curso ac ON ac.alumno_id = u.id
         JOIN cursos c ON c.id = ac.curso_id
         WHERE u.rol = 'alumno' AND u.activo = 1
         ORDER BY u.nombre"
    )->fetch_all(MYSQLI_ASSOC);

    $existe = $conn->prepare("SELECT id FROM facturas WHERE alumno_id = ? AND mes = ? AND anio = ?");
    $resultado = ['creadas' => 0, 'existentes' => 0, 'sin_items' => 0, 'avisos' => []];

    foreach ($alumnos as $alumno) {
        $alumno_id = (int) $alumno['id'];
        $existe->bind_param('iii', $alumno_id, $mes, $anio);
        $existe->execute();
        if ($existe->get_result()->fetch_row()) {
            $resultado['existentes']++;
            continue;
        }

        $conn->begin_transaction();
        try {
            $items = items_para_factura($conn, $alumno, $mes, $anio, $tarifas, $resultado['avisos']);
            if (!$items) {
                $conn->rollback();
                $resultado['sin_items']++;
                continue;
            }
            insertar_factura($conn, $alumno_id, $mes, $anio, $items, $usuario_id);
            $conn->commit();
            $resultado['creadas']++;
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            if ($e->getCode() !== 1062) {
                throw $e;
            }
            $resultado['existentes']++; // otro pedido la generó en paralelo
        }
    }
    $existe->close();
    $resultado['avisos'] = array_values(array_unique($resultado['avisos']));
    return $resultado;
}

function insertar_factura(mysqli $conn, int $alumno_id, int $mes, int $anio, array $items, ?int $usuario_id): int
{
    $emision = date('Y-m-d');
    $vence   = fecha_vencimiento_cuota($mes, $anio);
    $stmt = $conn->prepare(
        "INSERT INTO facturas (alumno_id, mes, anio, fecha_emision, fecha_vencimiento, generada_por)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('iiissi', $alumno_id, $mes, $anio, $emision, $vence, $usuario_id);
    $stmt->execute();
    $factura_id = (int) $conn->insert_id;
    $stmt->close();

    $ins = $conn->prepare(
        "INSERT INTO factura_items (factura_id, tipo, clave, cuota_id, deporte_id, recorrido_id, descripcion, importe, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($items as $it) {
        $ins->bind_param(
            'issiiisds',
            $factura_id,
            $it['tipo'],
            $it['clave'],
            $it['cuota_id'],
            $it['deporte_id'],
            $it['recorrido_id'],
            $it['descripcion'],
            $it['importe'],
            $it['estado']
        );
        $ins->execute();
    }
    $ins->close();
    recalcular_factura($conn, $factura_id);
    return $factura_id;
}

/** Ítem de factura con los campos por defecto. */
function nuevo_item(string $tipo, string $clave, string $descripcion, float $importe, array $refs = [], string $estado = 'pendiente'): array
{
    return [
        'tipo'         => $tipo,
        'clave'        => $clave,
        'descripcion'  => $descripcion,
        'importe'      => round($importe, 2),
        'cuota_id'     => $refs['cuota_id'] ?? null,
        'deporte_id'   => $refs['deporte_id'] ?? null,
        'recorrido_id' => $refs['recorrido_id'] ?? null,
        'estado'       => $estado,
    ];
}

/** Arma los ítems de la factura de un alumno (puede crear la cuota del mes o guardar su recargo). */
function items_para_factura(mysqli $conn, array $alumno, int $mes, int $anio, array $tarifas, array &$avisos): array
{
    $alumno_id = (int) $alumno['id'];
    $nivel     = $alumno['nivel_educativo'];
    $periodo   = periodo_texto($mes, $anio);
    $items     = [];

    // Cuota del mes (se crea en "cuotas" si el admin no la cargó) y matrícula, si hay.
    $cuota = cuota_del_mes($conn, $alumno_id, 'cuota', $mes, $anio);
    if (!$cuota && isset($tarifas["cuota:$nivel"])) {
        $cuota = crear_cuota($conn, $alumno_id, $mes, $anio, $tarifas["cuota:$nivel"]);
    } elseif (!$cuota) {
        $avisos[] = "Falta la tarifa de la cuota del nivel $nivel.";
    }
    if ($cuota) {
        $items[] = item_de_cuota('cuota', $cuota, "Cuota $periodo");
    }
    $matricula = cuota_del_mes($conn, $alumno_id, 'matricula', $mes, $anio);
    if ($matricula) {
        $items[] = item_de_cuota('matricula', $matricula, "Matrícula $periodo");
    }

    array_push($items, ...recargos_pendientes($conn, $alumno_id, $mes, $anio));

    // Deportes inscriptos antes del primer día del mes facturado.
    $inicio_mes = sprintf('%04d-%02d-01', $anio, $mes);
    $stmt = $conn->prepare(
        "SELECT d.id, d.nombre FROM inscripciones_deportivas i JOIN deportes d ON d.id = i.deporte_id
         WHERE i.alumno_id = ? AND i.created_at < ? ORDER BY d.nombre"
    );
    $stmt->bind_param('is', $alumno_id, $inicio_mes);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $d) {
        $clave = 'deporte:' . $d['id'];
        if (!isset($tarifas[$clave])) {
            $avisos[] = "Falta la tarifa del deporte {$d['nombre']}.";
            continue;
        }
        $items[] = nuevo_item('deporte', $clave, "Deporte · {$d['nombre']} ($periodo)", $tarifas[$clave], ['deporte_id' => (int) $d['id']]);
    }
    $stmt->close();

    // Comedor y transporte reservados para el mes.
    $stmt = $conn->prepare(
        "SELECT r.servicio, r.recorrido_id, rt.nombre AS recorrido
         FROM reservas_servicios r LEFT JOIN recorridos_transporte rt ON rt.id = r.recorrido_id
         WHERE r.alumno_id = ? AND r.mes = ? AND r.anio = ?"
    );
    $stmt->bind_param('iii', $alumno_id, $mes, $anio);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        if ($r['servicio'] === 'comedor') {
            if (isset($tarifas['comedor'])) {
                $items[] = nuevo_item('comedor', 'comedor', "Comedor ($periodo)", $tarifas['comedor']);
            } else {
                $avisos[] = 'Falta la tarifa del comedor.';
            }
            continue;
        }
        $clave = 'transporte:' . $r['recorrido_id'];
        if ($r['recorrido_id'] === null || !isset($tarifas[$clave])) {
            $avisos[] = $r['recorrido_id'] === null
                ? "{$alumno['nombre']}: reserva de transporte de $periodo sin recorrido (no se facturó)."
                : "Falta la tarifa del {$r['recorrido']}.";
            continue;
        }
        $items[] = nuevo_item('transporte', 'transporte', "Transporte · {$r['recorrido']} ($periodo)", $tarifas[$clave], ['recorrido_id' => (int) $r['recorrido_id']]);
    }
    $stmt->close();

    return $items;
}

function cuota_del_mes(mysqli $conn, int $alumno_id, string $concepto, int $mes, int $anio): ?array
{
    $stmt = $conn->prepare("SELECT id, importe, estado FROM cuotas WHERE alumno_id = ? AND concepto = ? AND mes = ? AND anio = ?");
    $stmt->bind_param('isii', $alumno_id, $concepto, $mes, $anio);
    $stmt->execute();
    $cuota = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $cuota ?: null;
}

function crear_cuota(mysqli $conn, int $alumno_id, int $mes, int $anio, float $importe): array
{
    $vence = fecha_vencimiento_cuota($mes, $anio);
    $stmt = $conn->prepare(
        "INSERT INTO cuotas (alumno_id, concepto, mes, anio, importe, fecha_vencimiento) VALUES (?, 'cuota', ?, ?, ?, ?)"
    );
    $stmt->bind_param('iiids', $alumno_id, $mes, $anio, $importe, $vence);
    $stmt->execute();
    $id = (int) $conn->insert_id;
    $stmt->close();
    return ['id' => $id, 'importe' => $importe, 'estado' => 'pendiente'];
}

/** Ítem que apunta a una fila de "cuotas"; si ya se cobró por la web, nace pagado. */
function item_de_cuota(string $tipo, array $cuota, string $descripcion): array
{
    $estado = $cuota['estado'] === 'pagada' ? 'pagado' : 'pendiente';
    return nuevo_item($tipo, $tipo . ':' . $cuota['id'], $descripcion, (float) $cuota['importe'], ['cuota_id' => (int) $cuota['id']], $estado);
}

/** Recargos de cuotas de meses anteriores impagas y vencidas que todavía no se facturaron. */
function recargos_pendientes(mysqli $conn, int $alumno_id, int $mes, int $anio): array
{
    $indice = $anio * 12 + $mes;
    $stmt = $conn->prepare(
        "SELECT c.id, c.mes, c.anio, c.importe, c.recargo, c.fecha_vencimiento
         FROM cuotas c
         WHERE c.alumno_id = ? AND c.concepto = 'cuota' AND c.estado = 'pendiente'
           AND c.fecha_vencimiento < CURDATE() AND (c.anio * 12 + c.mes) < ?
           AND NOT EXISTS (SELECT 1 FROM factura_items fi WHERE fi.tipo = 'recargo' AND fi.cuota_id = c.id)
         ORDER BY c.anio, c.mes"
    );
    $stmt->bind_param('ii', $alumno_id, $indice);
    $stmt->execute();
    $cuotas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $items = [];
    $guardar = $conn->prepare("UPDATE cuotas SET recargo = ? WHERE id = ?");
    foreach ($cuotas as $c) {
        $recargo = (float) $c['recargo'];
        if ($recargo <= 0) {
            // Única vez que se calcula: desde acá el número es el que guarda cuotas.recargo.
            $recargo = calcular_recargo((float) $c['importe'], $c['fecha_vencimiento']);
            $cuota_id = (int) $c['id'];
            $guardar->bind_param('di', $recargo, $cuota_id);
            $guardar->execute();
        }
        if ($recargo > 0) {
            $periodo = periodo_texto((int) $c['mes'], (int) $c['anio']);
            $items[] = nuevo_item('recargo', 'recargo:' . $c['id'], "Recargo por mora · Cuota $periodo", $recargo, ['cuota_id' => (int) $c['id']]);
        }
    }
    $guardar->close();
    return $items;
}

/** Recalcula total, pagado y estado de una factura a partir de sus ítems. */
function recalcular_factura(mysqli $conn, int $factura_id): void
{
    $stmt = $conn->prepare(
        "UPDATE facturas SET
            total  = (SELECT COALESCE(SUM(importe), 0) FROM factura_items WHERE factura_id = ?),
            pagado = (SELECT COALESCE(SUM(importe), 0) FROM factura_items WHERE factura_id = ? AND estado = 'pagado')
         WHERE id = ?"
    );
    $stmt->bind_param('iii', $factura_id, $factura_id, $factura_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
        "UPDATE facturas SET estado = CASE
            WHEN total > 0 AND pagado >= total THEN 'pagada'
            WHEN pagado > 0 THEN 'parcial'
            ELSE 'pendiente' END
         WHERE id = ?"
    );
    $stmt->bind_param('i', $factura_id);
    $stmt->execute();
    $stmt->close();
}

// ── Cuotas: sincronización web ↔ facturación ───────────────────────────

/**
 * Marca una cuota como pagada y sincroniza sus ítems de factura. La usan el
 * cobro del admin en la web (cuotas_pagar.php, $cobro_manual = true) y la
 * aprobación de un pago por transferencia ($cobro_manual = false).
 *
 * - Cobro manual: si la cuota está en un pago en revisión, se rechaza; el
 *   recargo es el guardado o, si no hay, el que corresponde a la fecha de pago
 *   (como antes); también se marca pagado el ítem de recargo, si lo hay.
 * - Transferencia: el recargo queda como está (se cobra en su propio ítem).
 *
 * @return float recargo que quedó en la cuota
 */
function marcar_cuota_pagada(
    mysqli $conn,
    int $cuota_id,
    string $fecha_pago,
    string $metodo,
    ?string $referencia,
    ?int $registrado_por,
    bool $cobro_manual
): float {
    $stmt = $conn->prepare("SELECT alumno_id, importe, recargo, fecha_vencimiento, estado FROM cuotas WHERE id = ? FOR UPDATE");
    $stmt->bind_param('i', $cuota_id);
    $stmt->execute();
    $cuota = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cuota) {
        throw new ReglaNegocioException('La cuota no existe.', 404);
    }
    if ($cuota['estado'] !== 'pendiente') {
        throw new ReglaNegocioException('Esa cuota ya fue registrada como pagada.', 409);
    }
    if ($cobro_manual && cuota_en_revision($conn, $cuota_id)) {
        throw new ReglaNegocioException('Esa cuota tiene un pago por transferencia en revisión: aprobalo o rechazalo en "Pagos a revisar".', 409);
    }

    $recargo = (float) $cuota['recargo'];
    if ($cobro_manual && $recargo <= 0) {
        $recargo = calcular_recargo((float) $cuota['importe'], $cuota['fecha_vencimiento'], $fecha_pago);
    }
    $upd = $conn->prepare(
        "UPDATE cuotas
         SET estado = 'pagada', recargo = ?, fecha_pago = ?, metodo_pago = ?, referencia_pago = ?, registrado_por = ?
         WHERE id = ? AND estado = 'pendiente'"
    );
    $upd->bind_param('dsssii', $recargo, $fecha_pago, $metodo, $referencia, $registrado_por, $cuota_id);
    $upd->execute();
    $upd->close();

    // Ítems de factura de esta cuota que siguen pendientes.
    $tipos = $cobro_manual ? "'cuota','matricula','recargo'" : "'cuota','matricula'";
    $stmt = $conn->prepare("SELECT DISTINCT factura_id FROM factura_items WHERE cuota_id = ? AND tipo IN ($tipos) AND estado = 'pendiente'");
    $stmt->bind_param('i', $cuota_id);
    $stmt->execute();
    $facturas = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'factura_id');
    $stmt->close();
    if ($facturas) {
        $upd = $conn->prepare("UPDATE factura_items SET estado = 'pagado' WHERE cuota_id = ? AND tipo IN ($tipos) AND estado = 'pendiente'");
        $upd->bind_param('i', $cuota_id);
        $upd->execute();
        $upd->close();
        foreach ($facturas as $factura_id) {
            recalcular_factura($conn, (int) $factura_id);
        }
    }

    actualizar_condicion_regularizacion($conn, (int) $cuota['alumno_id']);
    return $recargo;
}

function cuota_en_revision(mysqli $conn, int $cuota_id): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM factura_items WHERE cuota_id = ? AND estado = 'en_revision'");
    $stmt->bind_param('i', $cuota_id);
    $stmt->execute();
    $hay = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    return $hay;
}

// ── Pagos ───────────────────────────────────────────────────────────────

/**
 * El padre elige ítems pendientes de una factura de su hijo y el sistema emite
 * el comprobante de pago (estado pendiente_transferencia). Los ítems quedan en revisión.
 */
function crear_pago(mysqli $conn, int $factura_id, int $padre_id, array $item_ids): int
{
    $item_ids = array_values(array_unique(array_filter(array_map('intval', $item_ids), fn ($id) => $id > 0)));
    if (!$item_ids) {
        throw new ReglaNegocioException('Elegí al menos un ítem para pagar.', 422);
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT alumno_id FROM facturas WHERE id = ? FOR UPDATE");
        $stmt->bind_param('i', $factura_id);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$factura || !alumno_pertenece_a_padre($conn, (int) $factura['alumno_id'], $padre_id)) {
            // Mismo 403 exista o no la factura, para no revelar ids ajenos.
            throw new ReglaNegocioException('La factura no corresponde a tus hijos.', 403);
        }

        $marcas = implode(',', array_fill(0, count($item_ids), '?'));
        $stmt = $conn->prepare("SELECT id, descripcion, importe, estado FROM factura_items WHERE factura_id = ? AND id IN ($marcas) FOR UPDATE");
        $stmt->bind_param('i' . str_repeat('i', count($item_ids)), $factura_id, ...$item_ids);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        if (count($items) !== count($item_ids)) {
            throw new ReglaNegocioException('Algún ítem no pertenece a esta factura.', 422);
        }
        foreach ($items as $it) {
            if ($it['estado'] !== 'pendiente') {
                $estado = $it['estado'] === 'pagado' ? 'ya está pagado' : 'ya está en un pago en revisión';
                throw new ReglaNegocioException("\"{$it['descripcion']}\" $estado.", 409);
            }
        }

        $total = round(array_sum(array_map(fn ($it) => (float) $it['importe'], $items)), 2);
        $stmt = $conn->prepare("INSERT INTO pagos (factura_id, padre_id, total) VALUES (?, ?, ?)");
        $stmt->bind_param('iid', $factura_id, $padre_id, $total);
        $stmt->execute();
        $pago_id = (int) $conn->insert_id;
        $stmt->close();

        $ins = $conn->prepare("INSERT INTO pago_items (pago_id, factura_item_id, importe) VALUES (?, ?, ?)");
        $upd = $conn->prepare("UPDATE factura_items SET estado = 'en_revision' WHERE id = ?");
        foreach ($items as $it) {
            $item_id = (int) $it['id'];
            $importe = (float) $it['importe'];
            $ins->bind_param('iid', $pago_id, $item_id, $importe);
            $ins->execute();
            $upd->bind_param('i', $item_id);
            $upd->execute();
        }
        $ins->close();
        $upd->close();

        $conn->commit();
        return $pago_id;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/** Guarda un comprobante de transferencia (ya validado) y deja el pago en revisión. */
function registrar_comprobante(
    mysqli $conn,
    int $pago_id,
    int $usuario_id,
    string $nombre_archivo,
    string $mime,
    string $contenido,
    float $monto,
    string $fecha_transferencia
): int {
    $stmt = $conn->prepare("SELECT estado FROM pagos WHERE id = ?");
    $stmt->bind_param('i', $pago_id);
    $stmt->execute();
    $pago = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$pago) {
        throw new ReglaNegocioException('El pago no existe.', 404);
    }
    if (!in_array($pago['estado'], ['pendiente_transferencia', 'en_revision'], true)) {
        throw new ReglaNegocioException('Ese pago ya fue revisado: no admite más comprobantes.', 409);
    }

    $tamanio = strlen($contenido);
    $nulo    = null;
    $stmt = $conn->prepare(
        "INSERT INTO comprobantes_transferencia
            (pago_id, nombre_archivo, mime, tamanio, archivo, monto_declarado, fecha_transferencia, subido_por)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('issibdsi', $pago_id, $nombre_archivo, $mime, $tamanio, $nulo, $monto, $fecha_transferencia, $usuario_id);
    $stmt->send_long_data(4, $contenido);
    $stmt->execute();
    $id = (int) $conn->insert_id;
    $stmt->close();

    $upd = $conn->prepare("UPDATE pagos SET estado = 'en_revision' WHERE id = ? AND estado = 'pendiente_transferencia'");
    $upd->bind_param('i', $pago_id);
    $upd->execute();
    $upd->close();
    return $id;
}

/** Pago con los datos de su factura y alumno, bloqueado para actualizar (usar dentro de una transacción). */
function pago_para_revisar(mysqli $conn, int $pago_id): array
{
    $stmt = $conn->prepare(
        "SELECT p.id, p.factura_id, p.padre_id, p.total, p.estado, f.alumno_id, f.mes, f.anio
         FROM pagos p JOIN facturas f ON f.id = p.factura_id WHERE p.id = ? FOR UPDATE"
    );
    $stmt->bind_param('i', $pago_id);
    $stmt->execute();
    $pago = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$pago) {
        throw new ReglaNegocioException('El pago no existe.', 404);
    }
    if ($pago['estado'] !== 'en_revision') {
        throw new ReglaNegocioException('Solo se revisan pagos con comprobante cargado (estado "En revisión").', 409);
    }
    return $pago;
}

/** El admin aprueba: ítems pagados, cuotas pagadas, factura recalculada y aviso al padre. */
function aprobar_pago(mysqli $conn, int $pago_id, int $admin_id): void
{
    $conn->begin_transaction();
    try {
        $pago = pago_para_revisar($conn, $pago_id);
        $stmt = $conn->prepare("UPDATE pagos SET estado = 'aprobado', motivo_rechazo = NULL, revisado_por = ?, revisado_en = NOW() WHERE id = ?");
        $stmt->bind_param('ii', $admin_id, $pago_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT fi.id, fi.tipo, fi.cuota_id, c.estado AS estado_cuota
             FROM pago_items pi
             JOIN factura_items fi ON fi.id = pi.factura_item_id
             LEFT JOIN cuotas c ON c.id = fi.cuota_id
             WHERE pi.pago_id = ?"
        );
        $stmt->bind_param('i', $pago_id);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $upd = $conn->prepare("UPDATE factura_items SET estado = 'pagado' WHERE id = ?");
        $numero = numero_pago($pago_id);
        $hoy = date('Y-m-d');
        foreach ($items as $it) {
            $item_id = (int) $it['id'];
            $upd->bind_param('i', $item_id);
            $upd->execute();
            if (in_array($it['tipo'], ['cuota', 'matricula'], true) && $it['estado_cuota'] === 'pendiente') {
                marcar_cuota_pagada($conn, (int) $it['cuota_id'], $hoy, 'transferencia', $numero, $admin_id, false);
            }
        }
        $upd->close();
        recalcular_factura($conn, (int) $pago['factura_id']);

        crear_notificacion(
            $conn,
            (int) $pago['padre_id'],
            (int) $pago['alumno_id'],
            'pago',
            "Aprobamos tu pago $numero por " . formato_pesos((float) $pago['total']) . ' de la factura '
                . numero_factura((int) $pago['factura_id']) . ' (' . periodo_texto((int) $pago['mes'], (int) $pago['anio']) . ').'
        );
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/** El admin rechaza con motivo: los ítems vuelven a pendientes y se avisa al padre. */
function rechazar_pago(mysqli $conn, int $pago_id, int $admin_id, string $motivo): void
{
    $motivo = trim($motivo);
    if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 255) {
        throw new ReglaNegocioException('Escribí el motivo del rechazo (5 a 255 caracteres).', 422);
    }
    $conn->begin_transaction();
    try {
        $pago = pago_para_revisar($conn, $pago_id);
        $stmt = $conn->prepare("UPDATE pagos SET estado = 'rechazado', motivo_rechazo = ?, revisado_por = ?, revisado_en = NOW() WHERE id = ?");
        $stmt->bind_param('sii', $motivo, $admin_id, $pago_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE factura_items fi JOIN pago_items pi ON pi.factura_item_id = fi.id
             SET fi.estado = 'pendiente' WHERE pi.pago_id = ? AND fi.estado = 'en_revision'"
        );
        $stmt->bind_param('i', $pago_id);
        $stmt->execute();
        $stmt->close();
        recalcular_factura($conn, (int) $pago['factura_id']);

        crear_notificacion(
            $conn,
            (int) $pago['padre_id'],
            (int) $pago['alumno_id'],
            'pago',
            'Rechazamos tu pago ' . numero_pago($pago_id) . ' de la factura ' . numero_factura((int) $pago['factura_id'])
                . ' (' . periodo_texto((int) $pago['mes'], (int) $pago['anio']) . "). Motivo: $motivo"
        );
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}
