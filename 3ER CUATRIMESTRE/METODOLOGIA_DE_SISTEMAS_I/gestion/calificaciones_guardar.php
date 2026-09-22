<?php
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requerir_rol('docente');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

require_once __DIR__ . '/../database/db_config.php';

$materia_id       = (int) ($_POST['materia_id'] ?? 0);
$evaluacion       = trim($_POST['evaluacion'] ?? '');
$fecha_evaluacion = trim($_POST['fecha_evaluacion'] ?? '');
$notas            = json_decode($_POST['notas'] ?? '', true);

if ($materia_id <= 0 || $evaluacion === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_evaluacion)
    || !is_array($notas) || empty($notas)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$hoy = date('Y-m-d');

if ($fecha_evaluacion > $hoy) {
    echo json_encode(['success' => false, 'message' => 'La fecha de evaluación no puede ser futura.']);
    exit;
}

if (dias_habiles_entre($fecha_evaluacion, $hoy) > PLAZO_CARGA_NOTA_DIAS_HABILES) {
    echo json_encode([
        'success' => false,
        'message' => 'Venció el plazo de ' . PLAZO_CARGA_NOTA_DIAS_HABILES . ' días hábiles para cargar notas de esta evaluación.',
    ]);
    exit;
}

$docente_id = (int) $_SESSION['usuario_id'];
$materia = materia_valida_para_docente($conn, $materia_id, $docente_id);
if (!$materia) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'La materia no pertenece al docente.']);
    exit;
}

$alumnos_curso = [];
$stmt = $conn->prepare("SELECT alumno_id FROM alumno_curso WHERE curso_id = ?");
$stmt->bind_param('i', $materia['curso_id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $alumnos_curso[(int) $row['alumno_id']] = true;
}
$stmt->close();

// Fechas de recuperatorio por alumno: obligatorias para toda nota desaprobada.
$recuperatorios = json_decode($_POST['recuperatorios'] ?? '{}', true);
if (!is_array($recuperatorios)) {
    $recuperatorios = [];
}

$validas   = [];
$omitidos  = 0;
$sin_fecha = 0;
foreach ($notas as $alumno_id => $nota) {
    $alumno_id = (int) $alumno_id;
    if (!isset($alumnos_curso[$alumno_id])) {
        continue;
    }

    if (!is_numeric($nota)) {
        $omitidos++;
        continue;
    }
    $notaInt = (int) $nota;
    if ($notaInt != $nota || $notaInt < 0 || $notaInt > 10) {
        $omitidos++;
        continue;
    }

    $fecha_recup = null;
    if ($notaInt < NOTA_APROBACION) {
        $fecha_recup = trim((string) ($recuperatorios[$alumno_id] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_recup) || $fecha_recup <= $fecha_evaluacion) {
            $sin_fecha++;
            continue;
        }
    }
    $validas[] = [$alumno_id, $notaInt, $fecha_recup];
}

if ($sin_fecha > 0) {
    echo json_encode([
        'success' => false,
        'message' => "Falta una fecha de recuperatorio válida (posterior a la evaluación) para {$sin_fecha} "
                   . ($sin_fecha === 1 ? 'alumno desaprobado.' : 'alumnos desaprobados.'),
    ]);
    exit;
}

$periodo = periodo_desde_fecha($fecha_evaluacion);

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        "INSERT INTO calificaciones (alumno_id, materia_id, evaluacion, nota, fecha_evaluacion)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE nota = VALUES(nota), fecha_evaluacion = VALUES(fecha_evaluacion)"
    );
    // Si ya había un recuperatorio en el período y cambia la fecha, vuelve a
    // quedar pendiente (estado se asigna antes que fecha para comparar la vieja).
    $stmtRecup = $conn->prepare(
        "INSERT INTO recuperatorios (alumno_id, materia_id, periodo, fecha, estado)
         VALUES (?, ?, ?, ?, 'pendiente')
         ON DUPLICATE KEY UPDATE estado = IF(fecha <=> VALUES(fecha), estado, 'pendiente'),
                                 fecha  = VALUES(fecha)"
    );

    $guardados      = 0;
    $recup_fijados  = 0;
    foreach ($validas as [$alumno_id, $notaInt, $fecha_recup]) {
        $stmt->bind_param('iisis', $alumno_id, $materia_id, $evaluacion, $notaInt, $fecha_evaluacion);
        $stmt->execute();
        $guardados++;

        if ($fecha_recup !== null) {
            $stmtRecup->bind_param('iiss', $alumno_id, $materia_id, $periodo, $fecha_recup);
            $stmtRecup->execute();
            $recup_fijados++;
        }
    }
    $stmt->close();
    $stmtRecup->close();

    $conn->commit();
    echo json_encode([
        'success'        => true,
        'guardados'      => $guardados,
        'omitidos'       => $omitidos,
        'recuperatorios' => $recup_fijados,
        'periodo'        => $periodo,
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al guardar las calificaciones.']);
}

$conn->close();
