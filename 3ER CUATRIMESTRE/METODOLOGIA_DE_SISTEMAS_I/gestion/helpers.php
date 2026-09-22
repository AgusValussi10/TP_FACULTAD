<?php
require_once __DIR__ . '/../auth/session.php';

// Valor asumido: el TP1 no define un tope oficial de faltas por materia.
// Ajustar cuando el equipo confirme la regla de negocio real.
const LIMITE_FALTAS = 15;
const NOTA_APROBACION = 6;

const UMBRAL_ALERTA_FALTAS = 2;
const PLAZO_CARGA_NOTA_DIAS_HABILES = 10;

// Asunción: el TP1 no fija la edad mínima exacta para Nivel Inicial. Ajustar
// si el equipo confirma otro valor.
const EDAD_MINIMA_INICIAL = 3;
// Asunción: se debe reservar el servicio antes del día 25 del mes anterior
// al mes del servicio. Ajustar si el equipo define otro plazo.
const DIA_LIMITE_RESERVA_SERVICIOS = 25;

// Asunción: RN11 dice "dentro de los primeros 10 días de cada mes"; se
// toma el día 10 como vencimiento. El % de recargo no está fijado en el
// TP1, se asume 10%. Ajustar si el equipo confirma otro valor.
const DIA_VENCIMIENTO_CUOTA = 10;
const PORCENTAJE_RECARGO_MORA = 10;

// Asunción: RN12 fija el plazo en 60 días, no especifica más detalle.
const DIAS_LIMITE_REGULARIZACION = 60;

/**
 * Corta la ejecución con 403 + JSON si el rol de sesión no está permitido.
 * @param string|string[] $roles
 */
function requerir_rol($roles): void
{
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['rol'] ?? '', $roles, true)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
        exit;
    }
}

/** Cuenta días hábiles (lunes a viernes) estrictamente entre dos fechas, sin contar $desde. */
function dias_habiles_entre(string $desde, string $hasta): int
{
    $inicio = new DateTime($desde);
    $fin    = new DateTime($hasta);
    if ($inicio > $fin) {
        [$inicio, $fin] = [$fin, $inicio];
    }

    $dias   = 0;
    $cursor = clone $inicio;
    while ($cursor < $fin) {
        $cursor->modify('+1 day');
        if ((int) $cursor->format('N') <= 5) {
            $dias++;
        }
    }
    return $dias;
}

/**
 * Trimestre (valor del ENUM recuperatorios.periodo) al que pertenece una fecha.
 * Asunción: ciclo lectivo argentino, 1er trimestre hasta mayo, 2do de junio a
 * agosto y 3ro de septiembre en adelante. Ajustar si el equipo define otro corte.
 */
function periodo_desde_fecha(string $fecha): string
{
    $mes = (int) date('n', strtotime($fecha));
    if ($mes <= 5) return '1er Trimestre';
    if ($mes <= 8) return '2do Trimestre';
    return '3er Trimestre';
}

/**
 * Calcula el semáforo de faltas para un alumno en una materia.
 * @param int|null $faltas null cuando todavía no hay ningún registro de asistencia cargado.
 */
function calcular_semaforo(?int $faltas): array
{
    if ($faltas === null) {
        return ['estado' => 'sin_datos', 'label' => 'Sin datos', 'faltas' => null, 'restantes' => null];
    }

    if ($faltas === 0) {
        return ['estado' => 'libre', 'label' => 'Libre de faltas', 'faltas' => 0, 'restantes' => LIMITE_FALTAS];
    }

    $restantes = LIMITE_FALTAS - $faltas;

    if ($restantes <= 0) {
        $estado = 'excedido';
        $label  = 'Límite de faltas excedido';
    } elseif ($restantes <= UMBRAL_ALERTA_FALTAS) {
        $estado = 'alerta';
        $label  = 'Alerta: quedan pocas faltas disponibles';
    } else {
        $estado = 'normal';
        $label  = 'Normal';
    }

    return ['estado' => $estado, 'label' => $label, 'faltas' => $faltas, 'restantes' => $restantes];
}

