<?php
require_once __DIR__ . '/../auth/session.php';

// Valor asumido: el TP1 no define un tope oficial de faltas por materia.
// Ajustar cuando el equipo confirme la regla de negocio real.
const LIMITE_FALTAS = 15;

const UMBRAL_ALERTA_FALTAS = 2;
const PLAZO_CARGA_NOTA_DIAS_HABILES = 10;

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
