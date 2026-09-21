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