/** Devuelve la materia (con su curso) si pertenece al docente, o null si no. */
function materia_valida_para_docente(mysqli $conn, int $materia_id, int $docente_id): ?array
{
    $stmt = $conn->prepare(
        "SELECT m.id, m.nombre, m.curso_id, c.nombre AS curso_nombre
         FROM materias m
         JOIN cursos c ON c.id = m.curso_id
         WHERE m.id = ? AND m.docente_id = ?"
    );
    $stmt->bind_param('ii', $materia_id, $docente_id);
    $stmt->execute();
    $materia = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $materia;
}

/** true si el alumno cursa alguna materia dictada por el docente (para acotar el legajo). */
function alumno_pertenece_a_docente(mysqli $conn, int $alumno_id, int $docente_id): bool
{
    $stmt = $conn->prepare(
        "SELECT 1 FROM alumno_curso ac
         JOIN materias m ON m.curso_id = ac.curso_id
         WHERE ac.alumno_id = ? AND m.docente_id = ?
         LIMIT 1"
    );
    $stmt->bind_param('ii', $alumno_id, $docente_id);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

/** Edad en años cumplidos a partir de una fecha de nacimiento. */
function edad_desde_fecha_nacimiento(string $fecha_nacimiento): int
{
    $nac = new DateTime($fecha_nacimiento);
    $hoy = new DateTime('today');
    return (int) $nac->diff($hoy)->y;
}

/** true si ya venció el plazo (RN08) para reservar el servicio de ese mes/año. */
function plazo_vencido_reserva(int $mes, int $anio): bool
{
    $limite = new DateTime(sprintf('%04d-%02d-01', $anio, $mes));
    $limite->modify('-1 month');
    $limite->setDate((int)$limite->format('Y'), (int)$limite->format('m'), DIA_LIMITE_RESERVA_SERVICIOS);
    return new DateTime('today') > $limite;
}

/** Alumnos vinculados a un padre/tutor, vía padre_alumno. */
function alumnos_de_padre(mysqli $conn, int $padre_id): array
{
    $stmt = $conn->prepare(
        "SELECT u.id, u.nombre FROM padre_alumno pa
         JOIN usuarios u ON u.id = pa.alumno_id
         WHERE pa.padre_id = ? ORDER BY u.nombre"
    );
    $stmt->bind_param('i', $padre_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** true si el alumno_id pertenece al padre_id (evita que un padre opere sobre otro alumno). */
function alumno_pertenece_a_padre(mysqli $conn, int $alumno_id, int $padre_id): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM padre_alumno WHERE padre_id = ? AND alumno_id = ?");
    $stmt->bind_param('ii', $padre_id, $alumno_id);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

function crear_notificacion(mysqli $conn, ?int $padre_id, int $alumno_id, string $tipo, string $mensaje): void
{
    $stmt = $conn->prepare(
        "INSERT INTO notificaciones (padre_id, alumno_id, tipo, mensaje) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param('iiss', $padre_id, $alumno_id, $tipo, $mensaje);
    $stmt->execute();
    $stmt->close();
}

function fecha_vencimiento_cuota(int $mes, int $anio): string
{
    return sprintf('%04d-%02d-%02d', $anio, $mes, DIA_VENCIMIENTO_CUOTA);
}

function calcular_recargo(float $importe, string $fecha_vencimiento, ?string $fecha_pago = null): float
{
    $limite = new DateTime($fecha_vencimiento);
    $pago   = new DateTime($fecha_pago ?? 'today');
    return $pago > $limite ? round($importe * PORCENTAJE_RECARGO_MORA / 100, 2) : 0.0;
}

/** RFG13/RN12: recalcula y persiste la condición de regularización de un alumno. */
function actualizar_condicion_regularizacion(mysqli $conn, int $alumno_id): void
{
    $limite = (new DateTime('today'))->modify('-' . DIAS_LIMITE_REGULARIZACION . ' days')->format('Y-m-d');
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS vencidas FROM cuotas
         WHERE alumno_id = ? AND estado = 'pendiente' AND fecha_vencimiento < ?"
    );
    $stmt->bind_param('is', $alumno_id, $limite);
    $stmt->execute();
    $vencidas = (int)($stmt->get_result()->fetch_assoc()['vencidas'] ?? 0);
    $stmt->close();

    $condicion = $vencidas > 0 ? 'pendiente_regularizacion' : 'regular';
    $upd = $conn->prepare("UPDATE alumno_curso SET condicion = ? WHERE alumno_id = ?");
    $upd->bind_param('si', $condicion, $alumno_id);
    $upd->execute();
    $upd->close();
}
