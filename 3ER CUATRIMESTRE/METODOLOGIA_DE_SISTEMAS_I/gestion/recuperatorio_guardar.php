<?php

require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('docente');
errores_como_json();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

/** Responde con error y corta. */
function fallar(string $mensaje, int $http = 200): void
{
    http_response_code($http);
    echo json_encode(['success' => false, 'message' => $mensaje]);
    exit;
}

$id         = (int) ($_POST['id'] ?? 0);          // > 0 = editar uno existente
$alumno_id  = (int) ($_POST['alumno_id'] ?? 0);
$materia_id = (int) ($_POST['materia_id'] ?? 0);
$periodo    = trim($_POST['periodo'] ?? '');
$fecha      = trim($_POST['fecha'] ?? '');
$turno      = trim($_POST['turno'] ?? '');
$nota_txt   = trim($_POST['nota'] ?? '');

$periodos_validos = ['1er Trimestre', '2do Trimestre', '3er Trimestre', 'Anual'];
$turnos_validos   = ['mañana', 'tarde'];

if (!in_array($periodo, $periodos_validos, true)) {
    fallar('Elegí un período válido.');
}
if ($fecha !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !strtotime($fecha))) {
    fallar('Fecha inválida.');
}
if ($turno !== '' && !in_array($turno, $turnos_validos, true)) {
    fallar('Turno inválido.');
}
$nota = null;
if ($nota_txt !== '') {
    if (!ctype_digit($nota_txt) || (int) $nota_txt < 1 || (int) $nota_txt > 10) {
        fallar('La nota debe ser un número entero entre 1 y 10.');
    }
    $nota = (int) $nota_txt;
}

$docente_id = (int) $_SESSION['usuario_id'];
$hoy        = date('Y-m-d');

// Si se edita, el alumno y la materia salen del registro (no del formulario).
$anterior = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT id, alumno_id, materia_id, periodo, fecha, turno, estado, nota FROM recuperatorios WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $anterior = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$anterior) {
        fallar('El recuperatorio no existe.');
    }
    $alumno_id  = (int) $anterior['alumno_id'];
    $materia_id = (int) $anterior['materia_id'];
}

if ($alumno_id <= 0 || $materia_id <= 0) {
    fallar('Elegí un alumno.');
}

$materia = materia_valida_para_docente($conn, $materia_id, $docente_id);
if (!$materia) {
    fallar('La materia no pertenece al docente.', 403);
}

$stmt = $conn->prepare(
    "SELECT u.nombre FROM alumno_curso ac JOIN usuarios u ON u.id = ac.alumno_id
     WHERE ac.alumno_id = ? AND ac.curso_id = ?"
);
$stmt->bind_param('ii', $alumno_id, $materia['curso_id']);
$stmt->execute();
$alumno = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$alumno) {
    fallar('El alumno no pertenece al curso de la materia.', 403);
}

// Reglas de negocio (RFG06). Asunciones documentadas en CLAUDE.md:
//  - el examen se toma de lunes a viernes;
//  - al programar o reprogramar (sin nota todavía) la fecha no puede ser pasada;
//  - para cargar la nota tiene que haber fecha y el examen ya tuvo que tomarse;
//  - el estado sale de la nota: >= NOTA_APROBACION aprobado, si no desaprobado.
if ($fecha !== '' && (int) date('N', strtotime($fecha)) > 5) {
    fallar('El recuperatorio tiene que ser de lunes a viernes.');
}
$fecha_cambio = !$anterior || ($anterior['fecha'] ?? '') !== $fecha;
if ($nota === null && $fecha !== '' && $fecha_cambio && $fecha < $hoy) {
    fallar('La fecha del recuperatorio no puede ser anterior a hoy.');
}
if ($nota !== null) {
    if ($fecha === '') {
        fallar('Para cargar la nota indicá la fecha en que se tomó el examen.');
    }
    if ($fecha > $hoy) {
        fallar('No se puede cargar la nota de un examen que todavía no se tomó.');
    }
}

$estado   = $nota === null ? 'pendiente' : ($nota >= NOTA_APROBACION ? 'aprobado' : 'desaprobado');
$fechaVal = $fecha !== '' ? $fecha : null;
$turnoVal = $turno !== '' ? $turno : null;

try {
    if ($anterior) {
        $stmt = $conn->prepare(
            "UPDATE recuperatorios SET periodo = ?, fecha = ?, turno = ?, estado = ?, nota = ? WHERE id = ?"
        );
        $stmt->bind_param('ssssii', $periodo, $fechaVal, $turnoVal, $estado, $nota, $id);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO recuperatorios (alumno_id, materia_id, periodo, fecha, turno, estado, nota)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iissssi', $alumno_id, $materia_id, $periodo, $fechaVal, $turnoVal, $estado, $nota);
    }
    $stmt->execute();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        fallar("{$alumno['nombre']} ya tiene un recuperatorio de {$materia['nombre']} para el {$periodo}. Editá ese en lugar de crear otro.");
    }
    throw $e;
}

// Aviso al padre/tutor (aparece en Notificaciones del portal del padre).
$mensaje = null;
$detalleFecha = $fechaVal ? ' el ' . date('d/m/Y', strtotime($fechaVal)) . ($turnoVal ? " (turno $turnoVal)" : '') : ' (fecha a confirmar)';
if (!$anterior) {
    $mensaje = "{$alumno['nombre']} tiene un recuperatorio de {$materia['nombre']} ({$periodo})$detalleFecha.";
} elseif ($nota !== null && ($anterior['nota'] === null || (int) $anterior['nota'] !== $nota)) {
    $mensaje = "Resultado del recuperatorio de {$materia['nombre']} ({$periodo}) de {$alumno['nombre']}: nota $nota — "
             . ($estado === 'aprobado' ? 'aprobado' : 'desaprobado') . '.';
} elseif ($nota === null && ($fecha_cambio || ($anterior['turno'] ?? '') !== $turno)) {
    $mensaje = "Se reprogramó el recuperatorio de {$materia['nombre']} ({$periodo}) de {$alumno['nombre']}:$detalleFecha.";
}
if ($mensaje) {
    $padres = $conn->prepare("SELECT padre_id FROM padre_alumno WHERE alumno_id = ?");
    $padres->bind_param('i', $alumno_id);
    $padres->execute();
    foreach ($padres->get_result()->fetch_all(MYSQLI_ASSOC) as $p) {
        crear_notificacion($conn, (int) $p['padre_id'], $alumno_id, 'recuperatorio', $mensaje);
    }
    $padres->close();
}
$conn->close();

echo json_encode([
    'success' => true,
    'message' => $anterior ? 'Recuperatorio actualizado.' : 'Recuperatorio programado.',
    'estado'  => $estado,
]);
