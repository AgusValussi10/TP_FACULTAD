<?php

/**
 * Inscripciones a deportes (RFG11 / RFGm09) y reservas de comedor y transporte (RFG09 /
 * RFGm10 / RFGm11). Único lugar con estas reglas: lo usan la web (gestion/deportes_guardar.php,
 * gestion/servicios_*.php) y la app (app/api/).
 *
 * Reglas y asunciones (ver CLAUDE.md, "Parte 3"):
 * - Hasta MAX_DEPORTES_SIMULTANEOS deportes por alumno (RFGm09; reemplaza a RN10, que no fijaba máximo).
 * - Cupo por deporte, verificado con la fila del deporte bloqueada (FOR UPDATE) para que dos
 *   inscripciones simultáneas no superen el cupo.
 * - Conflicto de horario (ASUNCIÓN): dos deportes chocan si comparten al menos un día
 *   (deportes.dias, SET) y sus franjas se superponen (inicio1 < fin2 y inicio2 < fin1;
 *   que uno termine 17:00 y otro empiece 17:00 no es choque). Sin horario estructurado no hay choque.
 * - Sin inscripciones duplicadas (además lo garantiza la clave única de la tabla).
 * - Comedor y transporte por mes, con el plazo del día DIA_LIMITE_RESERVA_SERVICIOS del mes
 *   anterior (RN08, plazo_vencido_reserva()). Transporte: un recorrido por mes, opcional y variable.
 * - No se reserva ni se cancela un mes que ya se facturó (la factura no se regenera).
 */

require_once __DIR__ . '/facturacion.php';

const MAX_DEPORTES_SIMULTANEOS = 2;
const MESES_RESERVABLES = 3; // cuántos meses hacia adelante ofrece la app para comedor y transporte

const DIAS_SEMANA = ['lun' => 'Lunes', 'mar' => 'Martes', 'mie' => 'Miércoles', 'jue' => 'Jueves', 'vie' => 'Viernes', 'sab' => 'Sábado'];

// ── Deportes ────────────────────────────────────────────────────────────

/**
 * Catálogo de deportes para un alumno: horario, profesor, valor mensual, cupo disponible,
 * si ya está inscripto y, si no, por qué no se puede inscribir (sin cupo, choque de horario,
 * máximo alcanzado).
 */
function deportes_para_alumno(mysqli $conn, int $alumno_id): array
{
    $stmt = $conn->prepare(
        "SELECT d.id, d.nombre, d.horario, d.cupo_maximo, d.dias, d.hora_inicio, d.hora_fin, u.nombre AS profesor,
                t.valor,
                (SELECT COUNT(*) FROM inscripciones_deportivas i WHERE i.deporte_id = d.id) AS inscriptos,
                EXISTS(SELECT 1 FROM inscripciones_deportivas i WHERE i.deporte_id = d.id AND i.alumno_id = ?) AS inscripto,
                (SELECT MIN(d2.nombre) FROM inscripciones_deportivas i2 JOIN deportes d2 ON d2.id = i2.deporte_id
                 WHERE i2.alumno_id = ? AND d2.id <> d.id
                   AND (d.dias + 0) & (d2.dias + 0) > 0
                   AND d.hora_inicio < d2.hora_fin AND d2.hora_inicio < d.hora_fin) AS choca_con
         FROM deportes d
         LEFT JOIN usuarios u ON u.id = d.profesor_id
         LEFT JOIN tarifas t ON t.clave = CONCAT('deporte:', d.id)
         ORDER BY d.nombre"
    );
    $stmt->bind_param('ii', $alumno_id, $alumno_id);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $activos = count(array_filter($filas, fn ($d) => (bool) $d['inscripto']));
    return array_map(function ($d) use ($activos) {
        $disponibles = max(0, (int) $d['cupo_maximo'] - (int) $d['inscriptos']);
        $inscripto = (bool) $d['inscripto'];
        $motivo = null;
        if (!$inscripto) {
            if ($activos >= MAX_DEPORTES_SIMULTANEOS) {
                $motivo = 'Ya tiene ' . MAX_DEPORTES_SIMULTANEOS . ' deportes (el máximo).';
            } elseif ($disponibles === 0) {
                $motivo = 'Sin cupo.';
            } elseif ($d['choca_con']) {
                $motivo = "Se superpone con {$d['choca_con']}.";
            }
        }
        return [
            'id'          => (int) $d['id'],
            'nombre'      => $d['nombre'],
            'horario'     => $d['horario'],
            'profesor'    => $d['profesor'],
            'valor'       => $d['valor'] !== null ? (float) $d['valor'] : null,
            'cupo'        => (int) $d['cupo_maximo'],
            'disponibles' => $disponibles,
            'inscripto'   => $inscripto,
            'puede'       => !$inscripto && $motivo === null,
            'motivo'      => $motivo,
        ];
    }, $filas);
}

