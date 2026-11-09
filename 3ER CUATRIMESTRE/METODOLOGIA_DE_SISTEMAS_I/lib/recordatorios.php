<?php

/**
 * Mails automáticos a las familias (RFGm07 / RFGm08), por ahora en MODO SIMULACIÓN:
 * el mail se arma igual que si se mandara y se guarda en mails_enviados con estado
 * "simulado" (no hay SMTP configurado; cuando lo haya, el envío va en enviar_mail()).
 *
 * - fin_de_mes (RFGm07): el último día hábil del mes, a cada padre con saldo, el total a
 *   abonar de sus hijos con la composición por ítem y las facturas con saldo adjuntas.
 * - dia_20 (RFGm08): el día 20, a cada padre con deuda VENCIDA, el detalle de lo impago.
 *
 * Asunciones: día hábil = lunes a viernes, sin feriados por ahora; el mail del día 20 sale
 * el 20 aunque caiga en fin de semana; lo que está "en proceso de pago" (comprobante en
 * revisión) no se reclama. No se manda dos veces el mismo tipo y mes al mismo padre
 * (clave única de mails_enviados).
 */

require_once __DIR__ . '/facturacion.php';

const TIPOS_MAIL = ['fin_de_mes' => 'Fin de mes: total a abonar', 'dia_20' => 'Día 20: aviso de deuda'];

/** Último día hábil (lunes a viernes) del mes, como 'AAAA-MM-DD'. */
function ultimo_dia_habil(int $mes, int $anio): string
{
    $dia = new DateTime(sprintf('%04d-%02d-01', $anio, $mes));
    $dia->modify('last day of this month');
    while ((int) $dia->format('N') >= 6) { // 6 = sábado, 7 = domingo
        $dia->modify('-1 day');
    }
    return $dia->format('Y-m-d');
}

/** true si en esa fecha corresponde mandar ese tipo de mail. */
function corresponde_enviar(string $tipo, string $fecha): bool
{
    $d = new DateTime($fecha);
    if ($tipo === 'fin_de_mes') {
        return $d->format('Y-m-d') === ultimo_dia_habil((int) $d->format('n'), (int) $d->format('Y'));
    }
    return $tipo === 'dia_20' && (int) $d->format('j') === 20;
}

/**
 * Lo que un padre debe por sus hijos: ítems pendientes de pago (sin los que están en
 * revisión). Con $solo_vencido, solo los de facturas vencidas a $fecha.
 *
 * @return array{total: float, hijos: array<string, array>, facturas: int[]}
 */
function deuda_de_padre(mysqli $conn, int $padre_id, string $fecha, bool $solo_vencido): array
{
    $vencido = $solo_vencido ? 1 : 0;
    $stmt = $conn->prepare(
        "SELECT a.nombre AS alumno, f.id AS factura_id, f.mes, f.anio, f.fecha_vencimiento, fi.descripcion, fi.importe
         FROM padre_alumno pa
         JOIN usuarios a ON a.id = pa.alumno_id
         JOIN facturas f ON f.alumno_id = pa.alumno_id
         JOIN factura_items fi ON fi.factura_id = f.id
         WHERE pa.padre_id = ? AND fi.estado = 'pendiente' AND (? = 0 OR f.fecha_vencimiento < ?)
         ORDER BY a.nombre, f.anio, f.mes, fi.id"
    );
    $stmt->bind_param('iis', $padre_id, $vencido, $fecha);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $deuda = ['total' => 0.0, 'hijos' => [], 'facturas' => []];
    foreach ($filas as $f) {
        $deuda['hijos'][$f['alumno']][] = [
            'factura'     => numero_factura((int) $f['factura_id']),
            'periodo'     => periodo_texto((int) $f['mes'], (int) $f['anio']),
            'vence'       => date('d/m/Y', strtotime($f['fecha_vencimiento'])),
            'descripcion' => $f['descripcion'],
            'importe'     => (float) $f['importe'],
        ];
        $deuda['total'] = round($deuda['total'] + (float) $f['importe'], 2);
        $deuda['facturas'][(int) $f['factura_id']] = (int) $f['factura_id'];
    }
    $deuda['facturas'] = array_values($deuda['facturas']);
    return $deuda;
}