/** Inscribe a un alumno en un deporte validando duplicado, máximo, cupo y horario. Devuelve el nombre del deporte. */
function inscribir_deporte(mysqli $conn, int $alumno_id, int $deporte_id): string
{
    $conn->begin_transaction();
    try {
        // Bloquear la fila del deporte serializa las inscripciones a ese deporte (cupo).
        $stmt = $conn->prepare("SELECT id, nombre, cupo_maximo FROM deportes WHERE id = ? FOR UPDATE");
        $stmt->bind_param('i', $deporte_id);
        $stmt->execute();
        $deporte = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$deporte) {
            throw new ReglaNegocioException('El deporte no existe.', 404);
        }
        $nombre = $deporte['nombre'];

        $stmt = $conn->prepare("SELECT deporte_id FROM inscripciones_deportivas WHERE alumno_id = ? FOR UPDATE");
        $stmt->bind_param('i', $alumno_id);
        $stmt->execute();
        $actuales = array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'deporte_id'));
        $stmt->close();
        if (in_array($deporte_id, $actuales, true)) {
            throw new ReglaNegocioException("Ya está inscripto en $nombre.", 409);
        }
        if (count($actuales) >= MAX_DEPORTES_SIMULTANEOS) {
            throw new ReglaNegocioException('Puede estar en hasta ' . MAX_DEPORTES_SIMULTANEOS . ' deportes a la vez: dalo de baja de uno para sumar otro.', 409);
        }

        $stmt = $conn->prepare("SELECT COUNT(*) FROM inscripciones_deportivas WHERE deporte_id = ?");
        $stmt->bind_param('i', $deporte_id);
        $stmt->execute();
        $inscriptos = (int) $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        if ($inscriptos >= (int) $deporte['cupo_maximo']) {
            throw new ReglaNegocioException("$nombre no tiene cupo disponible.", 409);
        }

        $stmt = $conn->prepare(
            "SELECT d2.nombre FROM deportes d
             JOIN inscripciones_deportivas i ON i.alumno_id = ?
             JOIN deportes d2 ON d2.id = i.deporte_id
             WHERE d.id = ? AND (d.dias + 0) & (d2.dias + 0) > 0
               AND d.hora_inicio < d2.hora_fin AND d2.hora_inicio < d.hora_fin
             LIMIT 1"
        );
        $stmt->bind_param('ii', $alumno_id, $deporte_id);
        $stmt->execute();
        $choque = $stmt->get_result()->fetch_row()[0] ?? null;
        $stmt->close();
        if ($choque) {
            throw new ReglaNegocioException("$nombre se superpone en horario con $choque.", 409);
        }

        $stmt = $conn->prepare("INSERT INTO inscripciones_deportivas (alumno_id, deporte_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $alumno_id, $deporte_id);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        return $nombre;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/** Da de baja una inscripción. Lo ya facturado se mantiene; deja de facturarse desde el mes siguiente. */
function baja_deporte(mysqli $conn, int $alumno_id, int $deporte_id): void
{
    $stmt = $conn->prepare("DELETE FROM inscripciones_deportivas WHERE alumno_id = ? AND deporte_id = ?");
    $stmt->bind_param('ii', $alumno_id, $deporte_id);
    $stmt->execute();
    $borradas = $stmt->affected_rows;
    $stmt->close();
    if ($borradas === 0) {
        throw new ReglaNegocioException('No está inscripto en ese deporte.', 409);
    }
}

// ── Comedor y transporte ────────────────────────────────────────────────

/** Meses que todavía se pueden reservar (plazo RN08 no vencido), desde el actual hacia adelante. */
function meses_reservables(): array
{
    $meses = [];
    $d = new DateTime('first day of this month');
    for ($i = 0; $i <= MESES_RESERVABLES; $i++) {
        $mes = (int) $d->format('n');
        $anio = (int) $d->format('Y');
        if (!plazo_vencido_reserva($mes, $anio)) {
            $limite = (clone $d)->modify('-1 month');
            $limite->setDate((int) $limite->format('Y'), (int) $limite->format('n'), DIA_LIMITE_RESERVA_SERVICIOS);
            $meses[] = ['mes' => $mes, 'anio' => $anio, 'periodo' => periodo_texto($mes, $anio), 'plazo' => $limite->format('d/m/Y')];
        }
        $d->modify('+1 month');
        if (count($meses) === MESES_RESERVABLES) {
            break;
        }
    }
    return $meses;
}

/** Recorridos activos con su valor mensual. */
function recorridos_con_valor(mysqli $conn): array
{
    $filas = $conn->query(
        "SELECT r.id, r.nombre, r.descripcion, t.valor FROM recorridos_transporte r
         LEFT JOIN tarifas t ON t.clave = CONCAT('transporte:', r.id) WHERE r.activo = 1 ORDER BY r.id"
    )->fetch_all(MYSQLI_ASSOC);
    return array_map(fn ($r) => [
        'id' => (int) $r['id'], 'nombre' => $r['nombre'], 'descripcion' => $r['descripcion'],
        'valor' => $r['valor'] !== null ? (float) $r['valor'] : null,
    ], $filas);
}

/** Reservas de comedor y transporte del alumno desde el mes actual, con recorrido y valor. */
function servicios_de_alumno(mysqli $conn, int $alumno_id): array
{
    $hoy = new DateTime('first day of this month');
    $desde = (int) $hoy->format('Y') * 12 + (int) $hoy->format('n');
    $stmt = $conn->prepare(
        "SELECT r.servicio, r.mes, r.anio, r.recorrido_id, rt.nombre AS recorrido,
                t.valor
         FROM reservas_servicios r
         LEFT JOIN recorridos_transporte rt ON rt.id = r.recorrido_id
         LEFT JOIN tarifas t ON t.clave = IF(r.servicio = 'comedor', 'comedor', CONCAT('transporte:', r.recorrido_id))
         WHERE r.alumno_id = ? AND (r.anio * 12 + r.mes) >= ?
         ORDER BY r.anio, r.mes, r.servicio"
    );
    $stmt->bind_param('ii', $alumno_id, $desde);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return array_map(fn ($r) => [
        'servicio'     => $r['servicio'],
        'mes'          => (int) $r['mes'],
        'anio'         => (int) $r['anio'],
        'periodo'      => periodo_texto((int) $r['mes'], (int) $r['anio']),
        'recorrido_id' => $r['recorrido_id'] !== null ? (int) $r['recorrido_id'] : null,
        'recorrido'    => $r['recorrido'],
        'valor'        => $r['valor'] !== null ? (float) $r['valor'] : null,
    ], $filas);
}

/** 409 si el plazo del mes venció o si ese mes ya se facturó para el alumno. */
function validar_mes_modificable(mysqli $conn, int $alumno_id, int $mes, int $anio): void
{
    if ($mes < 1 || $mes > 12 || $anio < 2000 || $anio > 2100) {
        throw new ReglaNegocioException('Mes inválido.', 422);
    }
    if (plazo_vencido_reserva($mes, $anio)) {
        throw new ReglaNegocioException(
            'El plazo para ' . periodo_texto($mes, $anio) . ' venció el día ' . DIA_LIMITE_RESERVA_SERVICIOS
            . ' del mes anterior. Comunicate con administración.',
            409
        );
    }
    $stmt = $conn->prepare("SELECT 1 FROM facturas WHERE alumno_id = ? AND mes = ? AND anio = ?");
    $stmt->bind_param('iii', $alumno_id, $mes, $anio);
    $stmt->execute();
    $facturado = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    if ($facturado) {
        throw new ReglaNegocioException('La factura de ' . periodo_texto($mes, $anio) . ' ya se emitió. Comunicate con administración.', 409);
    }
}

/**
 * Reserva comedor o transporte para un mes (transporte con recorrido). Si ya había una
 * reserva de transporte ese mes, cambia el recorrido. Sin ON DUPLICATE KEY: la tabla
 * tiene dos claves únicas (id y alumno/servicio/mes/año), ver CLAUDE.md.
 */
function reservar_servicio(mysqli $conn, int $alumno_id, string $servicio, int $mes, int $anio, ?int $recorrido_id = null): void
{
    if (!in_array($servicio, ['comedor', 'transporte'], true)) {
        throw new ReglaNegocioException('Servicio inválido.', 422);
    }
    validar_mes_modificable($conn, $alumno_id, $mes, $anio);
    if ($servicio === 'comedor') {
        $recorrido_id = null;
    } else {
        $stmt = $conn->prepare("SELECT 1 FROM recorridos_transporte WHERE id = ? AND activo = 1");
        $id = (int) $recorrido_id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $valido = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();
        if (!$valido) {
            throw new ReglaNegocioException('Elegí uno de los recorridos del transporte.', 422);
        }
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT id FROM reservas_servicios WHERE alumno_id = ? AND servicio = ? AND mes = ? AND anio = ? FOR UPDATE");
        $stmt->bind_param('isii', $alumno_id, $servicio, $mes, $anio);
        $stmt->execute();
        $existente = $stmt->get_result()->fetch_row();
        $stmt->close();
        if ($existente) {
            $stmt = $conn->prepare("UPDATE reservas_servicios SET estado = 'confirmada', recorrido_id = ? WHERE id = ?");
            $stmt->bind_param('ii', $recorrido_id, $existente[0]);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO reservas_servicios (alumno_id, servicio, mes, anio, estado, recorrido_id) VALUES (?, ?, ?, ?, 'confirmada', ?)"
            );
            $stmt->bind_param('isiii', $alumno_id, $servicio, $mes, $anio, $recorrido_id);
        }
        $stmt->execute();
        $stmt->close();
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/** Cancela la reserva de comedor o transporte de un mes (dentro del plazo y si no se facturó). */
function cancelar_servicio(mysqli $conn, int $alumno_id, string $servicio, int $mes, int $anio): void
{
    validar_mes_modificable($conn, $alumno_id, $mes, $anio);
    $stmt = $conn->prepare("DELETE FROM reservas_servicios WHERE alumno_id = ? AND servicio = ? AND mes = ? AND anio = ?");
    $stmt->bind_param('isii', $alumno_id, $servicio, $mes, $anio);
    $stmt->execute();
    $borradas = $stmt->affected_rows;
    $stmt->close();
    if ($borradas === 0) {
        throw new ReglaNegocioException('No había una reserva para cancelar.', 409);
    }
}