/** Asunto y cuerpo HTML del mail (todo el texto variable pasa por htmlspecialchars). */
function armar_mail(string $tipo, string $padre, array $deuda, string $fecha): array
{
    $e = fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    $mes = periodo_texto((int) date('n', strtotime($fecha)), (int) date('Y', strtotime($fecha)));
    if ($tipo === 'fin_de_mes') {
        $asunto = "Total a abonar – $mes";
        $intro = 'Te acercamos el total a abonar por tus hijos y su composición. Adjuntamos las facturas con saldo.';
    } else {
        $asunto = "Aviso de deuda – $mes";
        $intro = 'Al día de hoy, estos conceptos están vencidos y todavía no registramos su pago.';
    }

    $html = '<div style="font-family:Arial,sans-serif;color:#111827;max-width:600px">'
        . '<h2 style="color:#C2410C">' . $e(INSTITUCION_NOMBRE) . '</h2>'
        . '<p>Hola, ' . $e($padre) . '.</p><p>' . $e($intro) . '</p>';
    foreach ($deuda['hijos'] as $alumno => $items) {
        $html .= '<h3 style="color:#374151">' . $e($alumno) . '</h3>'
            . '<table style="width:100%;border-collapse:collapse" cellpadding="6">'
            . '<tr style="background:#FFF7ED"><th align="left">Factura</th><th align="left">Concepto</th><th align="left">Vence</th><th align="right">Importe</th></tr>';
        foreach ($items as $it) {
            $html .= '<tr style="border-bottom:1px solid #E5E7EB"><td>' . $e($it['factura']) . '</td><td>' . $e($it['descripcion'])
                . '</td><td>' . $e($it['vence']) . '</td><td align="right">' . $e(formato_pesos($it['importe'])) . '</td></tr>';
        }
        $html .= '</table>';
    }
    $html .= '<p style="font-size:18px"><strong>Total: ' . $e(formato_pesos($deuda['total'])) . '</strong></p>'
        . '<p>Podés pagarlo desde la app (Facturas → Pagar) por transferencia. No se aceptan pagos en efectivo.</p>'
        . '<p style="color:#6B7280;font-size:12px">Mail automático de ' . $e(INSTITUCION_NOMBRE) . '. Si ya pagaste, ignoralo.</p></div>';
    return [$asunto, $html];
}

/**
 * Envío. MODO SIMULACIÓN: no hay SMTP configurado, así que no sale nada y el mail queda
 * guardado con estado "simulado". Acá iría el envío real (SMTP_HOST/USER/PASS/FROM).
 *
 * @return array{0: string, 1: ?string} [estado, error]
 */
function enviar_mail(?string $destinatario, string $asunto, string $html, array $adjuntos): array
{
    return ['simulado', null];
}

/**
 * Genera (y "envía") los mails de un tipo para una fecha. Si la fecha no corresponde, no
 * hace nada salvo $forzar (botón de prueba del admin).
 *
 * @return array{corresponde: bool, generados: int, ya_enviados: int, sin_deuda: int}
 */
function generar_recordatorios(mysqli $conn, string $tipo, string $fecha, bool $forzar = false): array
{
    if (!isset(TIPOS_MAIL[$tipo])) {
        throw new ReglaNegocioException('Tipo de mail inválido.', 422);
    }
    $d = DateTime::createFromFormat('!Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha) {
        throw new ReglaNegocioException('Fecha inválida.', 422);
    }
    $resultado = ['corresponde' => corresponde_enviar($tipo, $fecha), 'generados' => 0, 'ya_enviados' => 0, 'sin_deuda' => 0];
    if (!$resultado['corresponde'] && !$forzar) {
        return $resultado;
    }
    $mes = (int) $d->format('n');
    $anio = (int) $d->format('Y');

    $padres = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'padre' AND activo = 1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
    $existe = $conn->prepare("SELECT 1 FROM mails_enviados WHERE padre_id = ? AND tipo = ? AND mes = ? AND anio = ?");
    $insert = $conn->prepare(
        "INSERT IGNORE INTO mails_enviados
            (padre_id, tipo, mes, anio, destinatario, asunto, cuerpo_html, adjuntos, total, estado, error, fecha_simulada)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    foreach ($padres as $p) {
        $padre_id = (int) $p['id'];
        $existe->bind_param('isii', $padre_id, $tipo, $mes, $anio);
        $existe->execute();
        if ($existe->get_result()->fetch_row()) {
            $resultado['ya_enviados']++;
            continue;
        }
        $deuda = deuda_de_padre($conn, $padre_id, $fecha, $tipo === 'dia_20');
        if ($deuda['total'] <= 0) {
            $resultado['sin_deuda']++;
            continue;
        }
        [$asunto, $html] = armar_mail($tipo, $p['nombre'], $deuda, $fecha);
        $adjuntos = $tipo === 'fin_de_mes' ? implode(',', $deuda['facturas']) : null;
        $destino = destinatario_de_padre($conn, $padre_id);
        [$estado, $error] = enviar_mail($destino, $asunto, $html, $deuda['facturas']);
        $total = $deuda['total'];
        $insert->bind_param('isiissssdsss', $padre_id, $tipo, $mes, $anio, $destino, $asunto, $html, $adjuntos, $total, $estado, $error, $fecha);
        $insert->execute();
        $resultado['generados'] += $insert->affected_rows;
    }
    $existe->close();
    $insert->close();
    return $resultado;
}

/**
 * Mail del padre. La tabla usuarios no tiene email: se usa el del legajo de sus hijos
 * (alumnos_datos.email, que sale de la solicitud de inscripción). null si no hay.
 */
function destinatario_de_padre(mysqli $conn, int $padre_id): ?string
{
    $stmt = $conn->prepare(
        "SELECT ad.email FROM padre_alumno pa JOIN alumnos_datos ad ON ad.alumno_id = pa.alumno_id
         WHERE pa.padre_id = ? AND ad.email IS NOT NULL AND ad.email <> '' LIMIT 1"
    );
    $stmt->bind_param('i', $padre_id);
    $stmt->execute();
    $email = $stmt->get_result()->fetch_row()[0] ?? null;
    $stmt->close();
    return $email;
}
